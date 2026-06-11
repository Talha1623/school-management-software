<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformSchool extends Model
{
    use HasFactory;

    protected $connection = 'landlord';

    protected $table = 'platform_schools';

    protected $fillable = [
        'name',
        'subdomain',
        'domain',
        'db_host',
        'db_port',
        'db_database',
        'db_username',
        'db_password',
        'owner_name',
        'owner_email',
        'owner_password',
        'student_limit',
        'status',
    ];

    protected $hidden = [
        'db_password',
        'owner_password',
    ];
}
