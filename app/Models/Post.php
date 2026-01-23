<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'slug',
        'meta_title',
        'meta_description',
        'page_title',
        'image_alt_text',
        'heading_two',
        'body',
        'image_path',
        'published',
    ];
}
