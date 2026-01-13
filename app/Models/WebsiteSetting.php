<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    use HasFactory;

    protected $table = 'website_settings';

    protected $fillable = [
        'enable_website',
        'about_us',
        'school_timing',
        'welcome_text',
        'school_email',
        'twitter_link',
        'contact_number',
        'facebook_page',
        'slider_title',
        'slider_sub_title',
        'slider_details',
        'welcome_feature_title_one',
        'welcome_feature_details_one',
        'welcome_feature_title_two',
        'welcome_feature_details_two',
        'welcome_feature_title_three',
        'welcome_feature_details_three',
        'welcome_feature_title_four',
        'welcome_feature_details_four',
        'about_school',
        'classes_text',
        'students_enrolled',
        'classes_completed',
        'awards_won',
        'courses_completed',
        'school_facilities_text',
        'facilities_one_title',
        'facilities_one_text',
        'facilities_two_title',
        'facilities_two_text',
        'facilities_three_title',
        'facilities_three_text',
        'school_gallery_text',
        'school_noticeboard_text',
        'principal_message_title',
        'principal_message_text',
        'google_map_embed',
        'picture_gallery_settings',
        'slider_background_image',
        'principal_photo',
    ];

    /**
     * Get the first (and only) website setting record.
     */
    public static function getSettings()
    {
        return self::first() ?? self::create([
            'enable_website' => 'No',
        ]);
    }
}
