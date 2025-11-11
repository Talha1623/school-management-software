<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'father_husband_name',
        'campus',
        'designation',
        'gender',
        'emp_id',
        'phone',
        'whatsapp',
        'cnic',
        'qualification',
        'birthday',
        'joining_date',
        'marital_status',
        'salary_type',
        'salary',
        'email',
        'password',
        'home_address',
        'photo',
        'cv_resume',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the salaries for the staff member.
     */
    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    /**
     * Get the loans for the staff member.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}

