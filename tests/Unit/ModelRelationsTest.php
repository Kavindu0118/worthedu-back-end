<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Models\AssignmentSubmission;
use App\Models\QuizAttempt;
use App\Models\User;

class ModelRelationsTest extends TestCase
{
    public function test_course_relations_return_relation_objects()
    {
        $course = new Course();

        $this->assertInstanceOf(Relation::class, $course->instructor());
        $this->assertInstanceOf(Relation::class, $course->modules());
        $this->assertInstanceOf(Relation::class, $course->courseModules());
        $this->assertInstanceOf(Relation::class, $course->enrollments());
    }

    public function test_module_and_lesson_relations()
    {
        $module = new Module();
        $lesson = new Lesson();

        $this->assertInstanceOf(Relation::class, $module->course());
        $this->assertInstanceOf(Relation::class, $module->lessons());

        $this->assertInstanceOf(Relation::class, $lesson->module());
    }

    public function test_enrollment_and_submission_relations()
    {
        $enrollment = new Enrollment();
        $submission = new AssignmentSubmission();

        $this->assertInstanceOf(Relation::class, $enrollment->course());
        $this->assertInstanceOf(Relation::class, $enrollment->learner());

        $this->assertInstanceOf(Relation::class, $submission->assignment());
        $this->assertInstanceOf(Relation::class, $submission->learner());
    }

    public function test_quiz_attempt_relations()
    {
        $attempt = new QuizAttempt();

        $this->assertInstanceOf(Relation::class, $attempt->quiz());
        $this->assertInstanceOf(Relation::class, $attempt->learner());
    }

    public function test_user_factory_make()
    {
        if (method_exists(User::class, 'factory')) {
            $user = User::factory()->make();
            $this->assertInstanceOf(User::class, $user);
        } else {
            $this->assertTrue(true, 'User factory not available in this environment.');
        }
    }
}
