<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'registration_no', 'batch_id', 'program_id', 'apply_date', 'first_name', 'last_name', 
        'father_name', 'mother_name', 'father_occupation', 'mother_occupation', 'country', 
        'present_province', 'present_district', 'present_village', 'present_address', 
        'permanent_province', 'permanent_district', 'permanent_village', 'permanent_address', 
        'gender', 'dob', 'email', 'phone', 'emergency_phone', 'religion', 'caste', 
        'mother_tongue', 'marital_status', 'blood_group', 'nationality', 'national_id', 
        'passport_no', 'school_name', 'school_exam_id', 'school_graduation_year', 
        'school_graduation_point', 'school_transcript', 'school_certificate', 'collage_name', 
        'collage_exam_id', 'collage_graduation_year', 'collage_graduation_point', 
        'collage_transcript', 'collage_certificate', 'photo', 'signature', 'fee_amount', 
        'pay_status', 'payment_method', 'status', 'created_by', 'updated_by',
    ];

    // Existing relationships
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function presentProvince()
    {
        return $this->belongsTo(Province::class, 'present_province');
    }

    public function presentDistrict()
    {
        return $this->belongsTo(District::class, 'present_district');
    }

    public function permanentProvince()
    {
        return $this->belongsTo(Province::class, 'permanent_province');
    }

    public function permanentDistrict()
    {
        return $this->belongsTo(District::class, 'permanent_district');
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'docables', 'documentable_id', 'document_id')
                    ->where('documentable_type', self::class);
    }

    public function statuses()
    {
        return $this->belongsToMany(StatusType::class, 'application_status_type', 'application_id', 'status_type_id');
    }

    public function relatives()
    {
        return $this->hasMany(StudentRelative::class, 'student_id');
    }

    // ==================== NEW RELATIONSHIPS ADDED ====================
    /**
     * Current active enrollment (most important for edit form)
     */
    public function currentEnroll()
    {
        return $this->hasOne(StudentEnroll::class, 'student_id', 'id')
                    ->latest('id')                    // get latest enrollment
                    ->with(['program', 'session', 'semester', 'section']);
    }
}