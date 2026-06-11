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
        if (!Schema::hasTable('parent_complaints')) {
            return;
        }

        Schema::table('parent_complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('parent_complaints', 'reply')) {
                $table->text('reply')->nullable()->after('complain');
            }

            if (!Schema::hasColumn('parent_complaints', 'reply_date')) {
                $table->timestamp('reply_date')->nullable()->after('reply');
            }

            if (!Schema::hasColumn('parent_complaints', 'sms_notification')) {
                $table->boolean('sms_notification')->default(false)->after('reply_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('parent_complaints')) {
            return;
        }

        Schema::table('parent_complaints', function (Blueprint $table) {
            if (Schema::hasColumn('parent_complaints', 'sms_notification')) {
                $table->dropColumn('sms_notification');
            }
            if (Schema::hasColumn('parent_complaints', 'reply_date')) {
                $table->dropColumn('reply_date');
            }
            if (Schema::hasColumn('parent_complaints', 'reply')) {
                $table->dropColumn('reply');
            }
        });
    }
};
