<?php

namespace App\Plugins\User\Lms;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

use App\Models\User\Lms\LmsContent;
use App\Models\User\Lms\LmsContentProgress;
use App\Models\User\Lms\LmsCourse;
use App\Models\User\Lms\LmsEnrollment;
use App\Models\User\Lms\LmsFrame;
use App\Models\User\Lms\LmsSection;
use App\Plugins\User\Lms\Services\LmsAdminProgressService;
use App\Plugins\User\Lms\Services\LmsContentStatusService;
use App\Plugins\User\Lms\Services\LmsProgressService;
use App\Plugins\User\UserPluginBase;

/**
 * LMSプラグイン
 *
 * @category LMSプラグイン
 * @package Controller
 * @plugin_title LMS
 * @plugin_desc コース、教材、受講進捗を管理するLMSプラグインです。
 */
class LmsPlugin extends UserPluginBase
{
    protected function getViewPath($blade_name)
    {
        $template = !empty($this->frame->template) ? $this->frame->template : 'default';

        $template_path = resource_path().'/views/plugins/user/lms/'.$template.'/'.$blade_name.'.blade.php';
        if (File::exists($template_path)) {
            return 'plugins.user.lms.'.$template.'.'.$blade_name;
        }

        $option_template_path = resource_path().'/views/plugins_option/user/lms/'.$template.'/'.$blade_name.'.blade.php';
        if (File::exists($option_template_path)) {
            return 'plugins_option.user.lms.'.$template.'.'.$blade_name;
        }

        $default_path = resource_path().'/views/plugins/user/lms/default/'.$blade_name.'.blade.php';
        if (File::exists($default_path)) {
            return 'plugins.user.lms.default.'.$blade_name;
        }

        $option_default_path = resource_path().'/views/plugins_option/user/lms/default/'.$blade_name.'.blade.php';
        if (File::exists($option_default_path)) {
            return 'plugins_option.user.lms.default.'.$blade_name.'.'.$blade_name;
        }

        return 'errors/template_notfound';
    }

    public function getPublicFunctions()
    {
        return [
            'get' => [
                'listCourses', 'createCourse', 'editCourse', 'adminProgress', 'createSection', 'editSection',
                'createContent', 'editContent',
            ],
            'post' => [
                'selectCourse', 'saveCourse', 'saveSection', 'moveSection', 'saveContent', 'deleteContent',
                'markContentStarted', 'completeContent', 'syncContentProgress',
            ],
        ];
    }

    public function declareRole()
    {
        return [
            'listCourses' => ['role_article_admin'],
            'selectCourse' => ['role_article_admin'],
            'createCourse' => ['role_article_admin'],
            'editCourse' => ['role_article_admin'],
            'adminProgress' => ['role_article_admin'],
            'saveCourse' => ['role_article_admin'],
            'createSection' => ['role_article_admin'],
            'editSection' => ['role_article_admin'],
            'saveSection' => ['role_article_admin'],
            'moveSection' => ['role_article_admin'],
            'createContent' => ['role_article_admin'],
            'editContent' => ['role_article_admin'],
            'saveContent' => ['role_article_admin'],
            'deleteContent' => ['role_article_admin'],
        ];
    }

    private function getOrCreateLmsFrame($frame_id)
    {
        return LmsFrame::firstOrCreate(['frame_id' => (int) $frame_id], ['course_id' => null]);
    }

