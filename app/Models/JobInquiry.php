<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobInquiry extends Model
{
    use HasFactory;

    protected $table = 'job_inquiries';

    protected $fillable = [
        'name',
        'father_husband_name',
        'campus',
        'gender',
        'phone',
        'qualification',
        'birthday',
        'marital_status',
        'applied_for_designation',
        'salary_type',
        'salary_demand',
        'email',
        'home_address',
        'cv_resume',
    ];
}

