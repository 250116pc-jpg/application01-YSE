<?php
session_start();

// 最大値の定義（9桁：9億9999万9999）
define('MAX_DIGITS', 9);
define('MAX_VALUE', 999999999);

// セッションの初期化
if (!isset($_SESSION['amount'])) $_SESSION['amount'] = '';
if (!isset($_SESSION['quantity'])) $_SESSION['quantity'] = 1;

// 1. 数字入力処理（JSから送られてきた最終的な計算結果を受け取る）
if (isset($_POST['amount'])) {
    $input_amount = $_POST['amount'];
    
    // 数値以外を除去し、最大桁数でカット
    $clean_amount = preg_replace('/[^0-9]/', '', $input_amount);
    $_SESSION['amount'] = substr($clean_amount, 0, MAX_DIGITS);
}

// 2. 個数更新
if (isset($_POST['quantity'])) {
    $_SESSION['quantity'] = max(1, (int)$_POST['quantity']);
}

// 3. アクション処理
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'calc':
            $amount = (int)($_SESSION['amount'] ?: 0);
            $quantity = (int)$_SESSION['quantity'];

            // 掛け算を実行
            $total = $amount * $quantity;

            // 桁あふれ対策：9桁（MAX_VALUE）を超えたら最大値に固定
            if ($total > MAX_VALUE) {
                $total = MAX_VALUE;
            }

            $_SESSION['amount'] = (string)$total;
            break;

        case 'clear':
            $_SESSION['amount'] = '';
            $_SESSION['quantity'] = 1;
            break;

        // ★ 新規追加：計上ボタンが押された時のデータベース保存処理 ★
        case 'keijo':
            $amount = (int)($_SESSION['amount'] ?: 0);
            $customer_id = 1; // ※仕様に沿って顧客IDを取得、テスト用は固定値

            if ($amount > 0) {
                // データベース接続設定（ローカルの yse_pos_db を想定）
                $dsn = 'mysql:dbname=yse_pos_db;host=localhost;charset=utf8mb4';
                $user = 'root'; // XAMPP等のデフォルト
                $password = ''; // XAMPP等のデフォルト

                try {
                    $pdo = new PDO($dsn, $user, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);

                    // 売上情報を sales テーブルに保存（INSERT）
                    $sql = "INSERT INTO sales (customer_id, amount) VALUES (?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$customer_id, $amount]);

                    // 保存が成功したら、次の入力のためにセッションをクリアする
                    $_SESSION['amount'] = '';
                    $_SESSION['quantity'] = 1;
                    
                } catch (PDOException $e) {
                    // DB接続や保存に失敗した場合のエラーログ出力
                    error_log("計上エラー: " . $e->getMessage());
                }
            }
            break;
    }
}

// index.phpへ戻る
header("Location: index.php");
exit;
