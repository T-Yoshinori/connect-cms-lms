@extends('core.cms_frame_base_setting')

@section("core.cms_frame_edit_tab_$frame->id")
    @include('plugins.user.lms.lms_frame_edit_tab')
@endsection

@section("plugin_setting_$frame->id")

<div class="card">
    <div class="card-header">コース選択</div>

    <div class="card-body">
        <p class="text-muted">
            このフレームで表示するコースを選択してください。
        </p>

        @if ($courses->isEmpty())
            <div class="alert alert-secondary mb-3">
                使用できるコースはありません。「新規作成」からコースを作成してください。
            </div>
        @else
            <form action="{{ url('/redirect/plugin/lms/selectCourse/' . $page->id . '/' . $frame->id) }}"
                  method="POST">
                @csrf

                <input type="hidden"
                       name="normal_page_path"
                       value="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}">

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 70px;" class="text-center">選択</th>
                                <th>コース名</th>
                                <th style="width: 130px;" class="text-center">公開状態</th>
                                <th style="width: 160px;">更新日時</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($courses as $course)
                                <tr @if ((int) $selected_course_id === (int) $course->id) class="table-info" @endif>
                                    <td class="text-center align-middle">
                                        <input type="radio"
                                               id="course_id_{{ $course->id }}"
                                               name="course_id"
                                               value="{{ $course->id }}"
                                               @if ((int) old('course_id', $selected_course_id) === (int) $course->id) checked @endif>
                                    </td>
                                    <td class="align-middle">
                                        <label for="course_id_{{ $course->id }}" class="mb-0">
                                            <strong>{{ $course->name }}</strong>
                                            @if ((int) $selected_course_id === (int) $course->id)
                                                <span class="badge badge-primary ml-1">使用中</span>
                                            @endif
                                            @if (!empty($course->description))
                                                <div class="small text-muted mt-1">
                                                    {{ \Illuminate\Support\Str::limit(strip_tags($course->description), 100) }}
                                                </div>
                                            @endif
                                        </label>
                                    </td>
                                    <td class="text-center align-middle">
                                        @if ($course->status === 'published')
                                            <span class="badge badge-success">公開</span>
                                        @elseif ($course->status === 'pending_approval')
                                            <span class="badge badge-info">承認待ち</span>
                                        @elseif ($course->status === 'closed')
                                            <span class="badge badge-secondary">公開終了</span>
                                        @else
                                            <span class="badge badge-warning">下書き</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        @if (!empty($course->updated_at))
                                            {{ $course->updated_at->format('Y/m/d H:i') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('course_id')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> このコースを使用
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

@endsection

