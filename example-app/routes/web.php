<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});

// Define the routes outside the closure
Route::get('/create-payment-link', [PaymentController::class, 'createPaymentLink']);
Route::post('/webhook', [PaymentController::class, 'handleWebhook']);




