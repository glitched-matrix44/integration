<?php

namespace Iquesters\Integration\Jobs;

use Illuminate\Support\Facades\Http;
use Iquesters\Foundation\Jobs\BaseJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Iquesters\Foundation\System\Traits\Loggable;

class SyncVectorJob extends BaseJob
{
    use Loggable;

    protected array $integrationPayload;
    protected ?\Carbon\Carbon $startedAt = null;

    protected function initialize(...$arguments): void
    {
        [$payload] = $arguments;
        $this->integrationPayload = $payload;
    }

    /**
     * Main job handler
     */
    public function process(): void
    {
        try {
            $this->startedAt = now();
            $this->logMethodStart('Vector sync job started');

            $payload = $this->buildProviderPayload();

            $this->logDebug('Vector request payload: ' . json_encode($payload));

            $response = Http::timeout(0)
                ->withOptions([
                    'connect_timeout' => 10,
                    'read_timeout'    => 0,
                ])
                ->post(
                    'https://api-jobs.iquesters.com/vector/create/v1',
                    $payload
                );

            $this->logInfo(
                'Vector API response received | status=' . $response->status() .
                ' | body=' . $response->body()
            );

            if (! $response->successful()) {
                $status = $response->status();
                $body   = $response->body();

                $this->logError(
                    'Vector API call failed | status=' . $status .
                    ' | body=' . $body
                );

                return;
            }

            $this->setResponse($response->json());

            $this->logMethodEnd('Vector sync completed successfully');

        } catch (\Throwable $e) {
            $this->logError('Vector sync failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Runs after job execution
     */
    protected function afterHandle(): void
    {
        parent::afterHandle();

        if (!Schema::hasTable('vector_responses')) {
            return;
        }

        if ($this->getResponse() === null) {
            return;
        }

        try {
            $finishedAt = now();

            $duration = $this->startedAt
                ? abs((int) $this->startedAt->diffInSeconds($finishedAt))
                : null;

            DB::table('vector_responses')->insert([
                'uid'              => (string) Str::ulid(),
                'integration_id'   => $this->integrationPayload['integration_id'] ?? null,
                'job_uuid'         => $this->job?->getJobId(),
                'response'         => json_encode($this->getResponse()),
                'started_at'       => $this->startedAt,
                'finished_at'      => $finishedAt,
                'duration_seconds' => $duration,
                'status'           => 'active',
                'created_by'       => 0,
                'updated_by'       => 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

        } catch (\Throwable $e) {
            $this->logWarning('Vector response insert failed: '.$e->getMessage());
        }
    }

    /**
     * Build final vector payload
     */
    private function buildProviderPayload(): array
    {
        try {
            $systems = $this->buildSystemsPayload();

            if (empty($systems)) {
                throw new \RuntimeException('Vector payload has no systems to sync');
            }

            return [
                'systems' => $systems,
            ];

        } catch (\Throwable $e) {
            $this->logError('Failed to build vector payload: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Build systems list
     */
    private function buildSystemsPayload(): array
    {
        try {
            $items = $this->integrationPayload['systems']
                ?? [$this->integrationPayload];

            if (!is_array($items)) {
                throw new \InvalidArgumentException('Systems payload must be an array');
            }

            $systems = [];

            foreach ($items as $index => $item) {

                if (empty($item['integration_provider'])) {
                    throw new \InvalidArgumentException(
                        "Missing integration_provider at index {$index}"
                    );
                }

                if (empty($item['integration_uuid'])) {
                    throw new \InvalidArgumentException(
                        "Missing integration_uuid at index {$index}"
                    );
                }

                $systems[] = [
                    'system'           => $item['integration_provider'],
                    'integration_uuid' => $item['integration_uuid'], // @todo for now it is uuid but it should be uid
                    'recreate_flag'    => (bool) ($item['recreate_flag'] ?? false),
                ];
            }

            if (empty($systems)) {
                throw new \RuntimeException('No valid systems found for vector payload');
            }

            return $systems;

        } catch (\Throwable $e) {
            $this->logError('Failed to build systems payload: '.$e->getMessage());
            $this->logDebug('Payload received', $this->integrationPayload);
            throw $e;
        }
    }
}