<?php

use App\Http\Controllers\LeaseController;
use Illuminate\Support\Facades\Route;

Route::get('/',               [LeaseController::class, 'home'])->name('home');
Route::post('/leases/lookup', [LeaseController::class, 'lookup'])->name('leases.lookup');
Route::get('/leases',         [LeaseController::class, 'index'])->name('leases.index');
Route::post('/leases/redeem', [LeaseController::class, 'redeem'])->name('leases.redeem');
Route::get('/demo/reset',     [LeaseController::class, 'reset'])->name('demo.reset');
