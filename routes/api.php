<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\DebateController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TranscriptionController;
use App\Http\Controllers\VoiceAnswerController;
use App\Http\Controllers\VoiceModelController;
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
Route::post('/interactions/{interaction}/voice', [VoiceAnswerController::class, 'store']); // réponse en voix clonée (2.3)

// --- Présentation express (Epic 3) ---
Route::post('/sessions/{session}/presentations', [PresentationController::class, 'store']);
Route::get('/presentations/{presentation}', [PresentationController::class, 'show']);

// --- Débat du board (Epic 4) ---
Route::post('/sessions/{session}/debates', [DebateController::class, 'start']);
Route::get('/debates/{debate}', [DebateController::class, 'show']);

// --- Session : épinglage, compte rendu, audit (Epic 5) ---
Route::get('/sessions/{session}/pins', [PinController::class, 'index']);
Route::post('/sessions/{session}/pins', [PinController::class, 'store']);
Route::delete('/pins/{pin}', [PinController::class, 'destroy']);
Route::get('/sessions/{session}/export', [ExportController::class, 'download']);
Route::get('/sessions/{session}/audit', [AuditController::class, 'index']);

// --- Gouvernance voix (Epic 6) ---
Route::post('/tenants/{tenant}/voice-consents', [ConsentController::class, 'store']);
Route::delete('/voice-consents/{consent}', [ConsentController::class, 'destroy']);
Route::post('/voice-consents/{consent}/voice-model', [VoiceModelController::class, 'store']);
Route::delete('/voice-models/{voiceModel}', [VoiceModelController::class, 'destroy']);
