<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Shipping\Models\ShippingZone;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Shipping zones live inside a delivery profile location group. Shopify requires at least
 * one shipping rate to create a zone, which the dashboard does not collect, so only
 * existing zones are updated / deleted.
 */
class ShippingZoneSyncer extends BaseSyncer
{
    public function create(Model $model, SyncContext $ctx): void
    {
        if (Gid::make($model->shopify_zone_id, 'DeliveryZone')) {
            $this->update($model, [], ['name', 'countries'], $ctx);
            return;
        }

        $ctx->note('Shipping zone saved locally only: Shopify requires a shipping rate to create a zone.');
    }

    /**
     * @param ShippingZone $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $zoneGid = Gid::make($model->shopify_zone_id, 'DeliveryZone');
        $profileGid = Gid::make($model->shopify_profile_id, 'DeliveryProfile');

        if (!$zoneGid || !$profileGid || !$this->touched($changed, 'name', 'countries')) {
            return;
        }

        $groupGid = $this->locationGroupOf($profileGid, $zoneGid, $ctx);

        if (!$groupGid) {
            return;
        }

        $zone = ['id' => $zoneGid];
        if (filled($model->name)) {
            $zone['name'] = (string) $model->name;
        }
        if (in_array('countries', $changed, true) && ($countries = $this->countries($model->countries)) !== []) {
            $zone['countries'] = $countries;
        }

        $ctx->gw->mutate(<<<'GQL'
mutation ZoneUpdate($id: ID!, $profile: DeliveryProfileInput!) {
  deliveryProfileUpdate(id: $id, profile: $profile) {
    profile { id }
    userErrors { field message }
  }
}
GQL, [
            'id' => $profileGid,
            'profile' => ['locationGroupsToUpdate' => [['id' => $groupGid, 'zonesToUpdate' => [$zone]]]],
        ], 'deliveryProfileUpdate');
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $zoneGid = Gid::make($snapshot['shopify_zone_id'] ?? null, 'DeliveryZone');
        $profileGid = Gid::make($snapshot['shopify_profile_id'] ?? null, 'DeliveryProfile');

        if (!$zoneGid || !$profileGid) {
            return;
        }

        $this->ignoreNotFound(fn () => $ctx->gw->mutate(<<<'GQL'
mutation ZoneDelete($id: ID!, $profile: DeliveryProfileInput!) {
  deliveryProfileUpdate(id: $id, profile: $profile) {
    profile { id }
    userErrors { field message }
  }
}
GQL, ['id' => $profileGid, 'profile' => ['zonesToDelete' => [$zoneGid]]], 'deliveryProfileUpdate'));
    }

    private function locationGroupOf(string $profileGid, string $zoneGid, SyncContext $ctx): ?string
    {
        $groups = $ctx->gw->query(<<<'GQL'
query ProfileGroups($id: ID!) {
  deliveryProfile(id: $id) {
    profileLocationGroups {
      locationGroup { id }
      locationGroupZones(first: 100) { nodes { zone { id } } }
    }
  }
}
GQL, ['id' => $profileGid])['deliveryProfile']['profileLocationGroups'] ?? [];

        foreach ($groups as $group) {
            foreach ($group['locationGroupZones']['nodes'] ?? [] as $node) {
                if (($node['zone']['id'] ?? null) === $zoneGid) {
                    return $group['locationGroup']['id'] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Accepts country codes ("US") or Shopify country objects ({code: {countryCode}}).
     *
     * @return array<int, array<string, mixed>>
     */
    private function countries(mixed $countries): array
    {
        if (is_string($countries)) {
            $decoded = json_decode($countries, true);
            $countries = is_array($decoded) ? $decoded : explode(',', $countries);
        }

        return collect(is_array($countries) ? $countries : [])
            ->map(function ($country) {
                $code = is_array($country)
                    ? ($country['code']['countryCode'] ?? $country['code']['restOfWorld'] ?? $country['countryCode'] ?? $country['code'] ?? null)
                    : $country;

                if ($code === true || strtoupper((string) $code) === 'REST_OF_WORLD') {
                    return ['restOfWorld' => true];
                }

                $code = strtoupper(trim((string) $code));

                return strlen($code) === 2 ? ['code' => $code, 'includeAllProvinces' => true] : null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
