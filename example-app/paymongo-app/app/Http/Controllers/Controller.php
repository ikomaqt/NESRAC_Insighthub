<?php

namespace App\Http\Controllers;

abstract class Controller
{
    <?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createPaymentLink(Request $request)
    {
        $total_cost = 500 * 100; // PHP 500 converted to cents

        $client = new Client();

        try {
            $response = $client->post('https://api.paymongo.com/v1/links', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode('sk_test_JtXKpkk3PpZ9zt6dKMQwie7a:'),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'data' => [
                        'attributes' => [
                            'amount' => $total_cost,
                            'currency' => 'PHP',
                            'description' => 'Checkout Payment',
                            'payment_method' => 'gcash', // Specify payment method
                        ],
                    ],
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);

            // Debugging: log the response body
            Log::info('PayMongo Response: ', $responseBody);

            // Access the checkout URL from the response
            if (isset($responseBody['data']['attributes']['checkout_url'])) {
                $paymentLink = $responseBody['data']['attributes']['checkout_url'];
                // Redirect to the PayMongo payment link
                return redirect($paymentLink);
            } else {
                throw new \Exception('Payment link not found in response.');
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Webhook handler method
    public function handleWebhook(Request $request)
    {
        // Validate the incoming request from PayMongo
        $payload = $request->getContent();
        $signature = $request->header('PayMongo-Signature');

        // Verify webhook signature here (optional but recommended)

        // Process the webhook event
        $event = json_decode($payload, true);

        if (isset($event['data']['attributes']['status'])) {
            $status = $event['data']['attributes']['status'];
            // Handle different statuses (e.g., succeeded, failed)
            if ($status === 'paid') {
                // Update your order status in the database
                // Example: Order::where('payment_intent_id', $event['data']['id'])->update(['status' => 'paid']);
            }
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }
}

}
