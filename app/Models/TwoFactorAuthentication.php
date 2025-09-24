<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TwoFactorAuthentication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'secret_key',
        'recovery_codes',
        'is_enabled',
        'backup_phone',
        'status',
        'last_used_at',
        'device_name',
        'device_type',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'recovery_codes' => 'array',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the 2FA
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate recovery codes
     */
    public static function generateRecoveryCodes($count = 10)
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::random(8);
        }
        return $codes;
    }

    /**
     * Check if recovery code is valid
     */
    public function isValidRecoveryCode($code)
    {
        if (!$this->recovery_codes) {
            return false;
        }

        return in_array($code, $this->recovery_codes);
    }

    /**
     * Use a recovery code
     */
    public function useRecoveryCode($code)
    {
        if (!$this->isValidRecoveryCode($code)) {
            return false;
        }

        $this->recovery_codes = array_diff($this->recovery_codes, [$code]);
        $this->save();

        return true;
    }

    /**
     * Update last used timestamp
     */
    public function markAsUsed()
    {
        $this->update([
            'last_used_at' => Carbon::now(),
        ]);
    }

    /**
     * Enable 2FA for user
     */
    public static function enableForUser($userId, $secretKey, $deviceName = null, $deviceType = null)
    {
        return static::create([
            'user_id' => $userId,
            'secret_key' => $secretKey,
            'recovery_codes' => static::generateRecoveryCodes(),
            'is_enabled' => true,
            'status' => 'active',
            'device_name' => $deviceName,
            'device_type' => $deviceType,
            'last_used_at' => Carbon::now(),
        ]);
    }

    /**
     * Disable 2FA for user
     */
    public function disable()
    {
        $this->update([
            'is_enabled' => false,
            'status' => 'disabled',
            'recovery_codes' => null,
        ]);
    }

    /**
     * Get active 2FA for user
     */
    public static function getActiveForUser($userId)
    {
        return static::where('user_id', $userId)
            ->where('is_enabled', true)
            ->where('status', 'active')
            ->first();
    }
}
