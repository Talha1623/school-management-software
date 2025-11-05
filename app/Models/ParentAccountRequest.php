<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentAccountRequest extends Model
{
    use HasFactory;

    protected $table = 'parent_account_requests';

    protected $fillable = [
        'parent_id',
        'request_by',
        'email',
        'phone',
        'id_card',
        'request_status',
    ];
}

