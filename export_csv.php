<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/funcs/auth.php';

requireAdmin('login.php');

$period = $_GET['period'] ?? 'all';

try {
    $pdo = getPdo();
    
    // カラム名が amount か total かを自動で判別する仕組み（同期）
    $stmtCols = $pdo->query("SHOW COLUMNS FROM sales");
    $cols = array_column($stmtCols->fetchAll(), 'Field');
    $valField = in_array('total', $cols) ? 'total' : 'amount';
    
    // 期間の条件を組み立てる
    $whereClause = '';
    if ($period === 'today') {
        $whereClause = 'WHERE created_at >= CURDATE()';
    } elseif ($period === 'week') {
        $whereClause = 'WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
    } elseif ($period === 'month') {
        $whereClause = 'WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
    } elseif ($period === 'year') {
        $whereClause = 'WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
    }

    // SQLを実行
    $sql = "SELECT id, $valField, created_at FROM sales $whereClause ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);

    // ブラウザにCSVダウンロード用のヘッダーを送信
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sales_' . $period . '_' . date('Ymd') . '.csv"');

    $output = fopen('php://output', 'w');

    // Excelでの文字化け防止 (BOM)
    fwrite($output, "\xEF\xBB\xBF");

    // 見出し行を出力
    fputcsv($output, ['取引ID', '売上金額', '取引日時']);

    // データを1行ずつ出力
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row[$valField],
            $row['created_at']
        ]);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    die("エラーが発生しました: " . $e->getMessage());
}
?>
