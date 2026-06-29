<?php

// Kode ditulis oleh :
// Nama  : Fadhiil Akmal Hamizan
// Github: Axmalz

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Line items of an order. Snapshots title/price so history survives book deletion.
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('book_id')->nullable()->constrained('books')->nullOnDelete();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');

            $table->string('book_title'); // snapshot
            $table->enum('type', ['beli', 'sewa']);
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);

            $table->integer('rating')->nullable();        // buyer's review rating for this item
            $table->timestamp('rental_due_at')->nullable(); // sewa only
            $table->timestamp('returned_at')->nullable();   // sewa only

            $table->timestamps();
            $table->index('seller_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
