<?php
session_start();

// 最大桁数
define('MAX_DIGITS', 9);

// amount（入力金額）をPOSTから取得
$amount = isset($_POST['amount']) ? preg_replace('/\D/', '', $_POST['amount']) : '';
$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

// 桁数制限
if (strlen($amount) > MAX_DIGITS) {
    $amount = substr($amount, 0, MAX_DIGITS);
}

// アクション処理
if (isset($_POST['action'])) {

    switch ($_POST['action']) {

        case 'calc':
            $total = (int)$amount * $quantity;

            // 9桁制限（後ろ9桁残す）
            if (strlen((string)$total) > MAX_DIGITS) {
                $total = substr((string)$total, -MAX_DIGITS);
            }

            $_SESSION['amount'] = $total;
            $_SESSION['quantity'] = $quantity;
            break;

        case 'clear':
            $_SESSION['amount'] = '';
            $_SESSION['quantity'] = 1;
            break;
    }
}

// 画面に戻る
header("Location: index.php");
exit;
