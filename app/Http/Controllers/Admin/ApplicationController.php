<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Crypt;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Flasher\Laravel\Facade\Flasher;
use Illuminate\Support\Facades\DB;
use App\Models\StudentRelative;
use App\Models\StudentEnroll;
use App\Models\EnrollSubject;
use Illuminate\Http\Request;
use App\Traits\FileUploader;
use App\Models\Application;
use App\Models\StatusType;
use App\Models\Province;
use App\Models\District;
use App\Models\Document;
use App\Models\Program;
use App\Models\Student;
use App\Models\Batch;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApplicationController extends Controller
{
    use FileUploader;

    protected $title, $route, $view, $path, $access;

    public function __construct()
    {
        $this->title = trans_choice('module_application', 1);
        $this->route = 'admin.application';
        $this->view = 'admin.application';
        $this->path = 'student';
        $this->access = 'application';

        $this->middleware('permission:' . $this->access . '-view|' . $this->access . '-create|' . $this->access . '-edit|' . $this->access . '-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:' . $this->access . '-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:' . $this->access . '-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:' . $this->access . '-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['route'] = $this->route;
        $data['view'] = $this->view;
        $data['path'] = $this->path;
        $data['access'] = $this->access;

        $data['selected_batch'] = $request->batch ?? '0';
        $data['selected_program'] = $request->program ?? '0';
        $data['selected_status'] = $request->status ?? '99';
        $data['selected_start_date'] = $request->start_date ?? date('Y-m-d', strtotime(Carbon::now()->subYear()));
        $data['selected_end_date'] = $request->end_date ?? date('Y-m-d');
        $data['selected_registration_no'] = $request->registration_no ?? null;

        $data['batches'] = Batch::where('status', '1')->orderBy('id', 'desc')->get();
        $data['programs'] = Program::where('status', '1')->orderBy('title', 'asc')->get();

        $applications = Application::whereDate('apply_date', '>=', $data['selected_start_date'])
            ->whereDate('apply_date', '<=', $data['selected_end_date']);

        if ($request->filled('batch')) {
            $applications->where('batch_id', $request->batch);
        }
        if ($request->filled('program')) {
            $applications->where('program_id', $request->program);
        }
        if ($request->filled('registration_no')) {
            $applications->where('registration_no', 'LIKE', '%' . $request->registration_no . '%');
        }
        if ($request->filled('status')) {
            $applications->where('status', $request->status);
        }

        $data['rows'] = $applications->orderBy('registration_no', 'desc')->get();

        return view($this->view . '.index', $data);
    }

    public function create()
    {
        throw new NotFoundHttpException();
    }

    public function store(Request $request)
    {
         
        $request->validate([
            'student_id' => 'required|unique:students,student_id',
            'batch' => 'required',
            'program' => 'required',
            'session' => 'required',
            'semester' => 'required',
            'section' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:students,email',
            'phone' => 'required',
            'gender' => 'required',
            'dob' => 'required|date',
            'admission_date' => 'required|date',
            'photo' => 'nullable|image',
            'signature' => 'nullable|image',
        ]);

        $password = str_random(8);
        $applicationData = Application::where('registration_no', $request->registration_no)->firstOrFail();

        try {
            DB::beginTransaction();

            $student = new Student();
            $student->fill($request->only([
                'student_id',
                'registration_no',
                'batch_id',
                'program_id',
                'admission_date',
                'first_name',
                'last_name',
                'father_name',
                'mother_name',
                'father_occupation',
                'mother_occupation',
                'email',
                'phone',
                'emergency_phone',
                'gender',
                'dob',
                'country',
                'present_province',
                'present_district',
                'present_village',
                'present_address',
                'permanent_province',
                'permanent_district',
                'permanent_village',
                'permanent_address',
                'religion',
                'caste',
                'mother_tongue',
                'marital_status',
                'blood_group',
                'nationality',
                'national_id',
                'passport_no',
                'school_name',
                'school_exam_id',
                'school_graduation_year',
                'school_graduation_point',
                'collage_name',
                'collage_exam_id',
                'collage_graduation_year',
                'collage_graduation_point'
            ]));

            $student->password = Hash::make($password);
            $student->password_text = Crypt::encryptString($password);
            $student->status = '1';
            $student->created_by = Auth::guard('web')->user()->id;

            // File Uploads
            // SCHOOL TRANSCRIPT
            if ($request->hasFile('school_transcript')) {
                $file = $this->uploadMedia($request, 'school_transcript', $this->path);
                $student->school_transcript = $file;
                $applicationData->school_transcript = $file;
            } else {
                $student->school_transcript = $applicationData->school_transcript;
            }

            // SCHOOL CERTIFICATE
            if ($request->hasFile('school_certificate')) {
                $file = $this->uploadMedia($request, 'school_certificate', $this->path);
                $student->school_certificate = $file;
                $applicationData->school_certificate = $file;
            } else {
                $student->school_certificate = $applicationData->school_certificate;
            }

            // COLLEGE TRANSCRIPT
            if ($request->hasFile('collage_transcript')) {
                $file = $this->uploadMedia($request, 'collage_transcript', $this->path);
                $student->collage_transcript = $file;
                $applicationData->collage_transcript = $file;
            } else {
                $student->collage_transcript = $applicationData->collage_transcript;
            }

            // COLLEGE CERTIFICATE
            if ($request->hasFile('collage_certificate')) {
                $file = $this->uploadMedia($request, 'collage_certificate', $this->path);
                $student->collage_certificate = $file;
                $applicationData->collage_certificate = $file;
            } else {
                $student->collage_certificate = $applicationData->collage_certificate;
            }


            $student->photo = $request->hasFile('photo') ? $this->uploadImage($request, 'photo', $this->path, 300, 300) : $applicationData->photo;
            $student->signature = $request->hasFile('signature') ? $this->uploadImage($request, 'signature', $this->path, 300, 100) : $applicationData->signature;

            $student->save();
            $applicationData->save();

          

            // Statuses
            if ($request->has('statuses')) {
                $student->statuses()->attach($request->statuses);
            }

            // Relatives
            if (is_array($request->relations)) {
                foreach ($request->relations as $key => $relation) {
                    if (!empty($relation)) {
                        StudentRelative::create([
                            'student_id' => $student->id,
                            'relation' => $request->relations[$key] ?? null,
                            'name' => $request->relative_names[$key] ?? null,
                            'occupation' => $request->occupations[$key] ?? null,
                            'phone' => $request->relative_phones[$key] ?? null,
                            'address' => $request->addresses[$key] ?? null,
                        ]);
                    }
                }
            }

            // Documents (optional)
            if ($request->hasFile('documents')) {
                // Your document upload logic here...
            }

            // Enroll
            $enroll = StudentEnroll::create([
                'student_id' => $student->id,
                'program_id' => $request->program,
                'session_id' => $request->input('session'),
                'semester_id' => $request->semester,
                'section_id' => $request->section,
                'created_by' => Auth::guard('web')->user()->id,
            ]);

            // Assign Subjects
            $enrollSubject = EnrollSubject::where('program_id', $request->program)
                ->where('semester_id', $request->semester)
                ->where('section_id', $request->section)
                ->first();

            if ($enrollSubject) {
                foreach ($enrollSubject->subjects as $subject) {
                    $enroll->subjects()->attach($subject->id);
                }
            }

            // Update Application Status
            $applicationData->update([
                'status' => '2',
                'updated_by' => Auth::guard('web')->user()->id
            ]);

            DB::commit();

            Flasher::addSuccess(__('msg_created_successfully'), __('msg_success'));
            return redirect()->route($this->route . '.index');

        } catch (\Exception $e) {
            DB::rollback();
            Flasher::addError(__('msg_created_error') . ': ' . $e->getMessage());
            return redirect()->back();
        }
    }

    

    public function show(Application $application)
    {
        $data['title'] = $this->title;
        $data['route'] = $this->route;
        $data['view'] = $this->view;
        $data['path'] = $this->path;
        $data['access'] = $this->access;
        $data['row'] = $application;

        return view($this->view . '.show', $data);
    }

    public function edit(Application $application)
    {
        $data['title'] = $this->title;
        $data['route'] = $this->route;
        $data['view'] = $this->view;
        $data['path'] = $this->path;

        $data['provinces'] = Province::where('status', '1')->orderBy('title', 'asc')->get();
        $data['present_districts'] = District::where('status', '1')->where('province_id', $application->present_province)->orderBy('title', 'asc')->get();
        $data['permanent_districts'] = District::where('status', '1')->where('province_id', $application->permanent_province)->orderBy('title', 'asc')->get();
        $data['statuses'] = StatusType::where('status', '1')->get();
        $data['batches'] = Batch::where('status', '1')->orderBy('id', 'desc')->get();
        $data['programs'] = Program::where('status', '1')->orderBy('title', 'asc')->get();

        $data['row'] = $application->load([
            'statuses',
            'relatives',
            'currentEnroll',
            'currentEnroll.program',
            'currentEnroll.session',
            'currentEnroll.semester',
            'currentEnroll.section'
        ]);

        // For cascading dropdowns in edit mode
        if ($application->currentEnroll) {
            $enroll = $application->currentEnroll;
            $data['sessions'] = \App\Models\Session::where('program_id', $enroll->program_id)->get();
            $data['semesters'] = \App\Models\Semester::where('program_id', $enroll->program_id)->get();
            $data['sections'] = \App\Models\Section::where('semester_id', $enroll->semester_id)->get();
        } else {
            $data['sessions'] = $data['semesters'] = $data['sections'] = collect();
        }

        return view($this->view . '.edit', $data);
    }

    public function update(Request $request, Application $application)
    {
        if ($application->status == 0) {
            $application->status = '1';
        } else {
            $application->status = '0';
        }
        $application->updated_by = Auth::guard('web')->user()->id;
        $application->save();

        Flasher::addSuccess(__('msg_updated_successfully'), __('msg_success'));
        return redirect()->back();
    }

    public function destroy(Application $application)
    {
        DB::beginTransaction();
        try {
            $this->deleteMultiMedia($this->path, $application, 'photo');
            $this->deleteMultiMedia($this->path, $application, 'signature');
            $this->deleteMultiMedia($this->path, $application, 'school_transcript');
            $this->deleteMultiMedia($this->path, $application, 'school_certificate');
            $this->deleteMultiMedia($this->path, $application, 'collage_transcript');
            $this->deleteMultiMedia($this->path, $application, 'collage_certificate');

            $application->delete();

            DB::commit();

            Flasher::addSuccess(__('msg_deleted_successfully'), __('msg_success'));
            return redirect()->back();

        } catch (\Exception $e) {
            DB::rollback();
            Flasher::addError(__('msg_deleted_error'), __('msg_error'));
            return redirect()->back();
        }
    }
}