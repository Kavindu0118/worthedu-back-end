<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'note_title',
        'note_body',
        'attachment_url',
    ];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }
}
