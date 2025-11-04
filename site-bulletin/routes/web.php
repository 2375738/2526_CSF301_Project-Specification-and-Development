<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\DashboardController;
use App\Http\Controllers\Public\GovernanceController;
use App\Http\Controllers\Public\ReportTicketController;
use App\Http\Controllers\Public\RoleChangeRequestController;
use App\Http\Controllers\Public\TicketAttachmentController;
use App\Http\Controllers\Public\TicketCommentController;
use App\Http\Controllers\Public\TicketStatusController;
use App\Http\Controllers\Public\TicketViewController;
use App\Http\Controllers\Messaging\ConversationController;
use App\Http\Controllers\Messaging\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketViewController::class, 'index'])->name('index');

        Route::middleware('role:employee,manager,ops_manager,hr,admin')->group(function () {
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

    Route::middleware('role:manager,ops_manager,hr,admin')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
        Route::post('/analytics/views', [AnalyticsController::class, 'storeView'])->name('analytics.views.store');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [ConversationController::class, 'index'])->name('index');
        Route::post('/', [ConversationController::class, 'store'])->name('store');
        Route::get('/{conversation}', [ConversationController::class, 'show'])->name('show');
        Route::patch('/{conversation}/lock', [ConversationController::class, 'updateLock'])->name('lock');
        Route::post('/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');
    });

    Route::prefix('governance')->name('governance.')->group(function () {
        Route::get('/', [GovernanceController::class, 'index'])->name('index');
        Route::get('/policies', [GovernanceController::class, 'policies'])->name('policies');
        Route::get('/organisation', [GovernanceController::class, 'organisation'])->name('organisation');
        Route::get('/escalation', [GovernanceController::class, 'escalation'])->name('escalation');
    });

    Route::middleware('role:employee,manager,ops_manager,hr,admin')->group(function () {
        Route::get('/role-requests', [RoleChangeRequestController::class, 'index'])->name('role-requests.index');
        Route::get('/role-requests/new', [RoleChangeRequestController::class, 'create'])->name('role-requests.create');
        Route::post('/role-requests', [RoleChangeRequestController::class, 'store'])->name('role-requests.store');
    });
});

require __DIR__ . '/auth.php';
