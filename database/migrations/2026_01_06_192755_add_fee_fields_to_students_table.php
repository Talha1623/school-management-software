<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->decimal('transport_fare', 10, 2)->nullable()->after('transport_route');
            $table->string('generate_admission_fee')->nullable()->after('create_parent_account');
            $table->decimal('admission_fee_amount', 10, 2)->nullable()->after('generate_admission_fee');
            $table->string('fee_type')->nullable()->after('generate_other_fee');
            $table->decimal('other_fee_amount', 10, 2)->nullable()->after('fee_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['transport_fare', 'generate_admission_fee', 'admission_fee_amount', 'fee_type', 'other_fee_amount']);
        });
    }
};
