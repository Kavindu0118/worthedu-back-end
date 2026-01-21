<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'question',
        'type',
        'points',
        'options',
        'correct_answer',
        'allowed_file_types',
        'max_file_size',
        'max_characters',
        'order_index',
    ];

    protected $casts = [
        'options' => 'array',
        'allowed_file_types' => 'array',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function answers()
    {
        return $this->hasMany(TestAnswer::class, 'question_id');
    }

    public function isAutoGradable()
    {
        return $this->type === 'mcq';
    }
}
