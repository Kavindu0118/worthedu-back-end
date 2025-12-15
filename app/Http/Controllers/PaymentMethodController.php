<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Get all payment methods for authenticated user
     */
    public function index()
    {
        $methods = PaymentMethod::where('user_id', auth()->id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($methods);
    }

    /**
     * Add new payment method
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:credit_card,debit_card,paypal,stripe',
            'last4' => 'nullable|string',
            'brand' => 'nullable|string',
            'expiry_month' => 'nullable|integer|min:1|max:12',
            'expiry_year' => 'nullable|integer',
            'holder_name' => 'nullable|string',
            'provider_id' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        // If setting as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            PaymentMethod::where('user_id', auth()->id())
                ->update(['is_default' => false]);
        }

        $method = PaymentMethod::create([
            'user_id' => auth()->id(),
            ...$validated
        ]);

        return response()->json([
            'message' => 'Payment method added successfully',
            'payment_method' => $method
        ], 201);
    }

    /**
     * Delete payment method
     */
    public function destroy($id)
    {
        $method = PaymentMethod::where('user_id', auth()->id())->findOrFail($id);
        $method->delete();

        return response()->json([
            'message' => 'Payment method deleted successfully'
        ]);
    }

    /**
     * Set payment method as default
     */
    public function setDefault($id)
    {
        // Unset all defaults first
        PaymentMethod::where('user_id', auth()->id())
            ->update(['is_default' => false]);

        // Set the selected one as default
        $method = PaymentMethod::where('user_id', auth()->id())->findOrFail($id);
        $method->update(['is_default' => true]);

        return response()->json([
            'message' => 'Default payment method updated',
            'payment_method' => $method
        ]);
    }
}
