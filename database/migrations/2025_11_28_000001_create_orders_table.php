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
        // Parent order: one per checkout (a shipment). Line items live in order_items.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');

            // Address snapshot (survives the address row being deleted).
            $table->text('shipping_address')->nullable();

            // Money, computed at checkout.
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('admin_fee', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->string('promo_code')->nullable();
            $table->string('payment_method')->default('Card');

            $table->enum('status', ['Packing', 'Picked', 'In Transit', 'Delivered', 'Cancelled'])->default('Packing');
            $table->string('courier_name')->nullable();
            $table->string('courier_message')->nullable();

            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
