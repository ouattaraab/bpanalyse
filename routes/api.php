<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TranscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- Ingestion (Epic 1) ---
Route::post('/documents', [DocumentController::class, 'store']);
Route::get('/documents/{document}', [DocumentController::class, 'show']);

// --- Session + Chat RAG (Epic 2) ---
Route::post('/documents/{document}/sessions', [SessionController::class, 'store']);
Route::post('/sessions/{session}/chat', [ChatController::class, 'ask']);
Route::post('/sessions/{session}/transcribe', [TranscriptionController::class, 'store']);

// --- Présentation express (Epic 3) ---
Route::post('/sessions/{session}/presentations', [PresentationController::class, 'store']);
Route::get('/presentations/{presentation}', [PresentationController::class, 'show']);
