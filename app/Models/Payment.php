<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'enrollment_id',
        'amount',
        'currency',
        'payment_method',
        'payment_status',
        'transaction_id',
        'payment_intent_id',
        'stripe_payment_method_id',
        'stripe_customer_id',
        'receipt_url',
        'metadata',
        'error_message',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrollment_id', 'id')
            ->through('enrollment');
    }

    public function course(): BelongsTo
    {
        return $this->hasOneThrough(
            Course::class,
            Enrollment::class,
            'id',
            'id',
            'enrollment_id',
            'course_id'
        );
    }

    // Scopes
    public function scopeSucceeded($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }
}
