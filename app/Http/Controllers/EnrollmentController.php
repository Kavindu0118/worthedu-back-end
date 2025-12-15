<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    /**
     * Get all enrollments for authenticated user
     */
    public function index(Request $request)
    {
        $enrollments = Enrollment::with(['course.instructor', 'payment'])
            ->where('learner_id', auth()->id())
            ->orderBy('enrolled_at', 'desc')
            ->get();

        return response()->json($enrollments);
    }

    /**
     * Get specific enrollment
     */
    public function show($id)
    {
        $enrollment = Enrollment::with(['course.instructor', 'payment'])
            ->where('learner_id', auth()->id())
            ->findOrFail($id);

        return response()->json($enrollment);
    }

    /**
     * Enroll in course with payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'payment_method' => 'required|in:free,credit_card,debit_card,paypal,stripe',
            'payment_intent_id' => 'nullable|string',
            'coupon_code' => 'nullable|string',
        ]);

        $user = auth()->user();
        $course = Course::findOrFail($validated['course_id']);

        // Check if already enrolled
        $existingEnrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'message' => 'Already enrolled in this course',
                'enrollment' => $existingEnrollment
            ], 400);
        }

        // Calculate final price with coupon
        $finalPrice = $course->price;
        $coupon = null;
        
        if (!empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', $validated['coupon_code'])
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->where(function($q) {
                    $q->whereNull('starts_at')
                      ->orWhere('starts_at', '<=', now());
                })
                ->where(function($q) {
                    $q->whereNull('max_uses')
                      ->orWhereRaw('uses_count < max_uses');
                })
                ->first();

            if ($coupon) {
                if ($coupon->discount_type === 'percentage') {
                    $finalPrice = $course->price - ($course->price * $coupon->discount_value / 100);
                } else {
                    $finalPrice = max(0, $course->price - $coupon->discount_value);
                }
            }
        }

        DB::beginTransaction();
        try {
            // Create enrollment
            $enrollment = Enrollment::create([
                'learner_id' => $user->id,
                'course_id' => $course->id,
                'status' => 'active',
                'progress' => 0,
                'enrolled_at' => now(),
            ]);

            // Create payment record
            $payment = null;
            if ($finalPrice > 0) {
                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'amount' => $finalPrice,
                    'currency' => 'USD',
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'completed',
                    'transaction_id' => 'txn_' . uniqid(),
                    'payment_intent_id' => $validated['payment_intent_id'] ?? null,
                    'paid_at' => now(),
                ]);
            } else {
                // Free course or 100% discount
                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'amount' => 0,
                    'currency' => 'USD',
                    'payment_method' => 'free',
                    'payment_status' => 'completed',
                    'paid_at' => now(),
                ]);
            }

            // Update coupon usage
            if ($coupon) {
                $coupon->increment('uses_count');
            }

            // Update course student count
            $course->increment('student_count');

            DB::commit();

            return response()->json([
                'message' => 'Successfully enrolled in course',
                'enrollment' => $enrollment->load('course'),
                'payment' => $payment,
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'price' => $finalPrice,
                    'thumbnail' => $course->thumbnail,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Enrollment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update enrollment progress
     */
    public function updateProgress(Request $request, $id)
    {
        $validated = $request->validate([
            'progress' => 'required|numeric|min:0|max:100',
        ]);

        $enrollment = Enrollment::where('learner_id', auth()->id())->findOrFail($id);
        
        $enrollment->update([
            'progress' => $validated['progress'],
            'last_accessed' => now(),
            'completed_at' => $validated['progress'] >= 100 ? now() : null,
            'status' => $validated['progress'] >= 100 ? 'completed' : 'active',
        ]);

        return response()->json([
            'message' => 'Progress updated successfully',
            'enrollment' => $enrollment
        ]);
    }

    /**
     * Drop/cancel enrollment
     */
    public function destroy(Request $request, $id)
    {
        $enrollment = Enrollment::where('learner_id', auth()->id())->findOrFail($id);
        
        $enrollment->update(['status' => 'dropped']);

        return response()->json([
            'message' => 'Enrollment dropped successfully'
        ]);
    }

    /**
     * Check enrollment status for a specific course
     */
    public function checkEnrollmentStatus($courseId)
    {
        $enrollment = Enrollment::with('course')
            ->where('learner_id', auth()->id())
            ->where('course_id', $courseId)
            ->first();

        return response()->json([
            'isEnrolled' => $enrollment !== null,
            'enrollment' => $enrollment
        ]);
    }
}
