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
use NerdSnipe\LaravelCountries\Models\Country;
use NerdSnipe\LaravelCountries\Models\State;
use NerdSnipe\LaravelCountries\Models\City;
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
        $data['title'] = $this->title;
        $data['route'] = $this->route;
        $data['view'] = $this->view;

        $data['provinces'] = Province::where('status', '1')->orderBy('title', 'asc')->get();

        return view($this->view . '.create', $data);
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
            'location.country_id' => 'nullable|integer',
            'location.state_id' => 'nullable|integer',
            'location.city_id' => 'nullable|integer',
            'permanent_location.country_id' => 'nullable|integer',
            'permanent_location.state_id' => 'nullable|integer',
            'permanent_location.city_id' => 'nullable|integer',
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
                'present_village',
                'present_address',
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

            $student->present_country = $request->location['country_id'] ?? null;
            $student->present_province = $request->location['state_id'] ?? null;
            $student->present_district = $request->location['city_id'] ?? null;
            $student->permanent_country = $request->permanent_location['country_id'] ?? null;
            $student->permanent_province = $request->permanent_location['state_id'] ?? null;
            $student->permanent_district = $request->permanent_location['city_id'] ?? null;

            $student->password = Hash::make($password);
            $student->password_text = Crypt::encryptString($password);
            $student->status = '1';
            $student->created_by = Auth::guard('web')->user()->id;


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
            return redirect()->route($this->route . '.index')->with('password', $password);

        } catch (\Throwable $e) {
            DB::rollback();

            // Create a detailed error message with the file and line number
            $errorMessage = 'Error: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ' on line ' . $e->getLine();

            // Display the detailed error in the flash message
            Flasher::addError($errorMessage);

            return redirect()->back()->withInput();
        }
    }





    public function show(Application $application)
    {

        $data['title'] = $this->title;
        $data['route'] = $this->route;
        $data['view'] = $this->view;
        $data['path'] = $this->path;
        $data['access'] = $this->access;

        // Load proper objects using your reliable helper
        $application->present_country = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\Country::class, $application->present_country);
        $application->permanent_country = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\Country::class, $application->permanent_country);

        $application->present_province = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\State::class, $application->present_province);
        $application->permanent_province = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\State::class, $application->permanent_province);

        $application->present_district = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\City::class, $application->present_district);
        $application->permanent_district = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\City::class, $application->permanent_district);

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
        $data['statuses'] = StatusType::where('status', '1')->get();
        $data['batches'] = Batch::where('status', '1')->orderBy('id', 'desc')->get();
        $data['programs'] = Program::where('status', '1')->orderBy('title', 'asc')->get();

        // === Raw IDs for form pre-selection and cascading dropdowns ===
        $present_country_id = $application->present_country;
        $present_province_id = $application->present_province;
        $present_district_id = $application->present_district;

        $permanent_country_id = $application->permanent_country;
        $permanent_province_id = $application->permanent_province;
        $permanent_district_id = $application->permanent_district;

        // Load human-readable objects using your helper (for display if needed)
        $application->present_country = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\Country::class, $present_country_id);
        $application->present_province = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\State::class, $present_province_id);
        $application->present_district = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\City::class, $present_district_id);

        $application->permanent_country = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\Country::class, $permanent_country_id);
        $application->permanent_province = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\State::class, $permanent_province_id);
        $application->permanent_district = $this->findLocationObject(\NerdSnipe\LaravelCountries\Models\City::class, $permanent_district_id);

        // Pass raw IDs to blade for pre-selecting in the component
        $data['present_country_id'] = $present_country_id;
        $data['present_province_id'] = $present_province_id;
        $data['present_district_id'] = $present_district_id;

        $data['permanent_country_id'] = $permanent_country_id;
        $data['permanent_province_id'] = $permanent_province_id;
        $data['permanent_district_id'] = $permanent_district_id;

        // Load districts for cascading (based on saved province)
        $data['present_districts'] = District::where('status', '1')
            ->where('province_id', $present_province_id)
            ->orderBy('title', 'asc')->get();

        $data['permanent_districts'] = District::where('status', '1')
            ->where('province_id', $permanent_province_id)
            ->orderBy('title', 'asc')->get();

        $data['row'] = $application->load([
            'statuses',
            'relatives',
            'currentEnroll',
            'currentEnroll.program',
            'currentEnroll.session',
            'currentEnroll.semester',
            'currentEnroll.section'
        ]);

        // Cascading for enroll section
        if ($application->currentEnroll) {
            $enroll = $application->currentEnroll;
            $program = $enroll->program; // Get the loaded program

            // Use the relationships defined in the Program model
            $data['sessions'] = $program ? $program->sessions : collect();
            $data['semesters'] = $program ? $program->semesters : collect();

            // Correctly fetch sections through the semester's relationship
            $semester = $enroll->semester;
            $data['sections'] = $semester ? $semester->programSections->pluck('section') : collect();
        } else {
            $data['sessions'] = $data['semesters'] = $data['sections'] = collect();
        }

        return view($this->view . '.edit', $data);
    }
   public function update(Request $request, Application $application)
{
    $request->validate([
        'first_name' => 'required',
        'last_name'  => 'required',
        'email'      => 'required|email|unique:students,email,' . ($application->student?->id ?? 'NULL'),
        'phone'      => 'required',
        'gender'     => 'required',
        'dob'        => 'required|date',
        'student_id' => 'nullable|unique:students,student_id,' . ($application->student?->id ?? 'NULL'),
    ]);

    // Update Application
    $application->update([
        'first_name'         => $request->first_name,
        'last_name'          => $request->last_name,
        'father_name'        => $request->father_name,
        'mother_name'        => $request->mother_name,
        'father_occupation'  => $request->father_occupation,
        'mother_occupation'  => $request->mother_occupation,
        'email'              => $request->email,
        'phone'              => $request->phone,
        'emergency_phone'    => $request->emergency_phone,
        'gender'             => $request->gender,
        'dob'                => $request->dob,
        'marital_status'     => $request->marital_status,
        'blood_group'        => $request->blood_group,
        'religion'           => $request->religion,
        'nationality'        => $request->nationality,
        'national_id'        => $request->national_id,
        'passport_no'        => $request->passport_no,

        'present_address'    => $request->present_address,
        'permanent_address'  => $request->permanent_address,
        'present_village'    => $request->present_village,
        'permanent_village'  => $request->permanent_village,

        // Correct reading from component
        'present_country'    => $request->input('location.country_id'),
        'present_province'   => $request->input('location.state_id'),
        'present_district'   => $request->input('location.city_id'),

        'permanent_country'  => $request->input('permanent_location.country_id'),
        'permanent_province' => $request->input('permanent_location.state_id'),
        'permanent_district' => $request->input('permanent_location.city_id'),
    ]);

    // Sync everything to Student table
    if ($student = $application->student) {
        $student->update([
            'first_name'         => $application->first_name,
            'last_name'          => $application->last_name,
            'father_name'        => $application->father_name,
            'mother_name'        => $application->mother_name,
            'father_occupation'  => $application->father_occupation,
            'mother_occupation'  => $application->mother_occupation,
            'email'              => $application->email,
            'phone'              => $application->phone,
            'emergency_phone'    => $application->emergency_phone,
            'gender'             => $application->gender,
            'dob'                => $application->dob,
            'marital_status'     => $application->marital_status,
            'blood_group'        => $application->blood_group,
            'religion'           => $application->religion,
            'nationality'        => $application->nationality,
            'national_id'        => $application->national_id,
            'passport_no'        => $application->passport_no,

            'present_country'    => $application->present_country,
            'present_province'   => $application->present_province,
            'present_district'   => $application->present_district,
            'present_village'    => $application->present_village,
            'present_address'    => $application->present_address,

            'permanent_country'  => $application->permanent_country,
            'permanent_province' => $application->permanent_province,
            'permanent_district' => $application->permanent_district,
            'permanent_village'  => $application->permanent_village,
            'permanent_address'  => $application->permanent_address,
        ]);

        // Update student_id only if changed
        if ($request->filled('student_id') && $request->student_id != $student->student_id) {
            $student->student_id = $request->student_id;
            $student->save();
        }
    }

    Flasher::addSuccess(trans_choice('action_updated', 1, ['type' => $this->title]));

    return redirect()->back();
}

    public function updateStatus(Request $request, Application $application)
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

    private static $locationCache = [];

    private function findLocationObject($model, $id)
    {
        if (!$id)
            return null;

        $key = $model . ':' . $id;

        if (isset(self::$locationCache[$key])) {
            return self::$locationCache[$key];
        }

        if ($model === \NerdSnipe\LaravelCountries\Models\Country::class) {
            $path = storage_path('app/laravel-countries/countries.json');
            if (!file_exists($path)) {
                // Log error or throw meaningful exception in production
                return null;
            }
            $allRecords = collect(json_decode(file_get_contents($path), true));
        } else {
            $allRecords = collect((new $model())->all());
        }

        $found = $allRecords->first(function ($record) use ($id) {
            return (is_array($record) ? $record['id'] : ($record->id ?? null)) == $id;
        });

        $result = $found ? (object) $found : null;
        self::$locationCache[$key] = $result;

        return $result;
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