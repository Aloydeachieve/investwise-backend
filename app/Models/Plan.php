<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'min_deposit',
        'max_deposit',
        'profit_rate',
        'duration_days',
        'is_active',
    ];

    protected $casts = [
        'min_deposit' => 'decimal:2',
        'max_deposit' => 'decimal:2',
        'profit_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }
}
