# Connect-CMS LMS 第1版（独立プラグイン第2設計）

## 1. 方針

LMSはConnect-CMS標準のLearningtasksを基盤にせず、独立した `YuyuLearning` プラグインとして実装する。

LMS自身が「コース・章・教材・受講実績・教材進捗・コース修了」を管理し、既存プラグインは教材の実体として参照する。既存教材プラグインのテーブルをLMSから直接更新せず、LMSは参照・完了判定のみ行う。

LearningtasksはLMS基盤としては使用しない。ただし、レポート提出・本文提出・ファイル提出・教員評価という強みを生かし、LMSから呼び出す「レポート課題」教材として連携する。

### 1.1 第1版の教材種別

- `general` : 固定記事教材。固定記事のWYSIWYG本文、および固定記事に配置・添付したPDF・Word・画像・動画等を含む単発教材。
- `blog` : ブログ教材。複数記事で構成する継続型教材、コメントを利用した教材提供者・学習者間の対話型教材。
- `quiz` : 小テスト教材。Quizzesを教材実体として使用する。
- `questionnaire` : アンケート教材。Formsのアンケートを教材実体として使用する。
- `custom` : 外部教材。Connect-CMS外に存在する教材を外部URLで参照する。
- `learningtask` : レポート課題。Learningtasksを教材実体として使用する。

画面表示名称は「固定記事教材」「ブログ教材」「小テスト教材」「アンケート教材」「外部教材」「レポート課題」に統一する。

### 1.2 役割分担

- LMS: コース、章、教材構成、個人別受講実績、進捗、修了判定
- Connect-CMSページ／フレーム権限: 誰がそのLMSページを閲覧・受講できるかを制御
- LMSフレーム設定: そのフレームに表示するコースと、修了証の発行者となる主催者名を設定
- 固定記事: 単発教材、ローカルファイル教材の配置・添付
- Blogs: 複数記事教材、継続更新、コメントによる質疑・共有
- Quizzes: 小テスト受験、採点、得点、合否
- Forms: アンケート回答
- Learningtasks: レポート提出、ファイル提出、本文提出、教員評価
- 外部Webページ等: Connect-CMS外の教材

ローカルファイル教材はLMS側で独自アップロードせず、固定記事のWYSIWYGエディタからアップロード・配置し、その固定記事フレームを `general` として登録する。

### 1.3 権限モデル

第1版では、受講資格をLMS独自の受講グループ設定で二重管理しない。Connect-CMS標準のページ／フレーム権限でLMSページを閲覧できるログインユーザーを、そのページに配置されたLMSコースの受講者として扱う。

| LMS上の権限 | Connect-CMS権限 | できること |
| --- | --- | --- |
| コース管理 | `role_article_admin` | 受講進捗とForms登録一覧を閲覧し、コース・章・教材を作成・編集して、自ら公開できる。 |
| 受講進捗閲覧 | `role_article` | 受講進捗を閲覧できる。コース編集とForms登録一覧は利用できない。 |
| 承認付きコース作成 | `role_reporter` | 自分のコースを作成・編集できる。公開には承認が必要。 |
| 受講のみ | LMSページを閲覧できるログインユーザー | LMSフレームに設定されたコースを受講する。 |

承認者は `role_approval` を使用する。コース状態は `draft → pending_approval → published → closed` を基本とする。`role_article_admin` は `draft` から直接 `published` にできる。

`adminProgress` アクションは `role_article` 以上に許可する。受講進捗画面では、`role_article_admin` を持たない利用者を閲覧専用として扱い、「コース編集」とアンケート教材の「登録一覧」を表示しない。編集系アクションは引き続き `role_article_admin` を要求し、URLを直接指定した場合も権限エラーとする。

レポート課題の「提出内容」は受講進捗の閲覧者にも表示する。遷移先ではLearningtasks標準の教師権限と参加者設定による認可を適用するため、提出内容を確認する利用者は対象課題へ教師として参加させる。

### 1.4 LMSフレームとコース

1フレームにつき1コースを選択して表示する。フレーム設定は次の3タブとする。

```text
コース選択 ｜ 新規作成 ｜ 権限設定
```

同一コースを複数LMSフレームから参照することは許可する。コース本体は `yuyu_learning_courses` を正本とし、フレームごとに複製しない。

受講対象を分けたい場合は、Connect-CMS側でLMSフレームを別ページへ配置し、そのページの閲覧権限を標準のユーザーグループ／ページ権限で設定する。LMS側ではコースごとの受講グループ設定を重ねない。

