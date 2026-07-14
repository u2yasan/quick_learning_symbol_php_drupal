---
name: drupal-security-review
description: Drupal 11 モジュール（quicklearning_symbol / qls_chXX）のセキュリティ監査を行う。render 配列・#markup の XSS、ルートのアクセス制御と権限、秘密鍵/署名済みペイロードの取り扱い、t() プレースホルダ、SQL インジェクション、CSRF、入力検証、機密ログを本プロジェクトの確立パターンに沿ってチェックする。差分レビュー時・新規 Form/Service/サブモジュール追加時・「セキュリティ対策」「セキュリティレビュー」を依頼された時に使用。
---

# Drupal セキュリティレビュー（quicklearning_symbol）

本モジュールは Symbol ブロックチェーンの学習用で、**秘密鍵・署名済みペイロードを扱う**ため、出力エスケープとアクセス制御が最重要。以下のチェックリストを上から順に適用する。指摘は必ず `file:line` を添えて報告し、可能なら確立パターンに沿って修正する。

## 0. まず全体像を掴む

```bash
# ルーティングのアクセス制御を一覧
for f in *.routing.yml modules/*/*.routing.yml; do echo "== $f =="; cat "$f"; done
# 動的な出力シンク（要注意）
grep -rn --include='*.php' "\['#markup'\] =" src modules | grep -v "=> "
# 機密ログ・デバッグ残骸
grep -rn --include='*.php' -E "var_dump\(|print_r\(|error_log\(|\\\\Drupal::logger" src modules | grep -v "^\s*//"
```

## 1. アクセス制御（ルート）
- **全ルートに `requirements._permission`（または `_access`/`_role`）があること。** 欠落＝無認証アクセス。
- 秘密鍵の入力・署名・アナウンスを行うフォームは **`use quicklearning symbol transaction examples`** 権限（`quicklearning_symbol.permissions.yml` の `restrict access: true`）。
- 設定フォームは `administer quicklearning symbol settings`。
- 説明ページのみ `access content` 可。
- `_format: 'json'` の API 風ルートや `/json_table`・`/mosaic_data` のようなトップレベルパスも権限必須。

## 2. 出力エスケープ（XSS）— 最頻出
- `#markup` に**変数を連結**している箇所は、静的文字列と `$this->t()` 以外はすべて疑う。
- **ブロックチェーン/ノード応答・逆シリアライズしたトランザクション・ユーザー送信ペイロード**は攻撃者制御データ。必ずエスケープする：
  ```php
  $esc = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
  $element['box']['#markup'] = '<h1>Info</h1><pre>'.$esc(print_r($apiResponse, TRUE)).'</pre>';
  ```
- `print_r()` / `var_export()` の結果を markup に入れる場合も `$esc()` で包む。
- 可能なら `#markup` の生 HTML より **render 配列 + `#plain_text`** や `Drupal\Component\Utility\Html::escape()` / `Xss::filter()` を優先。
- 既存の安全ヘルパ：`ConfirmTransactionForm::safeHtmlspecialchars()`。同型で統一する。

## 3. 秘密鍵・機密データ
- 秘密鍵フィールド（キー名が `pvtKey` / `private_key`）は中央フック `quicklearning_symbol_form_alter()` が自動で `#type => password`・`autocomplete off`・`#default_value` 除去する。**新規フォームでも同じキー名規約に従う**とタダで保護される。
- 秘密鍵・署名済みペイロード・ニーモニックを **config / state / データベース / ログ / Drupal messages に保存・出力しない。** メッセージは要約のみ（例：`'Raw signed data is not displayed in Drupal messages.'`）。
- 生成鍵表示フォームは `#cache['max-age'] = 0` を維持。

## 4. 入力検証
- 中央バリデータ `quicklearning_symbol_validate_security_form()` がキー名の正規表現で pvtKey/pubkey/hash/payload/mosaicid/amount を検証する。
- **新しい入力フィールドを追加したら、キー名が既存パターンに一致するか確認**。一致しない機密入力（例：`recipient`, `message`, `seed`）は個別に `validateForm()` で形式検証を追加する。
- 16進の想定なら `preg_match('/^[a-fA-F0-9]{64}$/', $v)` 等で厳格に。

## 5. t() プレースホルダ
- 動的値は **`@placeholder`（自動エスケープ）** を使う。`!placeholder`（エスケープなし）は禁止。強調は `%placeholder`。
- `$this->t('... @x', ['@x' => $userValue])` が正。文字列連結で値を混ぜない。

## 6. SQL / エンティティ
- 生 SQL は禁止。`\Drupal::database()->select()` のプレースホルダ、または entityQuery を使う。文字列連結でクエリを組まない。
- entityQuery は既定で `->accessCheck(TRUE)`。無効化する場合は正当性を明記。

## 7. CSRF
- Form API は自動で CSRF トークンを付与する。**GET のカスタム `_controller` ルートで副作用（状態変更）を起こさない。** 必要なら `_csrf_token: 'TRUE'` を付ける。

## 8. デバッグ残骸
- `var_dump()` / `print_r(...)` の直接出力、`echo`、`\Drupal::logger()->debug()` は本番前に除去。特に AJAX コールバック内の `var_dump` はレスポンスを破壊する。

## 検証
- 変更後は必ず `php -l <file>` で構文確認。
- 出力系の修正は、悪意ある入力（`<script>` を含むメッセージ／メタデータ）を想定して手動レビュー。

報告形式：深刻度（高/中/低）・`file:line`・攻撃シナリオ・修正案。
