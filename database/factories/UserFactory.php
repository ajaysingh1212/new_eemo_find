<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_code' => User::generateUniqueUserCode(),
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('9#########'),
            'address' => fake()->address(),
            'password' => bcrypt('DemoUser@123'),
            'profile_image' => null,
            'status' => fake()->randomElement(['active', 'inactive']),
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'token' => Str::random(64),
        ];
    }
}
