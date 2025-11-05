<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentAccount extends Model
{
    use HasFactory;

    protected $table = 'parent_accounts';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'whatsapp',
        'id_card_number',
        'address',
        'profession',
    ];

    protected $hidden = [
        'password',
    ];
}

