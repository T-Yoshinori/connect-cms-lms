<?php

namespace App\Models\User\Lms;

use Illuminate\Database\Eloquent\Model;
use App\User;

class LmsEnrollment extends Model
{
    protected $table = 'lms_enrollments';
    protected $guarded = ['id'];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(LmsCourse::class, 'course_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function progresses()
    {
        return $this->hasMany(LmsContentProgress::class, 'enrollment_id', 'id');
    }
}

