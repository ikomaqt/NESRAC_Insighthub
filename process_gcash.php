<?php
session_start();
require 'vendor/autoload.php'; // Load Composer's autoloader

use GuzzleHttp\Client;

if (!isset($_POST['total_cost']) || !is_numeric($_POST['total_cost'])) {
    die('Invalid total cost.');
}

$total_cost = (int)$_POST['total_cost'] * 100; // Convert to cents

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
                    'amount' => $total_cost, // Amount in cents
                    'currency' => 'PHP',
                    'description' => 'Checkout Payment',
                    'payment_method' => 'gcash', // Specify payment method
                ],
            ],
        ],
    ]);

    $responseBody = json_decode($response->getBody(), true);

    // Debugging: print the response body
    echo '<pre>' . print_r($responseBody, true) . '</pre>';

    // Access the checkout URL from the response
    if (isset($responseBody['data']['attributes']['checkout_url'])) {
        $paymentLink = $responseBody['data']['attributes']['checkout_url'];
        // Redirect to the PayMongo payment link
        header('Location: ' . $paymentLink);
        exit();
    } else {
        throw new Exception('Payment link not found in response.');
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
