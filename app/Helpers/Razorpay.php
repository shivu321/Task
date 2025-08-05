<?php

namespace App\Helpers;

use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;

class Razorpay
{
    protected $api;

    public function __construct()
    {
        $this->api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    /**
     * Create a new Razorpay Order
     */
    public function createOrder($receiptId, $amount, $currency = 'INR')
    {
        $order = $this->api->order->create([
            'receipt' => $receiptId,
            'amount' => $amount * 100, // Amount in paise
            'currency' => $currency,
            'payment_capture' => 1
        ]);

        return $order;
    }

   
    public function verifySignature($data)
    {
        try {
            $attributes = [
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature'  => $data['razorpay_signature']
            ];

            $this->api->utility->verifyPaymentSignature($attributes);

            return true;
        } catch (\Exception $e) {
            Log::error('Razorpay signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch payment details by ID
     */
    public function fetchPayment($paymentId)
    {
        return $this->api->payment->fetch($paymentId);
    }
}
