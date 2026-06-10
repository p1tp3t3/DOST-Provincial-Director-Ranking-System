<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 💡 Use $this to pull data directly from the active User model instance
        $profile = $this->profile;

        // Cleanly concatenate string name blocks with safe spacing fallbacks
        $firstName = $profile?->first_name ?? '';
        $middleName = $profile?->middle_name ? $profile->middle_name . ' ' : '';
        $lastName = $profile?->last_name ?? '';

        return [
            'id'                => $this->id,
            'employee_id'       => $this->dost_employee_id,
            'role'              => $this->role,
            'activate'          => (bool) $this->activate,
            'profile_picture'   => $profile?->profile_picture,
            'name'              => trim($firstName . ' ' . $middleName . $lastName),
            'username'          => $this->username,
            'email'             => $this->email,
            'province'          => $this->province?->name,
            'province_id'       => $this->province_id,
            'prefix'            => $profile?->prefix,
            'first_name'        => $profile?->first_name,
            'middle_name'       => $profile?->middle_name,
            'last_name'         => $profile?->last_name,
            'suffix'            => $profile?->suffix,
            'length_of_service' => $profile?->length_of_service,
        ];
    }
}
