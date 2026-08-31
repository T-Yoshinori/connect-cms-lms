<?php

namespace App\Models\User\Lms;

use Illuminate\Database\Eloquent\Model;

class LmsContentProgress extends Model
{
    protected $table = 'lms_content_progress';
    protected $guarded = ['id'];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(LmsEnrollment::class, 'enrollment_id', 'id');
    }

    public function content()
    {
        return $this->belongsTo(LmsContent::class, 'content_id', 'id');
    }
}

