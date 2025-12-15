<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Course;
use App\Models\Coupon;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Create payment intent
     */
    public function createIntent(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'coupon_code' => 'nullable|string',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $finalPrice = $course->price;

        // Apply coupon if provided
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
                ->first();

            if ($coupon) {
                if ($coupon->discount_type === 'percentage') {
                    $finalPrice = $course->price - ($course->price * $coupon->discount_value / 100);
                } else {
                    $finalPrice = max(0, $course->price - $coupon->discount_value);
                }
            }
        }

        // Create payment intent (mock for now, integrate Stripe later)
        $paymentIntent = [
            'id' => 'pi_' . uniqid(),
            'amount' => $finalPrice,
            'currency' => 'USD',
            'status' => 'requires_payment_method',
            'client_secret' => 'secret_' . uniqid(),
            'created_at' => now()->toISOString(),
        ];

        return response()->json($paymentIntent);
    }

    /**
     * Confirm payment
     */
    public function confirmPayment(Request $request)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
            'payment_method_id' => 'required|string',
        ]);

        // Mock payment confirmation (integrate real payment gateway)
        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed successfully',
            'payment' => [
                'id' => 1,
                'amount' => 49.99,
                'payment_status' => 'completed',
                'paid_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Get payment history
     */
    public function history()
    {
        $payments = Payment::whereHas('enrollment', function($q) {
                $q->where('learner_id', auth()->id());
            })
            ->with('enrollment.course')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($payments);
    }

    /**
     * Get enrollment payment
     */
    public function getEnrollmentPayment($enrollmentId)
    {
        $payment = Payment::whereHas('enrollment', function($q) use ($enrollmentId) {
                $q->where('id', $enrollmentId)
                  ->where('learner_id', auth()->id());
            })
            ->with('enrollment')
            ->firstOrFail();

        return response()->json($payment);
    }

    /**
     * Request refund
     */
    public function requestRefund(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'reason' => 'required|string',
        ]);

        $payment = Payment::where('enrollment_id', $validated['enrollment_id'])
            ->whereHas('enrollment', function($q) {
                $q->where('learner_id', auth()->id());
            })
            ->firstOrFail();

        // Process refund logic here
        $payment->update(['payment_status' => 'refunded']);

        return response()->json([
            'message' => 'Refund request submitted successfully',
            'refund_status' => 'pending'
        ]);
    }

    /**
     * Download receipt
     */
    public function downloadReceipt($paymentId)
    {
        $payment = Payment::whereHas('enrollment', function($q) {
                $q->where('learner_id', auth()->id());
            })
            ->with('enrollment.course')
            ->findOrFail($paymentId);

        // Return payment details as JSON for now
        // In production, generate PDF receipt
        return response()->json([
            'receipt' => [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                'paid_at' => $payment->paid_at,
                'course' => [
                    'title' => $payment->enrollment->course->title,
                    'instructor' => $payment->enrollment->course->instructor->user->name ?? 'N/A',
                ]
            ]
        ]);
    }
}
