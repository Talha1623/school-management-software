<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentComplaint extends Model
{
    use HasFactory;

    protected $table = 'parent_complaints';

    protected $fillable = [
        'parent_account_id',
        'student_id',
        'parent_name',
        'email',
        'phone',
        'subject',
        'complain',
        'status',
    ];

    public function parentAccount()
    {
        return $this->belongsTo(ParentAccount::class, 'parent_account_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}


