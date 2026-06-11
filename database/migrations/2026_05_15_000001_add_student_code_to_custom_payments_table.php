<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('custom_payments')) {
            Schema::create('custom_payments', function (Blueprint $table) {
                $table->id();
                $table->string('campus')->nullable();
                $table->string('student_code')->nullable();
                $table->string('payment_title');
                $table->decimal('payment_amount', 12, 2)->default(0);
                $table->string('accountant')->nullable();
                $table->string('method');
                $table->string('notify_admin')->default('Yes');
                $table->date('payment_date')->nullable();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('custom_payments', 'student_code')) {
            Schema::table('custom_payments', function (Blueprint $table) {
                $table->string('student_code')->nullable()->after('campus');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('custom_payments') && Schema::hasColumn('custom_payments', 'student_code')) {
            Schema::table('custom_payments', function (Blueprint $table) {
                $table->dropColumn('student_code');
            });
        }
    }
};
