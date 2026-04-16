<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::middleware('role:farm_manager')->group(function () {
        Route::get('/farm-manager/requests/new', \App\Livewire\FarmManager\NewRequestPage::class)->name('farm-manager.requests.new');
        Route::get('/farm-manager/requests', \App\Livewire\FarmManager\MyRequestsPage::class)->name('farm-manager.requests.index');
    });

    Route::middleware('role:division_head')->group(function () {
        Route::get('/division-head/inbox', \App\Livewire\DivisionHead\InboxPage::class)->name('division-head.inbox');
        Route::get('/division-head/history', \App\Livewire\HistoryPage::class)->name('division-head.history');
    });

    Route::middleware('role:vp_gen_services')->group(function () {
        Route::get('/vp-gen-services/inbox', \App\Livewire\VPGenServices\InboxPage::class)->name('vp-gen-services.inbox');
        Route::get('/vp-gen-services/change-requests', \App\Livewire\VPGenServices\ChangeRequestsPage::class)->name('vp-gen-services.change-requests');
        Route::get('/vp-gen-services/history', \App\Livewire\HistoryPage::class)->name('vp-gen-services.history');
    });

    Route::middleware('role:dh_gen_services')->group(function () {
        Route::get('/dh-gen-services/late-filings', \App\Livewire\DHGenServices\LateFilingsPage::class)->name('dh-gen-services.late-filings');
        Route::get('/dh-gen-services/noting', \App\Livewire\DHGenServices\NotingPage::class)->name('dh-gen-services.noting');
        Route::get('/dh-gen-services/history', \App\Livewire\HistoryPage::class)->name('dh-gen-services.history');
        Route::get('/dh-gen-services/change-request', \App\Livewire\DHGenServices\SettingsChangeRequestPage::class)->name('dh-gen-services.change-request');
    });

    Route::middleware('role:ed_manager')->group(function () {
        Route::get('/ed-manager/inbox', \App\Livewire\EDManager\InboxPage::class)->name('ed-manager.inbox');
        Route::get('/ed-manager/history', \App\Livewire\HistoryPage::class)->name('ed-manager.history');
        Route::get('/ed-manager/change-request', \App\Livewire\EDManager\SettingsChangeRequestPage::class)->name('ed-manager.change-request');
    });

    Route::middleware('role:it_admin')->group(function () {
        Route::get('/it-admin/all-requests', \App\Livewire\ITAdmin\AllRequestsPage::class)->name('it-admin.all-requests');
        Route::get('/it-admin/users', \App\Livewire\ITAdmin\UsersPage::class)->name('it-admin.users');
        Route::get('/it-admin/audit', \App\Livewire\ITAdmin\AuditTrailPage::class)->name('it-admin.audit');
        Route::get('/it-admin/override', \App\Livewire\ITAdmin\StatusOverridePage::class)->name('it-admin.override');
        Route::get('/it-admin/pending-changes', \App\Livewire\ITAdmin\PendingChangesPage::class)->name('it-admin.pending-changes');
        Route::get('/it-admin/settings', \App\Livewire\ITAdmin\SettingsPage::class)->name('it-admin.settings');
    });

    Route::middleware('role:guest')->group(function () {
        Route::get('/guest/finished-requests', \App\Livewire\Guest\FinishedRequestsPage::class)->name('guest.finished-requests');
    });
});
