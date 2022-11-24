<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/users/token', [UserController::class, 'getToken']);
Route::post('/forgot-password', [UserController::class, 'forgotPassword'])->middleware('guest')->name('password.email');
Route::post('/reset-password',[UserController::class, 'resetPassword'])->middleware('guest')->name('password.update');
Route::post('/register', [UserController::class, 'register'])->name('register.api');
Route::post('/login', [UserController::class, 'login'])->name('login.api');
Route::post('/usuarios', [Auth\UsuarioController::class,'store'])->name('usuarios.store');

Route::group(['middleware' => ['auth:api']], function (){
    // User
    Route::group(["prefix" => "users"],function(){
        Route::get('current/session',  [UserController::class, 'getSession'])->name('session.show');
    });

    // Usuario
    Route::group(["prefix" => "usuarios"],function(){
        Route::get('/', [Auth\UsuarioController::class,'index'])->name('usuarios.index');
        Route::get('/{id}', [Auth\UsuarioController::class,'show'])->name('usuarios.show');
        Route::put('/{id}', [Auth\UsuarioController::class,'update'])->name('usuarios.update');
        Route::delete('/{id}', [Auth\UsuarioController::class,'destroy'])->name('usuarios.delete');
    });
});
