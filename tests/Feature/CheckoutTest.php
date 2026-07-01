<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: \App\Models\Book} */
    private function buyerWithCart(int $beliQty = 2, int $stokBeli = 5, int $stokSewa = 2, bool $withAddress = true): array
    {
        $seller = User::factory()->create();
        $book = $this->makeBook($seller, [
            'harga_beli' => 100000, 'harga_sewa' => 20000, 'stok_beli' => $stokBeli, 'stok_sewa' => $stokSewa,
        ]);
        $buyer = User::factory()->create();
        if ($withAddress) {
            $buyer->addresses()->create(['nickname' => 'Home', 'full_address' => 'Jl Test', 'is_default' => true]);
        }
        $cart = Cart::create(['user_id' => $buyer->id]);
        $cart->items()->create(['book_id' => $book->id, 'type' => 'beli', 'quantity' => $beliQty, 'is_selected' => true]);

        return [$buyer, $book];
    }

    public function test_checkout_reserves_stock_creates_order_and_clears_cart(): void
    {
        [$buyer, $book] = $this->buyerWithCart(beliQty: 2, stokBeli: 5);

        $this->actingAs($buyer)
            ->post('/checkout/process', ['payment_method' => 'Card', 'promo_code' => 'BOOKUY'])
            ->assertRedirect();

        $book->refresh();
        $this->assertEquals(3, $book->stok_beli); // 5 - 2

        $order = Order::where('buyer_id', $buyer->id)->firstOrFail();
        $this->assertEquals(1, $order->items->count());
        $this->assertEquals(200000, (int) $order->subtotal);
        $this->assertEquals(20000, (int) $order->discount_amount); // 10% of 200000
        $this->assertEquals(186000, (int) $order->total);          // 200000 + 5000 + 1000 - 20000
        $this->assertEquals(0, $buyer->cart->items()->count());
    }

    public function test_checkout_requires_a_default_address(): void
    {
        [$buyer] = $this->buyerWithCart(beliQty: 1, withAddress: false);

        $this->actingAs($buyer)
            ->post('/checkout/process', ['payment_method' => 'Card'])
            ->assertRedirect(route('address.index'));

        $this->assertEquals(0, Order::count());
    }

    public function test_checkout_rejects_when_stock_insufficient(): void
    {
        [$buyer, $book] = $this->buyerWithCart(beliQty: 3, stokBeli: 2);

        $this->actingAs($buyer)
            ->post('/checkout/process', ['payment_method' => 'Card'])
            ->assertRedirect(route('cart.index'));

        $book->refresh();
        $this->assertEquals(2, $book->stok_beli); // unchanged (rolled back)
        $this->assertEquals(0, Order::count());
    }
}
