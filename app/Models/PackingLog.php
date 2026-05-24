<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackingLog extends Model
{
    protected $fillable = [
        'order_id',
        'file_name',
        'file_path',
        'file_size',
        'duration_seconds',
        'staff_name',
    ];
}
