<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name'                => $this->name,
            'category'            => $this->category,
            'provincial_director' => $this->provincialDirector,
            'employees'           => $this->user->where('role', 'employee')->values(),
        ];
    }
}
