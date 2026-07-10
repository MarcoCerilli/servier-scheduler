<?php

use App\Http\Controllers\PublicScheduleController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home → redirect al form di creazione
Route::get('/', function () {
    return redirect()->route('schedule.create');
})->name('home');

/*
|--------------------------------------------------------------------------
| Creazione Pianificazione (pubblico, no auth richiesto per MVP)
|--------------------------------------------------------------------------
*/
Route::get('/crea', [ScheduleController::class, 'create'])->name('schedule.create');
Route::post('/crea', [ScheduleController::class, 'store'])->name('schedule.store');

// Pagina di successo dopo la creazione
Route::get('/crea/{token}/successo', [ScheduleController::class, 'success'])
    ->name('schedule.success')
    ->where('token', '[a-f0-9]{64}');

// API per anteprima occorrenze dal form Vue (rate limited)
Route::post('/api/schedule/preview', [ScheduleController::class, 'preview'])
    ->name('schedule.preview')
    ->middleware('throttle:60,1');

/*
|--------------------------------------------------------------------------
| Pagine Pubbliche (accessibili con token sicuro)
| Rate limited: 60 req/min per IP
|--------------------------------------------------------------------------
*/
Route::middleware('throttle:60,1')->group(function () {

    // Pagina pubblica di riepilogo
    Route::get('/s/{token}', [PublicScheduleController::class, 'show'])
        ->name('schedule.public')
        ->where('token', '[a-f0-9]{64}');

    // Endpoint file .ics
    Route::get('/s/{token}/calendar.ics', [PublicScheduleController::class, 'ics'])
        ->name('schedule.ics')
        ->where('token', '[a-f0-9]{64}');

    // URL di gestione (futuro: modifica senza login)
    Route::get('/s/{token}/gestione', function (string $token) {
        return Inertia::render('Schedules/ManagePlaceholder', [
            'message' => 'La gestione della pianificazione sarà disponibile in una versione futura.',
        ]);
    })->name('schedule.manage')
      ->where('token', '[a-f0-9]{64}');
});
