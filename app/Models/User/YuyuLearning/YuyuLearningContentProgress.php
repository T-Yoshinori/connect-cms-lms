<?php

namespace App\Models\User\YuyuLearning;

use Illuminate\Database\Eloquent\Model;

class YuyuLearningContentProgress extends Model
{
    protected $table = 'yuyu_learning_content_progress';
    protected $guarded = ['id'];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(YuyuLearningEnrollment::class, 'enrollment_id', 'id');
    }

    public function content()
    {
        return $this->belongsTo(YuyuLearningContent::class, 'content_id', 'id');
    }
}
