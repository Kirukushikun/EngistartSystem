<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/farm-manager/requests/new', \App\Livewire\FarmManager\NewRequestPage::class)->name('farm-manager.requests.new');
Route::get('/farm-manager/requests', \App\Livewire\FarmManager\MyRequestsPage::class)->name('farm-manager.requests.index');

Route::get('/division-head/inbox', \App\Livewire\DivisionHead\InboxPage::class)->name('division-head.inbox');

Route::get('/vp-gen-services/inbox', \App\Livewire\VPGenServices\InboxPage::class)->name('vp-gen-services.inbox');
Route::get('/vp-gen-services/change-requests', \App\Livewire\VPGenServices\ChangeRequestsPage::class)->name('vp-gen-services.change-requests');

Route::get('/dh-gen-services/late-filings', \App\Livewire\DHGenServices\LateFilingsPage::class)->name('dh-gen-services.late-filings');
Route::get('/dh-gen-services/noting', \App\Livewire\DHGenServices\NotingPage::class)->name('dh-gen-services.noting');
Route::get('/dh-gen-services/change-request', \App\Livewire\DHGenServices\SettingsChangeRequestPage::class)->name('dh-gen-services.change-request');

Route::get('/ed-manager/inbox', \App\Livewire\EDManager\InboxPage::class)->name('ed-manager.inbox');
Route::get('/ed-manager/change-request', \App\Livewire\EDManager\SettingsChangeRequestPage::class)->name('ed-manager.change-request');

Route::get('/it-admin/all-requests', \App\Livewire\ITAdmin\AllRequestsPage::class)->name('it-admin.all-requests');
Route::get('/it-admin/users', \App\Livewire\ITAdmin\UsersPage::class)->name('it-admin.users');
Route::get('/it-admin/audit', \App\Livewire\ITAdmin\AuditTrailPage::class)->name('it-admin.audit');
Route::get('/it-admin/override', \App\Livewire\ITAdmin\StatusOverridePage::class)->name('it-admin.override');
Route::get('/it-admin/pending-changes', \App\Livewire\ITAdmin\PendingChangesPage::class)->name('it-admin.pending-changes');
Route::get('/it-admin/settings', \App\Livewire\ITAdmin\SettingsPage::class)->name('it-admin.settings');
