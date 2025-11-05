@extends('layouts.app')

@section('title', 'Add Timetable')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Timetable</h3>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <form action="{{ route('timetable.store') }}" method="POST" id="timetable-form">
                @csrf
                
                <!-- First Row: Campus, Class, Section -->
                <div class="row mb-2">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Campus</h5>
                            
                            <div class="mb-1">
                                <label for="campus" class="form-label mb-0 fs-13 fw-medium">Campus <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="campus" name="campus" required style="height: 32px;">
                                    <option value="">Select Campus</option>
                                    <option value="Main Campus">Main Campus</option>
                                    <option value="Branch Campus 1">Branch Campus 1</option>
                                    <option value="Branch Campus 2">Branch Campus 2</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Class</h5>
                            
                            <div class="mb-1">
                                <label for="class" class="form-label mb-0 fs-13 fw-medium">Class <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="class" name="class" required style="height: 32px;">
                                    <option value="">Select Class</option>
                                    <option value="Nursery">Nursery</option>
                                    <option value="KG">KG</option>
                                    <option value="1st">1st</option>
                                    <option value="2nd">2nd</option>
                                    <option value="3rd">3rd</option>
                                    <option value="4th">4th</option>
                                    <option value="5th">5th</option>
                                    <option value="6th">6th</option>
                                    <option value="7th">7th</option>
                                    <option value="8th">8th</option>
                                    <option value="9th">9th</option>
                                    <option value="10th">10th</option>
                                    <option value="11th">11th</option>
                                    <option value="12th">12th</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Section</h5>
                            
                            <div class="mb-1">
                                <label for="section" class="form-label mb-0 fs-13 fw-medium">Section <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="section" name="section" required style="height: 32px;">
                                    <option value="">Select Section</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    </div>
                </div>
                
                <!-- Second Row: Subject, Day -->
                <div class="row mb-2">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Subject</h5>
                            
                            <div class="mb-1">
                                <label for="subject" class="form-label mb-0 fs-13 fw-medium">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm py-1" id="subject" name="subject" placeholder="Enter subject" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Day</h5>
                            
                            <div class="mb-1">
                                <label for="day" class="form-label mb-0 fs-13 fw-medium">Day <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="day" name="day" required style="height: 32px;">
                                    <option value="">Select Day</option>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Third Row: Starting Time, Ending Time -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Starting Time</h5>
                            
                            <div class="mb-1">
                                <label for="starting_time" class="form-label mb-0 fs-13 fw-medium">Starting Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control form-control-sm py-1" id="starting_time" name="starting_time" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Ending Time</h5>
                            
                            <div class="mb-1">
                                <label for="ending_time" class="form-label mb-0 fs-13 fw-medium">Ending Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control form-control-sm py-1" id="ending_time" name="ending_time" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-sm btn-secondary px-4 py-2">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-sm px-4 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                                Save Timetable
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    
    .form-label {
        color: #495057;
    }
</style>
@endsection
