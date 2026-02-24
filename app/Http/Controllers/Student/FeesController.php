<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentEnroll;
use Illuminate\Http\Request;
use App\Models\FeesCategory;
use App\Models\Student;
use App\Models\Fee;

class FeesController extends Controller
{
    protected $title, $route, $view, $path;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Module Data
        $this->title    = trans_choice('module_fees_report', 1);
        $this->route    = 'student.fees';
        $this->view     = 'student.fees';
        $this->path     = 'fees';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        $data['title']     = $this->title;
        $data['route']     = $this->route;
        $data['view']      = $this->view;
        $data['path']      = $this->path;


        $data['user'] = $user = Student::where('id', Auth::guard('student')->user()->id)->firstOrFail();

        $data['sessions'] = StudentEnroll::where('student_id', $user->id)->groupBy('session_id')->get();
        $data['semesters'] = StudentEnroll::where('student_id', $user->id)->groupBy('semester_id')->get();
        $data['categories'] = FeesCategory::where('status', '1')->orderBy('title', 'asc')->get();


        if(!empty($request->session) || $request->session != null){
            $data['selected_session'] = $session = $request->session;
        }
        else{
            $data['selected_session'] = $session = '0';
        }

        if(!empty($request->semester) || $request->semester != null){
            $data['selected_semester'] = $semester = $request->semester;
        }
        else{
            $data['selected_semester'] = $semester = '0';
        }

        if(!empty($request->category) || $request->category != null){
            $data['selected_category'] = $category = $request->category;
        }
        else{
            $data['selected_category'] = '0';
        }


        // Filter Fees
        $fees = Fee::with('studentEnroll')->whereHas('studentEnroll', function ($query) use ($user, $session, $semester){
                $query->where('student_id', $user->id);
            if($session != 0){
                $query->where('session_id', $session);
            }
            if($semester != 0){
                $query->where('semester_id', $semester);
            }
        });
        if(!empty($request->category)){
            $fees->where('category_id', $category);
        }
        $data['rows'] = $fees->where('status', '<=', '1')->orderBy('assign_date', 'desc')->get();


        return view($this->view.'.index', $data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function pay($id)
    {
        //
        $data['title']     = $this->title;
        $data['route']     = $this->route;
        $data['view']      = $this->view;
        $data['path']      = $this->path;

        $user = Auth::guard('student')->user()->id;

        // Filter Fees
        $fees = Fee::where('id', $id)->with('studentEnroll')->whereHas('studentEnroll', function ($query) use ($user){
            $query->where('student_id', $user);
        });
        $data['row'] = $fees->where('status', '<', '1')->firstOrFail();

        return view($this->view.'.pay', $data);
    }
}
