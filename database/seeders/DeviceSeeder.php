<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeviceSeeder extends Seeder
{
    private const SAMPLE_DEVICES = [
        ['manufacturer' => 'Eemo', 'device_model' => 'GT06', 'firmware_version' => '1.2.3'],
        ['manufacturer' => 'Concox', 'device_model' => 'GT06N', 'firmware_version' => '2.1.0'],
        ['manufacturer' => 'Teltonika', 'device_model' => 'FMC920', 'firmware_version' => '03.27.15'],
        ['manufacturer' => 'Queclink', 'device_model' => 'GV300', 'firmware_version' => '45.12.08'],
        ['manufacturer' => 'Coban', 'device_model' => 'TK103B', 'firmware_version' => '1.0.8'],
        ['manufacturer' => 'Meitrack', 'device_model' => 'T366L', 'firmware_version' => '6.4.2'],
        ['manufacturer' => 'Jimi IoT', 'device_model' => 'VL103M', 'firmware_version' => '3.8.1'],
        ['manufacturer' => 'Ruptela', 'device_model' => 'Trace5', 'firmware_version' => '01.18.4'],
    ];

    public function run(): void
    {
        $usersWithoutDevices = User::query()
            ->whereDoesntHave('devices')
            ->orderBy('id')
            ->get();

        foreach ($usersWithoutDevices as $index => $user) {
            $sample = self::SAMPLE_DEVICES[$index % count(self::SAMPLE_DEVICES)];

            Device::create([
                'user_id' => $user->id,
                ...$sample,
                'device_uuid' => (string) Str::uuid(),
            ]);
        }
    }
}
