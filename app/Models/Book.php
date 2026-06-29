<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul_buku', 'nama_penulis', 'harga_beli', 'harga_sewa',
        'stok_beli', 'stok_sewa', 'gambar_buku', 'deskripsi_buku',
        'user_id', 'kondisi_buku', 'alamat_buku', 'category_id',
        'jumlah_halaman', 'semester',
    ];

    protected $casts = [
        'gambar_buku' => 'array',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function reviews() { return $this->hasMany(Review::class); }

    /**
     * Resolve a single stored image value to a public URL.
     * Handles external (http) URLs and local files under public/books/.
     */
    public function resolveImageUrl($img): string
    {
        if (empty($img)) {
            return asset('images/illustration-no-books.png');
        }

        return Str::startsWith($img, 'http') ? $img : asset('books/' . basename($img));
    }

    /**
     * Primary cover image URL (first image, or a placeholder).
     * Replaces the image-resolution block that was duplicated across ~9 views.
     */
    public function getCoverUrlAttribute(): string
    {
        $images = $this->gambar_buku;
        $first = is_array($images) ? ($images[0] ?? null) : $images;

        return $this->resolveImageUrl($first);
    }

    /**
     * All of the book's images resolved to URLs (for the detail gallery).
     */
    public function getGalleryUrlsAttribute(): array
    {
        $images = $this->gambar_buku;

        if (empty($images)) {
            return [asset('images/illustration-no-books.png')];
        }

        $images = is_array($images) ? $images : [$images];

        return array_map(fn ($img) => $this->resolveImageUrl($img), array_values($images));
    }

    // Scope: Urutkan agar buku yang stoknya habis (beli 0 DAN sewa 0) ada di paling bawah
    // Prioritas 1: Ada stok (beli atau sewa > 0)
    // Prioritas 2: Stok habis (beli 0 dan sewa 0)
    public function scopeOrderByStockAvailability($query)
    {
        return $query->orderByRaw('CASE WHEN (stok_beli > 0 OR stok_sewa > 0) THEN 1 ELSE 2 END');
    }
}
