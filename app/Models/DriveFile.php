<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveFile extends Model
{
    protected $fillable = [
        'drive_file_id',
        'name',
        'filename',
        'extension',
        'path',
        'mime_type',
        'file_size',
        'visibility',
        'last_modified'
    ];
}