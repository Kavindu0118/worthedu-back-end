<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'module_title',
        'module_description',
        'order_index',
        'duration',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function quizzes()
    {
        return $this->hasMany(ModuleQuiz::class, 'module_id');
    }

    public function assignments()
    {
        return $this->hasMany(ModuleAssignment::class, 'module_id');
    }

    public function notes()
    {
        return $this->hasMany(ModuleNote::class, 'module_id');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'module_id')->orderBy('order_no');
    }

    public function tests()
    {
        return $this->hasMany(Test::class, 'module_id')->orderBy('start_date', 'asc');
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class, 'lesson_id');
    }
}
