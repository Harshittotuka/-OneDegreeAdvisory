<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->string('request_token', 64)->unique();
            $table->string('session_hash', 64)->index();
            $table->string('page_slug', 160)->index();
            $table->string('block_id', 120);
            $table->unsignedSmallInteger('option_index');
            $table->string('item_name', 200);
            $table->unsignedBigInteger('amount'); // smallest currency sub-unit (paise for INR)
            $table->string('currency', 3)->default('INR');
            $table->string('theme_color', 7)->default('#F05A28');
            $table->string('customer_name', 160);
            $table->string('customer_email', 190);
            $table->string('customer_phone', 40)->nullable();
            $table->string('razorpay_order_id', 100)->nullable()->unique();
            $table->string('razorpay_payment_id', 100)->nullable()->unique();
            $table->string('status', 40)->default('order_creating')->index();
            $table->string('failure_reason', 500)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['page_slug', 'block_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
