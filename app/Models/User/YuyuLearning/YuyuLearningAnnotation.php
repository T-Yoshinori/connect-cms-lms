<?php

namespace App\Models\User\YuyuLearning;

use Illuminate\Database\Eloquent\Model;

class YuyuLearningAnnotation extends Model
{
    protected $table = 'yuyu_learning_annotations';
    protected $guarded = ['id'];

    protected $casts = [
        'user_id' => 'integer',
        'content_id' => 'integer',
        'blog_post_id' => 'integer',
        'start_offset' => 'integer',
        'end_offset' => 'integer',
    ];

    public function content()
    {
        return $this->belongsTo(YuyuLearningContent::class, 'content_id', 'id');
    }
}
