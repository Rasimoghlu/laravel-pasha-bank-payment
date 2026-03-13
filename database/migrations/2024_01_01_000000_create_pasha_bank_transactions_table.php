<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pasha_bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('order_id')->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('944');
            $table->string('status')->index()->default('pending');
            $table->string('message_type', 3)->default('SMS');
            $table->string('card_number')->nullable();
            $table->string('rrn', 12)->nullable();
            $table->string('approval_code', 6)->nullable();
            $table->string('result')->nullable();
            $table->string('result_code', 3)->nullable();
            $table->text('redirect_url')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasha_bank_transactions');
    }
};
