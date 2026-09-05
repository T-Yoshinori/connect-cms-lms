<style>
    .yuyu-learning-badge {
        padding: .25rem .5rem;
        font-size: .875rem;
        line-height: 1.5;
        border-radius: .2rem;
    }
</style>

@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@php
    $can_manage = $can_manage ?? false;
    $status_labels = [
        'not_started' => '未着手',
        'in_progress' => '学習中',
        'failed' => '不合格',
        'completed' => '完了',
    ];
    $enrollment_labels = [
        'not_started' => '未着手',
        'in_progress' => '学習中',
        'completed' => '修了',
    ];
    $content_type_labels = [
        'general' => '固定記事',
        'blog' => 'ブログ',
        'quiz' => '小テスト',
        'questionnaire' => 'アンケート',
        'custom' => '外部教材',
        'learningtask' => 'レポート課題',
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
    <div>
        <h2 class="h4 mb-1">受講進捗</h2>
        <div class="text-muted">{{ $course->name }}</div>
    </div>
    <div class="d-flex flex-wrap mt-2 mt-sm-0">
        @if ($can_manage)
            <a href="{{ url('/') }}/plugin/yuyulearning/editCourse/{{ $page->id }}/{{ $frame->id }}/{{ $course->id }}#frame-{{ $frame->id }}"
               class="btn btn-outline-primary btn-sm mr-2 mb-1">
                コース編集
            </a>
        @else
            <span class="badge badge-secondary align-self-center mr-2 mb-1 px-2 py-2">閲覧専用</span>
        @endif
        <a href="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}"
           class="btn btn-secondary btn-sm mb-1">
            コース目次へ戻る
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-6 col-lg-3 mb-2">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small text-muted">受講者</div>
                <div class="h4 mb-0">{{ $report['summary']['total'] }}名</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small text-muted">未着手</div>
                <div class="h4 mb-0">{{ $report['summary']['not_started'] }}名</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="card h-100 border-info">
            <div class="card-body py-3">
                <div class="small text-muted">学習中</div>
                <div class="h4 mb-0 text-info">{{ $report['summary']['in_progress'] }}名</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="card h-100 border-success">
            <div class="card-body py-3">
                <div class="small text-muted">修了</div>
                <div class="h4 mb-0 text-success">{{ $report['summary']['completed'] }}名</div>
            </div>
        </div>
    </div>
</div>

@if ($report['rows']->isEmpty())
    <div class="alert alert-info mb-0">
        このコースを閲覧できる利用可能なユーザーがいません。ページの公開設定とグループ権限を確認してください。
    </div>
@else
    <div class="alert alert-light border small">
        教材列は現在の進捗を表示します。小テストは採点済み受験のうち最高得点を表示します。
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="thead-light">
                <tr>
                    <th class="text-nowrap">受講者</th>
                    <th class="text-nowrap text-center">コース状況</th>
                    <th class="text-nowrap text-center">必須進捗</th>
                    @foreach ($report['contents'] as $content)
                        <th class="text-nowrap text-center" style="min-width: 145px;">
                            <div>{{ $content->title }}</div>
                            <div class="small font-weight-normal text-muted">
                                {{ $content_type_labels[$content->content_type] ?? $content->content_type }}
                                @if ($content->is_required)
                                    ・必須
                                @else
                                    ・任意
                                @endif
                            </div>
                            @if (
                                $can_manage
                                && $content->content_type === 'questionnaire'
                                && !empty($content->page_id)
                                && !empty($content->frame_id)
                                && !empty($content->reference_id)
                            )
                                <a href="{{ url('/') }}/plugin/forms/listInputs/{{ $content->page_id }}/{{ $content->frame_id }}/{{ $content->reference_id }}#frame-{{ $content->frame_id }}"
                                   class="btn btn-outline-primary btn-sm mt-1"
                                   target="_blank"
                                   rel="noopener">
                                    登録一覧
                                </a>
                            @endif
                        </th>
                    @endforeach
                    <th class="text-nowrap">受講開始</th>
                    <th class="text-nowrap">修了日時</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['rows'] as $row)
                    @php
                        $enrollment = $row['enrollment'];
                        $course_status = $enrollment->status;
                    @endphp
                    <tr>
                        <th class="align-middle text-nowrap">
                            @if (!empty($row['user']))
                                {{ $row['user']->name }}
                            @else
                                ユーザーID {{ $enrollment->user_id }}
                            @endif
                        </th>
                        <td class="align-middle text-center">
                            @if ($course_status === 'completed')
                                <span class="badge badge-success yuyu-learning-badge">修了</span>
                            @elseif ($course_status === 'in_progress')
                                <span class="badge badge-info yuyu-learning-badge">学習中</span>
                            @else
                                <span class="badge badge-light border yuyu-learning-badge">未着手</span>
                            @endif
                        </td>
                        <td class="align-middle text-center text-nowrap">
                            <div>
                                <strong>{{ $row['required_completed'] }}</strong>
                                /
                                {{ $row['required_total'] }}
                            </div>
                            <div class="small text-muted">{{ $row['percentage'] }}%</div>
                        </td>
                        @foreach ($report['contents'] as $content)
                            @php
                                $content_progress = $row['content_progresses'][$content->id];
                                $content_status = $content_progress['status'];
                                $quiz_result = $content_progress['quiz_result'];
                            @endphp
                            <td class="align-middle text-center">
                                @if ($content_status === 'completed')
                                    <span class="badge badge-success yuyu-learning-badge">完了</span>
                                @elseif ($content_status === 'failed')
                                    <span class="badge badge-danger yuyu-learning-badge">不合格</span>
                                @elseif ($content_status === 'in_progress')
                                    <span class="badge badge-info yuyu-learning-badge">学習中</span>
                                @else
                                    <span class="badge badge-light border yuyu-learning-badge">未着手</span>
                                @endif

                                @if ($content->content_type === 'quiz' && !empty($quiz_result))
                                    <div class="small mt-1 text-nowrap">
                                        {{ number_format((float) $quiz_result->total_score, 2) }}
                                        /
                                        {{ number_format((float) $quiz_result->effective_max_score, 2) }}点
                                    </div>
                                    @if (!is_null($quiz_result->score_rate))
                                        <div class="small text-muted">
                                            {{ number_format((float) $quiz_result->score_rate, 1) }}%
                                        </div>
                                    @endif
                                    <div class="small">
                                        @if ($quiz_result->pass_status === 'passed')
                                            <span class="text-success">合格</span>
                                        @elseif ($quiz_result->pass_status === 'failed')
                                            <span class="text-danger">不合格</span>
                                        @else
                                            <span class="text-muted">判定なし</span>
                                        @endif
                                    </div>
                                @endif

                                @if (
                                    $content->content_type === 'learningtask'
                                    && !empty($content->page_id)
                                    && !empty($content->frame_id)
                                    && !empty($content->reference_id)
                                )
                                    <div class="mt-1">
                                        <a href="{{ url('/') }}/redirect/plugin/learningtasks/switchUserUrl/{{ $content->page_id }}/{{ $content->frame_id }}/{{ $content->reference_id }}?student_id={{ $enrollment->user_id }}#frame-{{ $content->frame_id }}"
                                           class="btn btn-outline-primary btn-sm text-nowrap"
                                           target="_blank"
                                           rel="noopener">
                                            提出内容
                                        </a>
                                    </div>
                                @endif
                            </td>
                        @endforeach
                        <td class="align-middle text-nowrap">
                            @if (!empty($enrollment->started_at))
                                {{ $enrollment->started_at->format('Y/m/d H:i') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="align-middle text-nowrap">
                            @if (!empty($enrollment->completed_at))
                                {{ $enrollment->completed_at->format('Y/m/d H:i') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection
