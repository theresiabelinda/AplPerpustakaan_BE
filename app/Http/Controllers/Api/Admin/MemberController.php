<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    /**
     * Tampilkan Data Anggota (Hak Akses Admin)
     */
    public function index()
    {
        // Hanya menampilkan user dengan role 'anggota'
        $members = User::where('role', 'anggota')->paginate(15);
        return UserResource::collection($members);
    }

    /**
     * Tambah Anggota Baru (Hak Akses Admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'member_card_id' => 'nullable|unique:users,member_card_id',
        ]);

        $member = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'anggota',
            'status' => 'active', // Default status: Aktif
            'member_card_id' => $request->member_card_id ?? 'MBR' . now()->timestamp, // ID otomatis
        ]);

        return response()->json([
            'message' => 'Anggota berhasil ditambahkan.',
            'data' => new UserResource($member)
        ], 201);
    }

    /**
     * Ubah Status Keanggotaan (Aktif/Non-Aktif) (Hak Akses Admin)
     */
    public function updateStatus(Request $request, User $member)
    {
        $request->validate(['status' => 'required|in:active,non-active']);

        // Pastikan yang diupdate adalah anggota
        if ($member->role !== 'anggota') {
            return response()->json(['message' => 'User ini bukan anggota.'], 403);
        }

        $member->status = $request->status;
        $member->save();

        return response()->json([
            'message' => 'Status keanggotaan berhasil diubah menjadi ' . $request->status . '.',
            'data' => new UserResource($member)
        ]);
    }
}
