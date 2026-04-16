<?php
session_start();

// 初期化
if (!isset($_SESSION['amount'])) {
    $_SESSION['amount'] = '';
}
if (!isset($_SESSION['quantity'])) {
    $_SESSION['quantity'] = 1;
}

// 表示用フォーマット（9桁・Zパディング）
function formatDisplay($value) {
    if ($value === '' || $value == 0) {
        return str_repeat(' ', 9); // 全Z（空白）
    }
    return str_pad($value, 9, ' ', STR_PAD_LEFT);
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>YSEレジ</title>
<style>
.display {
    width: 220px;
    height: 40px;
    background: black;
    color: lime;
    font-size: 24px;
    text-align: right;
    padding: 5px;
    letter-spacing: 2px;
}
button {
    width: 50px;
    height: 50px;
    font-size: 18px;
    margin: 2px;
}
</style>
</head>
<body>

<h2>YSEレジ</h2>

<!-- 表示欄 -->
<div class="display">
    <?= htmlspecialchars(formatDisplay($_SESSION['amount'])) ?>
</div>

<form method="post" action="process.php">

<?php for ($i = 1; $i <= 9; $i++): ?>
    <button type="submit" name="num" value="<?= $i ?>"><?= $i ?></button>
    <?php if ($i % 3 === 0) echo "<br>"; ?>
<?php endfor; ?>

<button type="submit" name="num" value="0">0</button>

<br><br>

個数：
<input type="number" name="quantity" value="<?= $_SESSION['quantity'] ?>" min="1">

<br><br>

<button type="submit" name="action" value="calc">計算</button>
<button type="submit" name="action" value="clear">AC</button>

</form>

</body>
</html>
