<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Book;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;

class CheckoutController extends Controller
{
    private const SHIPPING_FEE = 5000;
    private const ADMIN_FEE = 1000;

    public function index()
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $cartItems = $cart->items()->where('is_selected', true)->with('book')->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Pilih item terlebih dahulu.');
        }

        $subtotal = $cartItems->sum(fn ($item) => $item->subtotal);
        $adminFee = self::ADMIN_FEE;
        $shippingFee = self::SHIPPING_FEE;

        $defaultAddress = $user->addresses()->where('is_default', true)->first();
        $defaultCard = $user->payments()->where('is_default', true)->first();

        return view('checkout.index', compact('cartItems', 'subtotal', 'adminFee', 'shippingFee', 'defaultAddress', 'defaultCard'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:Card,Cash,Apple Pay',
            'promo_code'     => 'nullable|string|max:50',
        ]);

        $user = Auth::user();

        $address = $user->addresses()->where('is_default', true)->first();
        if (! $address) {
            return redirect()->route('address.index')->with('error', 'Pilih alamat pengiriman terlebih dahulu.');
        }

        $cart = Cart::where('user_id', $user->id)->first();
        $cartItems = $cart ? $cart->items()->where('is_selected', true)->with('book')->get() : collect();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Tidak ada item dipilih.');
        }

        // Promo discount.
        $promoCode = strtoupper((string) $request->input('promo_code'));
        $subtotal = $cartItems->sum(fn ($item) => $item->subtotal);
        $discountPercent = match ($promoCode) {
            'PPPLBOOKUY'   => 0.30,
            'DESIGNBYOCID' => 0.50,
            'BOOKUY'       => 0.10,
            default        => 0.0,
        };
        $discount = $subtotal * $discountPercent;
        $courier = collect(['Reksy', 'Adit', 'Rama', 'Abi', 'Budi'])->random();

        try {
            $orderId = DB::transaction(function () use ($user, $cartItems, $address, $subtotal, $discount, $promoCode, $request, $courier) {
                // Reserve stock atomically — this is the single point where stock is decremented.
                foreach ($cartItems as $item) {
                    $book = Book::lockForUpdate()->find($item->book_id);
                    if (! $book) {
                        throw new \RuntimeException('Buku tidak ditemukan.');
                    }
                    if ($item->type === 'beli') {
                        if ($book->stok_beli < $item->quantity) {
                            throw new \RuntimeException("Stok '{$book->judul_buku}' tidak mencukupi.");
                        }
                        $book->decrement('stok_beli', $item->quantity);
                    } else {
                        if ($book->stok_sewa < 1) {
                            throw new \RuntimeException("Stok sewa '{$book->judul_buku}' habis.");
                        }
                        $book->decrement('stok_sewa', 1);
                    }
                }

                $order = Order::create([
                    'buyer_id'         => $user->id,
                    'shipping_address' => $address->full_address,
                    'subtotal'         => $subtotal,
                    'shipping_fee'     => self::SHIPPING_FEE,
                    'admin_fee'        => self::ADMIN_FEE,
                    'discount_amount'  => $discount,
                    'total'            => $subtotal + self::SHIPPING_FEE + self::ADMIN_FEE - $discount,
                    'promo_code'       => $promoCode ?: null,
                    'payment_method'   => $request->input('payment_method', 'Card'),
                    'status'           => 'Packing',
                    'courier_name'     => $courier,
                    'courier_message'  => 'Sedang dikemas oleh penjual.',
                ]);

                foreach ($cartItems as $item) {
                    $order->items()->create([
                        'book_id'       => $item->book_id,
                        'seller_id'     => $item->book->user_id,
                        'book_title'    => $item->book->judul_buku,
                        'type'          => $item->type,
                        'quantity'      => $item->quantity,
                        'unit_price'    => $item->type === 'sewa' ? $item->book->harga_sewa : $item->book->harga_beli,
                        'subtotal'      => $item->subtotal,
                        'rental_due_at' => $item->type === 'sewa' ? now()->addMonths($item->quantity * 6) : null,
                    ]);
                }

                $user->cart->items()->where('is_selected', true)->delete();

                return $order->id;
            });
        } catch (\Throwable $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        app(\App\Services\NotificationService::class)->send(
            $user->id,
            'Order Placed!',
            'Checkout berhasil! Pesananmu sedang diproses oleh penjual.',
            'transaction',
            'icon-notif-shopping-bag.png'
        );

        return redirect()->route('checkout.success', ['orderId' => $orderId]);
    }

    public function success($orderId)
    {
        return view('checkout.success', compact('orderId'));
    }

    public function track($id)
    {
        $order = Order::with(['items.book'])->findOrFail($id);

        // Only the buyer or a seller of one of the items may track (IDOR fix).
        abort_unless(
            $order->buyer_id === Auth::id() || $order->items->contains('seller_id', Auth::id()),
            403
        );

        $statuses = ['Packing', 'Picked', 'In Transit', 'Delivered'];
        $currentStatusIndex = array_search($order->status, $statuses);

        return view('checkout.track', compact('order', 'statuses', 'currentStatusIndex'));
    }

    /**
     * Buyer returns a rented item — restores one unit of rental stock.
     */
    public function returnRental($id)
    {
        $item = OrderItem::with('order')->findOrFail($id);

        abort_unless($item->order->buyer_id === Auth::id(), 403);

        if ($item->type !== 'sewa') {
            return back()->with('error', 'Hanya item sewa yang bisa dikembalikan.');
        }
        if ($item->returned_at) {
            return back()->with('error', 'Item ini sudah dikembalikan.');
        }
        if ($item->order->status !== 'Delivered') {
            return back()->with('error', 'Item bisa dikembalikan setelah pesanan diterima.');
        }

        DB::transaction(function () use ($item) {
            $item->update(['returned_at' => now()]);
            if ($item->book_id) {
                $book = Book::lockForUpdate()->find($item->book_id);
                if ($book) {
                    $book->increment('stok_sewa', 1);
                }
            }
        });

        return back()->with('success', 'Buku sewa berhasil dikembalikan.');
    }
}
