<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'auth_id' => 'auth:'.$this->faker->uuid(),
            'name' => $this->faker->name(),
            'username' => substr($this->faker->unique()->userName(), 0, 15),
            'country_id' => 1220,
            'delete_flg' => rand(0,1)
        ];
    }
}
