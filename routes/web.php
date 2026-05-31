<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PackingLogController;

Route::get('/', [PackingLogController::class, 'dashboard'])->name('dashboard');
Route::get('/api/logs', [PackingLogController::class, 'index'])->name('api.logs.index');
Route::get('/api/stats', [PackingLogController::class, 'stats'])->name('api.logs.stats');
Route::get('/api/logs/export', [PackingLogController::class, 'export'])->name('api.logs.export');
Route::get('/api/logs/check/{orderId}', [PackingLogController::class, 'checkOrderExists'])->name('api.logs.check');
Route::post('/api/logs/upload', [PackingLogController::class, 'store'])->name('api.logs.store');
Route::delete('/api/logs/{id}', [PackingLogController::class, 'destroy'])->name('api.logs.destroy');
Route::post('/api/logs/cleanup', [PackingLogController::class, 'cleanup'])->name('api.logs.cleanup');
