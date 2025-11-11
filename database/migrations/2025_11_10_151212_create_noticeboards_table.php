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
        Schema::create('noticeboards', function (Blueprint $table) {
            $table->id();
            $table->string('campus')->nullable();
            $table->string('title');
            $table->text('notice')->nullable();
            $table->date('date');
            $table->string('image')->nullable();
            $table->string('show_on')->nullable(); // Comma-separated: uploads,website,mobile_app
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('noticeboards');
    }
};
