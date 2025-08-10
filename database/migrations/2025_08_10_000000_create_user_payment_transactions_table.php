<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_payment_id')->nullable()->index();
            $table->uuid('schedule_uuid')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();

            $table->string('gateway')->default('clickpay');
            $table->string('gateway_tran_ref')->nullable()->index(); // tran_ref من ClickPay
            $table->string('token')->nullable();

            $table->decimal('amount', 18, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('status', 32)->nullable();         // approved/declined/pending
            $table->string('result_code', 32)->nullable();    // response_code
            $table->string('result_message', 255)->nullable();// response_message

            $table->json('payload')->nullable();              // كامل رد ClickPay (IPN)
            $table->timestamps();

            // لمنع التكرارات من نفس tran_ref
            $table->unique(['gateway', 'gateway_tran_ref']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('payment_transactions');
    }
};