    private function getContentSources(): array
    {
        $sources = ['general'=>[], 'blog'=>[], 'quiz'=>[], 'questionnaire'=>[], 'learningtask'=>[]];

        $general_rows = DB::table('frames')->join('pages','pages.id','=','frames.page_id')->where('frames.plugin_name','contents')
            ->select('frames.id as frame_id','frames.page_id','frames.frame_title','pages.page_name','pages.permanent_link')
            ->orderBy('pages._lft')->orderBy('frames.display_sequence')->orderBy('frames.id')->get();
        foreach ($general_rows as $row) {
            $sources['general'][] = $this->makeContentSource('general',$row->page_id,$row->frame_id,null,$row->frame_title ?: '固定記事',$row->page_name,$row->permanent_link);
        }

        $blog_rows = DB::table('frames')->join('pages','pages.id','=','frames.page_id')->join('blogs','blogs.bucket_id','=','frames.bucket_id')
            ->where('frames.plugin_name','blogs')->select('frames.id as frame_id','frames.page_id','blogs.id as reference_id','blogs.blog_name as source_name','pages.page_name','pages.permanent_link')
            ->orderBy('pages._lft')->orderBy('blogs.blog_name')->orderBy('frames.id')->get();
        foreach ($blog_rows as $row) {
            $sources['blog'][] = $this->makeContentSource('blog',$row->page_id,$row->frame_id,$row->reference_id,$row->source_name,$row->page_name,$row->permanent_link);
        }

        $quiz_rows = DB::table('quiz_frames')->join('frames','frames.id','=','quiz_frames.frame_id')->join('pages','pages.id','=','frames.page_id')->join('quizzes','quizzes.id','=','quiz_frames.quiz_id')
            ->where('frames.plugin_name','quizzes')->whereNull('quizzes.deleted_at')
            ->select('frames.id as frame_id','frames.page_id','quizzes.id as reference_id','quizzes.title as source_name','pages.page_name','pages.permanent_link')
            ->orderBy('pages._lft')->orderBy('quizzes.title')->orderBy('frames.id')->get();
        foreach ($quiz_rows as $row) {
            $sources['quiz'][] = $this->makeContentSource('quiz',$row->page_id,$row->frame_id,$row->reference_id,$row->source_name,$row->page_name,$row->permanent_link);
        }

        $form_rows = DB::table('frames')->join('pages','pages.id','=','frames.page_id')->join('forms','forms.bucket_id','=','frames.bucket_id')
            ->where('frames.plugin_name','forms')->where('forms.form_mode','questionnaire')
            ->select('frames.id as frame_id','frames.page_id','forms.id as reference_id','forms.forms_name as source_name','pages.page_name','pages.permanent_link')
            ->orderBy('pages._lft')->orderBy('forms.forms_name')->orderBy('frames.id')->get();
        foreach ($form_rows as $row) {
            $sources['questionnaire'][] = $this->makeContentSource('questionnaire',$row->page_id,$row->frame_id,$row->reference_id,$row->source_name,$row->page_name,$row->permanent_link);
        }

        $learningtask_rows = DB::table('frames')->join('pages','pages.id','=','frames.page_id')
            ->join('learningtasks', function ($join) { $join->on('learningtasks.bucket_id','=','frames.bucket_id')->whereNull('learningtasks.deleted_at'); })
            ->join('learningtasks_posts', function ($join) { $join->on('learningtasks_posts.learningtasks_id','=','learningtasks.id')->whereNull('learningtasks_posts.deleted_at'); })
            ->where('frames.plugin_name','learningtasks')
            ->select('frames.id as frame_id','frames.page_id','learningtasks_posts.id as reference_id','learningtasks_posts.post_title as source_name','pages.page_name','pages.permanent_link')
            ->orderBy('pages._lft')->orderBy('learningtasks_posts.id')->orderBy('frames.id')->get();
        foreach ($learningtask_rows as $row) {
            $sources['learningtask'][] = $this->makeContentSource('learningtask',$row->page_id,$row->frame_id,$row->reference_id,strip_tags($row->source_name),$row->page_name,$row->permanent_link);
        }
        return $sources;
    }

    private function makeContentSource($type,$page_id,$frame_id,$reference_id,$source_name,$page_name,$permanent_link): array
    {
        $source_name = trim((string)$source_name) ?: '名称未設定';
        return ['key'=>$this->makeContentSourceKey($page_id,$frame_id,$reference_id),'type'=>$type,'page_id'=>(int)$page_id,'frame_id'=>(int)$frame_id,'reference_id'=>$reference_id===null?null:(int)$reference_id,'name'=>$source_name,'page_name'=>(string)$page_name,'permanent_link'=>(string)$permanent_link,'label'=>$source_name.' ｜ '.($page_name ?: 'ページ名未設定').'（'.($permanent_link ?: '/').'）'];
    }

    private function makeContentSourceKey($page_id,$frame_id,$reference_id=null): string { return (int)$page_id.':'.(int)$frame_id.':'.($reference_id===null?'0':(int)$reference_id); }
    private function getSelectedContentSourceKey(LmsContent $content): string { return empty($content->page_id)||empty($content->frame_id)?'':$this->makeContentSourceKey($content->page_id,$content->frame_id,$content->reference_id); }
    private function findContentSource(array $sources,string $type,?string $source_key): ?array { if(empty($source_key)||empty($sources[$type])) return null; foreach($sources[$type] as $source){ if($source['key']===$source_key) return $source; } return null; }

