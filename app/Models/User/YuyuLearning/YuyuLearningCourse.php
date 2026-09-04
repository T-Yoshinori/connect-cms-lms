<?php

namespace App\Models\User\YuyuLearning;

use Illuminate\Database\Eloquent\Model;
use App\UserableNohistory;

class YuyuLearningCourse extends Model
{
    use UserableNohistory;

    protected $table = 'yuyu_learning_courses';
    protected $guarded = ['id'];

    protected $casts = [
        'sort_order' => 'integer',
        'published_at' => 'datetime',
    ];

    public function sections()
    {
        return $this->hasMany(YuyuLearningSection::class, 'course_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function enrollments()
    {
        return $this->hasMany(YuyuLearningEnrollment::class, 'course_id', 'id');
    }

    public function course_groups()
    {
        return $this->hasMany(YuyuLearningCourseGroup::class, 'course_id', 'id');
    }

    public function frames()
    {
        return $this->hasMany(YuyuLearningFrame::class, 'course_id', 'id');
    }
}

