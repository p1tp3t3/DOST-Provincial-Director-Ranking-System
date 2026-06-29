<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->user->profile;

        $firstName = $profile?->first_name ?? '';
        $middleName = $profile?->middle_name ? $profile->middle_name . ' ' : '';
        $lastName = $profile?->last_name ?? '';

        return [
            'id'          => $this->id,
            'employee_id' => $this->user->dost_employee_id,
            'role'        => $this->user->role,
            'name'        => trim($firstName . ' ' . $middleName . $lastName),
            'profile_picture'    => $profile?->profile_picture,
            'type'        => $this->type,
            'description'       => $this->description,
            'created_at'    => $this->created_at,
        ];
    }
}
