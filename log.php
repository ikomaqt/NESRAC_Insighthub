<?php

require 'vendor/autoload.php'; // Load Composer's autoloader

use GuzzleHttp\Client;

// Create Guzzle client
$client = new Client();

$logFile = 'log.txt'; // Path to the log file

// Log the message to a file
function logToFile($message) {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Function to register a webhook
function registerWebhook($client, $webhookUrl) {
    try {
        $response = $client->post('https://api.paymongo.com/v1/webhooks', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('c2tfdGVzdF9KdFhLcGtrM1BwWjl6dDZkS01Rd2llN2E6:'),
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'data' => [
                    'attributes' => [
                        'url' => $webhookUrl,
                        'events' => ['source.chargeable', 'payment.paid', 'payment.failed']
                    ],
                ],
            ],
        ]);

        $responseBody = json_decode($response->getBody(), true);
        logToFile('Webhook registered: ' . print_r($responseBody, true));

        echo 'Webhook registered: ' . $responseBody['data']['id'];
    } catch (Exception $e) {
        logToFile('Error: ' . $e->getMessage());
        echo 'Error: ' . $e->getMessage();
    }
}

// Function to check if webhook exists
function checkWebhookExists($client, $webhookUrl) {
    try {
        $response = $client->get('https://api.paymongo.com/v1/webhooks', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('c2tfdGVzdF9KdFhLcGtrM1BwWjl6dDZkS01Rd2llN2E6:'),
            ],
        ]);

        $responseBody = json_decode($response->getBody(), true);

        foreach ($responseBody['data'] as $webhook) {
            if ($webhook['attributes']['url'] === $webhookUrl) {
                return $webhook['id'];
            }
        }

        return null;
    } catch (Exception $e) {
        logToFile('Error checking webhooks: ' . $e->getMessage());
        return null;
    }
}

// Webhook URL to register
$webhookUrl = 'https://yourdomain.com/webhook.php';

// Check if webhook already exists
$existingWebhookId = checkWebhookExists($client, $webhookUrl);

if ($existingWebhookId) {
    logToFile('Webhook already exists with ID: ' . $existingWebhookId);
    echo 'Webhook already exists with ID: ' . $existingWebhookId;
} else {
    // Register the webhook if it doesn't exist
    registerWebhook($client, $webhookUrl);
}

?>
