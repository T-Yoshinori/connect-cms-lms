<?php

namespace App\Models\User\YuyuLearning;

use Illuminate\Database\Eloquent\Model;

class YuyuLearningCourseGroup extends Model
{
    protected $table = 'yuyu_learning_course_groups';
    protected $guarded = ['id'];

    public function course()
    {
        return $this->belongsTo(YuyuLearningCourse::class, 'course_id', 'id');
    }
}
