<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'user_id',
        'action_type',
        'target_id',
        'target_type',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public static function log($adminId, $actionType, $targetId = null, $targetType = null, $details = null, $ipAddress = null)
    {
        return static::create([
            'admin_id' => $adminId,
            'action_type' => $actionType,
            'target_id' => $targetId,
            'target_type' => $targetType,
            'details' => $details,
            'ip_address' => $ipAddress ?: request()->ip(),
        ]);
    }
}
