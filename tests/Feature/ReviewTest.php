<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function deliveredPurchase(User $buyer, Book $book): void
    {
        $order = Order::create([
            'buyer_id' => $buyer->id, 'shipping_address' => 'x', 'subtotal' => 0, 'total' => 0, 'status' => 'Delivered',
        ]);
        $order->items()->create([
            'book_id' => $book->id, 'seller_id' => $book->user_id, 'book_title' => $book->judul_buku,
            'type' => 'beli', 'quantity' => 1, 'unit_price' => 1, 'subtotal' => 1,
        ]);
    }

    public function test_cannot_review_without_a_delivered_purchase(): void
    {
        $book = $this->makeBook();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/review/store', ['book_id' => $book->id, 'rating' => 5, 'comment' => 'nice']);

        $this->assertEquals(0, Review::count());
    }

    public function test_can_review_after_a_delivered_purchase(): void
    {
        $book = $this->makeBook();
        $buyer = User::factory()->create();
        $this->deliveredPurchase($buyer, $book);

        $this->actingAs($buyer)
            ->post('/review/store', ['book_id' => $book->id, 'rating' => 4, 'comment' => 'good']);

        $this->assertDatabaseHas('reviews', ['user_id' => $buyer->id, 'book_id' => $book->id, 'rating' => 4]);
    }

    public function test_cannot_review_the_same_book_twice(): void
    {
        $book = $this->makeBook();
        $buyer = User::factory()->create();
        $this->deliveredPurchase($buyer, $book);
        Review::create(['user_id' => $buyer->id, 'book_id' => $book->id, 'rating' => 3, 'comment' => 'ok']);

        $this->actingAs($buyer)
            ->post('/review/store', ['book_id' => $book->id, 'rating' => 5, 'comment' => 'again']);

        $this->assertEquals(1, Review::where('user_id', $buyer->id)->where('book_id', $book->id)->count());
    }
}
