<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_code',
        'name',
        'email',
        'phone',
        'address',
        'password',
        'profile_image',
        'status',
        'last_login_at',
        'token',
        'ip_address',
        'login_device',
    ];

    protected $hidden = [
        'password',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (empty($user->user_code)) {
                $user->user_code = self::generateUniqueUserCode();
            }

            if (empty($user->token)) {
                $user->token = Str::random(64);
            }
        });
    }

    public static function generateUniqueUserCode(): string
    {
        do {
            $code = strtoupper(Str::random(3)).random_int(1000, 9999).strtoupper(Str::random(3));
        } while (self::where('user_code', $code)->exists());

        return $code;
    }
}
