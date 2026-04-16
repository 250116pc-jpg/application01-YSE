<?php
session_start(); 
// 同日計算処理


// 最大桁数
define('MAX_DIGITS', 9);

// 数字入力処理（数値のみ＋9桁制限）
if (isset($_POST['num'])) {
    $current = $_SESSION['amount'];

    // 数値のみチェック
    if (ctype_digit($_POST['num'])) {

        // 桁数制限
        if (strlen($current) < MAX_DIGITS) {
            $_SESSION['amount'] = $current . $_POST['num'];
        }
    }
}

// 個数更新
if (isset($_POST['quantity'])) {
    $_SESSION['quantity'] = max(1, (int)$_POST['quantity']);
}

// アクション処理
if (isset($_POST['action'])) {

    switch ($_POST['action']) {

        case 'calc':
            $amount = (int)$_SESSION['amount'];
            $quantity = (int)$_SESSION['quantity'];

            // 同品計算処理
            $total = $amount * $quantity;

            // 桁あふれ対策（9桁超えたらカット）
            if (strlen((string)$total) > MAX_DIGITS) {
                $total = substr((string)$total, -MAX_DIGITS);
            }

            $_SESSION['amount'] = $total;
            break;

        case 'clear':
            $_SESSION['amount'] = '';
            $_SESSION['quantity'] = 1;
            break;
    }
}

header("Location: index.php");
exit;

