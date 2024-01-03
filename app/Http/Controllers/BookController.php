<?php

namespace App\Http\Controllers;

use App\Exports\BooksExport;
use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Maatwebsite\Excel\Facades\Excel;

class BookController extends Controller
{
    public function getBooks(Request $request)
    {
        $user_id = JWTAuth::parseToken()->authenticate()->id;
        $user = User::find($user_id);
        $categoryName = $request->query('category');
        // $book = Book::where('created_by', $user_id)->get(); // Ensure book belongs to the user
        // $book = Book::all();

        $page = $request->query('page', 1);
        $limit = $request->query('limit', 5);
        $skip = ($page - 1) * $limit;

        if ($categoryName) {
            if ($user->hasRole('admin')) {
                $books = Book::join('categories', 'books.category', '=', 'categories.id')
                    ->where('categories.category', $categoryName)
                    ->skip($skip)
                    ->take($limit)
                    ->select('books.*')  // to avoid column name conflict
                    ->get();
                $total = Book::join('categories', 'books.category', '=', 'categories.id')
                    ->where('categories.category', $categoryName)
                    ->count();
            } else {
                $books = Book::join('categories', 'books.category', '=', 'categories.id')
                    ->where('books.created_by', $user_id)
                    ->where('categories.category', $categoryName)
                    ->skip($skip)
                    ->take($limit)
                    ->select('books.*')  // to avoid column name conflict
                    ->get();
                $total = Book::join('categories', 'books.category', '=', 'categories.id')
                    ->where('categories.category', $categoryName)
                    ->count();
            }
        } else {
            $books = Book::where('created_by', $user_id)
                ->skip($skip)
                ->take($limit)
                ->get();
            $total = Book::count();
        }


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
            $user = User::find($user_id);
            if ($user->hasRole('admin')) {
                $book = Book::where('id', $book_id)->firstOrFail();
            } else {
                $book = Book::where('id', $book_id)->where('created_by', $user_id)->firstOrFail(); // Ensure book belongs to the user
            }

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
        $user = User::find($user_id);
        if ($user->hasRole('admin')) {
            $book = Book::where('id', $book_id)->first();
        } else {
            $book = Book::where('id', $book_id)
                ->where('created_by', $user_id) // Ensure book belongs to the user
                ->first();
            // Ensure book belongs to the user
        }

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
            $user = User::find($user_id);
            if ($user->hasRole('admin')) {
                $book = Book::findOrFail($book_id);
            } else {
                $book = Book::where('created_by', $user_id)->findOrFail($book_id);
            }

            if ($book_id !== strval($request->id)) {
                // Check for ISBN conflict
                if (Book::where('id', $request->id)->where('created_by', $user_id)->exists()) {
                    return response()->json([
                        'message' => 'The ISBN number is already in use on another book',
                        'errors' => ['id' => ['This ISBN number is already in use']],
                    ], 422);
                }
            }

            if ($request->hasFile('book_file')) {
                $request->validate([
                    'book_file' => 'mimes:pdf',
                ]);
                $book_file = $request->file('book_file')->store('books');
            } else {
                $book_file = Book::find($request->id)->first()->book_file;
            }

            if ($request->hasFile('cover_image')) {
                $request->validate([
                    'cover_image' => 'image|mimes:jpeg,png,jpg'
                ]);
                $cover_image = $request->file('cover_image')->store('cover_image');
            } else {
                $cover_image = Book::find($request->id)->first()->cover_image;
            }

            // $published = $request->published ? Carbon::createFromFormat('D M d Y H:i:s e+', $request->published)->format('Y-m-d H:i:s') : null;
            $book->update([
                'id' => strval($request->id),
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'author' => $request->author,
                'description' => $request->description,
                'published' => $request->published,
                'publisher' => $request->publisher,
                'website' => $request->website,
                'created_by' => $request->created_by,
                'category' => (int)$request->category,
                'pages' => (int) $request->pages,
                'book_file' => $book_file,
                'cover_image' => $cover_image,
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

            $request->validate([
                'book_file' => 'mimes:pdf',
                'cover_image' => 'image|mimes:jpeg,png,jpg'
            ]);

            $book_file = $request->file('book_file')->store('books');
            $cover_image = $request->file('cover_image')->store('cover_image');

            $published = $request->published ? Carbon::createFromFormat('D M d Y H:i:s e+', $request->published)->format('Y-m-d H:i:s') : null;

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
                'category' => (int)$request->category,
                'pages' => (int) $request->pages,
                'book_file' => $book_file,
                'cover_image' => $cover_image,
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

    public function showPdf(Request $request, Book $book)
    {
        try {
            JWTAuth::parseToken()->authenticate()->id;
            return response()->download(storage_path("app/{$book->book_file}"));
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function showCoverImage(Request $request, Book $book)
    {
        try {
            JWTAuth::parseToken()->authenticate()->id;
            return response()->download(storage_path("app/{$book->cover_image}"));
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            JWTAuth::parseToken()->authenticate()->id;
            return Excel::download(new BooksExport, 'books.xlsx');
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
