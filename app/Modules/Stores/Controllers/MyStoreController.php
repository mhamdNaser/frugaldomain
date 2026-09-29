<?php

namespace App\Modules\Stores\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Services\FreePlanService;
use App\Modules\Stores\Models\Store;
use App\Modules\Stores\Services\ShopifyConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The store owner's own store: connect it (domain + Admin API token), check
 * the connection, and follow the import. Every action works on the
 * authenticated user's store only.
 */
class MyStoreController extends Controller
{
    public function __construct(
        private ShopifyConnectionService $shopify,
        private FreePlanService $freePlan,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $store = $request->user()->store()->first();

        return response()->json(['data' => $store ? $this->present($store) : null]);
    }

    /** Checks credentials against Shopify without saving anything. */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shopify_domain' => ['required', 'string', 'max:255'],
            'shopify_access_token' => ['required', 'string', 'max:255'],
        ]);

        return $this->verified(fn () => $this->shopify->verify($data['shopify_domain'], $data['shopify_access_token']));
    }

    /** Re-checks the credentials already saved for the store. */
    public function test(Request $request): JsonResponse
    {
        $store = $request->user()->store()->first();
        abort_if(! $store || ! $store->shopify_domain || ! $store->shopify_access_token, 404, 'Connect your store first.');

        return $this->verified(fn () => $this->shopify->verify(
            $store->shopify_domain,
            Crypt::decryptString($store->shopify_access_token),
        ));
    }

    /**
     * Verifies the credentials, then creates (or updates) the user's store with
     * the details Shopify returns. The token is stored encrypted.
     */
    public function connect(Request $request): JsonResponse
    {
        $user = $request->user();
        $existing = $user->store()->first();

        $data = $request->validate([
            'shopify_domain' => ['required', 'string', 'max:255'],
            // Optional when updating: keep the saved token if none is sent.
            'shopify_access_token' => [$existing ? 'nullable' : 'required', 'string', 'max:255'],
            'shopify_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $token = $data['shopify_access_token'] ?? null;
        if (! $token && $existing?->shopify_access_token) {
            $token = Crypt::decryptString($existing->shopify_access_token);
        }

        try {
            $check = $this->shopify->verify($data['shopify_domain'], (string) $token);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($check['missing_required']) {
            return response()->json([
                'message' => 'The access token is missing permissions the import needs.',
                'data' => $check,
            ], 422);
        }

        $shop = $check['shop'];

        $takenBy = Store::withTrashed()
            ->where(fn ($q) => $q->where('shopify_domain', $shop['domain'])->orWhere('shopify_store_id', $shop['id']))
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();
        if ($takenBy) {
            return response()->json(['message' => 'This Shopify store is already connected to another account.'], 422);
        }

        $store = DB::transaction(function () use ($user, $existing, $shop, $token, $data) {
            $attributes = [
                'owner_id' => $user->id,
                'shopify_store_id' => $shop['id'],
                'shopify_domain' => $shop['domain'],
                'shopify_access_token' => Crypt::encryptString((string) $token),
                'name' => $shop['name'],
                'email' => $shop['email'],
                'currency' => $shop['currency'],
                'timezone' => $shop['timezone'],
                'status' => 'active',
            ];
            if (filled($data['shopify_webhook_secret'] ?? null)) {
                $attributes['shopify_webhook_secret'] = $data['shopify_webhook_secret'];
            }

            $store = $existing ?: new Store(['installed_at' => now()]);
            $store->fill($attributes)->save();

            $this->freePlan->subscribe($store);

            return $store;
        });

        return response()->json([
            'message' => $existing ? 'Store connection updated.' : 'Store connected successfully.',
            'data' => $this->present($store->fresh()),
            'check' => $check,
        ], $existing ? 200 : 201);
    }

    /** Latest import run per data type, for the progress view. */
    public function syncStatus(Request $request): JsonResponse
    {
        $store = $request->user()->store()->first();
        abort_if(! $store, 404, 'Connect your store first.');

        return response()->json(['data' => $this->syncSummary($store)]);
    }

    private function verified(callable $check): JsonResponse
    {
        try {
            return response()->json(['data' => $check()]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function present(Store $store): array
    {
        $subscription = Subscription::with('plan')
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->latest('started_at')
            ->first();

        return [
            'id' => $store->id,
            'name' => $store->name,
            'email' => $store->email,
            'shopify_domain' => $store->shopify_domain,
            'shopify_store_id' => $store->shopify_store_id,
            'currency' => $store->currency,
            'timezone' => $store->timezone,
            'status' => $store->status,
            'has_access_token' => filled($store->shopify_access_token),
            'has_webhook_secret' => filled($store->shopify_webhook_secret),
            'installed_at' => $store->installed_at,
            'last_synced_at' => $store->last_synced_at,
            'plan' => $subscription?->plan ? [
                'name' => $subscription->plan->name,
                'price' => $subscription->plan->price,
                'billing_interval' => $subscription->plan->billing_interval,
                'started_at' => $subscription->started_at,
            ] : null,
            'sync' => $this->syncSummary($store),
        ];
    }

    private function syncSummary(Store $store): array
    {
        $latestIds = DB::table('sync_runs')
            ->where('store_id', $store->id)
            ->selectRaw('MAX(id) as id')
            ->groupBy('type');

        $runs = DB::table('sync_runs')
            ->whereIn('id', $latestIds)
            ->orderBy('type')
            ->get(['type', 'status', 'fetched_count', 'synced_count', 'failed_count', 'error_message', 'started_at', 'finished_at', 'created_at']);

        $counts = $runs->countBy('status');

        return [
            'total' => $runs->count(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'running' => (int) ($counts['running'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
            'in_progress' => ($counts['pending'] ?? 0) + ($counts['running'] ?? 0) > 0,
            'last_synced_at' => $store->last_synced_at,
            'runs' => $runs->values(),
        ];
    }
}
