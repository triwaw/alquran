<?php

namespace App\Providers;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

// Debug: Check if this file is being loaded
\Illuminate\Support\Facades\Log::info('API routes file loaded');



use App\Http\Controllers\SurahController;
use App\Http\Controllers\VerseController;
use App\Http\Controllers\TranslationController;

// Test route to confirm API works
Route::get('/hello', function () {
    return ['message' => 'API is working'];
});

// Surah routes
Route::get('/surahs', [SurahController::class, 'index']);
Route::get('/surah/{id}', [SurahController::class, 'show']);

// Verse routes
Route::get('/verse/{surah}/{verse}', [VerseController::class, 'show']);
Route::get('/search', [VerseController::class, 'search']);

// Translation routes
Route::get('/translations', [TranslationController::class, 'index']);
Route::get('/translation/{surah}/{verse}', [TranslationController::class, 'show']);


