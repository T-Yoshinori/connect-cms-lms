@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@php
    $can_manage = $can_manage ?? false;
    $course = $course ?? null;
    $enrollment = $enrollment ?? null;
    $progresses = $progresses ?? collect();
    $course_progress = $course_progress ?? null;
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
        @if ($can_manage)
            <div class="d-flex flex-wrap mt-2 mt-sm-0">
                <a href="{{ url('/') }}/plugin/yuyulearning/adminProgress/{{ $page->id }}/{{ $frame->id }}/{{ $course->id }}#frame-{{ $frame->id }}"
                   class="btn btn-outline-success btn-sm mr-2 mb-1">
                    <i class="fas fa-chart-bar"></i> 受講進捗
                </a>
                <a href="{{ url('/') }}/plugin/yuyulearning/editCourse/{{ $page->id }}/{{ $frame->id }}/{{ $course->id }}#frame-{{ $frame->id }}"
                   class="btn btn-outline-primary btn-sm mb-1">
                    コース編集
                </a>
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
                                                    <span class="badge badge-success lms-progress-badge" data-lms-progress-id="{{ $content->id }}">完了</span>
                                                @elseif ($progress_status === 'failed')
                                                    <span class="badge badge-danger lms-progress-badge" data-lms-progress-id="{{ $content->id }}">不合格</span>
                                                @elseif ($progress_status === 'in_progress')
                                                    <span class="badge badge-info lms-progress-badge" data-lms-progress-id="{{ $content->id }}">学習中</span>
                                                @else
                                                    <span class="badge badge-light border lms-progress-badge" data-lms-progress-id="{{ $content->id }}">未着手</span>
                                                @endif
                                            @endif
                                            <span class="badge badge-light border ml-1">{{ $content_type_labels[$content->content_type] ?? $content->content_type }}</span>
                                            @if ($content->is_required)
                                                <span class="badge badge-danger ml-1">必須</span>
                                            @else
                                                <span class="badge badge-secondary ml-1">任意</span>
                                            @endif
                                            @if (!empty($launch_url))
                                                <a href="{{ $launch_url }}"
                                                   class="btn btn-outline-primary btn-sm ml-2 lms-material-launch"
                                                   data-lms-content-id="{{ $content->id }}"
                                                   data-lms-content-type="{{ $content->content_type }}"
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

    function setProgressBadge(contentId, status) {
        var badge = root.querySelector('[data-lms-progress-id="' + contentId + '"]');
        if (!badge) {
            return;
        }

        badge.className = 'badge lms-progress-badge';
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
                canTrack: currentMaterial.canTrack
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
        var features = 'width=1200,height=850,resizable=yes,scrollbars=yes';

        currentMaterial = {
            id: contentId,
            type: contentType,
            canTrack: canTrack
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

