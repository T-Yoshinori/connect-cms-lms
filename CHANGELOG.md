# Changelog

## 0.9.0-beta.3 - 2026-09-05

- モデレータ（`role_article`）へ受講進捗の閲覧を許可
- モデレータでは「コース編集」を非表示
- モデレータではFormsの「登録一覧」を非表示
- Learningtasksの「提出内容」は表示を維持し、遷移先の教師権限で認可
- コンテンツ管理者（`role_article_admin`）は従来どおり全管理機能を利用可能

## 0.9.0-beta.2 - 2026-09-05

- プラグイン識別子を`lms`から`yuyulearning`へ変更
- PHPクラス、View、DBテーブルをYuyuLearning命名へ統一
- 旧LMS版からの名称変更Migrationを追加
- アンケート完了判定を教材登録日時以後の回答に限定
- 教材・進捗バッジの表示を改善
- フレーム設定に修了証の主催者名を追加
- 修了済み受講者本人向けの簡易修了証PDFを追加
- 設定画面のBlade名を`yuyulearning_*`へ統一
- LMS連携対応版Quizzesの同梱を継続

## 0.9.0-beta.1 - 2026-08-31

- 初回ベータ版
- コース、章、教材の管理
- 6種類の教材登録
- 受講進捗とコース修了判定
- Quizzes、Forms、Learningtasksとの進捗連携
- 管理者向け受講進捗一覧
- LMS連携対応版Quizzesプラグインを配布ZIPへ同梱
