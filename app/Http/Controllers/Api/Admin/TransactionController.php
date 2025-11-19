<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Borrowing;
use App\Models\Book;
use App\Models\User;
use App\Models\Fine;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class TransactionController extends Controller
{
    private $FINE_RATE = 5000; // Tarif denda per hari (Rp 5.000)

    /**
     * Input Peminjaman Buku (Hak Akses Admin)
     */
    public function borrowBook(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'book_id' => 'required|exists:books,id',
        ]);

        $user = User::find($request->user_id);
        $book = Book::find($request->book_id);
        $max_borrow_days = 7;

        if ($book->stock < 1) {
            return response()->json(['message' => 'Stok buku habis.'], 400);
        }
        if ($user->role != 'anggota' || $user->status != 'active') {
            return response()->json(['message' => 'Hanya anggota aktif yang boleh meminjam.'], 400);
        }

        DB::beginTransaction();
        try {
            $book->decrement('stock');

            $borrowing = Borrowing::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'borrow_date' => now()->toDateString(),
                'due_date' => now()->addDays($max_borrow_days)->toDateString(),
                'status' => 'borrowed'
            ]);

            DB::commit();
            return response()->json(['message' => 'Peminjaman berhasil dicatat.', 'data' => $borrowing], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Peminjaman gagal.'], 500);
        }
    }

    /**
     * Pengembalian Buku dan Catat Denda (Hak Akses Admin)
     */
    public function returnBook($borrowing_id)
    {
        $borrowing = Borrowing::with('book')->findOrFail($borrowing_id);

        if ($borrowing->status == 'returned') {
            return response()->json(['message' => 'Buku sudah dikembalikan sebelumnya.'], 400);
        }

        $due_date = Carbon::parse($borrowing->due_date);
        $return_date = now();
        $days_late = 0;
        $fine_amount = 0;

        // Hitung Keterlambatan
        if ($return_date->greaterThan($due_date)) {
            $days_late = $return_date->diffInDays($due_date);
            $fine_amount = $days_late * $this->FINE_RATE;
        }

        DB::beginTransaction();
        try {
            // Update status peminjaman dan kembalikan stok
            $borrowing->update([
                'status' => 'returned',
                'return_date' => $return_date->toDateString()
            ]);
            $borrowing->book->increment('stock');

            // Catat Denda jika terlambat
            if ($days_late > 0) {
                Fine::create([
                    'borrowing_id' => $borrowing->id,
                    'amount' => $fine_amount,
                    'reason' => 'Keterlambatan ' . $days_late . ' hari.',
                    'is_paid' => false,
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'Pengembalian buku berhasil dicatat.',
                'denda_dikenakan' => $fine_amount,
                'keterlambatan_hari' => $days_late
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Pengembalian gagal.'], 500);
        }
    }

    /**
     * Perpanjangan Masa Peminjaman (Hak Akses Admin)
     */
    public function extendBorrowing($borrowing_id)
    {
        $borrowing = Borrowing::findOrFail($borrowing_id);

        if ($borrowing->status !== 'borrowed') {
            return response()->json(['message' => 'Peminjaman sudah tidak aktif.'], 400);
        }

        // Cek denda (Jika ada denda yang belum dibayar, harus diselesaikan dulu)
        if (Fine::where('borrowing_id', $borrowing_id)->where('is_paid', false)->exists()) {
            return response()->json(['message' => 'Peminjaman memiliki denda yang belum dibayar. Mohon diselesaikan dulu.'], 400);
        }

        // Tambah 7 hari dari tanggal jatuh tempo lama
        $new_due_date = Carbon::parse($borrowing->due_date)->addDays(7);

        $borrowing->update(['due_date' => $new_due_date->toDateString()]);

        return response()->json([
            'message' => 'Masa peminjaman berhasil diperpanjang.',
            'new_due_date' => $new_due_date->toDateString()
        ]);
    }

    /**
     * Pembayaran Denda (Hak Akses Admin)
     */
    public function payFine($fine_id)
    {
        $fine = Fine::findOrFail($fine_id);

        if ($fine->is_paid) {
            return response()->json(['message' => 'Denda ini sudah lunas.'], 400);
        }

        $fine->update([
            'is_paid' => true,
            'paid_at' => now(),
        ]);

        return response()->json(['message' => 'Pembayaran denda berhasil dicatat.'], 200);
    }
}
