<?php
session_start();
require_once 'db.php';
require_once 'funcs/auth.php';
require_once 'funcs/functions_process.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectToRegister();
}

requireLogin();

$action = $_POST['action'] ?? '';

try {
    $pdo = getPdo();
    ensureAppSchema($pdo);
    $taxRate = getTaxRate($pdo);

    if ($action === 'add_item') {
        $rawItemId = preg_replace('/[^0-9]/', '', (string)($_POST['item_id'] ?? ''));
        if ($rawItemId === '') {
            setNotice('商品IDを入力してください。', 'error');
            redirectToRegister();
        }
        $itemId = min(999999, (int)$rawItemId);
        $stmt = $pdo->prepare('SELECT id, name, price, stock FROM items WHERE id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            setNotice('商品ID ' . $itemId . ' は見つかりません。', 'error');
            redirectToRegister();
        }

        if ((int)$item['stock'] <= 0) {
            setNotice($item['name'] . ' は在庫がありません。', 'error');
            redirectToRegister();
        }

        rememberCart();
        $cart = getCart();
        $found = false;
        foreach ($cart as &$line) {
            $lineRate = (float)($line['discount_rate'] ?? 0);
            $lineAmount = (int)($line['discount_amount'] ?? ($line['discount'] ?? 0));
            if ((int)$line['item_id'] === (int)$item['id'] && $lineRate == 0.0 && $lineAmount === 0) {
                $line['quantity']++;
                $found = true;
                break;
            }
        }
        unset($line);

        if (!$found) {
            $cart[] = [
                'item_id' => (int)$item['id'],
                'name' => $item['name'],
                'price' => (int)$item['price'],
                'quantity' => 1,
                'discount_rate' => 0,
                'discount_amount' => 0,
                'discount' => 0,
            ];
        }
        setCart($cart);
        setNotice($item['name'] . ' を追加しました。');

    } elseif ($action === 'update_line') {
        $index = cleanInt($_POST['line_index'] ?? '', 0, 999);
        $quantity = cleanInt($_POST['quantity'] ?? 1, 1, 99);
        $discountRate = min(100, (float)cleanInt($_POST['discount_rate'] ?? 0, 0, 100));
        $discountAmount = cleanInt($_POST['discount_amount'] ?? 0, 0, 999999);
        $cart = getCart();

        if (isset($cart[$index])) {
            rememberCart();
            $base = (int)$cart[$index]['price'] * $quantity;
            $rateDiscount = (int)floor($base * ($discountRate / 100));
            $maxAmountDiscount = max(0, $base - $rateDiscount);
            $cart[$index]['quantity'] = $quantity;
            $cart[$index]['discount_rate'] = $discountRate;
            $cart[$index]['discount_amount'] = min($discountAmount, $maxAmountDiscount);
            $cart[$index]['discount'] = lineDiscountTotal($cart[$index]);
            setCart($cart);
            setNotice('レシート明細を更新しました。');
        }

    } elseif ($action === 'remove_line') {
        $index = cleanInt($_POST['line_index'] ?? '', 0, 999);
        $cart = getCart();
        if (isset($cart[$index])) {
            rememberCart();
            $removed = $cart[$index]['name'];
            unset($cart[$index]);
            setCart($cart);
            setNotice($removed . ' を取り消しました。');
        }

    } elseif ($action === 'undo') {
        if (isset($_SESSION['undo_cart'])) {
            $_SESSION['cart'] = $_SESSION['undo_cart'];
            unset($_SESSION['undo_cart']);
            setNotice('ひとつ前の状態に戻しました。');
        } else {
            setNotice('戻せる操作がありません。', 'error');
        }

    } elseif ($action === 'clear_cart') {
        rememberCart();
        unset($_SESSION['cart']);
        setNotice('レシートを空にしました。');

    } elseif ($action === 'sales_total') {
        $stmt = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM sales WHERE DATE(created_at) = CURDATE()');
        $_SESSION['sales_message'] = '本日の売上合計: ' . number_format((int)$stmt->fetchColumn()) . '円';

    } elseif ($action === 'checkout') {
        $cart = getCart();
        if (!$cart) {
            setNotice('商品が入っていません。', 'error');
            redirectToRegister();
        }

        $subtotal = cartSubtotal($cart);
        $total = cartTotalWithTax($cart, $taxRate);
        $customerId = (int)($_SESSION['user_db_id'] ?? 1);

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO sales (customer_id, amount) VALUES (?, ?)');
        $stmt->execute([$customerId, $total]);
        $saleId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('
            INSERT INTO sale_items
                (sale_id, item_id, item_name, unit_price, quantity, discount_rate, discount_amount, discount, subtotal)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stockStmt = $pdo->prepare('UPDATE items SET stock = GREATEST(stock - ?, 0) WHERE id = ?');

        foreach ($cart as $line) {
            $lineSubtotal = lineSubtotal($line);
            $lineDiscountRate = lineDiscountRate($line);
            $lineDiscountAmount = lineDiscountAmount($line);
            $lineDiscount = lineDiscountTotal($line);
            $itemStmt->execute([
                $saleId,
                (int)$line['item_id'],
                $line['name'],
                (int)$line['price'],
                (int)$line['quantity'],
                $lineDiscountRate,
                $lineDiscountAmount,
                $lineDiscount,
                $lineSubtotal,
            ]);
            $stockStmt->execute([(int)$line['quantity'], (int)$line['item_id']]);
        }

        $pdo->commit();

        $_SESSION['last_receipt'] = [
            'sale_id' => $saleId,
            'cart' => $cart,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'total' => $total,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        unset($_SESSION['cart'], $_SESSION['undo_cart']);
        setNotice('計上しました。レシート印刷データを作成しました。', 'success');
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('POS処理エラー: ' . $e->getMessage());
    setNotice('データベース処理でエラーが発生しました。', 'error');
}

redirectToRegister();
