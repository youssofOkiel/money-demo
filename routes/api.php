<?php

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;


Route::get('/transactions', [TransactionController::class, 'index']);
Route::post('/transactions', [TransactionController::class, 'store']);

