<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/login-redirect', '/login')
    ->name('login');
