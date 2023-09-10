<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use YooKassa\Client;

class PaymentController extends Controller
{
    private function getClient(): Client
    {
        $client = new Client();
        $client->setAuth(config('services.yookassa.client_id'), config('services.yookassa.client_key'));

        return $client;
    }

    public function createPayment(Request $request)
    {
        $client = $this->getClient();

        $payment = $client->createPayment(
            array(
                'amount' => array(
                    'value' => 100.0,
                    'currency' => 'RUB',
                ),
                'confirmation' => array(
                    'type' => 'redirect',
                    'return_url' => 'https://www.example.com/return_url',
                ),
                'save_payment_method' => true,
                'capture' => false,
                'description' => 'Order No. 1',
            ),
            uniqid('', true)
        );

        return response()->json([
            'payment' => $payment
        ]);
    }

    public function getPayments()
    {
        $client = $this->getClient();

        $paymentId = '2c87f210-000f-5000-9000-1984e62829ef';
        $payment = $client->getPaymentInfo($paymentId);


        return response()->json([
            'payment' => $payment
        ]);
    }

    public function allPayments()
    {
        $client = $this->getClient();

        $payments = $client->getPayments();


        return response()->json([
            'payment' => $payments
        ]);
    }
}
