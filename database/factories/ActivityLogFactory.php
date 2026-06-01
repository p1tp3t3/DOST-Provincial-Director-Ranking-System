<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user_ids = User::all(['id'])->toArray();
        $size = sizeof($user_ids);
        
        return [
            'user_id' => $user_ids[random_int(0, $size - 1)]['id'],
            'type' => fake()->randomElement(['log in', 'log out', 'province creation', 'registration', 'profile update']),
            'description' => fake()->sentence(4)
        ];
    }
}
