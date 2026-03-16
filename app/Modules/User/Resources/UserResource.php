<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'medium_name' => $this->medium_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => [
                'address_1' => $this->address_1,
                'address_2' => $this->address_2,
                'address_3' => $this->address_3,
            ],
            'image' => $this->image,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'roles' => $this->getRoleNames(), // ترجع كل الأدوار كـ array
            'permissions' => $this->getAllPermissions()->pluck('name'), // كل الصلاحيات
            'created_at' => $this->created_at?->toDateTimeString(),
            'country' => [
                'id'    => $this->country?->id,
                'name'  => $this->country?->name
            ],
            'state' => [
                'id'    => $this->state?->id,
                'name'  =>  $this->state?->name
            ],
            'city' => [
                'id'    => $this->city?->id,
                'name'  => $this->city?->name
            ],
        ];
    }
}
