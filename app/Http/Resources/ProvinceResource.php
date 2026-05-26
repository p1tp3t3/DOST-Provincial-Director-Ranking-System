<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->provincialDirector->profile;
        $name = "{$profile->first_name} {$profile->middle_name} {$profile->last_name}";

        return [
            'name' => $this->name,
            'provincial_director' => [
                'name' => $name,
            ],
            'employee_member_count' => sizeOf($this->user)
        ];
    }
}
