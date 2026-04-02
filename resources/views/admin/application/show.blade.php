
@extends('admin.layouts.master')
@section('title', $title)

@section('page_css')
    <link rel="stylesheet" href="{{ asset('dashboard/plugins/lightbox2-master/css/lightbox.min.css') }}">
@endsection

@section('content')

@php
    // Define the field() helper function
    function field($slug) {
        return \App\Models\Field::field($slug);
    }
@endphp

<div class="main-body">
    <div class="page-wrapper">
        <div class="row">

            <!-- Left Sidebar - User Info -->
            <div class="col-md-3">
                <div class="card user-card user-card-1">
                    <div class="card-body pb-0">
                        <div class="media user-about-block align-items-center mt-0 mb-3">
                            <div class="position-relative d-inline-block">
                                @if(is_file(public_path('uploads/'.$path.'/'.$row->photo)))
                                    <img src="{{ asset('uploads/'.$path.'/'.$row->photo) }}" 
                                         class="img-radius img-fluid wid-80" 
                                         alt="Photo"
                                         onerror="this.src='{{ asset('dashboard/images/user/avatar-2.jpg') }}';">
                                @else
                                    <img src="{{ asset('dashboard/images/user/avatar-2.jpg') }}" 
                                         class="img-radius img-fluid wid-80" alt="Photo">
                                @endif
                                <div class="certificated-badge">
                                    <i class="fas fa-certificate text-primary bg-icon"></i>
                                    <i class="fas fa-check front-icon text-white"></i>
                                </div>
                            </div>
                            <div class="media-body ms-3">
                                <h6 class="mb-1">{{ $row->first_name }} {{ $row->last_name }}</h6>
                                @if($row->registration_no)
                                    <p class="mb-0 text-muted">#{{ $row->registration_no }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <span class="f-w-500"><i class="far fa-envelope m-r-10"></i>{{ __('field_email') }} :</span>
                            <span class="float-end">{{ $row->email ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item">
                            <span class="f-w-500"><i class="fas fa-phone-alt m-r-10"></i>{{ __('field_phone') }} :</span>
                            <span class="float-end">{{ $row->phone ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item">
                            <span class="f-w-500"><i class="fas fa-graduation-cap m-r-10"></i>{{ __('field_program') }} :</span>
                            <span class="float-end">{{ $row->program->title ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item">
                            <span class="f-w-500"><i class="far fa-calendar-alt m-r-10"></i>{{ __('field_apply_date') }} :</span>
                            <span class="float-end">
                                {{ isset($setting->date_format) 
                                    ? date($setting->date_format, strtotime($row->apply_date)) 
                                    : date('Y-m-d', strtotime($row->apply_date)) }}
                            </span>
                        </li>
                        <li class="list-group-item border-bottom-0">
                            <span class="f-w-500"><i class="far fa-question-circle m-r-10"></i>{{ __('field_status') }} :</span>
                            <span class="float-end">
                                @if($row->status == 1)
                                    <span class="badge badge-pill badge-primary">{{ __('status_pending') }}</span>
                                @elseif($row->status == 2)
                                    <span class="badge badge-pill badge-success">{{ __('status_approved') }}</span>
                                @else
                                    <span class="badge badge-pill badge-danger">{{ __('status_rejected') }}</span>
                                @endif
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-block">
                        <div class="row gx-2">

                            <!-- Left Column - Personal Info -->
                            <div class="col-md-4">
                                @if(field('application_father_name')->status == 1 || field('application_father_occupation')->status == 1 || 
                                    field('application_mother_name')->status == 1 || field('application_mother_occupation')->status == 1)
                                    <fieldset class="row scheduler-border">
                                        @if(field('application_father_name')->status == 1)
                                            <p><mark class="text-primary">{{ __('field_father_name') }}:</mark> {{ $row->father_name ?? 'N/A' }}</p><hr/>
                                        @endif
                                        @if(field('application_father_occupation')->status == 1)
                                            <p><mark class="text-primary">{{ __('field_father_occupation') }}:</mark> {{ $row->father_occupation ?? 'N/A' }}</p><hr/>
                                        @endif
                                        @if(field('application_mother_name')->status == 1)
                                            <p><mark class="text-primary">{{ __('field_mother_name') }}:</mark> {{ $row->mother_name ?? 'N/A' }}</p><hr/>
                                        @endif
                                        @if(field('application_mother_occupation')->status == 1)
                                            <p><mark class="text-primary">{{ __('field_mother_occupation') }}:</mark> {{ $row->mother_occupation ?? 'N/A' }}</p><hr/>
                                        @endif>

                                        <p><mark class="text-primary">{{ __('field_gender') }}:</mark> 
                                            @switch($row->gender)
                                                @case(1) {{ __('gender_male') }} @break
                                                @case(2) {{ __('gender_female') }} @break
                                                @case(3) {{ __('gender_other') }} @break
                                                @default N/A
                                            @endswitch
                                        </p><hr/>

                                        <p><mark class="text-primary">{{ __('field_dob') }}:</mark> 
                                            {{ isset($setting->date_format) 
                                                ? date($setting->date_format, strtotime($row->dob)) 
                                                : date('Y-m-d', strtotime($row->dob)) }}
                                        </p><hr/>

                                        @if(field('application_emergency_phone')->status == 1)
                                            <p><mark class="text-primary">{{ __('field_emergency_phone') }}:</mark> {{ $row->emergency_phone ?? 'N/A' }}</p><hr/>
                                        @endif
                                    </fieldset>
                                @endif

                                @if(field('application_signature')->status == 1 && !empty($row->signature))
                                    <fieldset class="scheduler-border">
                                        <a href="{{ asset('uploads/'.$path.'/'.$row->signature) }}" data-lightbox="signature">
                                            <img src="{{ asset('uploads/'.$path.'/'.$row->signature) }}" class="img-fluid" style="max-height: 140px;">
                                        </a>
                                    </fieldset>
                                @endif
                            </div>

                            <!-- Address Column -->
                          
                            <div class="col-md-4">
                                @if(field('application_address')->status == 1)
                                    
                                    <!-- Present Address -->
                                    <fieldset class="scheduler-border">
                                        <legend>{{ __('field_present') }} {{ __('field_address') }}</legend>
                                        
                                        <p><mark class="text-primary">{{ __('field_country') }}:</mark> 
                                            {{ $row->present_country?->name ?? 'N/A' }}
                                        </p><hr/>
                                        
                                        <p><mark class="text-primary">{{ __('field_state') }} / {{ __('field_province') }}:</mark> 
                                            {{ $row->present_province?->name ?? 'N/A' }}
                                        </p><hr/>
                                        
                                        <p><mark class="text-primary">{{ __('field_city') }} / {{ __('field_district') }}:</mark> 
                                            {{ $row->present_district?->name ?? 'N/A' }}
                                        </p><hr/>
                                        
                                        <p><mark class="text-primary">{{ __('field_address') }}:</mark> 
                                            {{ $row->present_address ?? 'N/A' }}
                                        </p>
                                    </fieldset>

                                    <!-- Permanent Address -->
                                    <fieldset class="scheduler-border">
                                        <legend>{{ __('field_permanent') }} {{ __('field_address') }}</legend>
                                        
                                        <p><mark class="text-primary">{{ __('field_country') }}:</mark> 
                                            {{ $row->permanent_country?->name ?? 'N/A' }}
                                        </p><hr/>
                                        
                                        <p><mark class="text-primary">{{ __('field_province') }}:</mark> 
                                            {{ $row->permanent_province?->name ?? 'N/A' }}
                                        </p><hr/>
                                        
                                        <p><mark class="text-primary">{{ __('field_district') }}:</mark> 
                                            {{ $row->permanent_district?->name ?? 'N/A' }}
                                        </p><hr/>
                                        
                                        <p><mark class="text-primary">{{ __('field_address') }}:</mark> 
                                            {{ $row->permanent_address ?? 'N/A' }}
                                        </p>
                                    </fieldset>

                                @endif
                            </div>
                            <!-- School & College -->
                            <div class="col-md-4">
                                @if(field('application_school_info')->status == 1)
                                    <fieldset class="scheduler-border">
                                        <legend>{{ __('field_school_information') }}</legend>
                                        <p><mark class="text-primary">{{ __('field_school_name') }}:</mark> {{ $row->school_name ?? 'N/A' }}</p><hr/>
                                        <p><mark class="text-primary">{{ __('field_exam_id') }}:</mark> {{ $row->school_exam_id ?? 'N/A' }}</p><hr/>
                                        <p><mark class="text-primary">{{ __('field_graduation_year') }}:</mark> {{ $row->school_graduation_year ?? 'N/A' }}</p><hr/>
                                        <p><mark class="text-primary">{{ __('field_graduation_point') }}:</mark> {{ $row->school_graduation_point ?? 'N/A' }}</p>
                                    </fieldset>
                                @endif

                                @if(field('application_collage_info')->status == 1)
                                    <fieldset class="scheduler-border">
                                        <legend>{{ __('field_college_information') }}</legend>
                                        <p><mark class="text-primary">{{ __('field_collage_name') }}:</mark> {{ $row->collage_name ?? 'N/A' }}</p><hr/>
                                        <p><mark class="text-primary">{{ __('field_exam_id') }}:</mark> {{ $row->collage_exam_id ?? 'N/A' }}</p><hr/>
                                        <p><mark class="text-primary">{{ __('field_graduation_year') }}:</mark> {{ $row->collage_graduation_year ?? 'N/A' }}</p><hr/>
                                        <p><mark class="text-primary">{{ __('field_graduation_point') }}:</mark> {{ $row->collage_graduation_point ?? 'N/A' }}</p>
                                    </fieldset>
                                @endif
                            </div>
                        </div>

<!-- ====================== DOCUMENTS SECTION ====================== -->
<div class="row mt-4">
    <h5 class="mb-3">{{ __('field_documents') }}</h5>

    @php
        $documentFields = [
            'school_transcript'   => __('field_school_transcript'),
            'school_certificate'  => __('field_school_certificate'),
            'collage_transcript'  => __('field_collage_transcript'),
            'collage_certificate' => __('field_collage_certificate'),
        ];
    @endphp

    <div class="row g-4">
        @foreach($documentFields as $key => $label)
            @if(!empty($row->{$key}))
                @php
                    $fileName = $row->{$key};
                    $filePath = 'uploads/' . $path . '/' . $fileName;
                    $fullPath = public_path($filePath);
                    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                @endphp

                @if(file_exists($fullPath))
                    <div class="col-md-3 col-sm-6 text-center">
                        @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']))
                            <!-- Image -->
                            <a href="{{ asset($filePath) }}" data-lightbox="gallery" data-title="{{ $label }}">
                                <img src="{{ asset($filePath) }}" 
                                     class="img-fluid rounded border shadow-sm" 
                                     style="max-height: 180px; object-fit: contain;">
                            </a>
                        @elseif($extension === 'pdf')
                            <!-- PDF File -->
                            <a href="{{ asset($filePath) }}" target="_blank" class="text-decoration-none">
                                <i class="fas fa-file-pdf fa-5x text-danger mb-3"></i>
                                <p class="fw-bold text-dark mb-1">{{ $label }}</p>
                            </a>
                        @else
                            <!-- Other files (doc, zip, etc.) -->
                            <a href="{{ asset($filePath) }}" target="_blank" class="text-decoration-none">
                                <i class="fas fa-file fa-5x text-secondary mb-3"></i>
                                <p class="fw-bold text-dark mb-1">{{ $label }}</p>
                            </a>
                        @endif

                        <small class="text-muted d-block">{{ $fileName }}</small>

                       
                    </div>
                @endif
            @endif
        @endforeach
    </div>
</div>
</div>

    </div>
</div>

@endsection

@section('page_js')
<script src="{{ asset('dashboard/plugins/lightbox2-master/js/lightbox.min.js') }}"></script>
@endsection