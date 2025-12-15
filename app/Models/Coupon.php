<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'description',
        'max_uses',
        'uses_count',
        'starts_at',
        'expires_at',
        'is_active'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function courses()
    {
        return $this->belongsToMany(Course::class);
    }
}
