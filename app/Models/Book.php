<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'title',
        'subtitle',
        'author',
        'published',
        'publisher',
        'pages',
        'description',
        'website',
        'created_by',
        'book_file',
        'category',
        'cover_image'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