フレーム設定では、修了証の発行者として表示する主催者名も設定する。主催者名は設定画面では必須入力とし、未設定のフレームでは修了証を発行しない。

### 1.5 受講者管理

第1版の受講資格は次の2条件で決まる。

1. Connect-CMSへログインしている。
2. Connect-CMS標準の権限判定を通って、そのLMSページを閲覧できる。

この条件を満たしてLMSフレームが表示されたユーザーは、そのフレームに設定されたコースの受講者として扱う。

- LMS側でユーザーを一人ずつ「個別受講登録」する管理画面は設けない。
- LMS側でコースごとの「受講ユーザーグループ」を選択する操作も第1版では設けない。
- `yuyu_learning_enrollments` は受講資格を決めるマスタではなく、受講者ごとの開始・進捗・修了を保持する内部の受講実績テーブルとして使用する。
- ログインユーザーがLMSコースを表示した際、該当する `yuyu_learning_enrollments` がなければLMSが自動生成する。
- 未ログインユーザーについては個人進捗を作成しない。

将来、同一ページ・同一LMSフレーム内でユーザーグループごとに受講コースを切り替える要件が生じた場合は、コース別受講グループを拡張機能として再検討する。第1版では実装しない。

## 2. LMSの基本構造

```text
Connect-CMSページ（閲覧権限で受講対象を制御）
  └─ LMSフレーム
       └─ フレーム設定で選択されたコース
            ├─ 第1章
            │    ├─ 固定記事教材
            │    ├─ ブログ教材
            │    ├─ 小テスト教材
            │    └─ レポート課題
            ├─ 第2章
            │    ├─ 固定記事教材
            │    └─ アンケート教材
            └─ 第3章
                 └─ 外部教材
```

ブログ教材は個別記事を1件ずつLMS教材として登録せず、ブログ本体を1教材として登録する。

## 3. 第1版の対象機能

- LMSフレームごとのコース選択
- コース新規作成・自動割当
- コース・章・教材の管理
- 6教材タイプの登録・編集
- Connect-CMS標準のページ／フレーム権限による受講対象制御
- LMSページを閲覧したログインユーザーの `yuyu_learning_enrollments` 自動生成
- 未着手・学習中・不合格・完了の進捗管理
- 固定記事・ブログ・外部教材の本人完了
- Quizzesの結果と再受験設定による自動状態判定
- Formsアンケート回答済みによる自動完了
- Learningtasksレポート提出済みによる自動完了
- 必須教材によるコース修了判定
- 管理者用進捗・得点一覧、CSV出力
- 修了済み受講者本人による簡易修了証PDFの発行

### 3.1 第1版の対象外

- LMS独自の個別受講登録UI
- LMS独自のコース別受講グループ設定UI
- LMS独自の教材ファイルアップロード
- SCORM
- 動画の厳密な視聴率判定
- ブログの個別記事閲覧数による自動完了
- ブログコメント投稿を必須条件とする自動完了
- 外部教材のiframe標準表示
- メール自動督促、課金、複雑なコース間前提条件

## 4. データベース

### 4.1 `yuyu_learning_courses`

コース本体を管理する。既存Migrationを正本とする。

主な状態は `draft / pending_approval / published / closed` とする。

### 4.2 `yuyu_learning_sections`

- `id`
- `course_id`
- `title`
- `description` nullable
- `sort_order`
- timestamps

### 4.3 `yuyu_learning_contents`

- `id`
- `section_id`
- `title`
- `content_type`
- `reference_id` nullable
- `plugin_name` nullable
- `action` nullable
- `page_id` nullable
- `frame_id` nullable
- `reference_url` nullable
- `description` nullable
- `is_required`
- `sort_order`
- timestamps

`content_type` は `general / blog / quiz / questionnaire / custom / learningtask`。

`action` は教材参照情報として保持できるが、第1版の教材起動ではプラグイン固有アクションを直接呼ぶためには使用しない。

### 4.4 `yuyu_learning_enrollments`

既存Migrationを正本とする。

- `id`
- `course_id`
- `user_id`
- `enrollment_source` default `individual`
- `source_group_id` nullable
- `status` default `not_started`
- `enrolled_at`
- `started_at` nullable
- `completed_at` nullable
- timestamps

UNIQUE(`course_id`, `user_id`)。

