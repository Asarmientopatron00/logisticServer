<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\Terrestre;

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
});
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

// Usuario
Route::group(["prefix" => "clientes"],function(){
    Route::get('/', [ClienteController::class,'index'])->name('clientes.index');
    Route::post('/', [ClienteController::class,'store'])->name('clientes.store');
    Route::get('/{id}', [ClienteController::class,'show'])->name('clientes.show');
    Route::put('/{id}', [ClienteController::class,'update'])->name('clientes.update');
    Route::delete('/{id}', [ClienteController::class,'destroy'])->name('clientes.delete');
});

// ------------------  LOGISTICA TERRESTRE ------------------

// Bodegas
Route::group(["prefix" => "bodegas"],function(){
    Route::get('/', [Terrestre\BodegaController::class,'index'])->name('bodegas.index');
    Route::post('/', [Terrestre\BodegaController::class,'store'])->name('bodegas.store');
    Route::get('/{id}', [Terrestre\BodegaController::class,'show'])->name('bodegas.show');
    Route::put('/{id}', [Terrestre\BodegaController::class,'update'])->name('bodegas.update');
    Route::delete('/{id}', [Terrestre\BodegaController::class,'destroy'])->name('bodegas.delete');
});

// Tipos Productos
Route::group(["prefix" => "tipos-productos-terrestres"],function(){
    Route::get('/', [Terrestre\TipoProductoTerrestreController::class,'index'])->name('tipos-productos-terrestres.index');
    Route::post('/', [Terrestre\TipoProductoTerrestreController::class,'store'])->name('tipos-productos-terrestres.store');
    Route::get('/{id}', [Terrestre\TipoProductoTerrestreController::class,'show'])->name('tipos-productos-terrestres.show');
    Route::put('/{id}', [Terrestre\TipoProductoTerrestreController::class,'update'])->name('tipos-productos-terrestres.update');
    Route::delete('/{id}', [Terrestre\TipoProductoTerrestreController::class,'destroy'])->name('tipos-productos-terrestres.delete');
});

// Vehiculos
Route::group(["prefix" => "vehiculos"],function(){
    Route::get('/', [Terrestre\VehiculoController::class,'index'])->name('vehiculos.index');
    Route::post('/', [Terrestre\VehiculoController::class,'store'])->name('vehiculos.store');
    Route::get('/{id}', [Terrestre\VehiculoController::class,'show'])->name('vehiculos.show');
    Route::put('/{id}', [Terrestre\VehiculoController::class,'update'])->name('vehiculos.update');
    Route::delete('/{id}', [Terrestre\VehiculoController::class,'destroy'])->name('vehiculos.delete');
});

// Pedidos Terrestres
Route::group(["prefix" => "pedidos-terrestres"],function(){
    Route::get('/', [Terrestre\PedidoTerrestreController::class,'index'])->name('pedidos-terrestres.index');
    Route::post('/', [Terrestre\PedidoTerrestreController::class,'store'])->name('pedidos-terrestres.store');
    Route::get('/{id}', [Terrestre\PedidoTerrestreController::class,'show'])->name('pedidos-terrestres.show');
    Route::put('/{id}', [Terrestre\PedidoTerrestreController::class,'update'])->name('pedidos-terrestres.update');
    Route::delete('/{id}', [Terrestre\PedidoTerrestreController::class,'destroy'])->name('pedidos-terrestres.delete');
});
