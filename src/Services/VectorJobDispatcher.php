<?php

namespace Iquesters\Integration\Services;

use Illuminate\Support\Facades\Log;
use Iquesters\Integration\Models\Integration;
use Iquesters\Integration\Constants\Constants;
use Iquesters\Integration\Jobs\SyncVectorJob;
use Iquesters\Foundation\Support\ConfProvider;
use Iquesters\Foundation\Enums\Module;

class VectorJobDispatcher
{
    /**
     * Dispatch vector sync job for a single integration
     */
    public static function dispatchForIntegration(Integration $integration): void
    {
        $provider = $integration->supportedIntegration;

        if (! $provider) {
            Log::warning('Vector sync skipped: supportedIntegration missing', [
                'integration_uid' => $integration->uid,
            ]);
            return;
        }

        // Only WooCommerce for now
        if ($provider->name !== Constants::WOOCOMMERCE) {
            Log::info('Vector sync skipped: unsupported provider', [
                'integration_uid' => $integration->uid,
                'provider'        => $provider->name,
            ]);
            return;
        }

        Log::info('Dispatching vector sync job', [
            'integration_uid' => $integration->uid,
            'provider'        => $provider->name,
        ]);

        SyncVectorJob::dispatch([
            'integration_uid'      => $integration->uid,
            'url'                  => $integration->getMeta('website_url'),
            'consumer_key'         => $integration->getMeta('consumer_key'),
            'consumer_secret'      => $integration->getMeta('consumer_secret'),
            'integration_provider' => $provider->name,
        ]);
    }

    /**
     * Dispatch vector sync job for all active WooCommerce integrations
     */
    public static function dispatchForAllActive(): void
    {
        $conf = ConfProvider::from(Module::INTEGRATION);

        if (! $conf->vector_sync_enabled) {
            Log::info('Vector sync scheduler disabled via config');
            return;
        }

        Log::info('Starting scheduled vector sync dispatch');

        Integration::with('supportedIntegration')
            ->where('status', Constants::ACTIVE)
            ->whereHas('supportedIntegration', function ($q) {
                $q->where('name', Constants::WOOCOMMERCE);
            })
            ->chunkById(50, function ($integrations) {

                foreach ($integrations as $integration) {

                    if ($integration->getMeta('vector_sync_enabled') === '0') {
                        Log::info('Vector sync skipped: disabled at integration level', [
                            'integration_uid' => $integration->uid,
                        ]);
                        continue;
                    }

                    self::dispatchForIntegration($integration);
                }
            });

        Log::info('Scheduled vector sync dispatch completed');
    }

    /**
     * Dispatch vector sync job for manual trigger via UI
     */
    public static function dispatchManual(Integration $integration): bool
    {
        $conf = ConfProvider::from(Module::INTEGRATION);

        if (! $conf->vector_sync_manual_allowed) {
            Log::warning('Manual vector sync blocked by config', [
                'integration_uid' => $integration->uid,
            ]);
            return false;
        }

        Log::info('Manual vector sync triggered', [
            'integration_uid' => $integration->uid,
        ]);

        self::dispatchForIntegration($integration);

        return true;
    }
}