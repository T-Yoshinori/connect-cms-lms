@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@php
    $is_create = $is_create ?? false;
@endphp

<div class="card">
    <div class="card-header">
        {{ $is_create ? '章を追加' : '章を編集' }}
    </div>

    <div class="card-body">
        <div class="small text-muted mb-3">
            コース：{{ $course->name }}
        </div>

        <form action="{{ url('/redirect/plugin/yuyulearning/saveSection/' . $page->id . '/' . $frame->id . ($is_create ? '' : '/' . $section->id)) }}"
              method="POST">
            @csrf

            <input type="hidden" name="course_id" value="{{ $course->id }}">

            <div class="form-group">
                <label for="yuyu_learning_section_title">
                    章タイトル <span class="badge badge-danger">必須</span>
                </label>
                <input type="text"
                       id="yuyu_learning_section_title"
                       name="title"
                       value="{{ old('title', $section->title) }}"
                       class="form-control @error('title') is-invalid @enderror"
                       maxlength="191"
                       required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="yuyu_learning_section_description">章の説明</label>
                <textarea id="yuyu_learning_section_description"
                          name="description"
                          rows="5"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $section->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex flex-wrap">
                <button type="submit" class="btn btn-primary mr-2 mb-2">
                    {{ $is_create ? '章を追加する' : '章を保存する' }}
                </button>
                <a href="{{ url('/') }}/plugin/yuyulearning/editCourse/{{ $page->id }}/{{ $frame->id }}/{{ $course->id }}#frame-{{ $frame->id }}"
                   class="btn btn-secondary mb-2">
                    コース編集へ戻る
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
