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
        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('enable_website', ['Yes', 'No'])->default('No');
            $table->text('about_us')->nullable();
            $table->string('school_timing')->nullable();
            $table->text('welcome_text')->nullable();
            $table->string('school_email')->nullable();
            $table->string('twitter_link')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('facebook_page')->nullable();
            $table->string('slider_title')->nullable();
            $table->string('slider_sub_title')->nullable();
            $table->text('slider_details')->nullable();
            $table->string('welcome_feature_title_one')->nullable();
            $table->text('welcome_feature_details_one')->nullable();
            $table->string('welcome_feature_title_two')->nullable();
            $table->text('welcome_feature_details_two')->nullable();
            $table->string('welcome_feature_title_three')->nullable();
            $table->text('welcome_feature_details_three')->nullable();
            $table->string('welcome_feature_title_four')->nullable();
            $table->text('welcome_feature_details_four')->nullable();
            $table->text('about_school')->nullable();
            $table->text('classes_text')->nullable();
            $table->string('students_enrolled')->nullable();
            $table->string('classes_completed')->nullable();
            $table->string('awards_won')->nullable();
            $table->string('courses_completed')->nullable();
            $table->text('school_facilities_text')->nullable();
            $table->string('facilities_one_title')->nullable();
            $table->text('facilities_one_text')->nullable();
            $table->string('facilities_two_title')->nullable();
            $table->text('facilities_two_text')->nullable();
            $table->string('facilities_three_title')->nullable();
            $table->text('facilities_three_text')->nullable();
            $table->text('school_gallery_text')->nullable();
            $table->text('school_noticeboard_text')->nullable();
            $table->string('principal_message_title')->nullable();
            $table->text('principal_message_text')->nullable();
            $table->text('google_map_embed')->nullable();
            $table->text('picture_gallery_settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};
