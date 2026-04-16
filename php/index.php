<?php
session_start();
$amount = $_SESSION['amount'] ?? '';
$quantity = $_SESSION['quantity'] ?? 1;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>YSE POS System v3</title>
    <style>
        body { background: #34495e; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: 'Helvetica', sans-serif; }
        
        /* レジ筐体：横長に変更 */
        .pos-machine { 
            background: #dcdde1; padding: 30px; border-radius: 15px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            display: flex; gap: 30px; align-items: flex-start; /* 左右に並べる */
            border-bottom: 10px solid #7f8c8d;
        }

        /* 左側：ディスプレイエリア */
        .display-section { width: 350px; }
        
        .screen { 
            background: #2f3640; color: #00ecff; padding: 20px; 
            border-radius: 8px; border: 5px solid #1e272e;
            box-shadow: inset 0 0 15px #000;
            margin-bottom: 15px;
        }
        .screen-label { font-size: 14px; color: #718093; margin-bottom: 10px; font-weight: bold; }
        
        /* 数字はしっかり「右寄せ」 */
        .main-display { 
            font-size: 48px; font-family: 'Courier New', monospace; 
            text-align: right; 
            min-height: 55px; letter-spacing: 2px;
        }

        /* 右側：操作パネル */
        .control-section { width: 220px; }

        .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        button { 
            height: 55px; border: none; border-radius: 6px; font-size: 18px; font-weight: bold;
            cursor: pointer; background: #f5f6fa; box-shadow: 0 4px #bdc3c7; color: #2f3640;
        }
        button:active { transform: translateY(2px); box-shadow: 0 2px #95a5a6; }
        
        .btn-ac { background: #e84118; color: white; box-shadow: 0 4px #c23616; }
        .btn-enter { background: #27ae60; color: white; box-shadow: 0 4px #219150; grid-column: span 2; }
        
        .quantity-box { 
            margin-top: 20px; padding: 15px; background: #f5f6fa; 
            border-radius: 8px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        input[type="number"] { width: 60px; font-size: 20px; text-align: center; border: 2px solid #dcdde1; border-radius: 4px; }
    </style>
</head>
<body>

<div class="pos-machine">
    <!-- 左側：液晶画面 -->
    <div class="display-section">
        <div class="screen">
            <div class="screen-label">お会計金額 (TOTAL)</div>
            <div class="main-display" id="disp">0</div>
        </div>
        <div class="quantity-box">
            <span style="font-weight: bold; color: #2f3640;">数量:</span>
            <input type="number" form="pos-form" name="quantity" value="<?= $quantity ?>" min="1">
        </div>
    </div>

    <!-- 右側：テンキー -->
    <div class="control-section">
        <form id="pos-form" method="POST" action="process.php">
            <div class="keypad">
                <?php for ($i = 1; $i <= 9; $i++): ?>
                    <button type="button" onclick="addNum('<?= $i ?>')"><?= $i ?></button>
                <?php endfor; ?>
                
                <button type="button" class="btn-ac" onclick="clearAll()">AC</button>
                <button type="button" onclick="addNum('0')">0</button>
                <button type="submit" class="btn-enter">確定</button>
            </div>
            <input type="hidden" name="amount" id="hidden_amount" value="<?= $amount ?>">
        </form>
    </div>
</div>

<script>
    const disp = document.getElementById('disp');
    const hiddenInput = document.getElementById('hidden_amount');
    let currentVal = "<?= $amount ?>";

    function updateDisplay() {
        let num = parseInt(currentVal);
        if (isNaN(num)) {
            disp.innerText = "0";
            hiddenInput.value = "";
        } else {
            // 数字は右寄せでカンマ区切り
            disp.innerText = num.toLocaleString();
            hiddenInput.value = currentVal;
        }
    }

    function addNum(n) {
        if (currentVal.length < 9) {
            if (currentVal === "" && n === "0") return;
            currentVal += n;
            updateDisplay();
        }
    }

    function clearAll() {
        currentVal = "";
        updateDisplay();
    }

    updateDisplay();
</script>

</body>
</html>
