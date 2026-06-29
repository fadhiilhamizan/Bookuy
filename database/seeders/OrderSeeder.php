<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Book;
use App\Models\User;

class OrderSeeder extends Seeder
{
    private array $couriers = ['Reksy', 'Adit', 'Rama', 'Abi', 'Budi'];
    private array $ongoing = ['Packing', 'Picked', 'In Transit'];

    public function run(): void
    {
        $admin = User::first();
        if (! $admin) return;

        // Ensure the admin owns some books to sell (for Sales History).
        $adminBooks = Book::where('user_id', $admin->id)->take(5)->get();
        if ($adminBooks->count() < 3) {
            $adminBooks = $adminBooks->concat(Book::factory(3)->create(['user_id' => $admin->id]));
        }

        $buyers = User::where('id', '!=', $admin->id)->inRandomOrder()->take(5)->get();
        $otherBooks = Book::where('user_id', '!=', $admin->id)->inRandomOrder()->take(5)->get();

        // SALES: other users buy the admin's books (admin = seller of these items).
        foreach ($adminBooks->take(5)->values() as $i => $book) {
            $status = $i < 3 ? $this->ongoing[array_rand($this->ongoing)] : 'Delivered';
            $this->makeOrder($buyers->random(), [$book], $status);
        }

        // PURCHASES: the admin buys other people's books (admin = buyer).
        foreach ($otherBooks->values() as $i => $book) {
            $status = $i < 3 ? $this->ongoing[array_rand($this->ongoing)] : 'Delivered';
            $this->makeOrder($admin, [$book], $status);
        }
    }

    /**
     * @param  \App\Models\Book[]  $books
     */
    private function makeOrder(User $buyer, array $books, string $status): void
    {
        $subtotal = 0;
        $lines = [];

        foreach ($books as $book) {
            $type = rand(0, 1) ? 'beli' : 'sewa';
            $qty = $type === 'sewa' ? rand(1, 2) : rand(1, 3);
            $unit = $type === 'sewa' ? $book->harga_sewa : $book->harga_beli;
            $sub = $unit * $qty;
            $subtotal += $sub;

            $lines[] = [
                'book_id'       => $book->id,
                'seller_id'     => $book->user_id,
                'book_title'    => $book->judul_buku,
                'type'          => $type,
                'quantity'      => $qty,
                'unit_price'    => $unit,
                'subtotal'      => $sub,
                'rental_due_at' => $type === 'sewa' ? now()->addMonths($qty * 6) : null,
            ];
        }

        $shipping = 5000;
        $adminFee = 1000;

        $order = Order::create([
            'buyer_id'        => $buyer->id,
            'shipping_address' => 'Jl. Contoh No. ' . rand(1, 99) . ', Surabaya',
            'subtotal'        => $subtotal,
            'shipping_fee'    => $shipping,
            'admin_fee'       => $adminFee,
            'discount_amount' => 0,
            'total'           => $subtotal + $shipping + $adminFee,
            'payment_method'  => 'Card',
            'status'          => $status,
            'courier_name'    => $this->couriers[array_rand($this->couriers)],
            'courier_message' => $status === 'Delivered' ? 'Pesanan telah sampai.' : 'Pesanan sedang diproses.',
            'created_at'      => now()->subDays(rand(1, 20)),
        ]);

        foreach ($lines as $line) {
            $order->items()->create($line);
        }
    }
}
