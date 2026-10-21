<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::with('author', 'categories')
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhereHas('author', fn ($a) => $a->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->query('category_id'), function ($query, $categoryId) {
                $query->whereHas('categories', fn ($c) => $c->where('categories.id', $categoryId));
            })
            ->when($request->boolean('in_stock'), fn ($query) => $query->where('stock', '>', 0))
            ->get();

        return response()->json($books);
    }

    public function lowStock()
    {
        return response()->json(
            Book::with('author', 'categories')->where('stock', '<', 5)->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'isbn' => 'required|string|unique:books',
            'published_year' => 'required|integer|min:1900|max:' . date('Y'),
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'author_id' => 'required|exists:authors,id',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $book = Book::create(collect($validated)->except('category_ids')->all());
        $book->categories()->sync($validated['category_ids'] ?? []);

        return response()->json($book->load('author', 'categories'), 201);
    }

    public function show(Book $book)
    {
        return response()->json($book->load('author', 'categories'));
    }

    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'isbn' => 'sometimes|string|unique:books,isbn,' . $book->id,
            'published_year' => 'sometimes|integer|min:1900|max:' . date('Y'),
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'author_id' => 'sometimes|exists:authors,id',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $book->update(collect($validated)->except('category_ids')->all());

        if (array_key_exists('category_ids', $validated)) {
            $book->categories()->sync($validated['category_ids']);
        }

        return response()->json($book->load('author', 'categories'));
    }

    public function destroy(Book $book)
    {
        $book->delete();

        return response()->json(['message' => 'Buku berhasil dihapus']);
    }
}