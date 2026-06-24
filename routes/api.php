<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- Ingestion (Epic 1) ---
Route::post('/documents', [DocumentController::class, 'store']);

// --- Session + Chat RAG (Epic 2) ---
Route::post('/documents/{document}/sessions', [SessionController::class, 'store']);
Route::post('/sessions/{session}/chat', [ChatController::class, 'ask']);
