<?php
session_start();
include("config.php");
require 'vendor/autoload.php';

use GuzzleHttp\Client;

// Ensure that the user ID is set in the session
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    die('Error: User ID is not set in the session.');
}

// Get the user ID from the session
$user_id = $_SESSION['userid']; // Ensure session key matches

// Payment details
$total_cost = 500 * 100; // PHP 500 converted to cents
$payment_method = 'gcash'; // Specify payment method

// Create Guzzle client
$client = new Client();

try {
    // Create payment link
    $response = $client->post('https://api.paymongo.com/v1/links', [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode('sk_test_JtXKpkk3PpZ9zt6dKMQwie7a:'),
            'Content-Type'  => 'application/json',
        ],
        'json' => [
            'data' => [
                'attributes' => [
                    'amount' => $total_cost,
                    'description' => 'Checkout Payment', // Required field
                    'payment_method' => $payment_method,
                ],
            ],
        ],
    ]);

    $responseBody = json_decode($response->getBody(), true);

    if (isset($responseBody['data']['attributes']['checkout_url'])) {
        $paymentLink = $responseBody['data']['attributes']['checkout_url'];
        $paymentId = $responseBody['data']['id'];

        // Save payment details to the database
        $stmt = $conn->prepare("INSERT INTO payments (user_id, payment_id, amount, payment_method, payment_status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("isis", $user_id, $paymentId, $total_cost, $payment_method);
        $stmt->execute();
        $stmt->close();

        // Redirect to the PayMongo payment link
        header('Location: ' . $paymentLink);
        exit();
    } else {
        throw new Exception('Payment link not found in response.');
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>