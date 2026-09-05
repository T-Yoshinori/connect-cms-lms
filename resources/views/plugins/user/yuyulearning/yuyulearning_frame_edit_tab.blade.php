{{--
 * LMS設定画面タブ
 * コース選択、新規作成、Connect-CMS標準の権限設定を提供する。
 --}}
@if ($action == 'listCourses' || $action == 'selectCourse')
    <li role="presentation" class="nav-item">
        <span class="nav-link"><span class="active">コース選択</span></span>
    </li>
@else
    <li role="presentation" class="nav-item">
        <a href="{{ url('/') }}/plugin/yuyulearning/listCourses/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}" class="nav-link">コース選択</a>
    </li>
@endif

@if ($action == 'createCourse' || ($action == 'saveCourse' && empty($id)))
    <li role="presentation" class="nav-item">
        <span class="nav-link"><span class="active">新規作成</span></span>
    </li>
@else
    <li role="presentation" class="nav-item">
        <a href="{{ url('/') }}/plugin/yuyulearning/createCourse/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}" class="nav-link">新規作成</a>
    </li>
@endif

@if ($action == 'editBucketsRoles' || $action == 'saveBucketsRoles')
    <li role="presentation" class="nav-item">
        <span class="nav-link"><span class="active">権限設定</span></span>
    </li>
@else
    <li role="presentation" class="nav-item">
        <a href="{{ url('/') }}/plugin/yuyulearning/editBucketsRoles/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}" class="nav-link">権限設定</a>
    </li>
@endif
