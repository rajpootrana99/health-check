<?php

use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailLogController;
use App\Http\Controllers\Admin\JobLogController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Root → dashboard
Route::get('/', fn () => redirect()->route('admin.dashboard'));

// ---------------------------------------------------------------------------
// Webhooks — CSRF exempt (verified via svix signature)
// ---------------------------------------------------------------------------
Route::post('/webhooks/resend', [WebhookController::class, 'resend'])
    ->name('webhooks.resend')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// ---------------------------------------------------------------------------
// Admin panel — auth middleware added here when needed
// ---------------------------------------------------------------------------
Route::prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Campaigns
    Route::resource('campaigns', CampaignController::class)
        ->only(['index', 'create', 'store', 'show']);

    // Businesses
    Route::resource('businesses', BusinessController::class)
        ->only(['index', 'show']);

    Route::post('businesses/{business}/rescrape', [BusinessController::class, 'rescrape'])
        ->name('businesses.rescrape');

    Route::post('businesses/{business}/reaudit', [BusinessController::class, 'reaudit'])
        ->name('businesses.reaudit');

    // Reports
    Route::resource('reports', ReportController::class)
        ->only(['index', 'show']);

    Route::get('reports/{report}/download', [ReportController::class, 'download'])
        ->name('reports.download');

    Route::post('reports/{report}/regenerate', [ReportController::class, 'regenerate'])
        ->name('reports.regenerate');

    Route::post('reports/{report}/generate-email', [ReportController::class, 'generateEmail'])
        ->name('reports.generate-email');

    // Email logs
    Route::get('email-logs', [EmailLogController::class, 'index'])
        ->name('email-logs.index');

    Route::post('email-logs/{emailLog}/send', [EmailLogController::class, 'send'])
        ->name('email-logs.send');

    Route::get('email-logs/{emailLog}/preview', [EmailLogController::class, 'preview'])
        ->name('email-logs.preview');

    // Queue monitor
    Route::get('queue-monitor', [JobLogController::class, 'index'])
        ->name('queue-monitor.index');

});
