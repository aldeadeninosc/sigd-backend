<?php

use App\Http\Controllers\DocumentsModelController;
use App\Http\Controllers\FolderModelController;
use App\Http\Controllers\OcrFileModelController;
use App\Http\Controllers\SubFolderModelController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\JwtMiddleware;

Route::group([
    'prefix' => 'auth'
], function () {

    Route::post('/register', [AuthController::class, 'register'])->name('register')->middleware(JwtMiddleware::class);
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware(JwtMiddleware::class);
    Route::get('/getAll', [AuthController::class, 'getUsers'])->name('getAll')->middleware(JwtMiddleware::class);
    Route::get('/getCurrentUser', [AuthController::class, 'getCurrentUser'])->name('getCurrentUser')->middleware(JwtMiddleware::class);
    Route::post('/updateUser/{id}', [AuthController::class, 'updateUser'])->name('updateUser')->middleware(JwtMiddleware::class);
    Route::delete('/deleteUser/{id}', [AuthController::class, 'deleteUser'])->name('deleteUser')->middleware(JwtMiddleware::class);


});

Route::group([ 'middleware' => JwtMiddleware::class], function () {
    Route::apiResource('/folder', FolderModelController::class);
    Route::apiResource('/subfolder', SubFolderModelController::class);
    Route::apiResource('/documents', DocumentsModelController::class);
    Route::post('/documents/{id}', [DocumentsModelController::class, 'update'])->name('documents.update');
    Route::post('/search-documents', [DocumentsModelController::class, 'searchDocuments'])->middleware(JwtMiddleware::class);
});
