<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Book;
use App\Models\Category;

class BorrowingTest extends TestCase
{
    use RefreshDatabase;

    /** @var User */
    protected $admin;
    /** @var User */
    protected $anggota;
    /** @var Book */
    protected $book;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup data dasar
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->anggota = User::factory()->create(['role' => 'anggota', 'status' => 'active']);
        $category = Category::factory()->create();

        $this->book = Book::factory()->create([
            'category_id' => $category->id,
            'stock' => 5, // Stok awal 5
        ]);
    }

    /** @test */
    public function admin_can_borrow_book_and_stock_decreases()
    {
        $initialStock = $this->book->stock;

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/transactions/borrow', [
                'user_id' => $this->anggota->id,
                'book_id' => $this->book->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Peminjaman berhasil dicatat.']);

        // Assert 1: Stok buku harus berkurang 1
        $this->assertEquals($initialStock - 1, $this->book->fresh()->stock);

        // Assert 2: Transaksi peminjaman tercatat di database
        $this->assertDatabaseHas('borrowings', [
            'user_id' => $this->anggota->id,
            'book_id' => $this->book->id,
            'status' => 'borrowed',
        ]);
    }

    /** @test */
    public function cannot_borrow_book_if_stock_is_zero()
    {
        $this->book->update(['stock' => 0]); // Set stok 0

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/transactions/borrow', [
                'user_id' => $this->anggota->id,
                'book_id' => $this->book->id,
            ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'Stok buku habis.']);
    }

    /** @test */
    public function returning_book_late_creates_a_fine_record()
    {
        $FINE_RATE = 5000; // Asumsi tarif denda 5000

        // 1. Buat Transaksi Peminjaman yang sudah JATUH TEMPO 3 hari
        $borrowing = $this->book->borrowings()->create([
            'user_id' => $this->anggota->id,
            'borrow_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDays(3)->toDateString(), // Jatuh tempo 3 hari lalu
            'status' => 'borrowed'
        ]);

        // 2. Simulasikan Pengembalian Buku
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/transactions/return/{$borrowing->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'denda_dikenakan' => 3 * $FINE_RATE, // Denda 3 hari
                'keterlambatan_hari' => 3,
            ]);

        // Assert 1: Status peminjaman berubah
        $this->assertEquals('returned', $borrowing->fresh()->status);

        // Assert 2: Denda tercatat di database
        $this->assertDatabaseHas('fines', [
            'borrowing_id' => $borrowing->id,
            'amount' => 3 * $FINE_RATE,
            'is_paid' => false,
        ]);
    }

    /** @test */
    public function returning_book_on_time_does_not_create_a_fine_record()
    {
        // 1. Buat Transaksi Peminjaman yang BELUM JATUH TEMPO
        $borrowing = $this->book->borrowings()->create([
            'user_id' => $this->anggota->id,
            'borrow_date' => now()->subDays(2)->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(), // Jatuh tempo 5 hari lagi
            'status' => 'borrowed'
        ]);

        // 2. Simulasikan Pengembalian Buku
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/transactions/return/{$borrowing->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'denda_dikenakan' => 0,
                'keterlambatan_hari' => 0,
            ]);

        // Assert 1: Tidak ada catatan denda yang dibuat
        $this->assertDatabaseMissing('fines', [
            'borrowing_id' => $borrowing->id,
        ]);
    }
}
