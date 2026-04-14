<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/farm-manager/requests/new', \App\Livewire\FarmManager\NewRequestPage::class)->name('farm-manager.requests.new');
