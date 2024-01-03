<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class BookController extends Controller
{
    public function getBooks(Request $request)
    {
        $user_id = JWTAuth::parseToken()->authenticate()->id;

        // $book = Book::where('created_by', $user_id)->get(); // Ensure book belongs to the user
        // $book = Book::all();

        $page = $request->query('page', 1);
        $limit = $request->query('limit', 5);
        $skip = ($page - 1) * $limit;

        $books = Book::where('created_by', $user_id)->skip($skip)->take($limit)->get();
        $total = Book::count();

        // Prepare pagination data for the view
        return Response::json([
            'current_page' => $page,
            'data' => $books,
            'first_page_url' => url('/books?page=1'),
            'from' => $skip + 1,
            'last_page' => ceil($total / $limit),
            'last_page_url' => url('/books?page=' . ceil($total / $limit)),
            'next_page_url' => $page < ceil($total / $limit) ? url('/books?page=' . ($page + 1)) : null,
            'path' => '/books',
            'per_page' => $limit,
            'prev_page_url' => $page > 1 ? url('/books?page=' . ($page - 1)) : null,
            'to' => $skip + $books->count(),
            'total' => $total,
        ], 200);

        // return Response::json([
        //     'message' => 'Books found',
        //     'books' => $book,
        // ], 200);
    }

    public function getBookDetail(Request $request, $book_id)
    {
        try {
            $user_id = JWTAuth::parseToken()->authenticate()->id;
            $book = Book::where('id', $book_id)->where('created_by', $user_id)->firstOrFail(); // Ensure book belongs to the user

            return response()->json([
                'message' => 'Book found',
                'book' => $book,
            ], 200);
        } catch (\Exception $exception) {
            if ($exception instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'message' => 'Invalid token',
                ], 401);
            }

            if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'message' => 'The book does not exist',
                    'errors' => ['isbn' => ['The book does not exist']],
                ], 404);
            }

            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function deleteBook(Request $request, $book_id)
    {
        $user_id = JWTAuth::parseToken()->authenticate()->id;

        $book = Book::where('id', $book_id)
            ->where('created_by', $user_id) // Ensure book belongs to the user
            ->first();

        if (!$book) {
            return Response::json([
                'message' => "The book does not exist or you don't have right to delete",
            ], 404);
        }

        $book->delete();

        return Response::json([
            'message' => 'Book deleted',
            'book' => $book,
        ], 200);
    }

    public function updateBook(Request $request, $book_id)
    {
        try {
            $user_id = JWTAuth::parseToken()->authenticate()->id;
            $book = Book::findOrFail($book_id);

            if ($book_id !== strval($request->id)) {
                // Check for ISBN conflict
                if (Book::where('id', $request->id)->where('created_by', $user_id)->exists()) {
                    return response()->json([
                        'message' => 'The ISBN number is already in use on another book',
                        'errors' => ['id' => ['This ISBN number is already in use']],
                    ], 422);
                }
            }

            $published = $request->published ? Carbon::parse($request->published)->format('Y-m-d H:i:s') : null;
            $book->update([
                'id' => $request->id,
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'author' => $request->author,
                'description' => $request->description,
                'published' => $published,
                'publisher' => $request->publisher,
                'website' => $request->website,
                'created_by' => $request->created_by,
                'pages' => (int) $request->pages,
            ]);

            return response()->json([
                'message' => 'Book updated',
                'book' => $book,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function createBook(Request $request)
    {
        try {
            $user_id = JWTAuth::parseToken()->authenticate()->id;
            $book_id_request = $request->id;
            $book_existed = Book::where('id', $book_id_request)->first();

            if ($book_existed) {
                return response()->json([
                    'message' => 'The ISBN number is already in use on another book',
                    'errors' => ['id' => ['This ISBN number is already in use']],
                ], 422);
            }

            $published = $request->published ? Carbon::parse($request->published)->format('Y-m-d H:i:s') : null;
            $book_created = Book::create([
                'id' => strval($request->id),
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'author' => $request->author,
                'description' => $request->description,
                'published' => $published,
                'publisher' => $request->publisher,
                'website' => $request->website,
                'created_by' => $request->created_by,
                'pages' => (int) $request->pages,
            ]);

            return response()->json([
                'message' => 'Book created',
                'book' => $book_created,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
