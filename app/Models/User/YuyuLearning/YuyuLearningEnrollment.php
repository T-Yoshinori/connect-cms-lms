<?php

namespace App\Models\User\YuyuLearning;

use Illuminate\Database\Eloquent\Model;
use App\User;

class YuyuLearningEnrollment extends Model
{
    protected $table = 'yuyu_learning_enrollments';
    protected $guarded = ['id'];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(YuyuLearningCourse::class, 'course_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function progresses()
    {
        return $this->hasMany(YuyuLearningContentProgress::class, 'enrollment_id', 'id');
    }
}
