<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->profile;

        // Users without a profile (super_admin, sub_admin, provincial_admin)
        if (!$profile) {
            return [
                'id'                  => $this->id,
                'id_number'           => $this->dost_employee_id,
                'role'                => $this->role,
                'province'            => $this->province?->name,
                'name'                => $this->username ?? $this->email,
                'profile_picture'     => null,
                'cover_picture'       => null,
                'length_of_service'   => null,
                'position'            => null,
                'status'              => null,
                'education_attaiment' => [],
                'work_specification'  => null,
                'kpi'                 => null,
            ];
        }

        // Flatten education JSON {data:[...]} → filtered flat array
        $rawEdu    = $profile->education_attainment['data'] ?? [];
        $education = array_values(array_filter($rawEdu, fn($e) => trim($e) !== ''));

        // Unwrap work_specification JSON {data:['text','','']} → plain string
        $workSpec = null;
        $wsRaw = $profile->employeeProfile?->work_specification;
        if ($wsRaw) {
            $wsArr    = is_array($wsRaw) ? $wsRaw : json_decode($wsRaw, true);
            $workSpec = !empty($wsArr['data'][0]) ? $wsArr['data'][0] : null;
        }

        // Build name from profile parts, skipping blanks
        $nameParts = array_filter(
            [$profile->prefix, $profile->first_name, $profile->middle_name, $profile->last_name, $profile->suffix],
            fn($p) => trim((string) $p) !== ''
        );

        return [
            'id'                  => $this->id,
            'id_number'           => $this->dost_employee_id,
            'role'                => $this->role,
            'province'            => $this->province?->name,
            'name'                => implode(' ', $nameParts),
            'profile_picture'     => $profile->profile_picture,
            'cover_picture'       => $profile->cover_picture,
            'length_of_service'   => $profile->length_of_service,
            'position'            => $profile->employeeProfile?->position,
            'status'              => $profile->employeeProfile?->status,
            'education_attaiment' => $education,
            'work_specification'  => $workSpec,
            'kpi'                 => null,
        ];
    }
}
