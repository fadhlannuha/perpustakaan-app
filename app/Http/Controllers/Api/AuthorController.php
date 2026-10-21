<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index()
    {
        return response()->json(Author::withCount('books')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:authors',
            'bio' => 'nullable|string',
        ]);

        return response()->json(Author::create($validated), 201);
    }

    public function show(Author $author)
    {
        return response()->json($author->load('books'));
    }

    public function update(Request $request, Author $author)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:authors,email,' . $author->id,
            'bio' => 'nullable|string',
        ]);

        $author->update($validated);

        return response()->json($author);
    }

    public function destroy(Request $request, Author $author)
    {
        $booksCount = $author->books()->count();

        if ($booksCount > 0 && ! $request->boolean('confirm')) {
            return response()->json([
                'message' => "Penulis memiliki {$booksCount} buku yang juga akan terhapus. Kirim confirm=true untuk melanjutkan.",
                'books_count' => $booksCount,
            ], 409);
        }

        $author->delete();

        return response()->json([
            'message' => 'Penulis berhasil dihapus',
            'deleted_books' => $booksCount,
        ]);
    }
}
