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
    $can_view_progress = $can_view_progress ?? false;
    $course = $course ?? null;
    $enrollment = $enrollment ?? null;
    $progresses = $progresses ?? collect();
    $course_progress = $course_progress ?? null;
    $yuyu_learning_frame = $yuyu_learning_frame ?? null;
    $can_track_progress = !empty($enrollment);
    $content_type_labels = [
        'general' => '固定記事教材',
        'blog' => 'ブログ教材',
        'quiz' => '小テスト教材',
        'questionnaire' => 'アンケート教材',
        'custom' => '外部教材',
        'learningtask' => 'レポート課題',
    ];
    $progress_labels = [
        'not_started' => '未着手',
        'in_progress' => '学習中',
        'failed' => '不合格',
        'completed' => '完了',
    ];
@endphp

<div id="lms-frame-{{ $frame->id }}">
@if (empty($course))
    @if ($can_manage)
        <div class="alert alert-info mb-0">
            選択画面から、使用するLMSコースを選択するか、作成してください。
            <div class="mt-3">
                <a href="{{ url('/') }}/plugin/yuyulearning/listCourses/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}" class="btn btn-primary btn-sm mr-2">コース選択</a>
                <a href="{{ url('/') }}/plugin/yuyulearning/createCourse/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}" class="btn btn-outline-primary btn-sm">新規作成</a>
            </div>
        </div>
    @else
        <div class="alert alert-info mb-0">このLMSフレームにはコースが設定されていません。</div>
    @endif
