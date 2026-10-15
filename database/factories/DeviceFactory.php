<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'manufacturer' => fake()->company(),
            'device_model' => fake()->bothify('Model-####'),
            'device_uuid' => (string) Str::uuid(),
            'firmware_version' => fake()->numerify('v#.#.#'),
        ];
    }
}
