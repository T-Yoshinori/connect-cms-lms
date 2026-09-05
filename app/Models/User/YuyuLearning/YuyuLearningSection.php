<?php

namespace App\Models\User\YuyuLearning;

use Illuminate\Database\Eloquent\Model;

class YuyuLearningSection extends Model
{
    protected $table = 'yuyu_learning_sections';
    protected $guarded = ['id'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(YuyuLearningCourse::class, 'course_id', 'id');
    }

    public function contents()
    {
        return $this->hasMany(YuyuLearningContent::class, 'section_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
