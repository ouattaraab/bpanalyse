<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- Ingestion (Epic 1) ---
Route::post('/documents', [DocumentController::class, 'store']);
