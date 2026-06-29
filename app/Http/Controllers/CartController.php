<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Book;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Remember where the user came from so the back button is sensible.
        $previousUrl = url()->previous();
        if ($previousUrl !== url()->current() && ! str_contains($previousUrl, '/cart')) {
            Session::put('cart_back_url', $previousUrl);
        }
        $backUrl = Session::get('cart_back_url', route('home'));

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $activeTab = $request->query('tab', 'beli');

        $items = $cart->items()->where('type', $activeTab)->with('book')->get();

        $selectedItems = $items->where('is_selected', true);
        $subtotal = $selectedItems->sum(fn ($item) => $item->subtotal);

        $adminFee = $selectedItems->count() > 0 ? 1000 : 0;
        $shippingFee = $selectedItems->count() > 0 ? 5000 : 0;
        $total = $subtotal + $adminFee + $shippingFee;

        return view('cart.index', [
            'items'       => $items,
            'activeTab'   => $activeTab,
            'subtotal'    => $subtotal,
            'adminFee'    => $adminFee,
            'shippingFee' => $shippingFee,
            'total'       => $total,
            'hasSelected' => $selectedItems->count() > 0,
            'backUrl'     => $backUrl,
        ]);
    }

    public function add(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'book_id'  => 'required|exists:books,id',
            'type'     => 'required|in:beli,sewa',
            'quantity' => 'nullable|integer|min:1',
        ]);
        $bookId = $validated['book_id'];
        $type = $validated['type'];
        $quantity = $validated['quantity'] ?? 1;

        $book = Book::findOrFail($bookId);

        // Soft availability check. Stock is actually reserved at checkout, not here,
        // so abandoned carts never lock inventory.
        if ($type === 'beli' && $book->stok_beli < 1) {
            return $this->fail($request, 'Stok beli buku ini habis.');
        }
        if ($type === 'sewa' && $book->stok_sewa < 1) {
            return $this->fail($request, 'Stok sewa buku ini habis.');
        }

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        if ($type === 'beli') {
            $existing = $cart->items()->where('book_id', $bookId)->where('type', 'beli')->first();
            if ($existing) {
                $existing->increment('quantity', $quantity);
            } else {
                $cart->items()->create(['book_id' => $bookId, 'type' => 'beli', 'quantity' => $quantity, 'is_selected' => true]);
            }
        } else {
            // Sewa is always a new line (each rental is its own item).
            $cart->items()->create(['book_id' => $bookId, 'type' => 'sewa', 'quantity' => $quantity, 'is_selected' => true]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('cart.index', ['tab' => $type])->with('success', 'Berhasil ditambahkan ke keranjang.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity'    => 'sometimes|integer|min:1',
            'is_selected' => 'sometimes|boolean',
        ]);

        // Scope to the current user's cart (IDOR fix).
        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        $item = $cart->items()->with('book')->findOrFail($id);

        if ($request->has('quantity')) {
            $newQty = max(1, (int) $request->input('quantity'));

            if ($item->type === 'beli' && $item->book && $newQty > $item->book->stok_beli) {
                return response()->json(['success' => false, 'message' => 'Melebihi stok tersedia.'], 200);
            }
            if ($item->type === 'sewa' && $newQty > 8) {
                return response()->json(['success' => false, 'message' => 'Maksimal 8 semester.'], 200);
            }
            $item->quantity = $newQty;
        }

        if ($request->has('is_selected')) {
            $item->is_selected = filter_var($request->input('is_selected'), FILTER_VALIDATE_BOOLEAN);
        }

        $item->save();

        return response()->json(['success' => true]);
    }

    public function remove($id)
    {
        // Scope to the current user's cart (IDOR fix).
        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        $item = $cart->items()->findOrFail($id);
        $type = $item->type;

        $item->delete();

        return redirect()->route('cart.index', ['tab' => $type]);
    }

    private function fail(Request $request, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 400);
        }
        return back()->withErrors($message);
    }
}
