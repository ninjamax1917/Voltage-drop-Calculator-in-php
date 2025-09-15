<?php

use App\Http\Controllers\VoltageDropController;

Route::get('/voltage-drop', [VoltageDropController::class, 'showForm']);
Route::post('/voltage-drop', [VoltageDropController::class, 'calculate']);
