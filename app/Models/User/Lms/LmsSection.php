<?php

namespace App\Models\User\Lms;

use Illuminate\Database\Eloquent\Model;

class LmsSection extends Model
{
    protected $table = 'lms_sections';
    protected $guarded = ['id'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(LmsCourse::class, 'course_id', 'id');
    }

    public function contents()
    {
        return $this->hasMany(LmsContent::class, 'section_id', 'id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}

