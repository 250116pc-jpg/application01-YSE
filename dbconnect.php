<?php
try {
    // データベース接続設定
    $dsn = 'mysql:dbname=yse_pos_db;host=localhost;charset=utf8mb4';
    $user = 'root'; // XAMPPのデフォルトユーザー名
    $password = ''; // XAMPPのデフォルトパスワード（MAMPの場合は 'root' に変更してください）

    // データベースに接続（変数名は $db とします）
    $db = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // 接続に失敗した場合はエラーメッセージを表示して処理を止める
    echo 'データベース接続エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit();
}
?>
