<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::resource('clientes', App\Http\Controllers\ClienteController::class)->except('create', 'edit');

Route::resource('faturas', App\Http\Controllers\FaturaController::class)->except('create', 'edit', 'update');
