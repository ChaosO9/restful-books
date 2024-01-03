<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Login;
use App\Http\Controllers\Register;
use App\Http\Controllers\User;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::post('/login', [Login::class, 'userLogin']);
Route::post('/register', [Register::class, 'userRegister']);
Route::get('/user', [UserController::class, 'getUserDetail']);

Route::middleware('api')->group(function () {
    Route::get('/books', [BookController::class, 'getBooks']);
    Route::get('/books/{book_id}', [BookController::class, 'getBookDetail']);
    Route::delete('/books/{bookId}', [BookController::class, 'deleteBook']);
    Route::post('/books/{book_id}/edit', [BookController::class, 'updateBook']);
    Route::post('/books/add', [BookController::class, 'createBook']);
    Route::post('/verifyjwt', [Login::class, 'verifyJWT']);

    Route::get('/categories', [CategoryController::class, 'getCategories']);
});
