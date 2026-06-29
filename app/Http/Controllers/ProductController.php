<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Category;
use App\Models\Review;
use App\Models\Order; // <!-- PENTING: Baris ini harus ada
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Models\Notification;

class ProductController extends Controller
{
    public function show(Request $request, $id)
    {
        $book = Book::with(['user', 'category', 'reviews.user'])->findOrFail($id);

        $reviewSort = $request->input('review_sort', 'relevant');
        $reviewLimit = $request->input('review_limit', 5);

        $reviewsQuery = $book->reviews()->with('user');

        if ($reviewSort === 'newest') {
            $reviewsQuery->latest();
        } else {
            $reviewsQuery->orderByDesc('rating')->orderByRaw('LENGTH(comment) DESC');
        }

        $totalReviewsReal = $book->reviews()->count();

        if ($reviewLimit !== 'all') {
            $reviews = $reviewsQuery->take((int)$reviewLimit)->get();
        } else {
            $reviews = $reviewsQuery->get();
        }

        $allReviewsForStats = $book->reviews;
        $averageRating = $allReviewsForStats->avg('rating');

        $ratingCounts = [];
        for ($i = 5; $i >= 1; $i--) {
            $ratingCounts[$i] = $allReviewsForStats->where('rating', $i)->count();
        }

        return view('product.show', [
            'book' => $book,
            'reviews' => $reviews,
            'totalReviews' => $totalReviewsReal,
            'averageRating' => $averageRating ?? 0,
            'ratingCounts' => $ratingCounts,
            'currentSort' => $reviewSort,
            'currentLimit' => $reviewLimit
        ]);
    }

    public function edit($id)
    {
        $book = Book::findOrFail($id);
        $this->authorize('update', $book);
        $categories = Category::all();
        return view('product.edit', compact('book', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $book = Book::findOrFail($id);
        $this->authorize('update', $book);

        $request->validate([
            'judul_buku' => 'required|string|max:255',
            'nama_penulis' => 'required|string|max:255',
            'harga_beli' => 'required|numeric',
            'harga_sewa' => 'required|numeric',
            'stok_beli' => 'required|integer|min:0',
            'stok_sewa' => 'required|integer|min:0',
            'deskripsi_buku' => 'required|string',
            'kondisi_buku' => 'required|in:baru,bekas premium,bekas usang',
            'alamat_buku' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'jumlah_halaman' => 'required|integer',
            'semester' => 'required',
            'new_images.*' => 'image|mimes:jpeg,png,jpg|max:3072',
        ]);

        $data = $request->except(['new_images', 'keep_images']);

        $currentImages = $book->gambar_buku ?? [];
        $keepImages = $request->keep_images ?? [];

        // Filter gambar yang dihapus (opsional: tambahkan logika hapus file fisik di sini)
        $finalImages = $keepImages;

        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $file) {
                $path = $file->store('book-images', 'public');
                $finalImages[] = asset('storage/' . $path);
            }
        }

        $data['gambar_buku'] = $finalImages;
        $book->update($data);

        return redirect()->route('product.show', $book->id)->with('success', 'Product updated successfully');
    }

    public function create()
    {
        $categories = Category::all();
        $book = new Book();
        return view('product.create', compact('categories', 'book'));
    }

    public function store(Request $request)
    {
        $request->validate([
             'judul_buku' => 'required|string|max:255',
             'nama_penulis' => 'required|string|max:255',
             'harga_beli' => 'required|numeric',
             'harga_sewa' => 'required|numeric',
             'stok_beli' => 'required|integer|min:0',
             'stok_sewa' => 'required|integer|min:0',
             'deskripsi_buku' => 'required|string',
             'kondisi_buku' => 'required|in:baru,bekas premium,bekas usang',
             'alamat_buku' => 'required|string',
             'category_id' => 'required|exists:categories,id',
             'jumlah_halaman' => 'required|integer',
             'semester' => 'required',
             'new_images' => 'required|array|min:1|max:3',
             'new_images.*' => 'image|mimes:jpeg,png,jpg|max:3072',
        ]);

        $book = new Book();
        $book->user_id = Auth::id();
        $book->fill($request->except('new_images'));

        $imagePaths = [];
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $file) {
                $path = $file->store('book-images', 'public');
                $imagePaths[] = asset('storage/' . $path);
            }
        }
        $book->gambar_buku = $imagePaths;
        $book->save();

        Notification::create([
            'user_id' => Auth::id(),
            'title'   => 'Book Listed!',
            'message' => "Buku '{$book->judul_buku}' berhasil didaftarkan untuk dijual/disewa.",
            'type'    => 'transaction',
            'icon'    => 'icon-notif-book.png'
        ]);

        return redirect()->route('product.show', $book->id)->with('success', 'Product posted successfully!');
    }

    public function storeReview(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        // Proof of purchase: a *Delivered* order item for this book, bought by this user.
        $orderItem = \App\Models\OrderItem::where('book_id', $request->book_id)
            ->whereHas('order', fn ($q) => $q->where('buyer_id', Auth::id())->where('status', 'Delivered'))
            ->latest()
            ->first();

        if (! $orderItem) {
            return back()->with('error', 'Kamu hanya bisa mengulas buku yang sudah kamu beli dan terima.');
        }

        // One review per user per book.
        if (Review::where('user_id', Auth::id())->where('book_id', $request->book_id)->exists()) {
            return back()->with('error', 'Kamu sudah pernah mengulas buku ini.');
        }

        Review::create([
            'user_id'  => Auth::id(),
            'book_id'  => $request->book_id,
            'order_id' => $orderItem->order_id,
            'rating'   => $request->rating,
            'comment'  => $request->comment ?? '',
        ]);

        // Mirror the rating onto the order item (drives the "reviewed" state in purchase history).
        $orderItem->update(['rating' => $request->rating]);

        return back()->with('success', 'Ulasan berhasil dikirim!');
    }
}
