<?php

// файл: app/Models/Product.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model // ← singular
{
    use HasFactory;

    protected $fillable = [
        'name',
        'health_score',
        'concerns',
        'image',
        'user_id',
    ];

    protected $casts = [
        'concerns' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