    public function index($request,$page_id,$frame_id)
    {
        $can_manage = $this->checkRoleFromFrame(Auth::user(),'role_article_admin',$this->frame);
        $lms_frame = $this->getOrCreateLmsFrame($frame_id);
        $course = null; $enrollment = null; $progresses = collect(); $course_progress = null;
        if(!empty($lms_frame->course_id)){
            $course=LmsCourse::query()->withCount(['sections','enrollments'])->with(['sections'=>function($query){$query->with(['contents'=>function($contents){$contents->orderBy('sort_order')->orderBy('id');}])->orderBy('sort_order')->orderBy('id');}])->find($lms_frame->course_id);
        }
        if($course && Auth::check()){
            $progressService = new LmsProgressService();
            $enrollment = $progressService->resolveEnrollmentForCourse((int)$course->id,(int)Auth::id());
            if($enrollment){
                $contents=$course->sections->flatMap(function($section){return $section->contents;});
                (new LmsContentStatusService($progressService))->syncAutomaticCompletions($enrollment,$contents,(int)Auth::id());
                $progresses=LmsContentProgress::query()->where('enrollment_id',$enrollment->id)->get()->keyBy('content_id');
            }
        }
        if($course && $enrollment){
            $contents=$course->sections->flatMap(function($section){return $section->contents;});
            $required_content_ids=$contents->where('is_required',true)->pluck('id');
            $required_total=$required_content_ids->count();
            $required_completed=$progresses->whereIn('content_id',$required_content_ids)->where('status','completed')->count();
            $content_total=$contents->count();
            $content_completed=$progresses->where('status','completed')->count();
            $course_progress=[
                'status'=>$enrollment->status,
                'required_total'=>$required_total,
                'required_completed'=>$required_completed,
                'content_total'=>$content_total,
                'content_completed'=>$content_completed,
                'percentage'=>$required_total>0?(int)round(($required_completed/$required_total)*100):0,
                'completed_at'=>$enrollment->completed_at,
            ];
        }
        return $this->view('default',compact('page_id','frame_id','can_manage','course','enrollment','progresses','course_progress'));
    }

    public function adminProgress($request,$page_id,$frame_id,$course_id)
    {
        $course=LmsCourse::findOrFail($course_id);
        $report=(new LmsAdminProgressService())->build($course,(int)$page_id);

        return $this->view('lms_admin_progress',[
            'page_id'=>$page_id,
            'frame_id'=>$frame_id,
            'course'=>$course,
            'report'=>$report,
        ]);
    }

