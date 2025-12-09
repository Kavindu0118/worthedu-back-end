<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Module;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'title',
        'category',
        'description',
        'price',
        'level',
        'duration',
        'thumbnail',
        'status',
        'student_count',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'instructor_id', 'instructor_id');
    }

    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    public function courseModules()
    {
        return $this->hasMany(CourseModule::class)->orderBy('order_index');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function assignments()
    {
        return $this->hasManyThrough(ModuleAssignment::class, CourseModule::class, 'course_id', 'module_id');
    }

    public function quizzes()
    {
        return $this->hasManyThrough(ModuleQuiz::class, CourseModule::class, 'course_id', 'module_id');
    }
}

