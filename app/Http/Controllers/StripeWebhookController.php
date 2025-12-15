<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhook
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            
            // Verify webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
            
            Log::info('Stripe webhook received', [
                'type' => $event->type,
                'id' => $event->id,
            ]);
            
            // Handle different event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;
                    
                case 'payment_intent.canceled':
                    $this->handlePaymentIntentCanceled($event->data->object);
                    break;
                    
                case 'payment_intent.processing':
                    $this->handlePaymentIntentProcessing($event->data->object);
                    break;
                    
                default:
                    Log::info('Unhandled webhook event type: ' . $event->type);
            }
            
            return response()->json(['status' => 'success']);
            
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 400);
            
        } catch (\Exception $e) {
            Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Handle payment_intent.succeeded event
     */
    private function handlePaymentIntentSucceeded($paymentIntent): void
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();
        
        if ($payment && $payment->payment_status === 'pending') {
            $payment->update([
                'payment_status' => 'completed',
                'stripe_payment_method_id' => $paymentIntent->payment_method ?? null,
                'paid_at' => now(),
            ]);
            
            Log::info('Payment succeeded via webhook', [
                'payment_id' => $payment->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100,
            ]);
        }
    }
    
    /**
     * Handle payment_intent.payment_failed event
     */
    private function handlePaymentIntentFailed($paymentIntent): void
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();
        
        if ($payment) {
            $errorMessage = $paymentIntent->last_payment_error->message ?? 'Payment failed';
            
            $payment->update([
                'payment_status' => 'failed',
                'error_message' => $errorMessage,
            ]);
            
            Log::warning('Payment failed via webhook', [
                'payment_id' => $payment->id,
                'payment_intent_id' => $paymentIntent->id,
                'error' => $errorMessage,
            ]);
        }
    }
    
    /**
     * Handle payment_intent.canceled event
     */
    private function handlePaymentIntentCanceled($paymentIntent): void
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();
        
        if ($payment) {
            $payment->update([
                'payment_status' => 'refunded', // Using refunded as canceled status
            ]);
            
            Log::info('Payment canceled via webhook', [
                'payment_id' => $payment->id,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        }
    }
    
    /**
     * Handle payment_intent.processing event
     */
    private function handlePaymentIntentProcessing($paymentIntent): void
    {
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();
        
        if ($payment && $payment->payment_status === 'pending') {
            Log::info('Payment processing via webhook', [
                'payment_id' => $payment->id,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        }
    }
}
