<?php

namespace App\Models\User\YuyuLearning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class YuyuLearningFrame extends Model
{
    protected $table = 'yuyu_learning_frames';
    protected $guarded = ['id'];

    protected $casts = [
        'frame_id' => 'integer',
        'course_id' => 'integer',
    ];

    protected static function booted()
    {
        static::created(function (YuyuLearningFrame $yuyu_learning_frame) {
            $frame = DB::table('frames')->where('id', $yuyu_learning_frame->frame_id)->first();

            if (empty($frame) || !empty($frame->bucket_id)) {
                return;
            }

            $bucket_id = DB::table('buckets')->insertGetId([
                'bucket_name' => 'LMS',
                'plugin_name' => 'yuyulearning',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('frames')
                ->where('id', $yuyu_learning_frame->frame_id)
                ->update(['bucket_id' => $bucket_id]);
        });
    }

    public function course()
    {
        return $this->belongsTo(YuyuLearningCourse::class, 'course_id', 'id');
    }
}
