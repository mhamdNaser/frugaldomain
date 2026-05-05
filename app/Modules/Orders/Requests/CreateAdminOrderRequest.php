<?php

namespace App\Modules\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAdminOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer' => ['nullable', 'array'],
            'customer.first_name' => ['nullable', 'string', 'max:255'],
            'customer.last_name' => ['nullable', 'string', 'max:255'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:50'],
            'customer.note' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'max:80'],
            'payment_status' => ['nullable', 'in:pending,paid,authorized,partially_paid,refunded,voided'],
            'fulfillment_status' => ['nullable', 'in:pending,fulfilled,unfulfilled,partial'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'tax_lines' => ['nullable', 'array'],
            'tax_lines.*.title' => ['nullable', 'string', 'max:255'],
            'tax_lines.*.rate' => ['nullable', 'numeric', 'min:0'],
            'tax_lines.*.rate_percentage' => ['nullable', 'numeric', 'min:0'],
            'tax_lines.*.price' => ['required_with:tax_lines', 'numeric', 'min:0'],
            'tax_lines.*.channel_liable' => ['nullable', 'boolean'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:80'],
            'send_receipt' => ['nullable', 'boolean'],
            'sync_to_shopify' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
