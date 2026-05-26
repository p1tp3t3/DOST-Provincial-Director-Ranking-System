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
            'id' => $this->dost_employee_id,
            'role' => $this->role,
            'name' => trim($firstName . ' ' . $middleName . $lastName),
            'email' => $this->email,             // 💡 Changed from $request
        ];
    }
}
