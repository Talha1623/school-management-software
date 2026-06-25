<?php

use App\Models\Loan;
use App\Services\StaffLoanRepaymentService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (! Schema::hasColumn('loans', 'initial_approved_amount')) {
                $table->decimal('initial_approved_amount', 12, 2)->nullable()->after('approved_amount');
            }
        });

        Loan::query()->each(function (Loan $loan) {
            if ($loan->initial_approved_amount !== null) {
                return;
            }

            $loan->initial_approved_amount = $loan->approved_amount ?? $loan->requested_amount ?? 0;
            $loan->saveQuietly();
        });

        app(StaffLoanRepaymentService::class)->syncAllLoanBalances();
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'initial_approved_amount')) {
                $table->dropColumn('initial_approved_amount');
            }
        });
    }
};
