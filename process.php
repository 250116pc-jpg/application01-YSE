<?php
session_start();

define('MAX_DIGITS', 9);
define('MAX_VALUE', 999999999);

// POSTリクエストがない場合は即リダイレクト
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// 1. 金額の受け取り
if (isset($_POST['amount'])) {
    $clean_amount = preg_replace('/[^0-9]/', '', $_POST['amount']);
    $_SESSION['amount'] = substr($clean_amount, 0, MAX_DIGITS);
}

// 2. アクション処理
$action = $_POST['action'] ?? '';
$customer_id = 1; // テスト用固定ID
$dsn = 'mysql:dbname=yse_pos_db;host=localhost;charset=utf8mb4';
$user = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    if ($action === 'calc') {
        // ↓ここ追加　SQLから。created_atで日付を　件数は改造？
        $sql = "
            SELECT SUM(amount) 
            FROM sales 
            WHERE customer_id = ? 
              AND DATE(created_at) = (
                  SELECT DATE(MAX(created_at)) 
                  FROM sales 
                  WHERE customer_id = ?
              )
        ";
        $stmt = $pdo->prepare($sql);
        // SQL内に「?」が2つあるので、$customer_id を2つ配列に入れます
        $stmt->execute([$customer_id, $customer_id]);
        $total = (int)$stmt->fetchColumn();
        
        // 桁あふれ対策
        if ($total > MAX_VALUE) $total = MAX_VALUE;
        $_SESSION['amount'] = (string)$total;

    } elseif ($action === 'keijo') {
        // 売上の計上
        $amount = (int)($_SESSION['amount'] ?: 0);
        if ($amount > 0) {
            $sql = "INSERT INTO sales (customer_id, amount) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$customer_id, $amount]);
            
            // 計上後は画面を0にするためセッションを空にする
            $_SESSION['amount'] = '';
        }
    }
} catch (PDOException $e) {
    error_log("DBエラー: " . $e->getMessage());
}

// 最後に必ずリダイレクト（PRGパターン）
header("Location: index.php");
exit;
