<?php

namespace App\Modules\Shopify\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopifyWebhookSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'shopify_webhook_secret' => ['required', 'string', 'min:16', 'max:255'],
        ];
    }
}

