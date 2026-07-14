---
name: drupal-service
description: quicklearning_symbol に新しいサービスクラスを本プロジェクト規約で追加する。services.yml 登録・依存性注入・Symbol REST API クライアント（SymbolApiClientFactory）連携パターンを含む。「サービスを追加」「API ラッパーを作って」「Service クラスを作成」等の依頼時に使用。
---

# Drupal サービス生成（quicklearning_symbol 規約）

サービスは `src/Service/` に置き、`quicklearning_symbol.services.yml` に登録する。多くは Symbol REST API クライアントのラッパーで、`SymbolApiClientFactory` を注入して各 `*RoutesApi` を生成する。

## 1. クラス（`src/Service/<Name>Service.php`）
```php
<?php

namespace Drupal\quicklearning_symbol\Service;

use SymbolRestClient\Api\<Xxx>RoutesApi;
use Exception;

class <Name>Service {

  /**
   * @var \SymbolRestClient\Api\<Xxx>RoutesApi
   */
  protected $<xxx>Api;

  /**
   * @var \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory
   */
  protected $apiClientFactory;

  /**
   * @param \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory $api_client_factory
   *   The Symbol API client factory.
   */
  public function __construct(SymbolApiClientFactory $api_client_factory) {
    $this->apiClientFactory = $api_client_factory;
    $this-><xxx>Api = $api_client_factory->createApi(<Xxx>RoutesApi::class);
  }

  /**
   * @return \SymbolRestClient\Api\<Xxx>RoutesApi
   */
  public function get<Xxx>Api() {
    return $this-><xxx>Api;
  }

  /**
   * 任意ノード URL 指定にも対応する呼び出し例。
   */
  public function someCall(string $arg, ?string $node_url = NULL): mixed {
    $api = $node_url
      ? $this->apiClientFactory->createApiForNodeUrl(<Xxx>RoutesApi::class, $node_url)
      : $this-><xxx>Api;

    return $api->someEndpoint($arg);
  }

}
```

## 2. サービス登録（`quicklearning_symbol.services.yml`）
```yaml
  quicklearning_symbol.<snake_name>_service:
    class: 'Drupal\quicklearning_symbol\Service\<Name>Service'
    arguments: ['@quicklearning_symbol.api_client_factory']
```

## 依存関係の選び方（既存の注入先）
- Symbol REST API を叩く → `@quicklearning_symbol.api_client_factory`
- Facade（署名・アドレス導出など）→ `@quicklearning_symbol.facade_factory` または `@quicklearning_symbol.facade_service`
- 設定値（network_type / node_url）→ `@quicklearning_symbol.config`
- 複数必要なら配列で複数指定（例：`transaction_service` は factory と facade_factory の両方）。

## フォームからの利用
サービスはフォームの `create()` で注入する（`drupal-service` を使ったら `drupal-form` スキルの DI テンプレート参照）：
```php
$container->get('quicklearning_symbol.<snake_name>_service')
```

## 注意・確認
- サービス内で **秘密鍵をログ出力・保存しない**（`\Drupal::logger` に鍵やペイロードを渡さない）。エラー時はメッセージのみログ。
- 例外は握りつぶさず、必要なら `\Drupal::logger('quicklearning_symbol')->error('Error in @method: @message', [...])` で記録（機密を含めない）。
- 追加後 `php -l src/Service/<Name>Service.php`。
- キャッシュクリアが要る場合は `drush cr`（環境があれば）。

セキュリティ観点は `drupal-security-review` スキルで確認する。
