<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Staff extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

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
        'api_token',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    protected $casts = [
        'birthday' => 'date',
        'joining_date' => 'date',
    ];

    /**
     * Set the password attribute (hash it)
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Check if staff has dashboard access (has email and password)
     */
    public function hasDashboardAccess(): bool
    {
        return !empty($this->email) && !empty($this->password);
    }

    /**
     * Check if staff is a teacher (designation contains "teacher")
     */
    public function isTeacher(): bool
    {
        $designation = strtolower(trim($this->designation ?? ''));
        return strpos($designation, 'teacher') !== false;
    }

    /**
     * Get allowed routes based on designation
     */
    public function getAllowedRoutes(): array
    {
        // Default routes for all staff with dashboard access
        $defaultRoutes = [
            'dashboard',
            'student-list',
            'attendance.student',
            'student-behavior.recording',
        ];

        // Add more routes based on designation if needed
        if ($this->isTeacher()) {
            return $defaultRoutes;
        }

        return $defaultRoutes;
    }

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

