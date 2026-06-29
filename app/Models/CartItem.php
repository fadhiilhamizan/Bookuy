<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = ['cart_id', 'book_id', 'type', 'quantity', 'is_selected'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    // Helper untuk menghitung subtotal item ini
    public function getSubtotalAttribute()
    {
        $price = ($this->type == 'sewa') ? $this->book->harga_sewa : $this->book->harga_beli;
        return $price * $this->quantity;
    }
}
