<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'campus',
        'class',
        'section',
        'subject',
        'file_type',
        'file_path',
        'youtube_url',
    ];
}
