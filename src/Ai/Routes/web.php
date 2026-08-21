<?php

use Illuminate\Support\Facades\Route;
use Nreljed\DashboardCore\Ai\Controllers\AiProviderController;

// AI Provider Keys (BYOK) — self-contained routes for the AI feature.
Route::get('/ai-keys', [AiProviderController::class, 'index'])->name('ai-keys.index');
Route::post('/ai-keys', [AiProviderController::class, 'store'])->name('ai-keys.store');
Route::put('/ai-keys/{aiProvider}', [AiProviderController::class, 'update'])->name('ai-keys.update');
Route::delete('/ai-keys/{aiProvider}', [AiProviderController::class, 'destroy'])->name('ai-keys.destroy');
Route::post('/ai-keys/test', [AiProviderController::class, 'test'])->name('ai-keys.test');
