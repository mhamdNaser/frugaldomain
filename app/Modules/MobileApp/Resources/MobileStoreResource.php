<?php

namespace App\Modules\MobileApp\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MobileStoreResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'shopify_domain' => $this->shopify_domain,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'plan' => $this->plan,
            'status' => $this->status,
            'settings' => $this->relationLoaded('settings') ? [
                'allow_guest_checkout' => (bool) $this->settings?->allow_guest_checkout,
                'enable_cod' => (bool) $this->settings?->enable_cod,
                'enable_stripe' => (bool) $this->settings?->enable_stripe,
                'tax_included' => (bool) $this->settings?->tax_included,
                'default_language' => $this->settings?->default_language,
            ] : null,
            'branding' => $this->relationLoaded('branding') ? [
                'logo_url' => $this->branding?->logo_url,
                'favicon_url' => $this->branding?->favicon_url,
                'primary_color' => $this->branding?->primary_color,
                'secondary_color' => $this->branding?->secondary_color,
                'font_family' => $this->branding?->font_family,
            ] : null,
        ];
    }
}
