<?php

namespace App\Modules\Fulfillment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FulfillmentOrderItemsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'fulfillment_order_id' => ['nullable', 'integer', 'min:1'],
            'order_item_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