@else
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">{{ $course->name }}</h2>
            @if (!empty($course->description))
                <div class="text-muted">{!! nl2br(e($course->description)) !!}</div>
            @endif
        </div>
        @if ($can_view_progress || $can_manage)
            <div class="d-flex flex-wrap mt-2 mt-sm-0">
                @if ($can_view_progress)
                    <a href="{{ url('/') }}/plugin/yuyulearning/adminProgress/{{ $page->id }}/{{ $frame->id }}/{{ $course->id }}#frame-{{ $frame->id }}"
                       class="btn btn-outline-success btn-sm mr-2 mb-1">
                        <i class="fas fa-chart-bar"></i> 受講進捗
                    </a>
                @endif
                @if ($can_manage)
                    <a href="{{ url('/') }}/plugin/yuyulearning/editCourse/{{ $page->id }}/{{ $frame->id }}/{{ $course->id }}#frame-{{ $frame->id }}"
                       class="btn btn-outline-primary btn-sm mb-1">
                        コース編集
                    </a>
                @endif
            </div>
        @endif
    </div>

    <div class="mb-3">
        @if ($course->status === 'published')
            <span class="badge badge-success">公開</span>
        @elseif ($course->status === 'pending_approval')
            <span class="badge badge-info">承認待ち</span>
        @elseif ($course->status === 'closed')
            <span class="badge badge-secondary">公開終了</span>
        @else
            <span class="badge badge-warning">下書き</span>
        @endif
        <span class="small text-muted ml-2">章 {{ $course->sections_count }}件 / 受講者 {{ $course->enrollments_count }}名</span>
    </div>

    @if (!empty($course_progress))
        <div class="card mb-3 border-primary">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <span class="font-weight-bold">あなたの学習状況</span>
                @if ($course_progress['status'] === 'completed')
                    <span class="badge badge-success px-3 py-2">修了</span>
                @elseif ($course_progress['status'] === 'in_progress')
                    <span class="badge badge-info px-3 py-2">学習中</span>
                @else
                    <span class="badge badge-light border px-3 py-2">未着手</span>
                @endif
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between mb-2">
                    <span>
                        必須教材
                        <strong>{{ $course_progress['required_completed'] }}</strong>
                        /
                        <strong>{{ $course_progress['required_total'] }}</strong>
                        完了
                    </span>
                    <span class="font-weight-bold">{{ $course_progress['percentage'] }}%</span>
                </div>
                <div class="progress mb-2" style="height: 20px;">
                    <div class="progress-bar @if ($course_progress['status'] === 'completed') bg-success @endif"
                         role="progressbar"
                         style="width: {{ $course_progress['percentage'] }}%;"
                         aria-valuenow="{{ $course_progress['percentage'] }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        {{ $course_progress['percentage'] }}%
                    </div>
                </div>
                <div class="small text-muted">
                    全教材 {{ $course_progress['content_completed'] }} / {{ $course_progress['content_total'] }} 完了
                    @if ($course_progress['status'] === 'completed' && !empty($course_progress['completed_at']))
                        <span class="ml-3">
                            修了日時 {{ $course_progress['completed_at']->format('Y/m/d H:i') }}
                        </span>
                    @endif
                </div>
                @if ($course_progress['required_total'] === 0)
                    <div class="small text-warning mt-2">
                        必須教材が設定されていないため、コース修了の対象になりません。
                    </div>
                @endif

                @if ($course_progress['status'] === 'completed')
                    @if (!empty($yuyu_learning_frame) && !empty(trim((string) $yuyu_learning_frame->organizer_name)))
                        <div class="text-center mt-3">
                            <a href="{{ url('/') }}/download/plugin/yuyulearning/downloadCertificate/{{ $page->id }}/{{ $frame->id }}"
                               class="btn btn-outline-success">
                                <i class="fas fa-file-pdf"></i> 修了証PDFをダウンロード
                            </a>
                        </div>
                    @elseif ($can_manage)
                        <div class="alert alert-warning mt-3 mb-0">
                            修了証を発行するには、コース選択画面で主催者を入力してください。
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    <div class="alert alert-light border small mb-3">
        教材は別の教材ウィンドウ（ブラウザーの設定や端末によっては別タブ）で開きます。
        教材画面を閉じるだけでは学習完了にはなりません。途中で閉じた場合は「学習中」のまま再開できます。
    </div>

    @if (Auth::check() && !$can_track_progress)
        <div class="alert alert-warning small mb-3">
            このユーザーはこのコースに受講登録されていないため、教材の進捗は記録されません。
        </div>
    @endif

    <div class="card">
        <div class="card-header font-weight-bold">コース目次</div>
        <div class="card-body">
            @if ($course->sections->isEmpty())
                <div class="text-muted">このコースには章がまだ登録されていません。</div>
            @else
                @foreach ($course->sections as $section)
                    <div class="mb-4 @if (!$loop->last) border-bottom pb-3 @endif">
                        <h3 class="h5 mb-2">
                            <span class="badge badge-secondary mr-2">第{{ $loop->iteration }}章</span>{{ $section->title }}
                        </h3>
                        @if (!empty($section->description))
                            <div class="text-muted mb-2">{!! nl2br(e($section->description)) !!}</div>
                        @endif

                        @if ($section->contents->isEmpty())
                            <div class="small text-muted ml-2">この章には教材がまだありません。</div>
                        @else
                            <div class="list-group">
                                @foreach ($section->contents as $content)
                                    @php
                                        $launch_url = $content->getLaunchUrl();
                                        $is_external = $content->content_type === 'custom';
                                        $progress = $progresses->get($content->id);
                                        $progress_status = $progress->status ?? 'not_started';
                                    @endphp
                                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center"
                                         data-lms-content-row="{{ $content->id }}">
                                        <div class="mr-3 flex-grow-1">
                                            <span class="font-weight-bold mr-2">{{ $loop->iteration }}.</span>
                                            @if (!empty($launch_url))
                                                <a href="{{ $launch_url }}"
                                                   class="lms-material-launch"
                                                   data-lms-content-id="{{ $content->id }}"
                                                   data-lms-content-type="{{ $content->content_type }}"
                                                   data-lms-material-frame-id="{{ $content->frame_id }}"
                                                   data-lms-external="{{ $is_external ? '1' : '0' }}"
                                                   data-lms-track="{{ $can_track_progress ? '1' : '0' }}">
                                                    {{ $content->title }}
                                                </a>
                                            @else
                                                <span>{{ $content->title }}</span>
                                            @endif
                                            @if (!empty($content->description))
                                                <div class="small text-muted ml-4">{{ \Illuminate\Support\Str::limit(strip_tags($content->description), 100) }}</div>
                                            @endif
                                        </div>
                                        <div class="mt-2 mt-sm-0 d-flex flex-wrap align-items-center">
                                            @if ($can_track_progress)
                                                @if ($progress_status === 'completed')
                                                    <span class="badge badge-success yuyu-learning-badge lms-progress-badge" data-lms-progress-id="{{ $content->id }}">完了</span>
                                                @elseif ($progress_status === 'failed')
                                                    <span class="badge badge-danger yuyu-learning-badge lms-progress-badge" data-lms-progress-id="{{ $content->id }}">不合格</span>
                                                @elseif ($progress_status === 'in_progress')
                                                    <span class="badge badge-info yuyu-learning-badge lms-progress-badge" data-lms-progress-id="{{ $content->id }}">学習中</span>
                                                @else
                                                    <span class="badge badge-light border yuyu-learning-badge lms-progress-badge" data-lms-progress-id="{{ $content->id }}">未着手</span>
                                                @endif
                                            @endif
                                            <span class="badge badge-light border yuyu-learning-badge ml-1">{{ $content_type_labels[$content->content_type] ?? $content->content_type }}</span>
                                            @if ($content->is_required)
                                                <span class="badge badge-danger yuyu-learning-badge ml-1">必須</span>
                                            @else
                                                <span class="badge badge-secondary yuyu-learning-badge ml-1">任意</span>
                                            @endif
                                            @if (!empty($launch_url))
                                                <a href="{{ $launch_url }}"
                                                   class="btn btn-outline-primary btn-sm ml-2 lms-material-launch"
                                                   data-lms-content-id="{{ $content->id }}"
                                                   data-lms-content-type="{{ $content->content_type }}"
                                                   data-lms-material-frame-id="{{ $content->frame_id }}"
                                                   data-lms-external="{{ $is_external ? '1' : '0' }}"
                                                   data-lms-track="{{ $can_track_progress ? '1' : '0' }}">
                                                    教材を開く<i class="fas fa-external-link-alt ml-1"></i>
                                                </a>
                                            @else
                                                <span class="badge badge-warning ml-2">起動先未設定</span>
                                            @endif

                                            @if ($content->content_type === 'custom' && $can_track_progress && $progress_status !== 'completed')
                                                <form method="POST"
                                                      action="{{ url('/') }}/plugin/yuyulearning/completeContent/{{ $page->id }}/{{ $frame->id }}/{{ $content->id }}"
                                                      class="d-inline ml-2">
                                                    @csrf
                                                    <input type="hidden" name="return_url" value="{{ url($page->permanent_link) }}#frame-{{ $frame->id }}">
                                                    <button type="submit" class="btn btn-success btn-sm">学習完了</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endif
</div>

