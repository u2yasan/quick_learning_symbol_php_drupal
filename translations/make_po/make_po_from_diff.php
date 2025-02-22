<?php
/**
 * 英語ファイルと日本語ファイルから差分を抽出し、
 * msgid に英語、msgstr に日本語を対応付けた PO ファイルを生成するサンプルスクリプト
 *
 * 注意: このスクリプトは簡易的な LCS ベースの diff を実装しています。
 * より精度の高い処理が必要な場合は、専用の diff ライブラリの利用を検討してください。
 */

// ファイルパス（適宜調整してください）
$englishFile = './03_en.md';
$japaneseFile  = './03_ja.md';
$outputPo      = '03_translations.po';

// ファイルを行ごとの配列として読み込む
$enLines = file($englishFile, FILE_IGNORE_NEW_LINES);
$jaLines = file($japaneseFile, FILE_IGNORE_NEW_LINES);

/**
 * LCS に基づく差分を計算する簡易関数
 * @param array $old 英語側の行配列
 * @param array $new 日本語側の行配列
 * @return array 差分操作の配列（各要素は ['type' => 'equal'|'delete'|'insert', 'line' => string]）
 */
function computeDiff(array $old, array $new) {
    $n = count($old);
    $m = count($new);
    $L = [];
    for ($i = 0; $i <= $n; $i++) {
        $L[$i] = array_fill(0, $m+1, 0);
    }
    for ($i = 1; $i <= $n; $i++) {
        for ($j = 1; $j <= $m; $j++) {
            if ($old[$i-1] === $new[$j-1]) {
                $L[$i][$j] = $L[$i-1][$j-1] + 1;
            } else {
                $L[$i][$j] = max($L[$i-1][$j], $L[$i][$j-1]);
            }
        }
    }
    // バックトラックして差分操作を取得
    $result = [];
    $i = $n;
    $j = $m;
    while ($i > 0 || $j > 0) {
        if ($i > 0 && $j > 0 && $old[$i-1] === $new[$j-1]) {
            $result[] = ['type' => 'equal', 'line' => $old[$i-1]];
            $i--; $j--;
        } elseif ($j > 0 && ($i == 0 || $L[$i][$j-1] >= $L[$i-1][$j])) {
            $result[] = ['type' => 'insert', 'line' => $new[$j-1]];
            $j--;
        } else { // $i > 0 && ($j == 0 || $L[$i][$j-1] < $L[$i-1][$j])
            $result[] = ['type' => 'delete', 'line' => $old[$i-1]];
            $i--;
        }
    }
    return array_reverse($result);
}

// 差分操作の取得
$diffOps = computeDiff($enLines, $jaLines);

// 差分操作をブロックごとにまとめる
$entries = [];
$tempEn = [];
$tempJa = [];

foreach ($diffOps as $op) {
    if ($op['type'] === 'equal') {
        // 等しい部分が出たら、もし前に差分ブロックがあればフラッシュ
        if (!empty($tempEn) || !empty($tempJa)) {
            $entries[] = [
                'en' => implode("\n", $tempEn),
                'ja' => implode("\n", $tempJa)
            ];
            $tempEn = [];
            $tempJa = [];
        }
        // 等しい行は翻訳エントリには含めない
    } elseif ($op['type'] === 'delete') {
        $tempEn[] = $op['line'];
    } elseif ($op['type'] === 'insert') {
        $tempJa[] = $op['line'];
    }
}
// 最後に残ったブロックを追加
if (!empty($tempEn) || !empty($tempJa)) {
    $entries[] = [
        'en' => implode("\n", $tempEn),
        'ja' => implode("\n", $tempJa)
    ];
}

// PO ファイル用ヘッダー
$header = <<<EOT
# Automatically generated PO file.
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"

EOT;

// PO ファイル内容の組み立て
$poContent = $header . "\n";
foreach ($entries as $entry) {
    // ダブルクォートがあればエスケープする
    $enEscaped = addcslashes($entry['en'], "\"");
    $jaEscaped = addcslashes($entry['ja'], "\"");
    $poContent .= "msgid \"{$enEscaped}\"\n";
    $poContent .= "msgstr \"{$jaEscaped}\"\n\n";
}

// PO ファイルを書き出す
if (file_put_contents($outputPo, $poContent) !== false) {
    echo "PO file generated successfully: {$outputPo}\n";
} else {
    echo "Error: Failed to write PO file.\n";
}