<?php

use Illuminate\Support\Facades\Route;

Route::middleware('external.api')->group(function () {
    require __DIR__.'/external-api.php';
});
