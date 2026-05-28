<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DirectorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->profile;

        // Cleanly concatenate string name blocks with safe spacing fallbacks
        $firstName = $profile?->first_name ?? '';
        $middleName = $profile?->middle_name ? $profile->middle_name . ' ' : '';
        $lastName = $profile?->last_name ?? '';

        return [
            'id'                => $this->id,
            'employee_id'       => $this->dost_employee_id,
            'name'              => trim($firstName . ' ' . $middleName . $lastName),
            'province'          => $this->province?->name,
            'length_of_service' => $profile->length_of_service,
        ];
    }
}
