<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::post('/chat/upload', [ChatController::class, 'uploadFile'])->name('chat.upload');
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::get('/chat/new', [ChatController::class, 'newChat'])->name('chat.new');
Route::post('/chat/send', [ChatController::class, 'sendMessage']);

Route::get('/', function () {
    return view('welcome');
});


