<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->profile;

        return [
            'id_number' => $this->dost_employee_id,
            'name' => "{$profile->first_name} {$profile->middle_name} {$profile->last_name}",
            'length_of_service' => $profile->length_of_service,
            'position' => $profile?->employeeProfile?->position,
            'education_attaiment' => $profile->education_attainment,
            'work_specification' => $profile?->employeeProfile?->work_specification,
            'kpi' => $this?->kpi
        ];
    }
}
