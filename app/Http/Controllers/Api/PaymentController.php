<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CreatePaymentJob;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Request $request)
    {
        $data = $request->all();
        $lineItems = [];

        if (count($data['products']) == 0) return false;

        foreach ($data['products'] as $product) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $product['name'],
                        'images' => [$product['images']]
                    ],
                    'unit_amount' => $product['price'], // Amount in cents for card payment
                ],
                'quantity' => $product['quantity'],
            ];
        }

        $session = Session::create([
            'payment_method_types' => $data['payment_method_types'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => 'https://www.google.com/', // Redirect after successful payment
            'cancel_url' => 'https://www.google.com/',
        ]);

        $dataCreatePayment = [
            'cs_id' => $session->id,
            'total' => $session->amount_total,
            'status' => 0
        ];

        CreatePaymentJob::dispatch($dataCreatePayment);

        return response()->json(['data' => $session]);
    }

    public function retrieveCheckoutSession(Request $request)
    {
        $data = $request->all();
        $session = Session::retrieve($data['cs_id']);

        return response()->json(['session' => $session]);
    }

    // Payment intent
    public function getPaymentIntent(Request $request)
    {
        $data = $request->all();
        $paymentIntent = PaymentIntent::retrieve($data['pi_id']);

        return response()->json(['payment_intent' => $paymentIntent]);
    }

    public function StripeWebHook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        //Kiểm tra xác thực webhook bằng chữ ký
        $secret = '';
        $headerSignature = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $headerSignature,
                $secret
            );

            if($event->type == 'payment_intent.succeeded') {

            }
        } catch () {
        }
    }
}
