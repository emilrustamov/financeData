<?php

use App\Livewire\CashComponent;
use App\Livewire\UserComponent;
use App\Livewire\ObjectForm;
use App\Livewire\ProjectForm;
use App\Livewire\TransferForm;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::middleware('auth')->get('/', function () {
    return view('home'); 
})->name('home');

Auth::routes();

Route::middleware('auth')->get('/home', function () {
    return view('home'); 
})->name('home');

Route::get('/register', function () {
    return redirect('/login');
})->name('register');

Route::middleware(['auth', 'admin'])->get('/users', UserComponent::class)->name('users.index');
Route::middleware(['auth', 'admin'])->get('/cash', CashComponent::class)->name('cash.index');
Route::middleware(['auth', 'can:view objects'])->get('/object-form', ObjectForm::class)->name('object.form');
Route::middleware(['auth', 'can:view projects'])->get('/project-form', ProjectForm::class)->name('project.form');
Route::middleware(['auth', 'can:view analytics'])->get('/dashboard', Dashboard::class)->name('dashboard.index');
Route::middleware(['auth', 'can:view transfers'])->get('/transfer-form', TransferForm::class)->name('transfer.form');
