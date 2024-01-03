<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Response;

class CategoryController extends Controller
{
    public function getCategories(Request $request)
    {
        $page = $request->query('page', 1);
        $limit = $request->query('limit', 5);
        $skip = ($page - 1) * $limit;

        $category = Category::skip($skip)->take($limit)->get();
        $total = Category::count();

        return Response::json([
            'current_page' => $page,
            'data' => $category,
            'first_page_url' => url('/books?page=1'),
            'from' => $skip + 1,
            'last_page' => ceil($total / $limit),
            'last_page_url' => url('/books?page=' . ceil($total / $limit)),
            'next_page_url' => $page < ceil($total / $limit) ? url('/books?page=' . ($page + 1)) : null,
            'path' => '/books',
            'per_page' => $limit,
            'prev_page_url' => $page > 1 ? url('/books?page=' . ($page - 1)) : null,
            'to' => $skip + $category->count(),
            'total' => $total,
        ], 200);
    }

    public function deleteCategory(Request $request, $category_id)
    {
        $category = Category::find($category_id)->first();

        if (!$category) {
            return Response::json([
                'message' => "The category does not exist",
            ], 404);
        }

        $bookCount = $category->books()->count();
        if ($bookCount) {
            return Response::json([
                'message' => "The category can't be deleted because any book still categorize this category",
            ], 404);
        }

        $category->delete();

        return Response::json([
            'message' => 'Category deleted',
            'book' => $category,
        ], 200);
    }

    public function updateCategory(Request $request, $category_id)
    {
        try {
            $category = Category::find($category_id)->first();

            if (!$category) {
                return Response::json([
                    'message' => "The category does not exist",
                ], 404);
            }

            $category->update([
                'name' => $request->category_name,
            ]);

            return response()->json([
                'message' => 'Category updated',
                'book' => $category,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function createCategory(Request $request)
    {
        try {
            // $user_id = JWTAuth::parseToken()->authenticate()->id;
            $category_existed = Category::where('category', $request->category_name)->first();

            if ($category_existed) {
                return response()->json([
                    'message' => 'The category name is already exist',
                    'errors' => ['category_name' => ['The category name is already exist']],
                ], 422);
            }

            $category_created = Category::create([
                'name' => $request->category_name,
            ]);

            return response()->json([
                'message' => 'Category created',
                'category' => $category_created,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
