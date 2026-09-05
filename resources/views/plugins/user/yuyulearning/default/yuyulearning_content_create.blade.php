@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@php
    $content = $content ?? new \App\Models\User\YuyuLearning\YuyuLearningContent();
    $is_create = $is_create ?? empty($content->id);
    $content_sources = $content_sources ?? [];
    $selected_source_key = old('source_key', $selected_source_key ?? '');

    if (empty($content->content_type)) {
        $content->content_type = 'general';
    }
    if (is_null($content->is_required)) {
        $content->is_required = true;
    }
    if (is_null($content->sort_order)) {
        $content->sort_order = 0;
    }

    $selected_type = old('content_type', $content->content_type);
    $is_custom = $selected_type === 'custom';
    $initial_sources = $content_sources[$selected_type] ?? [];
@endphp

<div class="card">
    <div class="card-header">
        {{ $is_create ? '教材を追加' : '教材を編集' }}
    </div>

    <div class="card-body">
        <div class="mb-2">
            <span class="text-muted">コース：</span>{{ $course->name }}
        </div>
        <div class="mb-4">
            <span class="text-muted">章：</span>{{ $section->title }}
        </div>

        <form action="{{ url('/redirect/plugin/yuyulearning/saveContent/' . $page->id . '/' . $frame->id . ($is_create ? '' : '/' . $content->id)) }}"
              method="POST">
            @csrf
            <input type="hidden" name="section_id" value="{{ $section->id }}">

            <div class="form-group">
                <label for="yuyu_learning_content_title">教材名 <span class="badge badge-danger">必須</span></label>
                <input type="text" id="yuyu_learning_content_title" name="title"
                       value="{{ old('title', $content->title) }}"
                       class="form-control @error('title') is-invalid @enderror" maxlength="191" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-muted">LMSのコース目次に表示する名称です。</small>
            </div>

            <div class="form-group">
                <label for="yuyu_learning_content_type">教材タイプ <span class="badge badge-danger">必須</span></label>
                <select id="yuyu_learning_content_type" name="content_type"
                        class="form-control @error('content_type') is-invalid @enderror"
                        onchange="window.lmsRefreshContentFields && window.lmsRefreshContentFields();">
                    <option value="general" @if ($selected_type === 'general') selected @endif>固定記事教材</option>
                    <option value="blog" @if ($selected_type === 'blog') selected @endif>ブログ教材</option>
                    <option value="quiz" @if ($selected_type === 'quiz') selected @endif>小テスト教材</option>
                    <option value="questionnaire" @if ($selected_type === 'questionnaire') selected @endif>アンケート教材</option>
                    <option value="custom" @if ($selected_type === 'custom') selected @endif>外部教材</option>
                    <option value="learningtask" @if ($selected_type === 'learningtask') selected @endif>レポート課題</option>
                </select>
                @error('content_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="yuyu_learning_content_description">教材説明</label>
                <textarea id="yuyu_learning_content_description" name="description"
                          class="form-control @error('description') is-invalid @enderror"
                          rows="4">{{ old('description', $content->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div id="yuyu_learning_source_group" class="form-group" @if ($is_custom) style="display:none;" @endif>
                <label for="yuyu_learning_content_source">教材を選択 <span class="badge badge-danger">必須</span></label>
                <select id="yuyu_learning_content_source" name="source_key"
                        class="form-control @error('source_key') is-invalid @enderror"
                        @if ($is_custom) disabled @endif>
                    <option value="">-- 教材を選択してください --</option>
                    @foreach ($initial_sources as $source)
                        <option value="{{ $source['key'] }}" @if ($selected_source_key === $source['key']) selected @endif>
                            {{ $source['label'] }}
                        </option>
                    @endforeach
                </select>
                @error('source_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-muted">
                    教材名と配置ページから選択してください。ページID・フレームID・参照IDはLMSが自動的に登録します。
                </small>
                <div id="yuyu_learning_no_source_message" class="alert alert-warning mt-2 mb-0" style="display:none;">
                    この教材タイプで選択できる教材がありません。先にConnect-CMS側で教材を作成・配置してください。
                </div>
            </div>

            <div id="yuyu_learning_reference_url_group" class="form-group" @if (!$is_custom) style="display:none;" @endif>
                <label for="yuyu_learning_content_reference_url">外部教材URL <span class="badge badge-danger">必須</span></label>
                <input type="url" id="yuyu_learning_content_reference_url" name="reference_url"
                       value="{{ old('reference_url', $content->reference_url) }}"
                       class="form-control @error('reference_url') is-invalid @enderror"
                       placeholder="https://example.com/material"
                       @if (!$is_custom) disabled @endif>
                <small class="form-text text-muted">
                    Connect-CMS外にある教材のURLを指定します。PDF・Word・画像・動画などをConnect-CMSへアップロードして使う場合は、固定記事のWYSIWYGエディタで配置し「固定記事教材」として登録してください。
                </small>
                @error('reference_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" id="yuyu_learning_content_required" name="is_required" value="1"
                           class="custom-control-input" @if (old('is_required', $content->is_required)) checked @endif>
                    <label class="custom-control-label" for="yuyu_learning_content_required">必須教材にする</label>
                </div>
            </div>

            <div class="form-group">
                <label for="yuyu_learning_content_sort_order">表示順</label>
                <input type="number" id="yuyu_learning_content_sort_order" name="sort_order"
                       value="{{ old('sort_order', $content->sort_order) }}" min="0"
                       class="form-control @error('sort_order') is-invalid @enderror" style="max-width:160px;">
                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="alert alert-info" id="yuyu_learning_content_type_help"></div>

            <div class="d-flex flex-wrap">
                <button type="submit" class="btn btn-primary mr-2 mb-2">{{ $is_create ? '教材を追加' : '教材を更新' }}</button>
                <a href="{{ url('/') }}/plugin/yuyulearning/editCourse/{{ $page->id }}/{{ $frame->id }}/{{ $course->id }}#frame-{{ $frame->id }}"
                   class="btn btn-secondary mb-2">コース編集へ戻る</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var contentSources = @json($content_sources);
    var initialType = @json($selected_type);
    var initialSourceKey = @json($selected_source_key);
    var rememberedSources = {};
    if (initialSourceKey) {
        rememberedSources[initialType] = initialSourceKey;
    }

    function fillSourceSelect(type) {
        var sourceSelect = document.getElementById('yuyu_learning_content_source');
        var noSourceMessage = document.getElementById('yuyu_learning_no_source_message');
        if (!sourceSelect) return;

        var previousType = sourceSelect.getAttribute('data-current-type');
        if (previousType && sourceSelect.value) {
            rememberedSources[previousType] = sourceSelect.value;
        }

        while (sourceSelect.firstChild) {
            sourceSelect.removeChild(sourceSelect.firstChild);
        }

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- 教材を選択してください --';
        sourceSelect.appendChild(placeholder);

        var rows = contentSources[type] || [];
        rows.forEach(function (source) {
            var option = document.createElement('option');
            option.value = source.key;
            option.textContent = source.label;
            sourceSelect.appendChild(option);
        });

        var wanted = rememberedSources[type] || (type === initialType ? initialSourceKey : '');
        if (wanted) {
            sourceSelect.value = wanted;
        }

        sourceSelect.setAttribute('data-current-type', type);
        if (noSourceMessage) {
            noSourceMessage.style.display = rows.length === 0 ? '' : 'none';
        }
    }

    window.lmsRefreshContentFields = function () {
        var typeSelect = document.getElementById('yuyu_learning_content_type');
        var sourceGroup = document.getElementById('yuyu_learning_source_group');
        var sourceSelect = document.getElementById('yuyu_learning_content_source');
        var referenceUrlGroup = document.getElementById('yuyu_learning_reference_url_group');
        var referenceUrl = document.getElementById('yuyu_learning_content_reference_url');
        var typeHelp = document.getElementById('yuyu_learning_content_type_help');

        if (!typeSelect || !sourceGroup || !sourceSelect || !referenceUrlGroup) {
            return;
        }

        var type = typeSelect.value;
        var isCustom = type === 'custom';

        sourceGroup.style.display = isCustom ? 'none' : '';
        referenceUrlGroup.style.display = isCustom ? '' : 'none';
        sourceSelect.disabled = isCustom;
        if (referenceUrl) referenceUrl.disabled = !isCustom;

        if (!isCustom) {
            fillSourceSelect(type);
        }

        if (type === 'blog') {
            typeHelp.textContent = '既存のブログを教材として選択します。複数記事で構成する継続型教材やコメントを利用した質疑・共有に向いています。';
        } else if (type === 'quiz') {
            typeHelp.textContent = '既存の小テストを教材として選択します。受験・採点結果はQuizzes側を正としてLMSが参照します。';
        } else if (type === 'questionnaire') {
            typeHelp.textContent = '既存のアンケートを教材として選択します。Formsのアンケート回答結果をLMSが参照します。';
        } else if (type === 'learningtask') {
            typeHelp.textContent = '既存の課題管理の科目・レポート課題を教材として選択します。提出状況はLearningtasks側を正としてLMSが参照します。';
        } else if (type === 'custom') {
            typeHelp.textContent = 'Connect-CMS外にある教材をURLで参照します。';
        } else {
            typeHelp.textContent = '既存の固定記事フレームを教材として選択します。WYSIWYG本文や、そこへ配置・添付したファイルを一つの教材として利用できます。';
        }
    };

    window.lmsRefreshContentFields();
})();
</script>

@endsection
