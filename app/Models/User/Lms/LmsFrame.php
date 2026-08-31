<?php

namespace App\Models\User\Lms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LmsFrame extends Model
{
    protected $table = 'lms_frames';
    protected $guarded = ['id'];

    protected $casts = [
        'frame_id' => 'integer',
        'course_id' => 'integer',
    ];

    protected static function booted()
    {
        static::created(function (LmsFrame $lms_frame) {
            $frame = DB::table('frames')->where('id', $lms_frame->frame_id)->first();

            if (empty($frame) || !empty($frame->bucket_id)) {
                return;
            }

            $bucket_id = DB::table('buckets')->insertGetId([
                'bucket_name' => 'LMS',
                'plugin_name' => 'lms',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('frames')
                ->where('id', $lms_frame->frame_id)
                ->update(['bucket_id' => $bucket_id]);
        });
    }

    public function course()
    {
        return $this->belongsTo(LmsCourse::class, 'course_id', 'id');
    }
}

