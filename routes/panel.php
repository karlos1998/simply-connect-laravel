<?php

use Illuminate\Support\Facades\Route;
use SimplyConnect\Laravel\Http\Controllers\PanelController;

Route::get('/', [PanelController::class, 'index'])->name('simply-connect.index');
Route::post('/sms', [PanelController::class, 'sendSms'])->name('simply-connect.sms');
Route::post('/call-queue', [PanelController::class, 'queueCall'])->name('simply-connect.call-queue');
