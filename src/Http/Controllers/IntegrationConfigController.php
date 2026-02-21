<?php

namespace Iquesters\Integration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Iquesters\Integration\Models\Integration;
use Illuminate\Support\Facades\Log;
use Iquesters\Integration\Constants\Constants;
use Iquesters\Integration\Jobs\SyncVectorJob;
use Iquesters\Integration\Models\IntegrationMeta;
use Iquesters\Dev\Models\Knob;

class IntegrationConfigController extends Controller
{
    public function configure($integrationUid)
    {
        try {
            $integration = Integration::where('uid', $integrationUid)
                ->with('metas')
                ->firstOrFail();

            $provider = $integration->supportedIntegration;

            Log::debug('Integration Configure', [
                'integration_uid' => $integrationUid,
                'provider' => $provider->name,
            ]);

            // Get existing configuration data
            $websiteUrl = $integration->getMeta('website_url');
            $consumerKey = $integration->getMeta('consumer_key');
            $consumerSecret = $integration->getMeta('consumer_secret');
            $isActive = $integration->getMeta('is_active');
            $chatbot_vector = $integration->getMeta('chatbot_vector');

            Log::info('Loading Integration Configuration', [
                'integration_uid' => $integrationUid,
                'has_website_url' => !empty($websiteUrl),
                'has_consumer_key' => !empty($consumerKey),
                'has_consumer_secret' => !empty($consumerSecret),
                'is_active' => $isActive,
            ]);

            switch ($provider->name) {
                case Constants::WOOCOMMERCE:
                    return view(
                        'integration::integrations.woocommerces.configure',
                        compact(
                            'integration',
                            'websiteUrl',
                            'consumerKey',
                            'consumerSecret',
                            'isActive'
                        )
                    );
                case Constants::GAUTAMS_CHATBOT:
                    return view(
                        'integration::integrations.gautams_bot.configure',
                        compact(
                            'integration',
                            'chatbot_vector'
                        )
                    );
                default:
                    abort(404, 'Integration provider not supported.');
            }
        } catch (\Throwable $th) {
            Log::error('Integration Configure Error', [
                'integration_uid' => $integrationUid,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'url'             => 'required|url',
            'consumer_key'    => 'required|string',
            'consumer_secret' => 'required|string',
        ]);

        try {
            $integration = Integration::where('user_id', auth()->id())
                ->whereHas('supportedIntegration', function ($q) {
                    $q->where('name', 'woocommerce');
                })
                ->firstOrFail();

            $userId = auth()->id();

            $this->saveIntegrationMeta($integration->id, 'website_url', $request->url, $userId);
            $this->saveIntegrationMeta($integration->id, 'consumer_key', $request->consumer_key, $userId);
            $this->saveIntegrationMeta($integration->id, 'consumer_secret', $request->consumer_secret, $userId);

            $integration->update([
                'status'     => 'active',
                'updated_by' => $userId,
            ]);
            $provider = $integration->supportedIntegration;
            
            $payload = [
                'integration_id' => $integration->id,
                'systems' => [
                    [
                        'integration_provider' => $provider->name,
                        'integration_uuid'     => $integration->uid,
                        'recreate_flag'        => false,
                    ]
                ],
            ];
            
            SyncVectorJob::dispatch($payload);
            
            return response()->json([
                'success'  => true,
                'redirect' => route('integration.show', $integration->uid),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to save integration configuration.',
            ], 500);
        }
    }

    public function knob($integrationUid)
    {
        try {
            $integration = Integration::where('uid', $integrationUid)
                ->with(['metas','supportedIntegration'])
                ->firstOrFail();

            $provider = $integration->supportedIntegration;

            Log::debug('Integration Knob', [
                'integration_uid' => $integrationUid,
                'provider' => $provider->name
            ]);

            $metaMap = [
                'woocommerce'      => 'gc_vec_knob',
                'gautams-chatbot'  => 'gc_chatbot_knob',
            ];

            $knobUid = null;

            // try to get knob from meta if mapping exists
            if (isset($metaMap[$provider->name])) {
                $metaValue = $integration->getMeta($metaMap[$provider->name]);
                $knobUid = is_array($metaValue) ? ($metaValue[0] ?? null) : $metaValue;
            }

            /*
            |--------------------------------------------------------------------------
            | FALLBACK: no knob in meta
            |--------------------------------------------------------------------------
            | We fetch latest knob belonging to this provider.
            | Assumes knob UID starts with provider prefix OR you store provider
            | in knob YAML or status. Adjust if needed.
            */

            if (!$knobUid) {

                Log::warning('Knob missing in meta, using fallback', [
                    'integration_uid' => $integrationUid,
                    'provider' => $provider->name
                ]);

                // simplest fallback → latest knob overall
                $latestKnob = Knob::orderByDesc('version')->first();

            } else {

                $latestKnob = Knob::where('uid', $knobUid)
                    ->orderByDesc('version')
                    ->first();
            }

            if (!$latestKnob) {
                return view('integration::integrations.knob', [
                    'integration' => $integration,
                    'knob' => null,
                    'totalVersions' => 0,
                ]);
            }

            $totalVersions = Knob::where('uid', $latestKnob->uid)->count();

            return view('integration::integrations.knob', [
                'integration' => $integration,
                'knob' => $latestKnob,
                'totalVersions' => $totalVersions,
            ]);

        } catch (\Throwable $th) {
            Log::error('Integration knob Error', [
                'integration_uid' => $integrationUid,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', $th->getMessage());
        }
    }


    protected function saveIntegrationMeta(
        int $integrationId,
        string $key,
        $value,
        int $userId
    ): void {
        IntegrationMeta::updateOrCreate(
            [
                'ref_parent' => $integrationId,
                'meta_key'   => $key,
            ],
            [
                'meta_value' => $value,
                'status'     => Constants::ACTIVE,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }
}