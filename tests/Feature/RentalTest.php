<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalTest extends TestCase
{
    use RefreshDatabase;

    public function test_returning_a_rental_restores_stock(): void
    {
        $seller = User::factory()->create();
        $book = $this->makeBook($seller, ['stok_sewa' => 1]);
        $buyer = User::factory()->create();

        $order = Order::create([
            'buyer_id' => $buyer->id, 'shipping_address' => 'x', 'subtotal' => 20000, 'total' => 26000, 'status' => 'Delivered',
        ]);
        $item = $order->items()->create([
            'book_id' => $book->id, 'seller_id' => $seller->id, 'book_title' => $book->judul_buku,
            'type' => 'sewa', 'quantity' => 1, 'unit_price' => 20000, 'subtotal' => 20000,
        ]);

        $this->actingAs($buyer)->post("/order-item/{$item->id}/return")->assertRedirect();

        $book->refresh();
        $item->refresh();
        $this->assertEquals(2, $book->stok_sewa);      // 1 + 1
        $this->assertNotNull($item->returned_at);
    }

    public function test_cancelling_an_order_restores_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create();
        $book = $this->makeBook($seller, ['stok_beli' => 3]);
        $buyer = User::factory()->create();

        $order = Order::create([
            'buyer_id' => $buyer->id, 'shipping_address' => 'x', 'subtotal' => 0, 'total' => 0, 'status' => 'Packing',
        ]);
        $order->items()->create([
            'book_id' => $book->id, 'seller_id' => $seller->id, 'book_title' => $book->judul_buku,
            'type' => 'beli', 'quantity' => 2, 'unit_price' => 1, 'subtotal' => 2,
        ]);

        $this->actingAs($admin)
            ->post("/kurir/update/{$order->id}", ['status' => 'Cancelled', 'message' => 'batal'])
            ->assertRedirect();

        $book->refresh();
        $this->assertEquals(5, $book->stok_beli); // 3 + 2
    }
}
