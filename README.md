# YuyuLearning（LMS）— Connect-CMS Ver.2対応β版

- 配布版: 0.9.0-cms2.beta.1
- 対象: Connect-CMS 2.44.1 / PHP 8.3（移行確認環境8.3.36）
- [CMS Ver.1版](https://github.com/T-Yoshinori/connect-cms-lms/tree/main)
- [Ver.2用ZIP](downloads/connect-cms-yuyulearning-0.9.0-cms2.beta.1.zip)
- 開発・提供: ゆうゆう企画。非公式プラグインです。

コース・章・教材・受講進捗・修了を管理します。固定記事、ブログ、小テスト、Forms、
Learningtasks、外部教材を組み合わせ、修了証PDF、本人用マーカー・メモを提供します。

## 配布範囲と依存関係
LMS本体とYuyuQuizzes Ver.2標準版0.9.0-cms2.beta.1を同梱します。
小テストは単体版と同じ実行ファイルです。小テストの任意デモ初期化コマンドと数式入力支援オプションは含みません。
CMS標準のユーザー・グループ・ページ権限・Contents・Blogs・Forms・Learningtasksを利用します。
修了証はCMS本体のTCPDFを利用するため追加Composerパッケージは不要です。
独自テーマやCMS標準ファイルの上書きは不要。小テストの数式表示は外部MathJax CDNを利用します。

## 新規導入
Connect-CMS 2.44.1が正常稼働する検証サイト用です。Ver.1や旧LMSのデータ移行は対象外です。
統合移行版を導入・検証済みのサイトに再配置する必要はありません。

1. ファイルとDBをバックアップ。
2. ZIPのapp、database、resources、configをCMS設置先へ同じ構成で配置。
3. CMS設置先でcomposer dump-autoloadを実行。
4. [専用マイグレーション](docs/migrate-cms2.md)を実行。LMS11本・小テスト23本です。実行済みはスキップされます。
5. 以下を実行。

```sh
php artisan db:seed --class=YuyuLearningPluginSeeder
php artisan db:seed --class=YuyuQuizzesPluginSeeder
php artisan config:clear
php artisan view:clear
```

6. プラグイン管理のLMS・小テストを確認し、LMSをページへ配置。
7. フレーム設定からコースを作成し、主催者名・章・教材・公開状態を設定。

Seederはプラグイン登録のみで、既存登録・表示状態・教材・受講データを変更しません。
既存の別版小テストがある場合は、先にファイル差分とマイグレーション履歴を確認してください。
テーブルがあるのに履歴がない環境へそのまま実行しないでください。

## 権限と教材
コンテンツ管理者はコース等の作成・編集・公開・進捗確認、モデレータは進捗閲覧を行います。
一般利用者は閲覧権限のあるページで受講し、教材の参照先にも閲覧権限が必要です。
固定記事・ブログ・外部教材は本人が完了操作、小テスト・Forms・Learningtasksは参照先の結果から完了判定します。
修了証に受講者名・コース名・修了日・主催者名を表示します。
マーカーとメモはLMSから開いた固定記事・ブログで本人用に利用します。

## デモ初期化（任意）
既存のyuyulearning:reset-demoコマンドとServiceを同梱。通常利用では実行不要です。
不足していた専用configを追加し、既定では無効。自動実行設定は追加していません。
YUYU_LEARNING_DEMO_RESET_ENABLED、YUYU_LEARNING_DEMO_COURSE_ID、YUYU_LEARNING_DEMO_USER_IDSを参照します。
この機能は限定した対象の受講結果を削除するもので、小テストの保存済み状態への復元とは異なります。
利用前にバックアップし、php artisan yuyulearning:reset-demo --dry-runで対象を確認してください。
このデモ初期化機能の実行検証は今回未実施です。

## 確認状況
Ver.2統合環境について、利用者から一通りのテスト完了の報告を受けています。
今回の配布整理では元ソースとのGit blob照合、参照クラス・Blade部品、ZIP内容を確認しました。
単独同梱ZIPの新規インストール、追加Seeder・専用configの実行検証は今回未実施です。
全教材・全権限条件・全ブラウザーの動作を保証するものではありません。

## 不具合報告・開発
IssuesへCMS/PHP/配布版・再現手順・期待結果・実際の結果を記載してください。
個人情報・認証情報・受講結果は公開しないでください。[セキュリティ方針](SECURITY.md)も参照。
python3 scripts/build_package.pyでZIPを再生成できます。

## ライセンス
MIT License。CMS本体と各連携先は各配布元の条件に従います。公式提供・認定・保証を受けたものではありません。
設計・実装にはChatGPT（OpenAI）の開発支援を利用しています。
