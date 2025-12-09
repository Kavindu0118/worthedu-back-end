<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'quiz_title',
        'quiz_description',
        'quiz_data',
        'total_points',
        'time_limit',
    ];

    protected $casts = [
        'quiz_data' => 'array',
    ];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }
}
