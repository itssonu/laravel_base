<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*
|--------------------------------------------------------------------------
| Controllers path  
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Generalcontroller;

Route::get('/', function () {
    return view('welcome');
});

Route::get('changePassword/{token?}', [Generalcontroller::class, 'changePassword'])->name('change.password.get');
