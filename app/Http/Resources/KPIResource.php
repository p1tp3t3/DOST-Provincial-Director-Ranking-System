<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KPIResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'code'   => $this->code,
            'name'   => $this->name,
            'weight' => (float) $this->weight,
            'kpis'   => $this->kpis->map(fn($k) => [
                'id'              => $k->id,
                'code'            => $k->code,
                'name'            => $k->name,
                'weight'          => (float) $k->weight,
                'is_scored'       => (bool) $k->is_scored,
                'inverse_scoring' => (bool) $k->inverse_scoring,
                'derivation_type' => $k->derivation_type,
                'target'          => $k->target ?? '',
            ])
        ];
    }
}
