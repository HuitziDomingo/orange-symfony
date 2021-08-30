<?php

namespace App\Service;

use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentService
{
    private $secretKey;

    public function __construct()
    {
        $this->secretKey = $_ENV['STRIPE_API_SECRET'];
    }

    public function createPaymentIntent($amount, $currency = 'usd')
    {
        Stripe::setApiKey($this->secretKey);

        return PaymentIntent::create([
            'amount' => $amount * 100, // amount must be in smallest currency (cents)
            'currency' => $currency,
            'metadata' => ['integration_check' => 'accept_a_payment'],
        ]);
    }
}
