<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChargeController;

Route::post('/charge', ChargeController::class)->name('api.charge');
