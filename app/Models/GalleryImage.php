<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    use HasFactory;

    protected $table = 'gallery_images';

    protected $fillable = [
        'image_path',
        'image_name',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];
}
