<?php

use App\Livewire\CashComponent;
use App\Livewire\UserComponent;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

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


