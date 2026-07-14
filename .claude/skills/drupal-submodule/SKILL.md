---
name: drupal-submodule
description: quicklearning_symbol の新しい章サブモジュール（qls_chXX 形式）一式を scaffold する。info.yml / routing.yml / .module / links.menu.yml / libraries.yml / Controller/Page.php を本プロジェクト規約で生成する。「新しい章モジュール」「サブモジュールを追加」「qls_chXX を作って」等の依頼時に使用。
---

# Drupal サブモジュール生成（qls_chXX 規約）

各章は `quicklearning_symbol` に依存する子モジュール。以下の 6 ファイルを `modules/<module>/` 配下に作る（`<module>` は例：`qls_ch14`）。

## 1. `<module>.info.yml`
```yaml
name: <module>
type: module
description: Quick Learning Symbol Section <NN>
package: Quick Learning Symbol
core_version_requirement: ^11.0
configure: <module>.description
dependencies:
  - quicklearning_symbol:quicklearning_symbol
version: '0.0.1'
project: 'quicklearning_symbol'
```

## 2. `<module>.routing.yml`
```yaml
<module>.description:
  path: '/quicklearning_symbol/<module>'
  defaults:
    _controller: '\Drupal\<module>\Controller\Page::description'
    _title: 'Quick Learning Symbol Section <NN>'
  requirements:
    _permission: 'access content'

# 例：フォームを足す場合（秘密鍵/署名系は下記の権限を使う）
<module>.<name>_form:
  path: '/quicklearning_symbol/<module>/<name>_form'
  defaults:
    _form: '\Drupal\<module>\Form\<Name>Form'
    _title: 'Section <NN> <Human Title>'
  requirements:
    _permission: 'use quicklearning symbol transaction examples'
```

## 3. `Controller/Page.php`（説明ページ）
```php
<?php

namespace Drupal\<module>\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\quicklearning_symbol\Utility\DescriptionTemplateTrait;

/**
 * Simple page controller for drupal.
 */
class Page implements ContainerInjectionInterface {

  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  public function getModuleName() {
    return '<module>';
  }

}
```
> `DescriptionTemplateTrait` は `templates/description.html.twig`（および `description_ja.html.twig`）を描画する。テンプレートも用意すること。

## 4. `<module>.links.menu.yml`
```yaml
# Define default links for this module.
<module>.description:
  title: <module>
  description: Quick Learning Symbol Section <NN>
  route_name: <module>.description
  expanded: TRUE
```

## 5. `<module>.libraries.yml`（任意 — JS/CSS を使う場合）
```yaml
quicklearning_symbol.code:
  version: 1.x
  css:
    theme:
      css/quicklearning_symbol.css: {}
  js:
    js/quicklearning_symbol_code.js: {}
  dependencies:
    - core/jquery
    - core/once
```

## 6. `<module>.module`（任意）
```php
<?php

// 章固有のフック（hook_preprocess_page でライブラリ添付など）が必要ならここに。
```

## 完了後
1. `php -l modules/<module>/src/Controller/Page.php`。
2. 説明テンプレート `templates/description.html.twig` に該当章の記述を追加（既存章に倣う）。
3. フォームを足すなら `drupal-form` スキルを使う。
4. セキュリティは `drupal-security-review` スキルで確認。

命名規約：ディレクトリ・機械名・namespace はすべて同じ `<module>`（例 `qls_ch14`）で一致させる。
