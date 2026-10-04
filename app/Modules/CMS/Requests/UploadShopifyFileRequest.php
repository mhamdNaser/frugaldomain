<?php

namespace App\Modules\CMS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadShopifyFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['nullable', 'uuid'],
            // Formats Shopify accepts in Content > Files (images, videos, 3D models, documents).
            'file' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,heic,svg,mp4,mov,webm,glb,usdz,pdf,txt,csv,zip,doc,docx,xls,xlsx,ppt,pptx,json'],
            'title' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:120'],
            'owner_type' => ['nullable', 'in:product,variant,collection'],
            'owner_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

