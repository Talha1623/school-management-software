@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'General & Gallery Setting')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">settings</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">General & Gallery Setting</h3>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 20px;">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 20px;">error</span>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <!-- Column 1: General Settings -->
                <div class="col-md-6">
                    <form method="POST" action="{{ route('website-management.general-gallery.store') }}">
                        @csrf
                        <div class="card border-0 shadow-sm rounded-10 p-4 mb-4">
                            <div class="mb-4 p-3 rounded-8" style="background: #003471;">
                                <h5 class="mb-0 fw-semibold text-white d-flex align-items-center gap-2">
                                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">settings</span>
                                    <span style="color: white;">General Settings</span>
                                </h5>
                            </div>

                            <!-- Enable Website -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Enable Website</label>
                                <select class="form-select" name="enable_website" required style="font-size: 13px; padding: 6px 12px; height: auto;">
                                    <option value="Yes" {{ old('enable_website', $settings->enable_website ?? 'No') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ old('enable_website', $settings->enable_website ?? 'No') == 'No' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>

                            <!-- About Us -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">About Us</label>
                                <textarea class="form-control" name="about_us" rows="2" placeholder="Enter about message" style="font-size: 13px; padding: 6px 12px;">{{ old('about_us', $settings->about_us ?? '') }}</textarea>
                            </div>

                            <!-- School Timing -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">School Timing</label>
                                <input type="text" class="form-control" name="school_timing" placeholder="Enter school timing" value="{{ old('school_timing', $settings->school_timing ?? '') }}" style="font-size: 13px; padding: 6px 12px; height: auto;">
                            </div>

                            <!-- Welcome Text -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Welcome Text</label>
                                <textarea class="form-control" name="welcome_text" rows="2" placeholder="Enter welcome text" style="font-size: 13px; padding: 6px 12px;">{{ old('welcome_text', $settings->welcome_text ?? '') }}</textarea>
                            </div>

                            <!-- School Email -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">School Email</label>
                                <input type="email" class="form-control" name="school_email" placeholder="Enter school email" value="{{ old('school_email', $settings->school_email ?? '') }}" style="font-size: 13px; padding: 6px 12px; height: auto;">
                            </div>

                            <!-- Twitter Link -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Twitter Link</label>
                                <input type="url" class="form-control" name="twitter_link" placeholder="Enter Twitter link" value="{{ old('twitter_link', $settings->twitter_link ?? '') }}" style="font-size: 13px; padding: 6px 12px; height: auto;">
                            </div>

                            <!-- Contact Number -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Contact Number</label>
                                <input type="text" class="form-control" name="contact_number" placeholder="Enter contact number" value="{{ old('contact_number', $settings->contact_number ?? '') }}" style="font-size: 13px; padding: 6px 12px; height: auto;">
                            </div>

                            <!-- Facebook Page -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Facebook Page</label>
                                <input type="url" class="form-control" name="facebook_page" placeholder="Enter Facebook page URL" value="{{ old('facebook_page', $settings->facebook_page ?? '') }}" style="font-size: 13px; padding: 6px 12px; height: auto;">
                            </div>

                            <!-- Slider Title -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Slider Title</label>
                                <input type="text" class="form-control" name="slider_title" placeholder="Enter slider title" value="{{ old('slider_title', $settings->slider_title ?? '') }}" style="font-size: 13px; padding: 6px 12px; height: auto;">
                            </div>

                            <!-- Slider Sub Title -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Slider Sub Title</label>
                                <input type="text" class="form-control" name="slider_sub_title" placeholder="Enter slider sub title" value="{{ old('slider_sub_title', $settings->slider_sub_title ?? '') }}" style="font-size: 13px; padding: 6px 12px; height: auto;">
                            </div>

                            <!-- Slider Details -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Slider Details</label>
                                <textarea class="form-control" name="slider_details" rows="3" placeholder="Enter slider details">{{ old('slider_details', $settings->slider_details ?? '') }}</textarea>
                            </div>

                            <!-- Welcome Feature Title One -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Welcome Feature Title One</label>
                                <input type="text" class="form-control" name="welcome_feature_title_one" placeholder="Enter welcome feature title one" value="{{ old('welcome_feature_title_one', $settings->welcome_feature_title_one ?? '') }}">
                            </div>

                            <!-- Welcome Feature Details One -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Welcome Feature Details One</label>
                                <textarea class="form-control" name="welcome_feature_details_one" rows="2" placeholder="Enter welcome feature details one">{{ old('welcome_feature_details_one', $settings->welcome_feature_details_one ?? '') }}</textarea>
                            </div>

                            <!-- Welcome Feature Title Two -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Welcome Feature Title Two</label>
                                <input type="text" class="form-control" name="welcome_feature_title_two" placeholder="Enter welcome feature title two" value="{{ old('welcome_feature_title_two', $settings->welcome_feature_title_two ?? '') }}">
                            </div>

                            <!-- Welcome Feature Details Two -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Welcome Feature Details Two</label>
                                <textarea class="form-control" name="welcome_feature_details_two" rows="2" placeholder="Enter welcome feature details two">{{ old('welcome_feature_details_two', $settings->welcome_feature_details_two ?? '') }}</textarea>
                            </div>

                            <!-- Welcome Feature Title Three -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Welcome Feature Title Three</label>
                                <input type="text" class="form-control" name="welcome_feature_title_three" placeholder="Enter welcome feature title three" value="{{ old('welcome_feature_title_three', $settings->welcome_feature_title_three ?? '') }}">
                            </div>

                            <!-- Welcome Feature Details Three -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Welcome Feature Details Three</label>
                                <textarea class="form-control" name="welcome_feature_details_three" rows="2" placeholder="Enter welcome feature details three">{{ old('welcome_feature_details_three', $settings->welcome_feature_details_three ?? '') }}</textarea>
                            </div>

                            <!-- Welcome Feature Title Four -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Welcome Feature Title Four</label>
                                <input type="text" class="form-control" name="welcome_feature_title_four" placeholder="Enter welcome feature title four" value="{{ old('welcome_feature_title_four', $settings->welcome_feature_title_four ?? '') }}">
                            </div>

                            <!-- Welcome Feature Details Four -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Welcome Feature Details Four</label>
                                <textarea class="form-control" name="welcome_feature_details_four" rows="2" placeholder="Enter welcome feature details four">{{ old('welcome_feature_details_four', $settings->welcome_feature_details_four ?? '') }}</textarea>
                            </div>

                            <!-- About School -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">About School</label>
                                <textarea class="form-control" name="about_school" rows="3" placeholder="Enter about school">{{ old('about_school', $settings->about_school ?? '') }}</textarea>
                            </div>

                            <!-- Classes Text -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Classes Text</label>
                                <textarea class="form-control" name="classes_text" rows="2" placeholder="Enter classes text">{{ old('classes_text', $settings->classes_text ?? '') }}</textarea>
                            </div>

                            <!-- Students Enrolled -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Students Enrolled</label>
                                <input type="text" class="form-control" name="students_enrolled" placeholder="Enter students enrolled" value="{{ old('students_enrolled', $settings->students_enrolled ?? '') }}">
                            </div>

                            <!-- Classes Completed -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Classes Completed</label>
                                <input type="text" class="form-control" name="classes_completed" placeholder="Enter classes completed" value="{{ old('classes_completed', $settings->classes_completed ?? '') }}">
                            </div>

                            <!-- Awards Won -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Awards Won</label>
                                <input type="text" class="form-control" name="awards_won" placeholder="Enter awards won" value="{{ old('awards_won', $settings->awards_won ?? '') }}">
                            </div>

                            <!-- Courses Completed -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Courses Completed</label>
                                <input type="text" class="form-control" name="courses_completed" placeholder="Enter courses completed" value="{{ old('courses_completed', $settings->courses_completed ?? '') }}">
                            </div>

                            <!-- School Facilities Text -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">School Facilities Text</label>
                                <textarea class="form-control" name="school_facilities_text" rows="2" placeholder="Enter school facilities text">{{ old('school_facilities_text', $settings->school_facilities_text ?? '') }}</textarea>
                            </div>

                            <!-- Facilities One Title -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Facilities One Title</label>
                                <input type="text" class="form-control" name="facilities_one_title" placeholder="Enter facilities one title" value="{{ old('facilities_one_title', $settings->facilities_one_title ?? '') }}">
                            </div>

                            <!-- Facilities One Text -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Facilities One Text</label>
                                <textarea class="form-control" name="facilities_one_text" rows="2" placeholder="Enter facilities one text">{{ old('facilities_one_text', $settings->facilities_one_text ?? '') }}</textarea>
                            </div>

                            <!-- Facilities Two Title -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Facilities Two Title</label>
                                <input type="text" class="form-control" name="facilities_two_title" placeholder="Enter facilities two title" value="{{ old('facilities_two_title', $settings->facilities_two_title ?? '') }}">
                            </div>

                            <!-- Facilities Two Text -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Facilities Two Text</label>
                                <textarea class="form-control" name="facilities_two_text" rows="2" placeholder="Enter facilities two text">{{ old('facilities_two_text', $settings->facilities_two_text ?? '') }}</textarea>
                            </div>

                            <!-- Facilities Three Title -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Facilities Three Title</label>
                                <input type="text" class="form-control" name="facilities_three_title" placeholder="Enter facilities three title" value="{{ old('facilities_three_title', $settings->facilities_three_title ?? '') }}">
                            </div>

                            <!-- Facilities Three Text -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Facilities Three Text</label>
                                <textarea class="form-control" name="facilities_three_text" rows="2" placeholder="Enter facilities three text">{{ old('facilities_three_text', $settings->facilities_three_text ?? '') }}</textarea>
                            </div>

                            <!-- School Gallery Text -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">School Gallery Text</label>
                                <textarea class="form-control" name="school_gallery_text" rows="2" placeholder="Enter school gallery text">{{ old('school_gallery_text', $settings->school_gallery_text ?? '') }}</textarea>
                            </div>

                            <!-- School Noticeboard Text -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">School Noticeboard Text</label>
                                <textarea class="form-control" name="school_noticeboard_text" rows="2" placeholder="Enter school noticeboard text">{{ old('school_noticeboard_text', $settings->school_noticeboard_text ?? '') }}</textarea>
                            </div>

                            <!-- Principal Message Title -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Principal Message Title</label>
                                <input type="text" class="form-control" name="principal_message_title" placeholder="Enter principal message title" value="{{ old('principal_message_title', $settings->principal_message_title ?? '') }}">
                            </div>

                            <!-- Principal Message Text -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Principal Message Text</label>
                                <textarea class="form-control" name="principal_message_text" rows="3" placeholder="Enter principal message text">{{ old('principal_message_text', $settings->principal_message_text ?? '') }}</textarea>
                            </div>

                            <!-- Google Map Embed -->
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-1" style="color: #003471;">Google Map Embed</label>
                                <textarea class="form-control" name="google_map_embed" rows="4" placeholder="Enter Google Map embed code">{{ old('google_map_embed', $settings->google_map_embed ?? '') }}</textarea>
                                <small class="text-muted">Paste the iframe embed code from Google Maps</small>
                            </div>
                        </div>
                        <!-- Submit Button -->
                        <div class="d-flex justify-content-end gap-2 mb-4">
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-8" style="background: #003471; border-color: #003471; color: white;">
                                <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle; color: white;">save</span>
                                <span style="color: white;">Save Settings</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Column 2: Picture Gallery Settings -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-10 p-4 mb-4">
                        <div class="mb-4 p-3 rounded-8" style="background: #003471;">
                            <h5 class="mb-0 fw-semibold text-white d-flex align-items-center gap-2">
                                <span class="material-symbols-outlined" style="font-size: 20px; color: white;">photo_library</span>
                                <span style="color: white;">Picture Gallery Settings</span>
                            </h5>
                        </div>

                        <!-- Gallery Images Display -->
                        <div class="mb-4">
                            <label class="form-label fw-medium mb-2" style="color: #003471;">
                                Gallery Images 
                                @if(isset($galleryImages) && $galleryImages->count() > 0)
                                    <span class="badge bg-primary">({{ $galleryImages->count() }} images)</span>
                                @endif
                            </label>
                            @if(isset($galleryImages) && $galleryImages->count() > 0)
                                <div class="row g-2 mb-3" id="galleryImagesContainer">
                                    @foreach($galleryImages as $image)
                                        <div class="col-md-4 gallery-image-item" data-id="{{ $image->id }}">
                                            <div class="position-relative border rounded p-2" style="background: #f8f9fa;">
                                                <img src="{{ asset('storage/' . $image->image_path) }}" alt="Gallery Image" class="img-fluid rounded" style="width: 100%; height: 150px; object-fit: cover;" onerror="this.src='{{ asset('assets/images/placeholder.jpg') }}'; this.onerror=null;">
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="deleteGalleryImage({{ $image->id }})" title="Delete">
                                                    <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted py-4 border rounded mb-3" style="background: #f8f9fa;">
                                    <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">photo_library</span>
                                    <p class="mt-2 mb-0">No gallery images uploaded yet</p>
                                </div>
                            @endif
                        </div>

                        <!-- Upload Gallery Images -->
                        <div class="mb-3">
                            @if($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="material-symbols-outlined" style="font-size: 20px;">error</span>
                                        <div>
                                            @foreach($errors->all() as $error)
                                                <div>{{ $error }}</div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            <form action="{{ route('website-management.gallery-images.upload') }}" method="POST" enctype="multipart/form-data" id="galleryUploadForm">
                                @csrf
                                <label class="form-label fw-medium mb-2" style="color: #003471;">Choose Images</label>
                                <input type="file" class="form-control @error('gallery_images') is-invalid @enderror @error('gallery_images.*') is-invalid @enderror" name="gallery_images[]" id="gallery_images" multiple accept="image/*" onchange="previewImages(event)">
                                <small class="text-muted d-block mt-1 mb-2">You can select multiple images. Max size: 5MB per image</small>
                                <button type="submit" class="btn btn-primary btn-sm" style="background: #003471; border-color: #003471; color: white;">
                                    <span class="material-symbols-outlined me-1" style="font-size: 16px; vertical-align: middle; color: white;">upload</span>
                                    <span style="color: white;">Upload Images</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Upload Slider Background Image -->
                    <div class="card border-0 shadow-sm rounded-10 p-4 mb-4">
                        <div class="mb-4 p-3 rounded-8" style="background: #003471;">
                            <h5 class="mb-0 fw-semibold text-white d-flex align-items-center gap-2">
                                <span class="material-symbols-outlined" style="font-size: 20px; color: white;">image</span>
                                <span style="color: white;">Upload Slider Background Image</span>
                            </h5>
                        </div>

                        <!-- Slider Background Image Display -->
                        <div class="mb-4">
                            @if($settings->slider_background_image)
                                <div class="mb-3">
                                    <label class="form-label fw-medium mb-2" style="color: #003471;">Current Slider Background Image</label>
                                    <div class="position-relative border rounded p-2" style="background: #f8f9fa;">
                                        <img src="{{ asset('storage/' . $settings->slider_background_image) }}" alt="Slider Background" class="img-fluid rounded" style="width: 100%; max-height: 200px; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="deleteSliderBackground()" title="Delete">
                                            <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-muted py-4 border rounded mb-3" style="background: #f8f9fa;">
                                    <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">image</span>
                                    <p class="mt-2 mb-0">No slider background image uploaded yet</p>
                                </div>
                            @endif
                        </div>

                        <!-- Upload Slider Background Image Form -->
                        <div class="mb-3">
                            <form action="{{ route('website-management.slider-background.upload') }}" method="POST" enctype="multipart/form-data" id="sliderBackgroundForm">
                                @csrf
                                <label class="form-label fw-medium mb-2" style="color: #003471;">Choose Slider Background Image</label>
                                <input type="file" class="form-control @error('slider_background_image') is-invalid @enderror" name="slider_background_image" id="slider_background_image" accept="image/*">
                                <small class="text-muted d-block mt-1 mb-2">Max size: 5MB. Supported formats: JPEG, JPG, PNG, GIF</small>
                                <button type="submit" class="btn btn-primary btn-sm" style="background: #003471; border-color: #003471; color: white;">
                                    <span class="material-symbols-outlined me-1" style="font-size: 16px; vertical-align: middle; color: white;">upload</span>
                                    <span style="color: white;">Upload Slider Background</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Upload Principal Photo -->
                    <div class="card border-0 shadow-sm rounded-10 p-4 mb-4">
                        <div class="mb-4 p-3 rounded-8" style="background: #003471;">
                            <h5 class="mb-0 fw-semibold text-white d-flex align-items-center gap-2">
                                <span class="material-symbols-outlined" style="font-size: 20px; color: white;">person</span>
                                <span style="color: white;">Upload Principal Photo</span>
                            </h5>
                        </div>

                        <!-- Principal Photo Display -->
                        <div class="mb-4">
                            @if($settings->principal_photo)
                                <div class="mb-3">
                                    <label class="form-label fw-medium mb-2" style="color: #003471;">Current Principal Photo</label>
                                    <div class="position-relative border rounded p-2" style="background: #f8f9fa;">
                                        <img src="{{ asset('storage/' . $settings->principal_photo) }}" alt="Principal Photo" class="img-fluid rounded" style="width: 100%; max-height: 200px; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="deletePrincipalPhoto()" title="Delete">
                                            <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-muted py-4 border rounded mb-3" style="background: #f8f9fa;">
                                    <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">person</span>
                                    <p class="mt-2 mb-0">No principal photo uploaded yet</p>
                                </div>
                            @endif
                        </div>

                        <!-- Upload Principal Photo Form -->
                        <div class="mb-3">
                            <form action="{{ route('website-management.principal-photo.upload') }}" method="POST" enctype="multipart/form-data" id="principalPhotoForm">
                                @csrf
                                <label class="form-label fw-medium mb-2" style="color: #003471;">Choose Principal Photo</label>
                                <input type="file" class="form-control @error('principal_photo') is-invalid @enderror" name="principal_photo" id="principal_photo" accept="image/*">
                                <small class="text-muted d-block mt-1 mb-2">Max size: 5MB. Supported formats: JPEG, JPG, PNG, GIF</small>
                                <button type="submit" class="btn btn-primary btn-sm" style="background: #003471; border-color: #003471; color: white;">
                                    <span class="material-symbols-outlined me-1" style="font-size: 16px; vertical-align: middle; color: white;">upload</span>
                                    <span style="color: white;">Upload Principal Photo</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .gallery-image-item {
        margin-bottom: 10px;
    }
    
    .gallery-image-item img {
        transition: transform 0.3s ease;
    }
    
    .gallery-image-item:hover img {
        transform: scale(1.05);
    }
    
    /* Compact form fields styling */
    .card input.form-control,
    .card .form-select {
        font-size: 13px !important;
        padding: 6px 12px !important;
        height: auto !important;
        line-height: 1.5 !important;
    }
    
    .card textarea.form-control {
        font-size: 13px !important;
        padding: 6px 12px !important;
        line-height: 1.5 !important;
        min-height: 60px !important;
    }
</style>

<script>
function deleteGalleryImage(imageId) {
    if (confirm('Are you sure you want to delete this image?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('website-management.gallery-images.delete', ':id') }}'.replace(':id', imageId);
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function previewImages(event) {
    const files = event.target.files;
    if (files.length > 0) {
        // You can add preview functionality here if needed
        console.log(files.length + ' image(s) selected');
    }
}

function deleteSliderBackground() {
    if (confirm('Are you sure you want to delete the slider background image?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('website-management.slider-background.delete') }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function deletePrincipalPhoto() {
    if (confirm('Are you sure you want to delete the principal photo?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('website-management.principal-photo.delete') }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
