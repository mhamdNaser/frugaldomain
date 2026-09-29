<?php

namespace App\Modules\Stores\Services;

use App\Modules\Billing\Services\FreePlanService;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Connects a Shopify store to an owner: checks the credentials with Shopify,
 * then saves the store with the details Shopify returns (token encrypted) and
 * puts it on the free plan. Used by the store owner (My Store) and by the
 * super admin (Stores > Add store).
 */
class StoreConnector
{
    public function __construct(
        private ShopifyConnectionService $shopify,
        private FreePlanService $freePlan,
    ) {}

    /**
     * @param  array{shopify_domain: string, shopify_access_token?: ?string, shopify_webhook_secret?: ?string}  $input
     * @return array{store: Store, check: array, created: bool}
     *
     * @throws ValidationException when Shopify rejects the credentials or the store is taken
     */
    public function connect(User $owner, array $input, ?Store $existing = null): array
    {
        $token = $input['shopify_access_token'] ?? null;
        if (! $token && $existing?->shopify_access_token) {
            $token = Crypt::decryptString($existing->shopify_access_token);
        }

        try {
            $check = $this->shopify->verify($input['shopify_domain'], (string) $token);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['shopify_access_token' => $e->getMessage()]);
        }

        if ($check['missing_required']) {
            throw new MissingScopesException($check);
        }

        $shop = $check['shop'];

        $taken = Store::withTrashed()
            ->where(fn ($q) => $q->where('shopify_domain', $shop['domain'])->orWhere('shopify_store_id', $shop['id']))
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();
        if ($taken) {
            throw ValidationException::withMessages(['shopify_domain' => 'This Shopify store is already connected to another account.']);
        }

        $store = DB::transaction(function () use ($owner, $existing, $shop, $token, $input) {
            $attributes = [
                'owner_id' => $owner->id,
                'shopify_store_id' => $shop['id'],
                'shopify_domain' => $shop['domain'],
                'shopify_access_token' => Crypt::encryptString((string) $token),
                'name' => $shop['name'],
                'email' => $shop['email'],
                'currency' => $shop['currency'],
                'timezone' => $shop['timezone'],
                'status' => 'active',
            ];
            if (filled($input['shopify_webhook_secret'] ?? null)) {
                $attributes['shopify_webhook_secret'] = $input['shopify_webhook_secret'];
            }

            $store = $existing ?: new Store(['installed_at' => now()]);
            $store->fill($attributes)->save();

            $this->freePlan->subscribe($store);

            return $store;
        });

        return ['store' => $store->fresh(), 'check' => $check, 'created' => ! $existing];
    }
}
