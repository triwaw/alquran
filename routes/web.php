<?php

use App\Http\Controllers\QuranController;
use Illuminate\Support\Facades\Route;

Route::get('/test-web', function() {
    return 'Web routes are working';
});


Route::get('/', function () {
    return view('welcome');
});



// Route::get('/quran', [QuranController::class, 'index'])->name('quran.index');
// Route::get('/quran/{surah}', [QuranController::class, 'show'])->name('quran.show');

Route::get('/quran', [QuranController::class, 'index'])->name('quran.index');
Route::get('/quran/show', [QuranController::class, 'show'])->name('quran.show');

