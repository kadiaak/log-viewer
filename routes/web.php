<?php

use Illuminate\Support\Facades\Route;
use Kadiaak\LogViewer\Http\Controllers\AssetController;
use Kadiaak\LogViewer\Http\Controllers\IndexController;
use Kadiaak\LogViewer\Http\Controllers\LogController;
use Kadiaak\LogViewer\Http\Controllers\LogFileController;

Route::get('/', IndexController::class)->name('log-viewer.index');

Route::get('assets/{file}', AssetController::class)
    ->where('file', '[\w\.\-]+')
    ->name('log-viewer.assets');

Route::prefix('api')->name('log-viewer.api.')->group(function () {
    Route::get('files', [LogFileController::class, 'index'])->name('files.index');
    Route::get('files/{identifier}/download', [LogFileController::class, 'download'])->name('files.download');
    Route::delete('files/{identifier}/clear', [LogFileController::class, 'clear'])->name('files.clear');
    Route::delete('files/{identifier}', [LogFileController::class, 'destroy'])->name('files.destroy');

    Route::get('files/{identifier}/logs', [LogController::class, 'index'])->name('logs.index');
});
