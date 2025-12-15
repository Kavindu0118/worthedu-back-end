<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Course;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Validate coupon code
     */
    public function validate(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'coupon_code' => 'required|string',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        
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

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired coupon code'
            ]);
        }

        $discountAmount = 0;
        $finalPrice = $course->price;

        if ($coupon->discount_type === 'percentage') {
            $discountAmount = ($course->price * $coupon->discount_value) / 100;
            $finalPrice = $course->price - $discountAmount;
        } else {
            $discountAmount = min($coupon->discount_value, $course->price);
            $finalPrice = max(0, $course->price - $discountAmount);
        }

        return response()->json([
            'valid' => true,
            'coupon' => [
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'description' => $coupon->description,
            ],
            'original_price' => (float) $course->price,
            'discount_amount' => (float) $discountAmount,
            'final_price' => (float) $finalPrice,
        ]);
    }

    /**
     * Apply coupon (same as validate)
     */
    public function apply(Request $request)
    {
        return $this->validate($request);
    }
}
