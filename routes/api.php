<?php

use App\Http\Controllers\Api\TicketApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth.basic', 'throttle:60,1'])->group(function () {
    Route::get('/tickets', [TicketApiController::class, 'index'])->name('api.tickets.index');
    Route::post('/tickets', [TicketApiController::class, 'store'])->name('api.tickets.store');
    Route::get('/tickets/{ticket}', [TicketApiController::class, 'show'])->name('api.tickets.show');
    Route::post('/tickets/{ticket}/reply', [TicketApiController::class, 'reply'])->name('api.tickets.reply');
    Route::post('/tickets/{ticket}/resolve', [TicketApiController::class, 'resolve'])->name('api.tickets.resolve');
});