<script>
(function () {
    var root = document.getElementById('lms-frame-{{ $frame->id }}');
    if (!root) {
        return;
    }

    var materialWindow = null;
    var noticeTimer = null;
    var currentMaterial = null;
    var csrfToken = @json(csrf_token());
    var actionBase = @json(url('/').'/plugin/yuyulearning/');
    var jsonActionBase = @json(url('/').'/json/yuyulearning/');
    var pageId = @json($page->id);
    var frameId = @json($frame->id);

    function progressUrl(action, contentId) {
        return actionBase + action + '/' + pageId + '/' + frameId + '/' + contentId;
    }

    function postProgress(action, contentId) {
        var body = new URLSearchParams();
        body.append('_token', csrfToken);

        return fetch(progressUrl(action, contentId), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        });
    }

    function annotationUrl(action, id) {
        return jsonActionBase + action + '/' + pageId + '/' + frameId + '/' + id;
    }

    function readAnnotationJson(response) {
        return response.text().then(function (text) {
            var json = null;
            try {
                json = text ? JSON.parse(text) : {};
            } catch (e) {
                json = null;
            }
            if (!response.ok) {
                var detail = json && json.message ? json.message : '';
                if (response.status === 403) {
                    detail = '注釈を操作する権限を確認できません。';
                } else if (response.status === 419) {
                    detail = 'ログイン状態の有効期限が切れています。ページを再読み込みしてください。';
                } else if (response.status === 422 && json && json.errors) {
                    var errorKeys = Object.keys(json.errors);
                    if (errorKeys.length && json.errors[errorKeys[0]].length) {
                        detail = json.errors[errorKeys[0]][0];
                    }
                }
                throw new Error('HTTP ' + response.status + (detail ? '：' + detail : ''));
            }
            if (!json) {
                throw new Error('HTTP ' + response.status + '：サーバーからJSON形式ではない応答が返されました。');
            }
            return json;
        });
    }

    function fetchAnnotations(contentId, blogPostIds) {
        var url = annotationUrl('listAnnotations', contentId);
        if (blogPostIds && blogPostIds.length) {
            url += '?blog_post_ids=' + encodeURIComponent(blogPostIds.join(','));
        }
        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(readAnnotationJson);
    }

    function postAnnotation(action, id, values) {
        var body = new URLSearchParams();
        body.append('_token', csrfToken);
        Object.keys(values || {}).forEach(function (key) {
            if (values[key] !== null && values[key] !== undefined) {
                body.append(key, values[key]);
            }
        });

        return fetch(annotationUrl(action, id), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        }).then(readAnnotationJson);
    }

    function installAnnotationTools(win, notice, buttons, material) {
        if (win.__yuyuLearningAnnotationsInstalled || !material.canTrack ||
            (material.type !== 'general' && material.type !== 'blog')) {
            return;
        }

        var doc = win.document;
        var materialRoot = doc.getElementById('frame-' + material.materialFrameId);
        if (!materialRoot) {
            return;
        }
        win.__yuyuLearningAnnotationsInstalled = true;

        var state = {mode: null, color: 'yellow', annotations: []};
        var excludedSelector = '#lms-material-window-notice, script, style, noscript, button, input, textarea, select, [data-yuyu-ignore-text]';
        var colors = {
            yellow: '#ffe066',
            green: '#8ce99a',
            blue: '#74c0fc',
            pink: '#faa2c1'
        };

        var style = doc.createElement('style');
        style.textContent =
            '.yuyu-annotation-toolbar{display:flex;flex-wrap:wrap;gap:5px;align-items:center;margin-right:8px}' +
            '.yuyu-annotation-tool{padding:4px 9px;border:1px solid #856404;border-radius:4px;background:#fff;color:#856404;cursor:pointer}' +
            '.yuyu-annotation-tool.is-active{box-shadow:0 0 0 2px #856404 inset;font-weight:bold}' +
            '.yuyu-annotation-color{width:27px;height:27px;border:1px solid #777;border-radius:50%;cursor:pointer}' +
            '.yuyu-annotation-color.is-active{box-shadow:0 0 0 3px #fff,0 0 0 5px #856404}' +
            '.yuyu-annotation-mark{padding:.05em .02em;border-radius:2px;box-shadow:inset 0 -2px rgba(0,0,0,.12);cursor:pointer}' +
            '.yuyu-annotation-note-icon{margin:0 3px;padding:1px 4px;border:2px solid #b93815;border-radius:5px;background:#fff4e6;color:#c2410c;cursor:pointer;vertical-align:super;font-size:19px;line-height:1;box-shadow:0 1px 3px rgba(0,0,0,.25)}' +
            '.yuyu-annotations-hidden .yuyu-annotation-mark{background-color:transparent!important;box-shadow:none;cursor:text}' +
            '.yuyu-annotations-hidden .yuyu-annotation-note-icon{display:none!important}' +
            '.yuyu-annotation-panel{display:none;position:fixed;right:12px;top:70px;z-index:2147483647;width:min(420px,calc(100vw - 24px));max-height:70vh;overflow:auto;padding:12px;background:#fff;border:1px solid #856404;border-radius:6px;box-shadow:0 4px 15px rgba(0,0,0,.25);color:#333}' +
            '.yuyu-annotation-actions{display:none;position:fixed;z-index:2147483647;min-width:260px;max-width:calc(100vw - 24px);padding:10px;background:#fff;border:2px solid #856404;border-radius:6px;box-shadow:0 4px 15px rgba(0,0,0,.3);color:#333}' +
            '.yuyu-annotation-actions textarea{width:100%;min-height:80px;margin:6px 0;padding:6px;border:1px solid #aaa;border-radius:4px}' +
            '.yuyu-annotation-item{padding:8px 0;border-bottom:1px solid #ddd}' +
            '.yuyu-annotation-item:last-child{border-bottom:0}';
        doc.head.appendChild(style);

        var actionPanel = doc.createElement('div');
        actionPanel.className = 'yuyu-annotation-actions';
        actionPanel.setAttribute('data-yuyu-ignore-text', '1');
        doc.body.appendChild(actionPanel);

        function annotationValues(annotation) {
            return {
                annotation_id: annotation.id,
                annotation_type: annotation.annotation_type,
                blog_post_id: annotation.blog_post_id,
                color: annotation.color,
                selected_text: annotation.selected_text,
                prefix_text: annotation.prefix_text,
                suffix_text: annotation.suffix_text,
                start_offset: annotation.start_offset,
                end_offset: annotation.end_offset,
                note: annotation.note
            };
        }

        function deleteAnnotationRecord(annotation) {
            if (!win.confirm('この注釈を削除しますか。')) {
                return;
            }
            postAnnotation('deleteAnnotation', annotation.id, {}).then(function () {
                removeRendered(annotation.id);
                state.annotations = state.annotations.filter(function (row) { return row.id !== annotation.id; });
                actionPanel.style.display = 'none';
                refreshPanel();
            }).catch(function (error) {
                win.alert('注釈を削除できませんでした。\n' + error.message);
            });
        }

        function positionActionPanel(anchor) {
            var rect = anchor.getBoundingClientRect();
            var left = Math.min(rect.left, win.innerWidth - 280);
            actionPanel.style.left = Math.max(12, left) + 'px';
            actionPanel.style.top = Math.min(rect.bottom + 8, win.innerHeight - 210) + 'px';
            actionPanel.style.display = 'block';
        }

        function actionButton(label, className) {
            var button = doc.createElement('button');
            button.type = 'button';
            button.className = className || 'btn btn-sm btn-outline-secondary';
            button.textContent = label;
            return button;
        }

        function openAnnotationActions(annotation, anchor) {
            actionPanel.innerHTML = '';
            var heading = doc.createElement('div');
            heading.className = 'font-weight-bold mb-2';
            heading.textContent = annotation.annotation_type === 'highlight' ? 'マーカーの操作' : 'メモの操作';
            actionPanel.appendChild(heading);

            if (annotation.annotation_type === 'highlight') {
                var colorRow = doc.createElement('div');
                colorRow.className = 'd-flex flex-wrap align-items-center mb-2';
                Object.keys(colors).forEach(function (color) {
                    var colorButton = doc.createElement('button');
                    colorButton.type = 'button';
                    colorButton.className = 'yuyu-annotation-color mr-2' + (color === annotation.color ? ' is-active' : '');
                    colorButton.style.backgroundColor = colors[color];
                    colorButton.title = color;
                    colorButton.setAttribute('aria-label', color + 'へ変更');
                    colorButton.addEventListener('click', function () {
                        var values = annotationValues(annotation);
                        values.color = color;
                        postAnnotation('saveAnnotation', material.id, values).then(function (json) {
                            annotation.color = json.annotation.color;
                            materialRoot.querySelectorAll('.yuyu-annotation-mark[data-yuyu-annotation="' + annotation.id + '"]').forEach(function (mark) {
                                mark.style.backgroundColor = colors[annotation.color];
                            });
                            actionPanel.style.display = 'none';
                            refreshPanel();
                        }).catch(function (error) {
                            win.alert('マーカー色を変更できませんでした。\n' + error.message);
                        });
                    });
                    colorRow.appendChild(colorButton);
                });
                actionPanel.appendChild(colorRow);
            } else {
                var textarea = doc.createElement('textarea');
                textarea.value = annotation.note || '';
                textarea.setAttribute('aria-label', 'メモ本文');
                actionPanel.appendChild(textarea);
                var saveButton = actionButton('保存', 'btn btn-sm btn-primary mr-2');
                saveButton.addEventListener('click', function () {
                    if (!textarea.value.trim()) {
                        win.alert('メモを入力してください。');
                        return;
                    }
                    var values = annotationValues(annotation);
                    values.note = textarea.value.trim();
                    postAnnotation('saveAnnotation', material.id, values).then(function (json) {
                        annotation.note = json.annotation.note;
                        materialRoot.querySelectorAll('.yuyu-annotation-note-icon[data-yuyu-annotation="' + annotation.id + '"]').forEach(function (icon) {
                            icon.title = annotation.note;
                        });
                        actionPanel.style.display = 'none';
                        refreshPanel();
                    }).catch(function (error) {
                        win.alert('メモを更新できませんでした。\n' + error.message);
                    });
                });
                actionPanel.appendChild(saveButton);
            }

            var deleteButton = actionButton('削除', 'btn btn-sm btn-danger mr-2');
            deleteButton.addEventListener('click', function () { deleteAnnotationRecord(annotation); });
            actionPanel.appendChild(deleteButton);
            var closeButton = actionButton('閉じる');
            closeButton.addEventListener('click', function () { actionPanel.style.display = 'none'; });
            actionPanel.appendChild(closeButton);
            positionActionPanel(anchor);
        }

        function blogPostId(article) {
            if (!article) {
                return null;
            }
            var dataId = article.getAttribute('data-blog-post-id');
            if (dataId && /^\d+$/.test(dataId)) {
                return Number(dataId);
            }
            var link = article.querySelector('a[href*="/plugin/blogs/show/"]');
            var match = link ? link.getAttribute('href').match(/\/plugin\/blogs\/show\/\d+\/\d+\/(\d+)/) : null;
            if (match) {
                return Number(match[1]);
            }
            match = win.location.pathname.match(/\/plugin\/blogs\/show\/\d+\/\d+\/(\d+)/);
            return match ? Number(match[1]) : null;
        }

        function visibleBlogRoots() {
            if (material.type !== 'blog') {
                return [];
            }
            return Array.prototype.slice.call(materialRoot.querySelectorAll('article')).map(function (article) {
                return {root: article, blogPostId: blogPostId(article)};
            }).filter(function (item) { return item.blogPostId; });
        }

        function annotationRoot(annotation) {
            if (material.type !== 'blog') {
                return materialRoot;
            }
            var targetId = Number(annotation.blog_post_id);
            var item = visibleBlogRoots().find(function (candidate) {
                return candidate.blogPostId === targetId;
            });
            return item ? item.root : null;
        }

        function textNodes(scopeRoot) {
            var nodes = [];
            var walker = doc.createTreeWalker(scopeRoot, win.NodeFilter.SHOW_TEXT, {
                acceptNode: function (node) {
                    var parent = node.parentElement;
                    if (!parent || parent.closest(excludedSelector)) {
                        return win.NodeFilter.FILTER_REJECT;
                    }
                    return win.NodeFilter.FILTER_ACCEPT;
                }
            });
            var node;
            while ((node = walker.nextNode())) {
                nodes.push(node);
            }
            return nodes;
        }

        function plainText(scopeRoot) {
            return textNodes(scopeRoot).map(function (node) { return node.nodeValue; }).join('');
        }

        function selectionData() {
            var selection = win.getSelection();
            if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
                return null;
            }
            var range = selection.getRangeAt(0);
            if (!materialRoot.contains(range.commonAncestorContainer)) {
                return null;
            }
            var scopeRoot = materialRoot;
            var selectedBlogPostId = null;
            if (material.type === 'blog') {
                var startElement = range.startContainer.nodeType === 1 ? range.startContainer : range.startContainer.parentElement;
                var endElement = range.endContainer.nodeType === 1 ? range.endContainer : range.endContainer.parentElement;
                var startArticle = startElement ? startElement.closest('article') : null;
                var endArticle = endElement ? endElement.closest('article') : null;
                if (!startArticle || startArticle !== endArticle || !materialRoot.contains(startArticle)) {
                    win.alert('ブログでは1つの記事内の文字列を選択してください。');
                    return null;
                }
                selectedBlogPostId = blogPostId(startArticle);
                if (!selectedBlogPostId) {
                    win.alert('ブログ記事を特定できませんでした。個別記事を開いて、もう一度操作してください。');
                    return null;
                }
                scopeRoot = startArticle;
            }
            var nodes = textNodes(scopeRoot);
            var start = 0;
            var end = 0;
            var offset = 0;
            var foundStart = false;
            var foundEnd = false;
            nodes.forEach(function (node) {
                if (node === range.startContainer) {
                    start = offset + range.startOffset;
                    foundStart = true;
                }
                if (node === range.endContainer) {
                    end = offset + range.endOffset;
                    foundEnd = true;
                }
                offset += node.nodeValue.length;
            });
            if (!foundStart || !foundEnd || end <= start) {
                return null;
            }
            var allText = plainText(scopeRoot);
            var selected = allText.substring(start, end);
            if (!selected.trim()) {
                return null;
            }
            return {
                selected_text: selected,
                prefix_text: allText.substring(Math.max(0, start - 80), start),
                suffix_text: allText.substring(end, Math.min(allText.length, end + 80)),
                start_offset: start,
                end_offset: end,
                blog_post_id: selectedBlogPostId
            };
        }

        function overlapsExisting(data) {
            return state.annotations.some(function (annotation) {
                return Number(data.blog_post_id || 0) === Number(annotation.blog_post_id || 0) &&
                    data.start_offset < annotation.end_offset && data.end_offset > annotation.start_offset;
            });
        }

        function rangeParts(scopeRoot, start, end) {
            var parts = [];
            var offset = 0;
            textNodes(scopeRoot).forEach(function (node) {
                var nodeStart = offset;
                var nodeEnd = offset + node.nodeValue.length;
                var partStart = Math.max(start, nodeStart);
                var partEnd = Math.min(end, nodeEnd);
                if (partStart < partEnd) {
                    parts.push({node: node, start: partStart - nodeStart, end: partEnd - nodeStart});
                }
                offset = nodeEnd;
            });
            return parts;
        }

        function locate(annotation, scopeRoot) {
            var text = plainText(scopeRoot);
            var start = Number(annotation.start_offset);
            var end = Number(annotation.end_offset);
            if (text.substring(start, end) === annotation.selected_text) {
                return {start: start, end: end};
            }
            var needle = (annotation.prefix_text || '') + annotation.selected_text + (annotation.suffix_text || '');
            var contextAt = needle ? text.indexOf(needle) : -1;
            if (contextAt >= 0) {
                start = contextAt + (annotation.prefix_text || '').length;
                return {start: start, end: start + annotation.selected_text.length};
            }
            start = text.indexOf(annotation.selected_text);
            return start >= 0 ? {start: start, end: start + annotation.selected_text.length} : null;
        }

        function renderAnnotation(annotation) {
            var scopeRoot = annotationRoot(annotation);
            if (!scopeRoot) {
                return;
            }
            var location = locate(annotation, scopeRoot);
            if (!location) {
                annotation.unresolved = true;
                return;
            }
            annotation.start_offset = location.start;
            annotation.end_offset = location.end;
            var parts = rangeParts(scopeRoot, location.start, location.end);
            if (!parts.length) {
                annotation.unresolved = true;
                return;
            }
            if (annotation.annotation_type === 'highlight') {
                parts.slice().reverse().forEach(function (part) {
                    var range = doc.createRange();
                    range.setStart(part.node, part.start);
                    range.setEnd(part.node, part.end);
                    var mark = doc.createElement('mark');
                    mark.className = 'yuyu-annotation-mark';
                    mark.setAttribute('data-yuyu-annotation', annotation.id);
                    mark.style.backgroundColor = colors[annotation.color] || colors.yellow;
                    mark.title = 'クリックして色変更・削除';
                    mark.addEventListener('click', function (event) {
                        if (materialRoot.classList.contains('yuyu-annotations-hidden')) {
                            return;
                        }
                        event.preventDefault();
                        event.stopPropagation();
                        openAnnotationActions(annotation, mark);
                    });
                    range.surroundContents(mark);
                });
            } else {
                var last = parts[parts.length - 1];
                var iconRange = doc.createRange();
                iconRange.setStart(last.node, last.end);
                iconRange.collapse(true);
                var icon = doc.createElement('button');
                icon.type = 'button';
                icon.className = 'yuyu-annotation-note-icon';
                icon.setAttribute('data-yuyu-annotation', annotation.id);
                icon.setAttribute('data-yuyu-ignore-text', '1');
                icon.title = annotation.note;
                icon.setAttribute('aria-label', 'メモを編集・削除');
                icon.innerHTML = '<i class="fas fa-comment-alt" aria-hidden="true"></i>';
                icon.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    openAnnotationActions(annotation, icon);
                });
                iconRange.insertNode(icon);
            }
        }

        function removeRendered(annotationId) {
            materialRoot.querySelectorAll('[data-yuyu-annotation="' + annotationId + '"]').forEach(function (element) {
                if (element.classList.contains('yuyu-annotation-mark')) {
                    var parent = element.parentNode;
                    while (element.firstChild) {
                        parent.insertBefore(element.firstChild, element);
                    }
                    parent.removeChild(element);
                    parent.normalize();
                } else {
                    element.remove();
                }
            });
        }

        var panel = doc.createElement('div');
        panel.className = 'yuyu-annotation-panel';
        panel.setAttribute('data-yuyu-ignore-text', '1');
        doc.body.appendChild(panel);

        function refreshPanel() {
            panel.innerHTML = '';
            var heading = doc.createElement('div');
            heading.className = 'd-flex justify-content-between align-items-center mb-2';
            heading.innerHTML = '<strong>自分の注釈</strong>';
            var close = doc.createElement('button');
            close.type = 'button';
            close.className = 'btn btn-sm btn-outline-secondary';
            close.textContent = '閉じる';
            close.addEventListener('click', function () { panel.style.display = 'none'; });
            heading.appendChild(close);
            panel.appendChild(heading);
            if (!state.annotations.length) {
                var empty = doc.createElement('div');
                empty.className = 'text-muted small';
                empty.textContent = '注釈はまだありません。';
                panel.appendChild(empty);
                return;
            }
            state.annotations.forEach(function (annotation) {
                var item = doc.createElement('div');
                item.className = 'yuyu-annotation-item';
                var label = doc.createElement('div');
                label.className = 'small';
                label.textContent = (annotation.unresolved ? '【位置を確認できません】' : '') +
                    (annotation.annotation_type === 'highlight' ? 'マーカー：' : 'メモ：') + annotation.selected_text;
                item.appendChild(label);
                if (annotation.annotation_type === 'note') {
                    var note = doc.createElement('div');
                    note.className = 'mt-1';
                    note.textContent = annotation.note;
                    item.appendChild(note);
                }
                var remove = doc.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-sm btn-outline-danger mt-1';
                remove.textContent = '削除';
                remove.addEventListener('click', function () {
                    deleteAnnotationRecord(annotation);
                });
                item.appendChild(remove);
                panel.appendChild(item);
            });
        }

        var toolbar = doc.createElement('div');
        toolbar.className = 'yuyu-annotation-toolbar';
        toolbar.setAttribute('data-yuyu-ignore-text', '1');
        var modeButtons = [];

        function setMode(mode) {
            state.mode = state.mode === mode ? null : mode;
            modeButtons.forEach(function (button) {
                button.classList.toggle('is-active', button.getAttribute('data-mode') === state.mode);
            });
        }

        function addModeButton(label, mode) {
            var button = doc.createElement('button');
            button.type = 'button';
            button.className = 'yuyu-annotation-tool';
            button.setAttribute('data-mode', mode);
            button.textContent = label;
            button.addEventListener('click', function () { setMode(mode); });
            toolbar.appendChild(button);
            modeButtons.push(button);
        }
        addModeButton('マーカー', 'highlight');
        addModeButton('メモ', 'note');

        Object.keys(colors).forEach(function (color) {
            var button = doc.createElement('button');
            button.type = 'button';
            button.className = 'yuyu-annotation-color' + (color === state.color ? ' is-active' : '');
            button.style.backgroundColor = colors[color];
            button.title = color;
            button.setAttribute('aria-label', color + 'のマーカー');
            button.addEventListener('click', function () {
                state.color = color;
                toolbar.querySelectorAll('.yuyu-annotation-color').forEach(function (item) { item.classList.remove('is-active'); });
                button.classList.add('is-active');
                if (state.mode !== 'highlight') {
                    setMode('highlight');
                }
            });
            toolbar.appendChild(button);
        });

        var listButton = doc.createElement('button');
        listButton.type = 'button';
        listButton.className = 'yuyu-annotation-tool';
        listButton.textContent = '注釈一覧';
        listButton.addEventListener('click', function () {
            refreshPanel();
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        });
        toolbar.appendChild(listButton);

        var visibilityButton = doc.createElement('button');
        visibilityButton.type = 'button';
        visibilityButton.className = 'yuyu-annotation-tool';
        visibilityButton.textContent = '注釈を隠す';
        visibilityButton.setAttribute('aria-pressed', 'false');
        visibilityButton.addEventListener('click', function () {
            var hidden = materialRoot.classList.toggle('yuyu-annotations-hidden');
            visibilityButton.textContent = hidden ? '注釈を表示' : '注釈を隠す';
            visibilityButton.setAttribute('aria-pressed', hidden ? 'true' : 'false');
            if (hidden) {
                panel.style.display = 'none';
                state.mode = null;
                modeButtons.forEach(function (button) { button.classList.remove('is-active'); });
            }
        });
        toolbar.appendChild(visibilityButton);
        buttons.insertBefore(toolbar, buttons.firstChild);

        function saveSelection() {
            if (!state.mode) {
                return;
            }
            var data = selectionData();
            if (!data) {
                return;
            }
            if (overlapsExisting(data)) {
                win.alert('すでに注釈がある範囲には重ねて注釈を付けられません。');
                return;
            }
            data.annotation_type = state.mode;
            data.color = state.mode === 'highlight' ? state.color : null;
            if (state.mode === 'note') {
                var note = win.prompt('メモを入力してください。', '');
                if (note === null || !note.trim()) {
                    return;
                }
                data.note = note.trim();
            }
            postAnnotation('saveAnnotation', material.id, data).then(function (json) {
                state.annotations.push(json.annotation);
                renderAnnotation(json.annotation);
                refreshPanel();
                win.getSelection().removeAllRanges();
            }).catch(function (error) { win.alert('注釈を保存できませんでした。\n' + error.message); });
        }

        materialRoot.addEventListener('mouseup', saveSelection);
        materialRoot.addEventListener('touchend', function () { win.setTimeout(saveSelection, 100); });

        var displayedBlogPostIds = visibleBlogRoots().map(function (item) { return item.blogPostId; });
        fetchAnnotations(material.id, displayedBlogPostIds).then(function (json) {
            state.annotations = json.annotations || [];
            state.annotations.slice().sort(function (a, b) { return b.start_offset - a.start_offset; })
                .forEach(renderAnnotation);
            refreshPanel();
        }).catch(function (error) {
            win.__yuyuLearningAnnotationsInstalled = false;
            win.alert('注釈を読み込めませんでした。\n' + error.message);
        });
    }

    function setProgressBadge(contentId, status) {
        var badge = root.querySelector('[data-lms-progress-id="' + contentId + '"]');
        if (!badge) {
            return;
        }

        badge.className = 'badge yuyu-learning-badge lms-progress-badge';
        badge.setAttribute('data-lms-progress-id', contentId);

        if (status === 'completed') {
            badge.classList.add('badge-success');
            badge.textContent = '完了';
        } else if (status === 'failed') {
            badge.classList.add('badge-danger');
            badge.textContent = '不合格';
        } else if (status === 'in_progress') {
            badge.classList.add('badge-info');
            badge.textContent = '学習中';
        } else {
            badge.classList.add('badge-light', 'border');
            badge.textContent = '未着手';
        }
    }

    function adjustMaterialPosition(win, notice) {
        try {
            if (!win.location.hash || !win.document || !win.document.body) {
                return;
            }

            var targetId = decodeURIComponent(win.location.hash.substring(1));
            var target = win.document.getElementById(targetId);
            if (!target) {
                return;
            }

            var noticeHeight = notice.getBoundingClientRect().height;
            var desiredTop = noticeHeight + 16;
            var currentTop = target.getBoundingClientRect().top;
            var delta = currentTop - desiredTop;

            if (Math.abs(delta) > 2) {
                win.scrollBy(0, delta);
            }
        } catch (e) {
            // 対象位置を調整できない場合も教材表示自体は継続する。
        }
    }

    function scheduleMaterialPositionAdjustments(win, notice) {
        [0, 100, 300, 700, 1200, 2000].forEach(function (delay) {
            window.setTimeout(function () {
                if (!win || win.closed) {
                    return;
                }
                adjustMaterialPosition(win, notice);
            }, delay);
        });
    }

    function closeAfterRequest(win, request, reloadOutline) {
        request.then(function (response) {
            if (!response.ok) {
                throw new Error('progress update failed');
            }
            win.close();
            if (reloadOutline) {
                window.setTimeout(function () {
                    window.location.reload();
                }, 100);
            }
        }).catch(function () {
            try {
                win.alert('進捗を更新できませんでした。コース目次に戻ってから、もう一度操作してください。');
            } catch (e) {
                window.alert('進捗を更新できませんでした。');
            }
        });
    }

    function injectYuyuLearningNotice(win) {
        try {
            if (!win || win.closed || !win.document || !win.document.body || !currentMaterial) {
                return;
            }

            var existingNotice = win.document.getElementById('lms-material-window-notice');
            if (existingNotice) {
                return;
            }

            var material = {
                id: currentMaterial.id,
                type: currentMaterial.type,
                canTrack: currentMaterial.canTrack,
                materialFrameId: currentMaterial.materialFrameId
            };

            var notice = win.document.createElement('div');
            notice.id = 'lms-material-window-notice';
            notice.setAttribute('role', 'status');
            notice.style.position = 'sticky';
            notice.style.top = '0';
            notice.style.zIndex = '2147483647';
            notice.style.display = 'flex';
            notice.style.flexWrap = 'wrap';
            notice.style.alignItems = 'center';
            notice.style.justifyContent = 'space-between';
            notice.style.gap = '8px';
            notice.style.padding = '10px 16px';
            notice.style.background = '#fff3cd';
            notice.style.borderBottom = '1px solid #ffeeba';
            notice.style.color = '#856404';
            notice.style.fontFamily = 'sans-serif';
            notice.style.fontSize = '14px';
            notice.style.lineHeight = '1.5';
            notice.style.boxSizing = 'border-box';

            var message = win.document.createElement('div');
            var type = material.type;
            var canTrack = material.canTrack;

            if (!canTrack) {
                message.innerHTML = '<strong>LMS教材</strong>　このユーザーは受講登録されていないため、進捗は記録されません。';
            } else if (type === 'general' || type === 'blog') {
                message.innerHTML = '<strong>LMS教材</strong>　学習途中なら「途中で閉じる」、この教材の学習を終えたら「学習完了して閉じる」を選んでください。';
            } else if (type === 'quiz') {
                message.innerHTML = '<strong>LMS教材</strong>　完了・不合格は小テストの結果と再受験設定から自動判定します。';
            } else if (type === 'questionnaire') {
                message.innerHTML = '<strong>LMS教材</strong>　完了はアンケート回答から自動判定します。';
            } else if (type === 'learningtask') {
                message.innerHTML = '<strong>LMS教材</strong>　完了はレポート提出から自動判定します。';
            } else {
                message.innerHTML = '<strong>LMS教材</strong>';
            }

            var buttons = win.document.createElement('div');
            buttons.style.display = 'flex';
            buttons.style.flexWrap = 'wrap';
            buttons.style.gap = '8px';

            function makeButton(label, primary) {
                var button = win.document.createElement('button');
                button.type = 'button';
                button.textContent = label;
                button.style.padding = '5px 12px';
                button.style.border = '1px solid #856404';
                button.style.borderRadius = '4px';
                button.style.background = primary ? '#856404' : '#ffffff';
                button.style.color = primary ? '#ffffff' : '#856404';
                button.style.cursor = 'pointer';
                return button;
            }

            if (canTrack && (type === 'general' || type === 'blog')) {
                var pauseButton = makeButton('途中で閉じる', false);
                pauseButton.addEventListener('click', function () {
                    win.close();
                });
                buttons.appendChild(pauseButton);

                var completeButton = makeButton('学習完了して閉じる', true);
                completeButton.addEventListener('click', function () {
                    completeButton.disabled = true;
                    pauseButton.disabled = true;
                    closeAfterRequest(
                        win,
                        postProgress('completeContent', material.id),
                        true
                    );
                });
                buttons.appendChild(completeButton);
            } else {
                var closeButton = makeButton('閉じる', false);
                closeButton.addEventListener('click', function () {
                    if (canTrack && (type === 'quiz' || type === 'questionnaire' || type === 'learningtask')) {
                        closeButton.disabled = true;
                        closeAfterRequest(
                            win,
                            postProgress('syncContentProgress', material.id),
                            true
                        );
                    } else {
                        win.close();
                    }
                });
                buttons.appendChild(closeButton);
            }

            notice.appendChild(message);
            notice.appendChild(buttons);
            win.document.body.insertBefore(notice, win.document.body.firstChild);

            installAnnotationTools(win, notice, buttons, material);

            scheduleMaterialPositionAdjustments(win, notice);
        } catch (e) {
            // 外部サイトなど同一オリジンでない画面には案内バーを挿入しない。
        }
    }

    function startNoticeWatch(win) {
        if (noticeTimer) {
            window.clearInterval(noticeTimer);
        }

        noticeTimer = window.setInterval(function () {
            if (!win || win.closed) {
                window.clearInterval(noticeTimer);
                noticeTimer = null;
                return;
            }
            injectYuyuLearningNotice(win);
        }, 500);
    }

    function openMaterial(event) {
        event.preventDefault();

        var link = event.currentTarget;
        var isExternal = link.getAttribute('data-lms-external') === '1';
        var canTrack = link.getAttribute('data-lms-track') === '1';
        var contentId = link.getAttribute('data-lms-content-id');
        var contentType = link.getAttribute('data-lms-content-type');
        var materialFrameId = link.getAttribute('data-lms-material-frame-id');
        var features = 'width=1200,height=850,resizable=yes,scrollbars=yes';

        currentMaterial = {
            id: contentId,
            type: contentType,
            canTrack: canTrack,
            materialFrameId: materialFrameId
        };

        if (canTrack) {
            postProgress('markContentStarted', contentId).then(function (response) {
                if (response.ok) {
                    setProgressBadge(contentId, 'in_progress');
                }
            }).catch(function () {
                // 教材起動自体は継続し、進捗同期は次回表示時にも行う。
            });
        }

        materialWindow = window.open(link.href, 'yuyu_learning_material_window', features);

        if (!materialWindow) {
            window.location.href = link.href;
            return;
        }

        materialWindow.focus();

        if (!isExternal) {
            startNoticeWatch(materialWindow);
        } else if (noticeTimer) {
            window.clearInterval(noticeTimer);
            noticeTimer = null;
        }
    }

    root.querySelectorAll('.lms-material-launch').forEach(function (link) {
        link.addEventListener('click', openMaterial);
    });
})();
</script>

@endsection
