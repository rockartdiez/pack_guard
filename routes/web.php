<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PackingLogController;

Route::get('/', [PackingLogController::class, 'dashboard'])->name('dashboard');
Route::get('/api/logs', [PackingLogController::class, 'index'])->name('api.logs.index');
Route::post('/api/logs/upload', [PackingLogController::class, 'store'])->name('api.logs.store');
Route::delete('/api/logs/{id}', [PackingLogController::class, 'destroy'])->name('api.logs.destroy');
Route::post('/api/logs/cleanup', [PackingLogController::class, 'cleanup'])->name('api.logs.cleanup');
