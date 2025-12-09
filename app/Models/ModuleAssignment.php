<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'assignment_title',
        'instructions',
        'attachment_url',
        'max_points',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }
}
