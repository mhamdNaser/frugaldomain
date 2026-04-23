<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'shopify_customer_id' => $this->shopify_customer_id,
            'display_name' => $this->display_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'state' => $this->state,
            'tags' => $this->tags,
            'note' => $this->note,
            'verified_email' => $this->verified_email,
            'tax_exempt' => $this->tax_exempt,
            'orders_count' => (int) ($this->orders_count ?? $this->orders?->count() ?? 0),
            'total_spent' => number_format((float) (
                $this->orders_total_spent
                ?? $this->orders?->where('payment_status', 'paid')->sum('total')
                ?? 0
            ), 2, '.', ''),
            'currency' => $this->currency,
            'default_address_id' => $this->default_address_id,
            'shopify_created_at' => $this->shopify_created_at,
            'shopify_updated_at' => $this->shopify_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'addresses' => $this->addresses?->map(function ($address) {
                return [
                    'id' => $address->id,
                    'shopify_customer_address_id' => $address->shopify_customer_address_id,
                    'first_name' => $address->first_name,
                    'last_name' => $address->last_name,
                    'name' => $address->name,
                    'company' => $address->company,
                    'address1' => $address->address1,
                    'address2' => $address->address2,
                    'city' => $address->city,
                    'province' => $address->province,
                    'province_code' => $address->province_code,
                    'country' => $address->country,
                    'country_code' => $address->country_code,
                    'zip' => $address->zip,
                    'phone' => $address->phone,
                    'is_default' => (bool) $address->is_default,
                ];
            })->values()->all(),
            'marketing_consent' => $this->marketingConsent ? [
                'email_marketing_state' => $this->marketingConsent->email_marketing_state,
                'email_marketing_opt_in_level' => $this->marketingConsent->email_marketing_opt_in_level,
                'email_consent_updated_at' => $this->marketingConsent->email_consent_updated_at,
                'sms_marketing_state' => $this->marketingConsent->sms_marketing_state,
                'sms_marketing_opt_in_level' => $this->marketingConsent->sms_marketing_opt_in_level,
                'sms_consent_updated_at' => $this->marketingConsent->sms_consent_updated_at,
                'source_location_id' => $this->marketingConsent->source_location_id,
            ] : null,
            'orders' => $this->orders?->map(function ($order) {
                return [
                    'id' => $order->id,
                    'shopify_order_id' => $order->shopify_order_id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'fulfillment_status' => $order->fulfillment_status,
                    'subtotal' => $order->subtotal,
                    'tax' => $order->tax,
                    'shipping' => $order->shipping,
                    'discount' => $order->discount,
                    'total' => $order->total,
                    'currency' => $order->currency,
                    'placed_at' => $order->placed_at,
                    'items' => $order->items?->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'shopify_line_item_id' => $item->shopify_line_item_id,
                            'shopify_product_id' => $item->shopify_product_id,
                            'shopify_variant_id' => $item->shopify_variant_id,
                            'product_title' => $item->product_title,
                            'variant_title' => $item->variant_title,
                            'sku' => $item->sku,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
            'draft_orders' => $this->draftOrders?->map(function ($draftOrder) {
                return [
                    'id' => $draftOrder->id,
                    'shopify_draft_order_id' => $draftOrder->shopify_draft_order_id,
                    'name' => $draftOrder->name,
                    'status' => $draftOrder->status,
                    'invoice_url' => $draftOrder->invoice_url,
                    'subtotal' => $draftOrder->subtotal,
                    'tax' => $draftOrder->tax,
                    'total' => $draftOrder->total,
                    'currency' => $draftOrder->currency,
                    'completed_at' => $draftOrder->completed_at,
                    'items' => $draftOrder->items?->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'shopify_line_item_id' => $item->shopify_line_item_id,
                            'shopify_product_id' => $item->shopify_product_id,
                            'shopify_variant_id' => $item->shopify_variant_id,
                            'product_title' => $item->product_title,
                            'variant_title' => $item->variant_title,
                            'sku' => $item->sku,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
            'carts' => $this->carts?->map(function ($cart) {
                return [
                    'id' => $cart->id,
                    'status' => $cart->status,
                    'total_amount' => $cart->total_amount,
                    'currency' => $cart->currency,
                    'expires_at' => $cart->expires_at,
                    'items_count' => $cart->items?->count() ?? 0,
                ];
            })->values()->all(),
            'devices' => $this->devices?->map(function ($device) {
                return [
                    'id' => $device->id,
                    'device_token' => $device->device_token,
                    'platform' => $device->platform,
                    'app_version' => $device->app_version,
                    'is_active' => (bool) $device->is_active,
                    'last_active_at' => $device->last_active_at,
                ];
            })->values()->all(),
            'app_sessions' => $this->appSessions?->map(function ($session) {
                return [
                    'id' => $session->id,
                    'device_id' => $session->device_id,
                    'is_revoked' => (bool) $session->is_revoked,
                    'expires_at' => $session->expires_at,
                    'last_used_at' => $session->last_used_at,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                ];
            })->values()->all(),
            'selling_plan_subscriptions' => $this->sellingPlanSubscriptions?->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'shopify_subscription_contract_id' => $subscription->shopify_subscription_contract_id,
                    'shopify_customer_id' => $subscription->shopify_customer_id,
                    'status' => $subscription->status,
                    'currency' => $subscription->currency,
                    'next_billing_amount' => $subscription->next_billing_amount,
                    'next_billing_date' => $subscription->next_billing_date,
                ];
            })->values()->all(),
        ];
    }
}