既存Migrationの `enrollment_source` のデフォルト値は `individual` だが、第1版では手動の個別受講登録を行わない。LMSページ表示から内部生成する受講実績は `enrollment_source = page`、`source_group_id = null` とする。既存Migrationは変更しない。

教材を初めて開始した時、受講状態が `not_started` なら `in_progress` に更新し `started_at` を保存する。必須教材がすべて完了した時 `completed` に更新し `completed_at` を保存する。

### 4.5 `yuyu_learning_course_groups`

既存Migration／テーブルは残すが、第1版の受講資格判定には使用しない。第1版では設定UIも作らない。

将来、同一ページ・同一LMSフレームでユーザーグループごとに受講コースを切り替える要件が生じた場合の拡張候補として保留する。

### 4.6 `yuyu_learning_content_progress`

既存Migrationを正本とし、教材単位の進捗を管理する。

- `id`
- `enrollment_id`
- `content_id`
- `status` default `not_started`
- `started_at` nullable
- `completed_at` nullable
- `completion_source` nullable
- timestamps

制約:

- UNIQUE(`enrollment_id`, `content_id`)
- FK `enrollment_id` → `yuyu_learning_enrollments.id` cascade delete
- FK `content_id` → `yuyu_learning_contents.id` cascade delete

`status` は `not_started / in_progress / failed / completed`。

`failed` は、教材側で不合格が確定し、かつ再受験・再提出などにより継続できない状態を表す。第1版ではQuizzesの「不合格かつ再受験不能」に使用する。不合格でも再受験可能な場合は `in_progress` を維持する。

`completion_source` は `self / quiz / questionnaire / learningtask / system` を使用する。固定記事・ブログ・外部教材の本人完了は `self` とする。`failed` の場合も判定元を識別するため `completion_source = quiz` を使用できるが、`completed_at` は保存しない。

Quizzes、Forms、Learningtasksの実績データを状態判定の正本とし、`yuyu_learning_content_progress` はLMS上の表示・開始状態・不合格状態・完了状態を記録する。

### 4.7 `yuyu_learning_frames`

- `id`
- `frame_id` unique
- `course_id` nullable
- `organizer_name` nullable
- timestamps

同じ `course_id` を複数LMSフレームで使用できる。

`yuyu_learning_frames.course_id` が、そのLMSフレームに表示するコースを決める。受講対象の制御は `yuyu_learning_frames` ではなく、フレームが配置されたConnect-CMSページの閲覧権限で行う。

`organizer_name` は修了証の発行者として表示する主催者名を保持する。既存フレームとの互換性を維持するためDBではNULLを許容するが、コース選択画面で保存する際は必須とする。主催者名が未設定の場合、修了証ダウンロードボタンは表示しない。

## 5. 教材参照情報と起動方式

LMSは教材参照として `plugin_name / action / page_id / frame_id / reference_id` を保持する。外部教材だけは `reference_url` を使用する。

### 5.1 Connect-CMS内部教材

固定記事、ブログ、小テスト、アンケート、レポート課題の5種類は、LMSから各プラグイン固有の `index / start / show` を直接呼ばない。

教材フレームが配置されている通常のConnect-CMSページを入口とし、次のURLを生成する。

```text
/{page.permanent_link}?yuyu_learning_content_id={content_id}#frame-{frame_id}
```

これにより、教材プラグインは通常ページ上で標準どおり描画され、LMS側から固有アクションの公開条件・権限判定へ依存しない。

### 5.2 外部教材

`custom` は `reference_url` をそのまま起動する。Connect-CMS外サイトにはLMSの案内バーをDOM挿入しない。

### 5.3 別教材ウィンドウ

コース目次は元のウィンドウに残し、教材は `window.open()` で別教材ウィンドウ `yuyu_learning_material_window` に開く。同じ名前のウィンドウを再利用する。ブラウザー・端末によっては別タブとして開かれる場合がある。

内部教材では、LMS側JavaScriptから教材画面上部へLMS案内バーを挿入する。案内バーを挿入した後は、対象 `#frame-{frame_id}` のフレームヘッダーが案内バーに隠れないようスクロール位置を補正する。

## 6. 教材の開始・終了・完了仕様

### 6.1 基本原則

**教材ウィンドウを閉じることと、教材を完了することは別の操作・判定とする。**

教材を開いた時点で進捗を `in_progress` にする。途中でウィンドウを閉じても `in_progress` のままとし、完了にはしない。

