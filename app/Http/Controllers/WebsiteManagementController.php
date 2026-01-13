<?php

namespace App\Http\Controllers;

use App\Models\WebsiteSetting;
use App\Models\GalleryImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WebsiteManagementController extends Controller
{
    /**
     * Display the General & Gallery Setting page.
     */
    public function generalGallery(): View
    {
        $settings = WebsiteSetting::getSettings();
        $galleryImages = GalleryImage::orderBy('display_order')->orderBy('created_at', 'desc')->get() ?? collect();
        return view('website-management.general-gallery', compact('settings', 'galleryImages'));
    }

    /**
     * Store or update website settings.
     */
    public function storeGeneralGallery(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enable_website' => ['required', 'in:Yes,No'],
            'about_us' => ['nullable', 'string'],
            'school_timing' => ['nullable', 'string', 'max:255'],
            'welcome_text' => ['nullable', 'string'],
            'school_email' => ['nullable', 'email', 'max:255'],
            'twitter_link' => ['nullable', 'url', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'facebook_page' => ['nullable', 'url', 'max:255'],
            'slider_title' => ['nullable', 'string', 'max:255'],
            'slider_sub_title' => ['nullable', 'string', 'max:255'],
            'slider_details' => ['nullable', 'string'],
            'welcome_feature_title_one' => ['nullable', 'string', 'max:255'],
            'welcome_feature_details_one' => ['nullable', 'string'],
            'welcome_feature_title_two' => ['nullable', 'string', 'max:255'],
            'welcome_feature_details_two' => ['nullable', 'string'],
            'welcome_feature_title_three' => ['nullable', 'string', 'max:255'],
            'welcome_feature_details_three' => ['nullable', 'string'],
            'welcome_feature_title_four' => ['nullable', 'string', 'max:255'],
            'welcome_feature_details_four' => ['nullable', 'string'],
            'about_school' => ['nullable', 'string'],
            'classes_text' => ['nullable', 'string'],
            'students_enrolled' => ['nullable', 'string', 'max:255'],
            'classes_completed' => ['nullable', 'string', 'max:255'],
            'awards_won' => ['nullable', 'string', 'max:255'],
            'courses_completed' => ['nullable', 'string', 'max:255'],
            'school_facilities_text' => ['nullable', 'string'],
            'facilities_one_title' => ['nullable', 'string', 'max:255'],
            'facilities_one_text' => ['nullable', 'string'],
            'facilities_two_title' => ['nullable', 'string', 'max:255'],
            'facilities_two_text' => ['nullable', 'string'],
            'facilities_three_title' => ['nullable', 'string', 'max:255'],
            'facilities_three_text' => ['nullable', 'string'],
            'school_gallery_text' => ['nullable', 'string'],
            'school_noticeboard_text' => ['nullable', 'string'],
            'principal_message_title' => ['nullable', 'string', 'max:255'],
            'principal_message_text' => ['nullable', 'string'],
            'google_map_embed' => ['nullable', 'string'],
            'picture_gallery_settings' => ['nullable', 'string'],
        ]);

        $settings = WebsiteSetting::getSettings();
        $settings->update($validated);

        return redirect()
            ->route('website-management.general-gallery')
            ->with('success', 'Website settings saved successfully!');
    }

    /**
     * Upload gallery image.
     */
    public function uploadGalleryImage(Request $request): RedirectResponse
    {
        $request->validate([
            'gallery_images' => ['required'],
            'gallery_images.*' => ['required', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'], // Max 5MB per image
        ], [
            'gallery_images.required' => 'Please select at least one image to upload.',
            'gallery_images.*.required' => 'Please select at least one image to upload.',
            'gallery_images.*.image' => 'The file must be an image.',
            'gallery_images.*.mimes' => 'The image must be a file of type: jpeg, jpg, png, gif.',
            'gallery_images.*.max' => 'The image may not be greater than 5MB.',
        ]);

        $uploadedCount = 0;
        if ($request->hasFile('gallery_images')) {
            $maxOrder = GalleryImage::max('display_order') ?? 0;
            foreach ($request->file('gallery_images') as $image) {
                try {
                    $imagePath = $image->store('gallery-images', 'public');
                    if ($imagePath) {
                        $maxOrder++;
                        
                        GalleryImage::create([
                            'image_path' => $imagePath,
                            'image_name' => $image->getClientOriginalName(),
                            'display_order' => $maxOrder,
                        ]);
                        $uploadedCount++;
                    }
                } catch (\Exception $e) {
                    \Log::error('Gallery image upload error: ' . $e->getMessage());
                    return redirect()
                        ->route('website-management.general-gallery')
                        ->with('error', 'Error uploading image: ' . $e->getMessage());
                }
            }
        }

        if ($uploadedCount > 0) {
            return redirect()
                ->route('website-management.general-gallery')
                ->with('success', $uploadedCount . ' gallery image(s) uploaded successfully!');
        } else {
            return redirect()
                ->route('website-management.general-gallery')
                ->with('error', 'No images were uploaded. Please try again.');
        }
    }

    /**
     * Delete gallery image.
     */
    public function deleteGalleryImage(GalleryImage $galleryImage): RedirectResponse
    {
        // Delete file from storage
        if ($galleryImage->image_path && Storage::disk('public')->exists($galleryImage->image_path)) {
            Storage::disk('public')->delete($galleryImage->image_path);
        }

        $galleryImage->delete();

        return redirect()
            ->route('website-management.general-gallery')
            ->with('success', 'Gallery image deleted successfully!');
    }

    /**
     * Upload slider background image.
     */
    public function uploadSliderBackground(Request $request): RedirectResponse
    {
        $request->validate([
            'slider_background_image' => ['required', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'], // Max 5MB
        ], [
            'slider_background_image.required' => 'Please select an image to upload.',
            'slider_background_image.image' => 'The file must be an image.',
            'slider_background_image.mimes' => 'The image must be a file of type: jpeg, jpg, png, gif.',
            'slider_background_image.max' => 'The image may not be greater than 5MB.',
        ]);

        $settings = WebsiteSetting::getSettings();

        // Delete old image if exists
        if ($settings->slider_background_image && Storage::disk('public')->exists($settings->slider_background_image)) {
            Storage::disk('public')->delete($settings->slider_background_image);
        }

        // Upload new image
        if ($request->hasFile('slider_background_image')) {
            $imagePath = $request->file('slider_background_image')->store('slider-backgrounds', 'public');
            $settings->update(['slider_background_image' => $imagePath]);
        }

        return redirect()
            ->route('website-management.general-gallery')
            ->with('success', 'Slider background image uploaded successfully!');
    }

    /**
     * Delete slider background image.
     */
    public function deleteSliderBackground(): RedirectResponse
    {
        $settings = WebsiteSetting::getSettings();

        // Delete image from storage
        if ($settings->slider_background_image && Storage::disk('public')->exists($settings->slider_background_image)) {
            Storage::disk('public')->delete($settings->slider_background_image);
        }

        $settings->update(['slider_background_image' => null]);

        return redirect()
            ->route('website-management.general-gallery')
            ->with('success', 'Slider background image deleted successfully!');
    }

    /**
     * Upload principal photo.
     */
    public function uploadPrincipalPhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'principal_photo' => ['required', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'], // Max 5MB
        ], [
            'principal_photo.required' => 'Please select an image to upload.',
            'principal_photo.image' => 'The file must be an image.',
            'principal_photo.mimes' => 'The image must be a file of type: jpeg, jpg, png, gif.',
            'principal_photo.max' => 'The image may not be greater than 5MB.',
        ]);

        $settings = WebsiteSetting::getSettings();

        // Delete old image if exists
        if ($settings->principal_photo && Storage::disk('public')->exists($settings->principal_photo)) {
            Storage::disk('public')->delete($settings->principal_photo);
        }

        // Upload new image
        if ($request->hasFile('principal_photo')) {
            $imagePath = $request->file('principal_photo')->store('principal-photos', 'public');
            $settings->update(['principal_photo' => $imagePath]);
        }

        return redirect()
            ->route('website-management.general-gallery')
            ->with('success', 'Principal photo uploaded successfully!');
    }

    /**
     * Delete principal photo.
     */
    public function deletePrincipalPhoto(): RedirectResponse
    {
        $settings = WebsiteSetting::getSettings();

        // Delete image from storage
        if ($settings->principal_photo && Storage::disk('public')->exists($settings->principal_photo)) {
            Storage::disk('public')->delete($settings->principal_photo);
        }

        $settings->update(['principal_photo' => null]);

        return redirect()
            ->route('website-management.general-gallery')
            ->with('success', 'Principal photo deleted successfully!');
    }
}
