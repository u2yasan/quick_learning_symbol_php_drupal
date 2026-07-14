---
name: drupal-form
description: quicklearning_symbol / qls_chXX に新しい Drupal フォーム（FormBase）を本プロジェクトの規約どおり scaffold する。ルート登録・権限・依存性注入（create()）・中央セキュリティハードニング連携・出力エスケープを含む。「フォームを追加」「新しい入力画面」「Form を作って」等の依頼時に使用。
---

# Drupal フォーム生成（quicklearning_symbol 規約）

新規フォームを追加する手順とテンプレート。**サービスを使う場合は必ず `create()` で DI する**（`\Drupal::service()` の直呼びは避ける）。

## 手順
1. クラスを `modules/<module>/src/Form/<Name>Form.php` に作成。
2. ルートを `modules/<module>/<module>.routing.yml` に追加。
3. 秘密鍵入力があるか確認 → キー名を `pvtKey` にすれば中央フックが自動ハードニング。
4. 動的出力は必ずエスケープ。
5. `php -l` で構文確認。

## ルート（routing.yml）
```yaml
<module>.<snake_name>_form:
  path: '/quicklearning_symbol/<module>/<snake_name>_form'
  defaults:
    _form: '\Drupal\<module>\Form\<Name>Form'
    _title: '<Human Title>'
  requirements:
    # 秘密鍵/署名/アナウンスを扱うなら必ずこの権限。閲覧のみなら 'access content'。
    _permission: 'use quicklearning symbol transaction examples'
```

## フォームクラス（サービス注入あり）
```php
<?php

namespace Drupal\<module>\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\quicklearning_symbol\Service\AccountService;

class <Name>Form extends FormBase {

  /**
   * @var \Drupal\quicklearning_symbol\Service\AccountService
   */
  protected $accountService;

  public function __construct(AccountService $account_service) {
    $this->accountService = $account_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.account_service'),
    );
  }

  public function getFormId() {
    // 中央フックはこの form_id / フィールド名で判定するので命名を明確に。
    return '<snake_name>_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('<説明>'),
    ];

    // 秘密鍵入力はキー名を pvtKey にする → 中央フックが password 化・no-store 化する。
    $form['pvtKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key'),
      '#required' => TRUE,
    ];

    // AJAX 結果表示用コンテナ。
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'box-container'],
    ];
    $form['container']['box'] = ['#type' => 'markup', '#markup' => ''];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::promptCallback',
        'wrapper' => 'box-container',
      ],
    ];

    return $form;
  }

  /**
   * フォーム固有の検証。中央バリデータがキー名でカバーしない入力はここで厳格化。
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // 例：中央バリデータの対象外フィールドの形式チェック。
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // AJAX 利用時は空でよい。
  }

  public function promptCallback(array &$form, FormStateInterface $form_state) {
    $api = $this->accountService->getAccountApi();
    $result = $api->getAccountInfo($form_state->getValue('address'));

    // 出力は必ずエスケープ（API 応答は攻撃者制御データになり得る）。
    $esc = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $form['container']['box']['#markup'] = '<pre>'.$esc(print_r($result, TRUE)).'</pre>';
    return $form['container'];
  }

}
```

## 注意
- サービス不要なら `__construct` / `create` を省き `FormBase` を直接継承。
- 秘密鍵表示・生成系は中央フックが `#cache max-age=0` と警告・testnet 確認チェックボックスを自動付与する（`quicklearning_symbol.module` 参照）。手動で重複追加しない。
- 追加後は関連の `links.menu.yml` にメニューリンクを足すと導線ができる。

出力エスケープや権限の妥当性は `drupal-security-review` スキルで確認する。
