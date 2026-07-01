<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_courier_dashboard(): void
    {
        $this->get('/kurir')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_courier_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get('/kurir')->assertForbidden();
    }

    public function test_admin_can_access_courier_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/kurir')->assertOk();
    }

    public function test_user_cannot_update_another_users_cart_item(): void
    {
        $victim = User::factory()->create();
        $book = $this->makeBook();
        $cart = Cart::create(['user_id' => $victim->id]);
        $item = $cart->items()->create(['book_id' => $book->id, 'type' => 'beli', 'quantity' => 1, 'is_selected' => true]);

        $attacker = User::factory()->create();
        $this->actingAs($attacker)
            ->postJson("/cart/update/{$item->id}", ['quantity' => 5])
            ->assertNotFound();
    }

    public function test_user_cannot_track_another_users_order(): void
    {
        $buyer = User::factory()->create();
        $order = Order::create([
            'buyer_id' => $buyer->id, 'shipping_address' => 'x', 'subtotal' => 0, 'total' => 0, 'status' => 'Packing',
        ]);

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get("/track-order/{$order->id}")->assertForbidden();
    }

    public function test_only_the_owner_can_edit_a_book(): void
    {
        $seller = User::factory()->create();
        $book = $this->makeBook($seller);

        $other = User::factory()->create();
        $this->actingAs($other)->get("/product/{$book->id}/edit")->assertForbidden();
        $this->actingAs($seller)->get("/product/{$book->id}/edit")->assertOk();
    }
}