```text
未着手
  ↓ 教材を開く
学習中
  ├─ 途中で閉じる → 学習中のまま
  ├─ 教材側で継続不能の不合格が確定 → 不合格
  └─ 教材ごとの完了条件を満たす → 完了
```

一度 `completed` になった教材は、再度開いても `in_progress` や `failed` に戻さない。

### 6.2 固定記事教材・ブログ教材

同一オリジンの教材ページ上部へLMS案内バーを挿入し、次の2操作を表示する。

- **途中で閉じる**: 教材ウィンドウだけを閉じる。進捗は `in_progress` のまま。
- **学習完了して閉じる**: `yuyu_learning_content_progress.status = completed`、`completion_source = self`、`completed_at` を保存してから閉じる。

ブログ教材も第1版では本人完了方式とする。全記事閲覧・コメント投稿を一律の完了条件にはしない。

### 6.3 外部教材

外部教材は同一オリジンではないため教材ページ内へLMS操作を挿入できない。教材は別ウィンドウ／別タブで開き、LMSコース目次側に本人完了操作を置く。

外部教材も `completion_source = self` とする。単に外部教材ウィンドウを閉じただけでは完了にしない。

### 6.4 小テスト教材

教材画面では終了操作だけを提供し、「完了・不合格は小テストの結果と再受験設定から自動判定する」ことを案内する。閉じる操作自体で任意の完了判定を行うのではなく、閉じる際にQuizzesの実績を同期する。

Quizzesの受験結果を正本とし、Adapterは次の状態を返す。

- 未受験 → 状態変更なし（LMSで教材を開けば `in_progress`）
- 受験中・提出済み・採点待ち → `in_progress`
- 合格済み → `completed`
- 合格判定なしで採点済み → `completed`
- 不合格で再受験可能 → `in_progress`
- 不合格で再受験不能 → `failed`

再受験可否はQuizzes本体と同じ基準を使用する。

- `retry_type = unlimited` → 不合格でも `in_progress`
- `retry_type = once` → 不合格なら `failed`
- `retry_type = limited` → 完了済み受験回数が `retry_limit` 未満なら `in_progress`、上限到達後の不合格は `failed`

LMS側の自己申告完了は許可しない。

### 6.5 アンケート教材

教材画面では終了操作だけを提供し、「完了はアンケート回答から自動判定する」ことを案内する。閉じる操作自体では完了にしない。

Formsの回答データを正本とし、完了条件はAdapterで判定する。LMS側の自己申告完了は許可しない。

### 6.6 レポート課題

教材画面では終了操作だけを提供し、「完了はレポート提出状態から自動判定する」ことを案内する。閉じる操作自体では完了にしない。

Learningtasksの提出状態を正本とし、完了条件はAdapterで判定する。LMS側の自己申告完了は許可しない。

## 7. 進捗Service

進捗更新はBladeや各教材Adapterへ分散させず、`YuyuLearningProgressService` に集約する。

### 7.1 受講資格と受講実績

Connect-CMS標準の権限判定を通ってLMSページが表示された時点で、ログインユーザーはそのフレームに設定されたコースの受講者である。

`resolveEnrollmentForCourse()` はLMS独自のグループ判定を行わず、対象コース・対象ログインユーザーの受講実績を解決する。

- `yuyu_learning_enrollments` がない → `enrollment_source = page` で自動生成
- 既存実績がある → その実績を利用
- `yuyu_learning_course_groups` / `group_users` は第1版の判定に使用しない

ページへのアクセス可否そのものはConnect-CMS標準のページ／フレーム権限に委ねる。

### 7.2 教材開始

`markStartedForUser()` は対象教材のコースに対する受講実績を解決し、進捗を作成・更新する。

- 進捗未作成 → `in_progress` として作成し `started_at` を保存
- `not_started` → `in_progress` へ更新
- `failed` → 教材を再び開始できる場合は `in_progress` へ戻す
- `completed` → 状態を変更しない
- 受講状態が `not_started` → `in_progress` へ更新し `started_at` を保存

### 7.3 本人完了

`markSelfCompletedForUser()` は `general / blog / custom` だけを対象とする。

- `status = completed`
- `completed_at` を初回完了時に保存
- `completion_source = self`
- `quiz / questionnaire / learningtask` に対して本人完了を要求されても完了させない

### 7.4 教材実績による自動状態同期

Quizzes、Forms、Learningtasksは各Adapterが教材実体の実績を確認し、`completed / failed / in_progress / null` の状態を返す。

