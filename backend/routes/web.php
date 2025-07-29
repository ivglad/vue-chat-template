<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactRequestController;

Route::get('/', function () {
    return view('index');
});

Route::post('/contact-request', [ContactRequestController::class, 'store'])->name('contact.store');
