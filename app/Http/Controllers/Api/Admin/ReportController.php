<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Borrowing;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Laporan Buku Terfavorit/Paling Sering Dipinjam (Hak Akses Admin)
     */
    public function topBooksReport()
    {
        // Query untuk menghitung jumlah peminjaman per buku
        $topBooks = Book::withCount('borrowings')
            ->orderByDesc('borrowings_count')
            ->limit(10)
            ->get();

        return response()->json([
            'message' => 'Laporan 10 Buku Paling Sering Dipinjam',
            'data' => $topBooks->map(function ($book) {
                return [
                    'title' => $book->title,
                    'author' => $book->author,
                    'times_borrowed' => $book->borrowings_count,
                ];
            })
        ]);
    }

    /**
     * Laporan Keuangan Sederhana (Denda) (Hak Akses Admin)
     */
    public function financialReport()
    {
        // Total Denda yang sudah dibayar
        $totalPaid = Fine::where('is_paid', true)->sum('amount');

        // Total Denda yang belum dibayar (Piutang)
        $totalUnpaid = Fine::where('is_paid', false)->sum('amount');

        return response()->json([
            'message' => 'Laporan Keuangan Sederhana (Denda)',
            'data' => [
                'total_denda_lunas' => $totalPaid,
                'total_denda_piutang' => $totalUnpaid,
                'total_semua_denda' => $totalPaid + $totalUnpaid,
            ]
        ]);
    }

    /**
     * Laporan Peminjaman dan Pengembalian (Hak Akses Admin)
     */
    public function borrowingReport(Request $request)
    {
        $borrowings = Borrowing::with(['user', 'book'])
            ->when($request->from_date, function ($query) use ($request) {
                return $query->whereDate('borrow_date', '>=', $request->from_date);
            })
            ->when($request->to_date, function ($query) use ($request) {
                return $query->whereDate('borrow_date', '<=', $request->to_date);
            })
            ->paginate(20);

        return response()->json([
            'message' => 'Laporan Peminjaman Berdasarkan Filter Tanggal',
            'data' => $borrowings, // Di sini Anda bisa menggunakan Resource Collection jika diperlukan format yang lebih rapi
        ]);
    }
}
