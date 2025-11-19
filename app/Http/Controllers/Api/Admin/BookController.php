<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Http\Resources\BookResource;
use App\Models\Category;

class BookController extends Controller
{
    /**
     * Tampil & Cari Buku (Untuk Admin dan Anggota)
     */
    public function index(Request $request)
    {
        $books = Book::query();

        // Pencarian berdasarkan Judul, Pengarang, Kategori
        if ($request->has('search')) {
            $searchTerm = '%' . $request->search . '%';
            $books->where('title', 'like', $searchTerm)
                ->orWhere('author', 'like', $searchTerm);
        }

        // Filter berdasarkan category_id
        if ($request->has('category_id')) {
            $books->where('category_id', $request->category_id);
        }

        return BookResource::collection($books->with('category')->paginate(15));
    }

    /**
     * Tambah Buku Baru (Hak Akses Admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'stock' => 'required|integer|min:0',
            'shelf_location' => 'required|string|max:50',
        ]);

        $book = Book::create($request->all());

        return response()->json([
            'message' => 'Buku berhasil ditambahkan.',
            'data' => new BookResource($book)
        ], 201);
    }

    /**
     * Tampilkan Detail Buku (Untuk Admin dan Anggota)
     */
    public function show(Book $book)
    {
        // $book di-resolve otomatis melalui Route Model Binding
        return new BookResource($book);
    }

    /**
     * Edit/Update Buku (Hak Akses Admin)
     */
    public function update(Request $request, Book $book)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'author' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'stock' => 'sometimes|integer|min:0',
            'shelf_location' => 'sometimes|string|max:50',
        ]);

        $book->update($request->all());

        return response()->json([
            'message' => 'Buku berhasil diperbarui.',
            'data' => new BookResource($book)
        ]);
    }

    /**
     * Hapus Buku (Hak Akses Admin)
     */
    public function destroy(Book $book)
    {
        $book->delete();

        return response()->json([
            'message' => 'Buku berhasil dihapus.'
        ], 204);
    }
}