`YuyuLearningContentStatusService` はAdapterの返却状態を解釈し、進捗更新を `YuyuLearningProgressService` に委譲する。

- `completed` → `markCompleted()`
- `failed` → `markFailed()`
- `in_progress` → `markStarted()`
- `null` → LMS進捗を変更しない

`markFailed()` は `status = failed`、`completed_at = null` とし、判定元を `completion_source` に保存する。一度 `completed` になった教材は `failed` へ戻さない。

LMSから教材プラグイン側の結果データを書き換えない。

### 7.5 コース修了

教材完了時に、対象コースの `is_required = true` の教材を確認する。

必須教材がすべて `completed` なら、受講実績を次の状態へ更新する。

- `status = completed`
- `started_at` が未設定なら設定
- `completed_at` を初回修了時に設定

必須教材が1件でも `failed` または未完了ならコース修了にはしない。必須教材が1件もないコースは、自動修了にはしない。

### 7.6 修了証PDF

コースを修了したログインユーザー本人は、対象フレームの主催者名が設定されている場合に限り、簡易修了証PDFをダウンロードできる。

修了証には次の項目を表示する。

- タイトル「修了証」
- 受講者名（`users.name`）
- 修了文言
- コース名
- 修了日（`yuyu_learning_enrollments.completed_at`）
- 主催者名（`yuyu_learning_frames.organizer_name`）

PDFはConnect-CMSに同梱されているTCPDFを使用し、A4横向きで生成する。ダウンロード要求時には、ログインユーザー本人の受講実績が `status = completed` であり、`completed_at` が存在すること、対象フレームにコースと主催者名が設定されていることをサーバー側で再確認する。未修了者、別ユーザー、主催者未設定の場合は403とする。

## 8. 教材登録時の参照方法

教材登録画面では内部IDを直接入力させず、Connect-CMS内の教材候補を教材名・配置ページから選択する。LMSが `page_id / frame_id / reference_id` を自動登録する。

- 固定記事教材: `contents` フレームを選択
- ブログ教材: ブログ本体を選択
- 小テスト教材: Quizzesを選択
- アンケート教材: Formsのアンケートを選択
- 外部教材: 外部URLを指定
- レポート課題: Learningtasksの課題を選択

ブログ教材は個別記事ではなくブログ本体を1教材として登録する。

## 9. 実装順序

第1版の進捗・完了機能は次の順で実装する。

1. `docs/lms/design.md` と既存Migrationを基準に進捗Serviceを確定する。
2. 固定記事・ブログに「途中で閉じる」「学習完了して閉じる」を実装する。
3. 外部教材の本人完了操作をコース目次側へ実装する。
4. Quizzes Adapterを実DB・採点・再受験仕様へ接続し、完了・不合格・継続中を判定する。
5. Forms Adapterを回答DBへ接続する。
6. Learningtasks Adapterを提出状態DBへ接続する。
7. 必須教材完了によるコース修了を通しで確認する。

## 10. 第1版での設計原則

1. LMS固有概念はLMSテーブルで管理する。
2. 誰が受講できるかはConnect-CMS標準のページ／フレーム閲覧権限を正本とし、LMS側で受講グループを二重管理しない。
3. どのコースを表示するかは `yuyu_learning_frames.course_id` でフレーム単位に決定する。
4. `yuyu_learning_enrollments` は個人別受講実績として内部生成・管理する。
5. `yuyu_learning_course_groups` は第1版の受講資格判定には使用しない。
6. Quizzes / Forms / Learningtasks / Blogsの標準テーブルをLMSから更新しない。
7. 教材プラグインの結果・提出状態をLMS状態判定の正本とする。
8. 得点等をLMSへ重複保存しない。
9. Connect-CMS内部教材は通常ページを入口として開く。
10. 教材ウィンドウを閉じただけでは完了にしない。
11. 進捗更新は `YuyuLearningProgressService` に集約する。
12. 各教材固有の状態判定はAdapterへ分離する。
13. `failed` は「失敗した」一般状態ではなく、教材側で不合格が確定し継続不能になった場合に限定して使用する。
14. 既存プラグインへの大規模改修を避ける。
15. Learningtasksの試験機能はLMS小テストとして使用せず、Quizzesへ統一する。
16. 修了証は修了済み受講者本人だけに発行し、フレーム設定の主催者名を発行者として明記する。

旧Learningtasks基盤案のテーブル・Serviceは、独立LMS第1版が安定した後に別PRで撤去する。
