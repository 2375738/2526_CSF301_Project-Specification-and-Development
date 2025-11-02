<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\DashboardController;
use App\Http\Controllers\Public\ReportTicketController;
use App\Http\Controllers\Public\TicketAttachmentController;
use App\Http\Controllers\Public\TicketCommentController;
use App\Http\Controllers\Public\TicketStatusController;
use App\Http\Controllers\Public\TicketViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketViewController::class, 'index'])->name('index');

        Route::middleware('role:employee')->group(function () {
            Route::get('/report', [ReportTicketController::class, 'create'])->name('create');
            Route::post('/', [ReportTicketController::class, 'store'])->name('store');
            Route::post('/link', [ReportTicketController::class, 'link'])->name('link');
        });

        Route::get('/{ticket}', [TicketViewController::class, 'show'])
            ->name('show')
            ->middleware('can:view,ticket');

        Route::post('/{ticket}/comments', [TicketCommentController::class, 'store'])
            ->name('comments.store')
            ->middleware('can:comment,ticket');

        Route::post('/{ticket}/attachments', [TicketAttachmentController::class, 'store'])
            ->name('attachments.store')
            ->middleware('can:upload,ticket');

        Route::patch('/{ticket}/status', [TicketStatusController::class, 'update'])
            ->name('status.update');
    });

    Route::get('/attachments/{attachment}', [TicketAttachmentController::class, 'download'])
        ->name('tickets.attachments.download');

    Route::middleware('role:manager,admin')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
