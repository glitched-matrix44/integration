<?php

namespace Iquesters\Integration\Jobs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Iquesters\Foundation\Jobs\BaseJob;
use Iquesters\Integration\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SyncVectorJob extends BaseJob
{
    protected array $integrationPayload;
    protected ?\Carbon\Carbon $startedAt = null;

    protected function initialize(...$arguments): void
    {
        [$payload] = $arguments;
        $this->integrationPayload = $payload;
    }

    /**
     * Handle the job – Sync integration data to Vector service
     */
    public function process(): void
    {
        try {
            $this->startedAt = now();
            Log::info('Starting vector sync job', [
                'integration_uid' => $this->integrationPayload['integration_uid'],
                'provider' => $this->integrationPayload['integration_provider'],
            ]);

            // Build provider-specific payload
            $payload = $this->buildProviderPayload();

            Log::debug('Calling vector API with payload: ' . json_encode($payload));

            $response = Http::timeout(0)
                ->withOptions([
                    'connect_timeout' => 10,
                    'read_timeout' => 0,
                ])
                ->post(
                    'https://api-jobs.iquesters.com/vector/create',
                    $payload
                );

            Log::info('Vector API response received', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            if (! $response->successful()) {
                Log::error('Vector API call failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                return;
            }

            $this->setResponse($response->json());

            Log::info('Vector sync completed successfully', [
                'integration_uid' => $this->integrationPayload['integration_uid'],
                'provider' => $this->integrationPayload['integration_provider'],
            ]);

        } catch (\Throwable $e) {
            Log::error('SyncVectorJob failed', [
                'integration_uid' => $this->integrationPayload['integration_uid'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
    
    protected function afterHandle(): void
    {
        parent::afterHandle();

        if (! Schema::hasTable('vector_responses')) {
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
                'integration_id'   => $this->integrationPayload['integration_id'],
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
            Log::warning('Vector response DB insert failed', [
                'job_id' => $this->job?->getJobId(),
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build vector API payload based on provider
     */
    private function buildProviderPayload(): array
    {
        return match ($this->integrationPayload['integration_provider']) {
            Constants::WOOCOMMERCE => $this->wooCommercePayload(),

            default => throw new \InvalidArgumentException(
                'Unsupported integration provider: '
                . $this->integrationPayload['integration_provider']
            ),
        };
    }

    /**
     * WooCommerce → Vector payload
     */
    private function wooCommercePayload(): array
    {
        return [
            'company_id' => $this->resolveCompanyCode(),

            'systems' => [
                'system' => Constants::WOOCOMMERCE,

                'credentials' => [
                    'url'     => $this->integrationPayload['url'],
                    'userid'  => $this->integrationPayload['consumer_key'],
                    'api_key' => $this->integrationPayload['consumer_secret'],
                ],
            ],
        ];
    }

    /**
     * TEMP LOGIC:
     * Currently deriving company_code from integration URL.
     *
     * Example:
     * https://example.com → EXAM
     *
     * TODO:
     * In future, do NOT derive company_code from URL.
     * Instead, pass and use integration_uid directly
     * for vector identification.
     */
    private function resolveCompanyCode(): string
    {
        $url = $this->integrationPayload['url'] ?? null;

        if (! $url) {
            return '456789'; // default fallback
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return '456789';
        }

        // Normalize host (remove www.)
        $host = preg_replace('/^www\./', '', strtolower($host));

        return match ($host) {
            'gigigadgets.com' => '456789',
            'nams.store',
            'nams.design'     => '123456',
            default           => '456789',
        };
    }

}