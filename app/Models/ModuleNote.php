<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ModuleNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'note_title',
        'note_body',
        'attachment_url',
    ];

    protected $appends = ['full_attachment_url', 'attachment_name'];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    /**
     * Get the full URL for the attachment
     */
    public function getFullAttachmentUrlAttribute()
    {
        if (!$this->attachment_url) {
            return null;
        }

        // If already a full URL, return as is
        if (strpos($this->attachment_url, 'http') === 0) {
            return $this->attachment_url;
        }

        // Convert storage path to full URL
        return url('storage/' . $this->attachment_url);
    }

    /**
     * Get the attachment file name
     */
    public function getAttachmentNameAttribute()
    {
        if (!$this->attachment_url) {
            return null;
        }

        return basename($this->attachment_url);
    }
}
