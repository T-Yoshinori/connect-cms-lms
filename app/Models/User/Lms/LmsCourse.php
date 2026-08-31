<?php

namespace App\Models\User\Lms;

use Illuminate\Database\Eloquent\Model;
use App\UserableNohistory;

class LmsCourse extends Model
{
    use UserableNohistory;

    protected $table = 'lms_courses';
    protected $guarded = ['id'];

    protected $casts = [
        'sort_order' => 'integer',
        'published_at' => 'datetime',
    ];

    public function sections()
    {
        return $this->hasMany(LmsSection::class, 'course_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function enrollments()
    {
        return $this->hasMany(LmsEnrollment::class, 'course_id', 'id');
    }

    public function course_groups()
    {
        return $this->hasMany(LmsCourseGroup::class, 'course_id', 'id');
    }

    public function frames()
    {
        return $this->hasMany(LmsFrame::class, 'course_id', 'id');
    }
}

