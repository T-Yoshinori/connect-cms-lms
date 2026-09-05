@extends('core.cms_frame_base_setting')

@section("core.cms_frame_edit_tab_$frame->id")
    @include('plugins.user.yuyulearning.yuyulearning_frame_edit_tab')
@endsection

@section("plugin_setting_$frame->id")

<div class="card">
    <div class="card-header">新規作成</div>

    <div class="card-body">
        <p class="text-muted">
            新しいコースを作成します。作成したコースは、このフレームで使用するコースとして自動的に選択されます。
        </p>

        <form action="{{ url('/redirect/plugin/yuyulearning/saveCourse/' . $page->id . '/' . $frame->id) }}"
              method="POST">
            @csrf

            <input type="hidden" name="assign_to_frame" value="1">
            <input type="hidden"
                   name="normal_page_path"
                   value="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}">

            <div class="form-group">
                <label for="yuyu_learning_course_name">コース名 <span class="badge badge-danger">必須</span></label>
                <input type="text"
                       id="yuyu_learning_course_name"
                       name="name"
                       value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror"
                       maxlength="191"
                       required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="yuyu_learning_course_description">コース説明</label>
                <textarea id="yuyu_learning_course_description"
                          name="description"
                          class="form-control @error('description') is-invalid @enderror"
                          rows="5">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="yuyu_learning_course_status">公開状態</label>
                <select id="yuyu_learning_course_status"
                        name="status"
                        class="form-control @error('status') is-invalid @enderror">
                    <option value="draft" @if (old('status', 'draft') === 'draft') selected @endif>下書き</option>
                    <option value="published" @if (old('status') === 'published') selected @endif>公開</option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <input type="hidden" name="sort_order" value="{{ old('sort_order', $sort_order) }}">

            <div class="text-center mt-4">
                <button type="submit" name="after_save" value="return" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 作成して使用
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
