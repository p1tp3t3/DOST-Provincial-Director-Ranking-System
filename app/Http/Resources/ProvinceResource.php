<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class ProvinceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $director = $this->provincialDirector;
        $directorData = null;

        if ($director && $director->profile) {
            $p = $director->profile;
            $middle = $p->middle_name ? " {$p->middle_name}" : '';
            $directorData = ['name' => "{$p->first_name}{$middle} {$p->last_name}"];
        }

        return [
            'id'                   => Crypt::encrypt($this->id),
            'name'                 => $this->name,
            'category_label'       => self::category($this->category),
            'category'             => $this->category,
            'provincial_director'  => $directorData,
            'employee_member_count' => $this->users->where('role', 'employee')->count(),
        ];
    }

    private function category(?string $c): string
    {
        return ucfirst((string) $c);
    }
}
