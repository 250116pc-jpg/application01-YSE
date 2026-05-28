<?php
session_start();
require_once 'db.php';

// 【セキュリティ】未ログイン・一般ユーザーを完全に締め出す
if (!isset($_SESSION['user_db_id']) || (int)($_SESSION['role'] ?? 0) !== 1 || ($_SESSION['login_user_id'] ?? '') !== 'adm') {
    header('Location: login.php');
    exit;
}

// POSTリクエスト以外は弾いて一覧へ戻す
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sales_view.php');
    exit;
}

$action = $_POST['action'] ?? '';
$period = $_POST['period'] ?? 'all';

if ($action === 'cancel_sale') {
    $cancelIds = [];

    // 「個別取消」ボタンが押された場合
    if (!empty($_POST['single_sale_id'])) {
        $cancelIds[] = (int)$_POST['single_sale_id'];
    } 
    // 「一括取消」ボタンが押された場合
    elseif (!empty($_POST['bulk_cancel']) && !empty($_POST['sale_ids']) && is_array($_POST['sale_ids'])) {
        $cancelIds = array_map('intval', $_POST['sale_ids']);
    }

    if (count($cancelIds) > 0) {
        try {
            $pdo = getPdo();
            // トランザクション開始
            $pdo->beginTransaction();

            // 必要なSQLの準備
            // 1. 取り消す売上の商品と数量を取得する
            $stmtGetItems = $pdo->prepare('SELECT item_id, quantity FROM sale_items WHERE sale_id = ?');
            // 2. 在庫を元に戻す
            $stmtRestoreStock = $pdo->prepare('UPDATE items SET stock = stock + ? WHERE id = ?');
            // 3. 売上明細を削除
            $stmtDelSaleItems = $pdo->prepare('DELETE FROM sale_items WHERE sale_id = ?');
            // 4. 売上データを削除
            $stmtDelSale = $pdo->prepare('DELETE FROM sales WHERE id = ?');

            foreach ($cancelIds as $saleId) {
                // 売上明細を取得して、在庫を戻す
                $stmtGetItems->execute([$saleId]);
                $itemsToRestore = $stmtGetItems->fetchAll();

                foreach ($itemsToRestore as $item) {
                    $stmtRestoreStock->execute([(int)$item['quantity'], (int)$item['item_id']]);
                }

                // データの削除
                $stmtDelSaleItems->execute([$saleId]);
                $stmtDelSale->execute([$saleId]);
            }

            // コミットして変更を確定
            $pdo->commit();

            // 成功メッセージをセット
            $_SESSION['cancel_message'] = count($cancelIds) . '件の売上を取り消し、在庫を元に戻しました。';
            $_SESSION['cancel_message_type'] = 'success';

        } catch (Exception $e) {
            // エラーが起きたら元に戻す
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['cancel_message'] = '売上の取り消しに失敗しました: ' . $e->getMessage();
            $_SESSION['cancel_message_type'] = 'error';
        }
    } else {
        // IDが選択されていなかった場合
        $_SESSION['cancel_message'] = '取り消す売上が選択されていません。';
        $_SESSION['cancel_message_type'] = 'error';
    }
}

// 処理が終わったら、元の分析画面（表示していた期間のまま）へリダイレクト
header('Location: sales_view.php?period=' . urlencode($period));
exit;
