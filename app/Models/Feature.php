<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'default_enabled',
        'default_limit',
    ];

    protected $casts = [
        'default_enabled' => 'boolean',
        'default_limit' => 'integer',
    ];
}
