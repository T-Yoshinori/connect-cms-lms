<?php

namespace App\Models\User\YuyuLearning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\Common\Page;

class YuyuLearningContent extends Model
{
    protected $table = 'yuyu_learning_contents';
    protected $guarded = ['id'];

    protected $casts = [
        'reference_id' => 'integer',
        'page_id' => 'integer',
        'frame_id' => 'integer',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function (YuyuLearningContent $content) {
            if ($content->content_type !== 'quiz' || empty($content->frame_id)) {
                return;
            }

            $quiz_id = DB::table('quiz_frames')
                ->where('frame_id', (int) $content->frame_id)
                ->value('quiz_id');

            if (!empty($quiz_id)) {
                $content->reference_id = (int) $quiz_id;
            }
        });
    }

    public function section()
    {
        return $this->belongsTo(YuyuLearningSection::class, 'section_id', 'id');
    }

    public function progresses()
    {
        return $this->hasMany(YuyuLearningContentProgress::class, 'content_id', 'id');
    }

    public function annotations()
    {
        return $this->hasMany(YuyuLearningAnnotation::class, 'content_id', 'id');
    }

    /**
     * LMSのコース目次から教材を起動するURLを返す。
     *
     * 第1版ではConnect-CMS内の教材はすべて、その教材フレームが配置された
     * 通常ページを入口として開く。プラグイン固有の start / show / index 等を
     * LMSから直接呼び出さない。
     *
     * これにより、固定記事・ブログ・小テスト・アンケート・レポート課題を
     * Connect-CMS標準のページ描画・権限処理に統一する。
     *
     * 外部教材だけは登録URLをそのまま返す。
     *
     * @param string|null $return_url LMSコース目次へ戻るためのURL（現行の別ウィンドウ方式では通常未使用）
     * @return string|null
     */
    public function getLaunchUrl($return_url = null)
    {
        if ($this->content_type === 'custom') {
            return $this->reference_url ?: null;
        }

        if (empty($this->page_id) || empty($this->frame_id)) {
            return null;
        }

        $page = Page::find($this->page_id);
        if (empty($page)) {
            return null;
        }

        $query = [
            'yuyu_learning_content_id' => $this->id,
        ];
        if (!empty($return_url)) {
            $query['return_url'] = $return_url;
        }

        $path = '/' . ltrim((string) $page->permanent_link, '/');

        return url($path)
            . '?' . http_build_query($query)
            . '#frame-' . $this->frame_id;
    }
}
