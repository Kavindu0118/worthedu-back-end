<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class StripePaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Check if user is already enrolled in a course
     */
    public function checkEnrollmentStatus(Request $request, $courseId): JsonResponse
    {
        $user = auth()->user();
        
        $isEnrolled = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();
        
        return response()->json([
            'isEnrolled' => $isEnrolled,
        ]);
    }

    /**
     * Create Stripe payment intent
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);
        
        $user = auth()->user();
        $course = Course::findOrFail($validated['course_id']);
        
        // Check if already enrolled
        $existingEnrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();
        
        if ($existingEnrollment) {
            return response()->json([
                'error' => 'You are already enrolled in this course',
            ], 400);
        }
        
        // Check if course is free
        if ($course->price <= 0) {
            return response()->json([
                'error' => 'This course is free. No payment required.',
            ], 400);
        }
        
        try {
            // Convert price to cents
            $amount = (int) ($course->price * 100);
            
            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'usd',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                ],
                'description' => "Enrollment for: {$course->title}",
            ]);
            
            // Store payment record
            $payment = Payment::create([
                'enrollment_id' => null, // Will be set after successful payment
                'amount' => $course->price,
                'currency' => 'usd',
                'payment_method' => 'stripe',
                'payment_status' => 'pending',
                'payment_intent_id' => $paymentIntent->id,
                'metadata' => [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                ],
            ]);
            
            Log::info('Payment intent created', [
                'payment_intent_id' => $paymentIntent->id,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $amount,
            ]);
            
            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => 'usd',
                'publishable_key' => config('services.stripe.publishable'),
            ]);
            
        } catch (ApiErrorException $e) {
            Log::error('Stripe API error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'course_id' => $course->id,
            ]);
            
            return response()->json([
                'error' => 'Failed to create payment intent: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Payment intent creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'course_id' => $course->id,
            ]);
            
            return response()->json([
                'error' => 'An unexpected error occurred. Please try again.',
            ], 500);
        }
    }

    /**
     * Confirm enrollment after successful payment
     */
    public function confirmEnrollment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'payment_intent_id' => 'required|string',
        ]);
        
        $user = auth()->user();
        $course = Course::findOrFail($validated['course_id']);
        
        try {
            // Retrieve payment intent from Stripe
            $paymentIntent = PaymentIntent::retrieve($validated['payment_intent_id']);
            
            // Verify payment succeeded
            if ($paymentIntent->status !== 'succeeded') {
                return response()->json([
                    'success' => false,
                    'error' => 'Payment has not been completed. Status: ' . $paymentIntent->status,
                ], 400);
            }
            
            // Find payment record
            $payment = Payment::where('payment_intent_id', $validated['payment_intent_id'])->first();
            
            if (!$payment) {
                Log::warning('Payment record not found', [
                    'payment_intent_id' => $validated['payment_intent_id'],
                    'user_id' => $user->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Payment record not found',
                ], 404);
            }
            
            // Check if enrollment already exists
            $existingEnrollment = Enrollment::where('learner_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
            
            if ($existingEnrollment) {
                // Update payment with enrollment ID
                if (!$payment->enrollment_id) {
                    $payment->update([
                        'enrollment_id' => $existingEnrollment->id,
                        'payment_status' => 'completed',
                        'paid_at' => now(),
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Already enrolled',
                    'enrollment' => $existingEnrollment,
                ]);
            }
            
            // Create enrollment and update payment in transaction
            DB::beginTransaction();
            try {
                // Create enrollment
                $enrollment = Enrollment::create([
                    'learner_id' => $user->id,
                    'course_id' => $course->id,
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'progress' => 0,
                ]);
                
                // Update payment record
                $payment->update([
                    'enrollment_id' => $enrollment->id,
                    'payment_status' => 'completed',
                    'stripe_payment_method_id' => $paymentIntent->payment_method ?? null,
                    'paid_at' => now(),
                ]);
                
                // Increment course student count
                $course->increment('student_count');
                
                DB::commit();
                
                Log::info('Enrollment confirmed', [
                    'enrollment_id' => $enrollment->id,
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'payment_intent_id' => $validated['payment_intent_id'],
                ]);
                
                // Load relationships
                $enrollment->load('course');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Enrollment successful',
                    'enrollment' => $enrollment,
                    'payment' => $payment,
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (ApiErrorException $e) {
            Log::error('Stripe API error during confirmation', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $validated['payment_intent_id'],
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to verify payment: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Enrollment confirmation failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $validated['payment_intent_id'],
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to confirm enrollment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request, $paymentIntentId): JsonResponse
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            return response()->json([
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
            ]);
            
        } catch (ApiErrorException $e) {
            return response()->json([
                'error' => 'Failed to retrieve payment status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
