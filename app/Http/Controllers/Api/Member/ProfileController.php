<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Borrowing;
use App\Http\Resources\BorrowingResource;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Lihat Profil & Status Keanggotaan
     */
    public function show()
    {
        $user = auth()->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status_keanggotaan' => $user->status,
            'member_card_id' => $user->member_card_id,
        ]);
    }

    /**
     * Edit Data Pribadi
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id, // Abaikan email sendiri
        ]);

        $user->update($request->only('name', 'email'));

        return response()->json(['message' => 'Profil berhasil diperbarui.']);
    }

    /**
     * Ganti Password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Password lama salah.'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    /**
     * Riwayat Peminjaman (Buku yang sedang dipinjam & denda)
     */
    public function myBorrowings()
    {
        $user_id = auth()->id();

        $borrowings = Borrowing::with(['book', 'fine'])
            ->where('user_id', $user_id)
            ->latest()
            ->paginate(10);

        return BorrowingResource::collection($borrowings);
    }
}
