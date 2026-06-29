<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz
// NRP   : 5026231128
// Kelas : PPPL B

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Pembeli
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->foreignId('order_id')->nullable(); // provenance; FK added with the orders rebuild (Phase B)
            $table->integer('rating'); // 1-5
            $table->text('comment');
            $table->timestamps();

            // One review per user per book (prevents duplicate / spammed ratings).
            $table->unique(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
