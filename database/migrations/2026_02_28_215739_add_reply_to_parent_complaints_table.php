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
        Schema::table('parent_complaints', function (Blueprint $table) {
            $table->text('reply')->nullable()->after('complain');
            $table->timestamp('reply_date')->nullable()->after('reply');
            $table->boolean('sms_notification')->default(false)->after('reply_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_complaints', function (Blueprint $table) {
            $table->dropColumn(['reply', 'reply_date', 'sms_notification']);
        });
    }
};