    public function listCourses($request,$page_id,$frame_id){$lms_frame=$this->getOrCreateLmsFrame($frame_id);$courses=LmsCourse::query()->orderBy('sort_order')->orderBy('id')->get();return $this->view('lms_course_list',compact('page_id','frame_id','courses')+['selected_course_id'=>$lms_frame->course_id])->withInput($request->all());}
    public function selectCourse($request,$page_id,$frame_id){$validator=Validator::make($request->all(),['course_id'=>['required','integer','exists:lms_courses,id']]);$validator->setAttributeNames(['course_id'=>'コース']);if($validator->fails())return redirect()->back()->withErrors($validator)->withInput();$lms_frame=$this->getOrCreateLmsFrame($frame_id);$lms_frame->course_id=(int)$request->input('course_id');$lms_frame->save();$request->flash_message='このフレームで使用するコースを変更しました。';$request->merge(['redirect_path'=>$request->input('normal_page_path')]);}
    public function createCourse($request,$page_id,$frame_id){return $this->view('lms_course_create',['page_id'=>$page_id,'frame_id'=>$frame_id,'sort_order'=>(int)LmsCourse::max('sort_order')+1])->withInput($request->all());}
    public function editCourse($request,$page_id,$frame_id,$course_id){$course=LmsCourse::findOrFail($course_id);$course->load(['sections'=>function($query){$query->with(['contents'=>function($contents){$contents->orderBy('sort_order')->orderBy('id');}])->orderBy('sort_order')->orderBy('id');}]);return $this->view('lms_course_edit',['course'=>$course,'is_create'=>false])->withInput($request->all());}
    public function saveCourse($request,$page_id,$frame_id,$course_id=null){$validator=Validator::make($request->all(),['name'=>['required','string','max:191'],'description'=>['nullable','string'],'status'=>['required','in:draft,published,closed'],'sort_order'=>['required','integer','min:0']]);$validator->setAttributeNames(['name'=>'コース名','description'=>'コース説明','status'=>'公開状態','sort_order'=>'表示順']);if($validator->fails())return redirect()->back()->withErrors($validator)->withInput();$course=$course_id?LmsCourse::findOrFail($course_id):new LmsCourse();$course->name=$request->input('name');$course->description=$request->input('description');$course->status=$request->input('status');$course->sort_order=(int)$request->input('sort_order');if($course->status==='published'&&empty($course->published_at))$course->published_at=now();$course->save();if(!$course_id&&$request->boolean('assign_to_frame')){$lms_frame=$this->getOrCreateLmsFrame($frame_id);$lms_frame->course_id=$course->id;$lms_frame->save();}$request->flash_message=$course_id?'コースを更新しました。':'コースを作成しました。';$redirect_path=$request->input('after_save')==='continue'?url('/').'/plugin/lms/editCourse/'.$page_id.'/'.$frame_id.'/'.$course->id.'#frame-'.$frame_id:$request->input('normal_page_path');$request->merge(['redirect_path'=>$redirect_path]);}
    public function createSection($request,$page_id,$frame_id,$course_id){$course=LmsCourse::findOrFail($course_id);$section=new LmsSection(['course_id'=>$course->id,'sort_order'=>(int)LmsSection::where('course_id',$course->id)->max('sort_order')+1]);return $this->view('lms_section_edit',['course'=>$course,'section'=>$section,'is_create'=>true])->withInput($request->all());}
    public function editSection($request,$page_id,$frame_id,$section_id){$section=LmsSection::with('course')->findOrFail($section_id);return $this->view('lms_section_edit',['course'=>$section->course,'section'=>$section,'is_create'=>false])->withInput($request->all());}
    public function saveSection($request,$page_id,$frame_id,$section_id=null){$validator=Validator::make($request->all(),['course_id'=>['required','integer','exists:lms_courses,id'],'title'=>['required','string','max:191'],'description'=>['nullable','string']]);$validator->setAttributeNames(['title'=>'章タイトル','description'=>'章の説明']);if($validator->fails())return redirect()->back()->withErrors($validator)->withInput();$course_id=(int)$request->input('course_id');if($section_id){$section=LmsSection::findOrFail($section_id);if((int)$section->course_id!==$course_id)abort(404);}else{$section=new LmsSection();$section->course_id=$course_id;$section->sort_order=(int)LmsSection::where('course_id',$course_id)->max('sort_order')+1;}$section->title=$request->input('title');$section->description=$request->input('description');$section->save();$request->flash_message=$section_id?'章を更新しました。':'章を追加しました。';$request->merge(['redirect_path'=>url('/').'/plugin/lms/editCourse/'.$page_id.'/'.$frame_id.'/'.$course_id.'#frame-'.$frame_id]);}
    public function moveSection($request,$page_id,$frame_id,$section_id){$validator=Validator::make($request->all(),['direction'=>['required','in:up,down']]);if($validator->fails())return redirect()->back()->withErrors($validator);$section=LmsSection::findOrFail($section_id);$sections=LmsSection::where('course_id',$section->course_id)->orderBy('sort_order')->orderBy('id')->get();$ids=$sections->pluck('id')->values()->all();$index=array_search($section->id,$ids,true);$target_index=$request->input('direction')==='up'?$index-1:$index+1;if($index!==false&&isset($ids[$target_index])){$tmp=$ids[$index];$ids[$index]=$ids[$target_index];$ids[$target_index]=$tmp;DB::transaction(function()use($ids){foreach($ids as $position=>$id)LmsSection::whereKey($id)->update(['sort_order'=>$position+1]);});$request->flash_message='章の表示順を変更しました。';}$request->merge(['redirect_path'=>url('/').'/plugin/lms/editCourse/'.$page_id.'/'.$frame_id.'/'.$section->course_id.'#frame-'.$frame_id]);}
    public function createContent($request,$page_id,$frame_id,$section_id){$section=LmsSection::with('course')->findOrFail($section_id);$content=new LmsContent(['section_id'=>$section->id,'content_type'=>'general','is_required'=>true,'sort_order'=>(int)LmsContent::where('section_id',$section->id)->max('sort_order')+1]);$content_sources=$this->getContentSources();$selected_source_key='';return $this->view('lms_content_create',compact('content_sources','selected_source_key')+['course'=>$section->course,'section'=>$section,'content'=>$content,'is_create'=>true])->withInput($request->all());}
    public function editContent($request,$page_id,$frame_id,$content_id){$content=LmsContent::with('section.course')->findOrFail($content_id);$content_sources=$this->getContentSources();$selected_source_key=$this->getSelectedContentSourceKey($content);return $this->view('lms_content_create',compact('content_sources','selected_source_key')+['course'=>$content->section->course,'section'=>$content->section,'content'=>$content,'is_create'=>false])->withInput($request->all());}
    public function saveContent($request,$page_id,$frame_id,$content_id=null){$content_types=['general','blog','quiz','questionnaire','custom','learningtask'];$sources=$this->getContentSources();$validator=Validator::make($request->all(),['section_id'=>['required','integer','exists:lms_sections,id'],'title'=>['required','string','max:191'],'content_type'=>['required','in:'.implode(',',$content_types)],'description'=>['nullable','string'],'source_key'=>['nullable','string','max:100'],'reference_url'=>['nullable','url','max:2000'],'sort_order'=>['required','integer','min:0']]);$validator->setAttributeNames(['title'=>'教材名','content_type'=>'教材タイプ','description'=>'教材説明','source_key'=>'教材','reference_url'=>'教材URL','sort_order'=>'表示順']);$validator->after(function($validator)use($request,$sources){$type=$request->input('content_type');if($type==='custom'){if(!$request->filled('reference_url'))$validator->errors()->add('reference_url','外部教材では教材URLが必要です。');return;}if(!$request->filled('source_key')){$validator->errors()->add('source_key','教材を選択してください。');return;}if(!$this->findContentSource($sources,$type,$request->input('source_key')))$validator->errors()->add('source_key','選択した教材を確認できません。教材を選び直してください。');});if($validator->fails())return redirect()->back()->withErrors($validator)->withInput();$section_id=(int)$request->input('section_id');$section=LmsSection::with('course')->findOrFail($section_id);if($content_id){$content=LmsContent::findOrFail($content_id);if((int)$content->section_id!==$section_id)abort(404);}else{$content=new LmsContent();$content->section_id=$section_id;}$type=$request->input('content_type');$source=$type==='custom'?null:$this->findContentSource($sources,$type,$request->input('source_key'));$content->title=$request->input('title');$content->content_type=$type;$content->description=$request->input('description');$content->is_required=$request->boolean('is_required');$content->sort_order=(int)$request->input('sort_order');$content->reference_id=null;$content->page_id=null;$content->frame_id=null;$content->reference_url=null;$content->plugin_name=null;$content->action=null;if($type==='custom'){$content->reference_url=$request->input('reference_url');}else{$content->page_id=$source['page_id'];$content->frame_id=$source['frame_id'];$content->reference_id=$source['reference_id'];$content->plugin_name=['general'=>'contents','blog'=>'blogs','quiz'=>'quizzes','questionnaire'=>'forms','learningtask'=>'learningtasks'][$type];$content->action=$type==='learningtask'?'show':'index';}$content->save();$request->flash_message=$content_id?'教材を更新しました。':'教材を追加しました。';$request->merge(['redirect_path'=>url('/').'/plugin/lms/editCourse/'.$page_id.'/'.$frame_id.'/'.$section->course->id.'#frame-'.$frame_id]);}
    public function deleteContent($request,$page_id,$frame_id,$content_id){$content=LmsContent::with('section.course')->findOrFail($content_id);$course_id=$content->section->course->id;$content->delete();$request->flash_message='教材を削除しました。';$request->merge(['redirect_path'=>url('/').'/plugin/lms/editCourse/'.$page_id.'/'.$frame_id.'/'.$course_id.'#frame-'.$frame_id]);}
    public function markContentStarted($request,$page_id,$frame_id,$content_id){if(!Auth::check())abort(403);$content=LmsContent::with('section')->findOrFail($content_id);$progressService=new LmsProgressService();$enrollment=$progressService->getEnrollmentForContent($content,(int)Auth::id());if(!$enrollment)abort(403);$progressService->markStarted($enrollment,$content);}
    public function completeContent($request,$page_id,$frame_id,$content_id){if(!Auth::check())abort(403);$content=LmsContent::with('section')->findOrFail($content_id);if(!in_array($content->content_type,['general','blog','custom'],true))abort(403);$progressService=new LmsProgressService();$enrollment=$progressService->getEnrollmentForContent($content,(int)Auth::id());if(!$enrollment)abort(403);$progressService->markCompleted($enrollment,$content,'self');$request->flash_message='教材を学習完了にしました。';if($request->filled('return_url'))$request->merge(['redirect_path'=>url($this->page->permanent_link).'#frame-'.$frame_id]);}
    public function syncContentProgress($request,$page_id,$frame_id,$content_id){if(!Auth::check())abort(403);$content=LmsContent::with('section')->findOrFail($content_id);if(!in_array($content->content_type,['quiz','questionnaire','learningtask'],true))abort(403);$progressService=new LmsProgressService();$enrollment=$progressService->getEnrollmentForContent($content,(int)Auth::id());if(!$enrollment)abort(403);$progressService->markStarted($enrollment,$content);(new LmsContentStatusService($progressService))->syncAutomaticCompletion($enrollment,$content,(int)Auth::id());}
}

