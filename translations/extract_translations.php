<?php
/**
 * Twigテンプレートから翻訳キーを抽出し、POファイル形式で保存
 */

// 解析する Twig テンプレートファイルのパス
$twig_file = '../templates/description.html.twig'; // Twigテンプレートファイル名を適宜変更

// 出力する PO ファイルのパス
$po_file = 'translations.po';

// テンプレートの内容を読み込み
$template_content = file_get_contents($twig_file);

// 翻訳文字列を格納する配列
$translations = [];

// 1️⃣ {% trans %} ... {% endtrans %} の間の文字列を取得
preg_match_all('/{% trans %}(.+?){% endtrans %}/s', $template_content, $matches);
foreach ($matches[1] as $match) {
    $translations[] = trim($match);
}

// 2️⃣ {{ "..."|t }} の間の文字列を取得
// preg_match_all('/{{\s*"([^"]+)"\s*\|t\s*}}/', $template_content, $matches);
// preg_match_all("/{{\s*'([^']+)'\s*\|t\s*}}/", $template_content, $matches);
preg_match_all('/{{\s*["\']([^"\']+)["\']\s*\|t\s*}}/', $template_content, $matches); 
foreach ($matches[1] as $match) {
    $translations[] = trim($match);
}

// 重複を削除
$translations = array_unique($translations);

// PO ファイルの書き出し
$po_content = "";
foreach ($translations as $msgid) {
    $po_content .= "msgid \"$msgid\"\n";
    $po_content .= "msgstr \"\"\n\n";
}

// ファイルに書き出し
file_put_contents($po_file, $po_content);

// 完了メッセージ
echo "翻訳キーを $po_file に書き出しました。\n";
?>