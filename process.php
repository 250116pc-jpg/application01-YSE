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
            $customer_id = 1; // ※現在はテスト用に固定
            $total = 0;

            // データベース接続
            $dsn = 'mysql:dbname=yse_pos_db;host=localhost;charset=utf8mb4';
            $user = 'root'; 
            $password = ''; 

            try {
                $pdo = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);

                // salesテーブルから、この顧客のamountの合計（SUM）を取得
                $sql = "SELECT SUM(amount) FROM sales WHERE customer_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$customer_id]);
                
                // 取得した合計金額を変数に入れる（何もデータがない場合は0になる）
                $total = (int)$stmt->fetchColumn();

            } catch (PDOException $e) {
                error_log("売上計算エラー: " . $e->getMessage());
            }

            // 桁あふれ対策：9桁（MAX_VALUE）を超えたら最大値に固定
            if ($total > MAX_VALUE) {
                $total = MAX_VALUE;
            }

            // 取得した合計を画面表示用セッションにセット
            $_SESSION['amount'] = (string)$total;
            break;

        case 'clear':
            $_SESSION['amount'] = '';
            $_SESSION['quantity'] = 1;
            break;

        //↓ここから、、売上表示
        case 'keijo':
            $amount = (int)($_SESSION['amount'] ?: 0);
            $customer_id = 1; // ※現在はテスト用に固定

            if ($amount > 0) {
                // データベース接続
                $dsn = 'mysql:dbname=yse_pos_db;host=localhost;charset=utf8mb4';
                $user = 'root'; 
                $password = ''; 

                try {
                    $pdo = new PDO($dsn, $user, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);

                    // 売上情報を sales テーブルに保存（INSERT）
                    $sql = "INSERT INTO sales (customer_id, amount) VALUES (?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$customer_id, $amount]);

                    // 保存が成功したら、次の入力のために画面の数字を初期化
                    $_SESSION['amount'] = '';
                    $_SESSION['quantity'] = 1;
                    
                } catch (PDOException $e) {
                    error_log("計上エラー: " . $e->getMessage());
                }
            }
            break;
    }
}

// index.phpへ戻る
header("Location: index.php");
exit;
