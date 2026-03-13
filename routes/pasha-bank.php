<?php

use Illuminate\Support\Facades\Route;
use Sarkhanrasimoghlu\PashaBank\Http\Controllers\ReturnController;

Route::post('/pasha-bank/return', [ReturnController::class, 'handle'])
    ->name('pasha-bank.return');
