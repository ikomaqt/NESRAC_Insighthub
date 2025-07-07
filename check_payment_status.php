<?php
include("config.php");
require 'vendor/autoload.php';

use GuzzleHttp\Client;

// Replace with the payment ID you want to check
$paymentId = ''; // Fetch this from your database or another source
if (empty($paymentId)) {
    die('Error: Payment ID is not set.');
}

$client = new Client();

try {
    $response = $client->get('https://api.paymongo.com/v1/payments/' . $paymentId, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode('sk_test_JtXKpkk3PpZ9zt6dKMQwie7a:'),
        ],
    ]);

    $paymentData = json_decode($response->getBody(), true);

    if ($paymentData['data']['attributes']['status'] === 'paid') {
        // Update payment status in the database
        $stmt = $conn->prepare("UPDATE payments SET payment_status = 'paid' WHERE payment_id = ?");
        $stmt->bind_param("s", $paymentId);
        $stmt->execute();
        $stmt->close();
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>