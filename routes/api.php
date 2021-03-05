<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ExchangeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get(
    'exchange-rate',
    [ExchangeController::class, 'getExchangeRateJSON']
)->name('exchange-rate');

Route::get(
    'exchange-rates',
    [ExchangeController::class, 'getExchangeRatesJSON']
)->name('exchange-rates');
