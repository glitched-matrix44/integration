<?php

namespace Iquesters\Integration\Services;

use Iquesters\Integration\Models\Integration;
use Iquesters\Integration\Constants\Constants;
use Iquesters\Integration\Jobs\SyncVectorJob;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\Enums\Module;
use Iquesters\Foundation\System\Traits\Loggable;

class VectorJobDispatcher
{
    use Loggable;

    /**
     * Dispatch vector sync job for a single integration
     */
    public static function dispatchForIntegration(Integration $integration): void
    {
        $logger = new self();

        try {
            $provider = $integration->supportedIntegration;

            if (! $provider) {
                $logger->logWarning('supportedIntegration missing', [
                    'integration_uid' => $integration->uid,
                ]);
                return;
            }

            $supportedProviders = [
                Constants::WOOCOMMERCE,
                // add new ecom providers here later
            ];

            if (! in_array($provider->name, $supportedProviders, true)) {
                $logger->logInfo('Vector sync skipped: unsupported provider', [
                    'integration_uid' => $integration->uid,
                    'provider'        => $provider->name,
                ]);
                return;
            }

            $payload = [
                'integration_id'       => $integration->id,
                'integration_uid'      => $integration->uid,
                'url'                  => $integration->getMeta('website_url'),
                'consumer_key'         => $integration->getMeta('consumer_key'),
                'consumer_secret'      => $integration->getMeta('consumer_secret'),
                'integration_provider' => $provider->name,
            ];

            $logger->logInfo('Dispatching vector sync job', [
                'integration_uid' => $integration->uid,
                'provider'        => $provider->name,
            ]);

            $logger->logDebug('Dispatch payload', $payload);

            SyncVectorJob::dispatch($payload);

        } catch (\Throwable $e) {
            $logger->logError('Failed to dispatch vector job: '.$e->getMessage(), [
                'integration_uid' => $integration->uid ?? null,
                'trace'           => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Dispatch vector sync job for all active WooCommerce integrations
     */
    public static function dispatchForAllActive(): void
    {
        $logger = new self();

        try {
            $conf = ConfProvider::from(Module::INTEGRATION);

            if (! $conf->vector_sync_enabled) {
                $logger->logInfo('Vector sync scheduler disabled via config');
                return;
            }

            $logger->logMethodStart('Scheduled vector dispatch started');

            Integration::with('supportedIntegration')
                ->where('status', Constants::ACTIVE)
                ->whereHas('supportedIntegration', function ($q) {
                    $q->where('name', Constants::WOOCOMMERCE);
                })
                ->chunkById(50, function ($integrations) use ($logger) {

                    foreach ($integrations as $integration) {

                        if ($integration->getMeta('vector_sync_enabled') === '0') {
                            $logger->logInfo('Vector sync disabled for integration', [
                                'integration_uid' => $integration->uid,
                            ]);
                            continue;
                        }

                        self::dispatchForIntegration($integration);
                    }
                });

            $logger->logMethodEnd('Scheduled vector dispatch completed');

        } catch (\Throwable $e) {
            $logger->logError('Scheduled dispatch failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Dispatch vector sync job for manual trigger
     */
    public static function dispatchManual(Integration $integration): bool
    {
        $logger = new self();

        try {
            $conf = ConfProvider::from(Module::INTEGRATION);

            if (! $conf->vector_sync_manual_allowed) {
                $logger->logWarning('Manual sync blocked by config', [
                    'integration_uid' => $integration->uid,
                ]);
                return false;
            }

            $logger->logInfo('Manual vector sync triggered', [
                'integration_uid' => $integration->uid,
            ]);

            self::dispatchForIntegration($integration);

            return true;

        } catch (\Throwable $e) {
            $logger->logError('Manual dispatch failed: '.$e->getMessage(), [
                'integration_uid' => $integration->uid ?? null,
                'trace'           => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}