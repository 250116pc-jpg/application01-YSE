<?php
require('db.php');
// 最新の売上を5件取得
$sales = $db->query('SELECT * FROM sales ORDER BY created_at DESC LIMIT 5');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>売上データ一覧</title>
    <style>
        body { font-family: sans-serif; background: #34495e; color: white; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; color: black; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background: #eee; }
        .back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>売上データ一覧</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>金額</th>
            <th>日時</th>
        </tr>
        <?php foreach ($sales as $sale): ?>
        <tr>
            <td><?= $sale['id'] ?></td>
            <td><?= number_format($sale['amount']) ?>円</td>
            <td><?= $sale['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <a href="index.php" class="back-btn">レジ画面に戻る</a>
</body>
</html>
