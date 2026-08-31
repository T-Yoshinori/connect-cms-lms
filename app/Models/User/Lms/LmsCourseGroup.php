<?php

namespace App\Models\User\Lms;

use Illuminate\Database\Eloquent\Model;

class LmsCourseGroup extends Model
{
    protected $table = 'lms_course_groups';
    protected $guarded = ['id'];

    public function course()
    {
        return $this->belongsTo(LmsCourse::class, 'course_id', 'id');
    }
}

