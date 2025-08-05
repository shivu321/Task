<?php

namespace App\Helpers;

use Stripe\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeHelper
{
    protected static $stripeInstance;

    public static function getStripeInstance()
    {
        if (!self::$stripeInstance) {
            $key = config('services.stripe.secret');
            self::$stripeInstance = new StripeClient($key);
        }

        return self::$stripeInstance;
    }

    public static function getPaymentIntent($paymentIntentId)
    {
        try {
            $paymentIntent = self::getStripeInstance()->paymentIntents->retrieve($paymentIntentId);
            Log::info('Payment Intent Log', ['paymentIntent' => $paymentIntent]);
            return $paymentIntent;
        } catch (\Exception $e) {
            Log::error('Error retrieving payment intent: ' . $e->getMessage());
            return null;
        }
    }

    public static function getPaymentCharges($chargeIntentId)
    {
        try {
            $charges = self::getStripeInstance()->charges->retrieve($chargeIntentId);
            Log::info('Charges Log', ['charges' => $charges]);
            return $charges;
        } catch (\Exception $e) {
            Log::error('Error retrieving charge: ' . $e->getMessage());
            return null;
        }
    }

    public static function initiateRefund($chargeIntentId)
    {
        try {
            $refund = self::getStripeInstance()->refunds->create(['charge' => $chargeIntentId]);
            Log::info('Refund Log', ['refund' => $refund]);
            return $refund;
        } catch (\Exception $e) {
            Log::error('Error initiating refund: ' . $e->getMessage());
            return null; // Or handle as needed
        }
    }

    public  static function handleWebhook($request)
    {
        $endpoint_secret = config('services.stripe.webhook_secret');
        $payload = @file_get_contents('php://input');
        $sig_header = $request->header('Stripe-Signature');
        $event = null;

        try {

            $event = Event::constructFrom(json_decode($payload, true));
        } catch (\UnexpectedValueException $e) {
            Log::error('Error handling webhook: ' . $e->getMessage() . " " . $e->getLine() . " " . $e->getFile());
            return response('Webhook error while parsing basic request.', 400);
        }

        if ($endpoint_secret) {
            try {
                $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                return response('Webhook error while validating signature: ' . $e->getMessage(), 400);
            }
        }

        switch ($event->type) {
            case 'payment_intent.created':
                 $paymentIntent = $event->data->object;
                Log::info("payment_intent.created", (array) $paymentIntent);
                return $paymentIntent;
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                Log::info("Payment Intent payment_intent.succeeded", (array) $paymentIntent);
                return $paymentIntent;
            case 'charge.succeeded':
                $paymentMethod = $event->data->object;
                Log::info("charge.succeeded", (array) $paymentMethod);
                return $paymentMethod;
            case 'refund.created':
                $refund = $event->data->object;
                Log::info("refund.created", (array) $refund);
                return $refund;
            case 'charge.refunded':
                $refund = $event->data->object;
                Log::info("charge.refunded", (array) $refund);
                return $refund;
            default:
                Log::warning('Received unknown event type', ['event' => $event->type]);
        }

        return response('Webhook handled', 200);
    }
}

