@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@php
    $is_create = $is_create ?? false;
    $sections = $course->sections ?? collect();
    $content_type_labels = [
        'general' => '固定記事教材',
        'blog' => 'ブログ教材',
        'quiz' => '小テスト教材',
        'questionnaire' => 'アンケート教材',
        'custom' => '外部教材',
        'learningtask' => 'レポート課題',
    ];
@endphp

<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <span>{{ $is_create ? 'コース新規作成' : 'コース編集' }}</span>
        @if (!$is_create)
            <a href="{{ url('/') }}/plugin/lms/adminProgress/{{ $page->id }}/{{ $frame->id }}/{{ $course->id }}#frame-{{ $frame->id }}"
               class="btn btn-outline-success btn-sm mt-2 mt-sm-0">
                <i class="fas fa-chart-bar"></i> 受講進捗
            </a>
        @endif
    </div>

    <div class="card-body">
        <form action="{{ url('/redirect/plugin/lms/saveCourse/' . $page->id . '/' . $frame->id . ($is_create ? '' : '/' . $course->id)) }}"
              method="POST">
            @csrf

            <input type="hidden"
                   name="normal_page_path"
                   value="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}">

            <div class="form-group">
                <label for="lms_course_name">コース名 <span class="badge badge-danger">必須</span></label>
                <input type="text"
                       id="lms_course_name"
                       name="name"
                       value="{{ old('name', $course->name) }}"
                       class="form-control @error('name') is-invalid @enderror"
                       maxlength="191"
                       required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="lms_course_description">コース説明</label>
                <textarea id="lms_course_description"
                          name="description"
                          class="form-control @error('description') is-invalid @enderror"
                          rows="5">{{ old('description', $course->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="lms_course_status">公開状態</label>
                <select id="lms_course_status"
                        name="status"
                        class="form-control @error('status') is-invalid @enderror">
                    <option value="draft" @if (old('status', $course->status) === 'draft') selected @endif>下書き</option>
                    <option value="published" @if (old('status', $course->status) === 'published') selected @endif>公開</option>
                    <option value="closed" @if (old('status', $course->status) === 'closed') selected @endif>公開終了</option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="lms_course_sort_order">表示順</label>
                <input type="number"
                       id="lms_course_sort_order"
                       name="sort_order"
                       value="{{ old('sort_order', $course->sort_order ?? 0) }}"
                       min="0"
                       class="form-control @error('sort_order') is-invalid @enderror"
                       style="max-width: 160px;">
                @error('sort_order')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex flex-wrap">
                <button type="submit" name="after_save" value="continue" class="btn btn-primary mr-2 mb-2">
                    保存して継続
                </button>
                <button type="submit" name="after_save" value="return" class="btn btn-success mr-2 mb-2">
                    保存して戻る
                </button>
                <a href="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}"
                   class="btn btn-secondary mb-2">
                    戻る
                </a>
            </div>
        </form>
    </div>
</div>

@if (!$is_create)
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <span class="font-weight-bold">章・教材構成</span>
            <span class="small text-muted ml-2">コース内の学習順を設定します。</span>
        </div>
        <a href="{{ url('/') }}/plugin/lms/createSection/{{ $page->id }}/{{ $frame->id }}/{{ $course->id }}#frame-{{ $frame->id }}"
           class="btn btn-primary btn-sm mt-2 mt-sm-0">
            <i class="fas fa-plus"></i> 章を追加
        </a>
    </div>

    <div class="card-body">
        @if ($sections->isEmpty())
            <div class="alert alert-secondary mb-0">
                章はまだ登録されていません。「章を追加」から最初の章を作成してください。
            </div>
        @else
            @foreach ($sections as $section)
                @php $contents = $section->contents ?? collect(); @endphp
                <div class="border rounded mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center bg-light px-3 py-2 border-bottom">
                        <div class="mr-3">
                            <span class="badge badge-secondary mr-2">第{{ $loop->iteration }}章</span>
                            <span class="font-weight-bold">{{ $section->title }}</span>
                            <span class="small text-muted ml-2">教材 {{ $contents->count() }}件</span>
                        </div>

                        <div class="d-flex flex-wrap align-items-center mt-2 mt-sm-0">
                            <form action="{{ url('/redirect/plugin/lms/moveSection/' . $page->id . '/' . $frame->id . '/' . $section->id) }}"
                                  method="POST"
                                  class="mr-1">
                                @csrf
                                <input type="hidden" name="direction" value="up">
                                <button type="submit"
                                        class="btn btn-outline-secondary btn-sm"
                                        title="上へ移動"
                                        @if ($loop->first) disabled @endif>
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                            </form>

                            <form action="{{ url('/redirect/plugin/lms/moveSection/' . $page->id . '/' . $frame->id . '/' . $section->id) }}"
                                  method="POST"
                                  class="mr-2">
                                @csrf
                                <input type="hidden" name="direction" value="down">
                                <button type="submit"
                                        class="btn btn-outline-secondary btn-sm"
                                        title="下へ移動"
                                        @if ($loop->last) disabled @endif>
                                    <i class="fas fa-arrow-down"></i>
                                </button>
                            </form>

                            <a href="{{ url('/') }}/plugin/lms/editSection/{{ $page->id }}/{{ $frame->id }}/{{ $section->id }}#frame-{{ $frame->id }}"
                               class="btn btn-outline-primary btn-sm mr-2">
                                章を編集
                            </a>

                            <a href="{{ url('/') }}/plugin/lms/createContent/{{ $page->id }}/{{ $frame->id }}/{{ $section->id }}#frame-{{ $frame->id }}"
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> 教材を追加
                            </a>
                        </div>
                    </div>

                    <div class="px-3 py-3">
                        @if (!empty($section->description))
                            <div class="mb-3">{!! nl2br(e($section->description)) !!}</div>
                        @endif

                        @if ($contents->isEmpty())
                            <div class="small text-muted">この章には教材がまだありません。</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 70px;" class="text-center">順序</th>
                                            <th>教材名</th>
                                            <th style="width: 140px;">タイプ</th>
                                            <th style="width: 80px;" class="text-center">必須</th>
                                            <th style="width: 150px;" class="text-center">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($contents as $content)
                                            <tr>
                                                <td class="text-center align-middle">{{ $content->sort_order }}</td>
                                                <td class="align-middle">
                                                    <strong>{{ $content->title }}</strong>
                                                    @if (!empty($content->description))
                                                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($content->description), 80) }}</div>
                                                    @endif
                                                </td>
                                                <td class="align-middle">{{ $content_type_labels[$content->content_type] ?? $content->content_type }}</td>
                                                <td class="text-center align-middle">
                                                    @if ($content->is_required)
                                                        <span class="badge badge-danger">必須</span>
                                                    @else
                                                        <span class="badge badge-secondary">任意</span>
                                                    @endif
                                                </td>
                                                <td class="text-center align-middle">
                                                    <div class="d-flex justify-content-center flex-wrap">
                                                        <a href="{{ url('/') }}/plugin/lms/editContent/{{ $page->id }}/{{ $frame->id }}/{{ $content->id }}#frame-{{ $frame->id }}"
                                                           class="btn btn-outline-primary btn-sm mr-1 mb-1">
                                                            編集
                                                        </a>
                                                        <form action="{{ url('/redirect/plugin/lms/deleteContent/' . $page->id . '/' . $frame->id . '/' . $content->id) }}"
                                                              method="POST"
                                                              class="mb-1"
                                                              onsubmit="return confirm('教材「{{ addslashes($content->title) }}」を削除します。よろしいですか？');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                削除
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endif

@endsection

