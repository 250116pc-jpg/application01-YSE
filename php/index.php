<?php
session_start();
$amount = $_SESSION['amount'] ?? '';
$quantity = $_SESSION['quantity'] ?? 1;

// レジの標準的なテンキー配列（上から789, 456, 123）
$numpad = [7, 8, 9, 4, 5, 6, 1, 2, 3];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>YSE POS System v3</title>
    <style>
        /* 全体のベース設定：クリーンなライトグレー */
        body { 
            background: #f0f2f5; 
            display: flex; justify-content: center; align-items: center; 
            height: 100vh; margin: 0; 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
        }
        
        /* レジ筐体：モダンな白基調のカードデザイン */
        .pos-machine { 
            background: #ffffff; padding: 40px; border-radius: 24px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            display: flex; gap: 40px; align-items: flex-start;
        }

        /* 左側：ディスプレイエリア */
        .display-section { 
            width: 360px; display: flex; flex-direction: column; gap: 20px;
        }
        
        /* 液晶画面：Apple製品のようなフラットな見栄え */
        .screen { 
            background: #f8f9fa; padding: 30px 20px; 
            border-radius: 16px; border: 1px solid #e9ecef;
        }
        .screen-label { 
            font-size: 13px; color: #adb5bd; margin-bottom: 5px; 
            font-weight: 700; letter-spacing: 1px;
        }
        .main-display { 
            font-size: 64px; font-weight: 700; color: #212529; 
            text-align: right; min-height: 75px; letter-spacing: -2px;
        }

        /* 数量入力ボックス */
        .quantity-box { 
            padding: 20px; background: #ffffff; 
            border-radius: 16px; border: 1px solid #e9ecef;
            display: flex; align-items: center; justify-content: space-between;
        }
        .quantity-box span { font-weight: 600; color: #495057; font-size: 16px; }
        input[type="number"] { 
            width: 80px; font-size: 24px; font-weight: bold; text-align: center; 
            border: 2px solid #e9ecef; border-radius: 8px; padding: 5px; color: #212529;
            outline: none; transition: border-color 0.2s;
        }
        input[type="number"]:focus { border-color: #0d6efd; }

        /* 右側：操作パネル（テンキー） */
        .control-section { width: 300px; }
        .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        
        /* ボタン共通デザイン：押しやすさ重視 */
        button { 
            height: 75px; border: none; border-radius: 16px; 
            font-size: 28px; font-weight: 500; cursor: pointer; 
            background: #f8f9fa; color: #212529; 
            box-shadow: 0 4px 0 #e9ecef; 
            transition: all 0.1s cubic-bezier(0.4, 0, 0.2, 1);
        }
        button:active { 
            transform: translateY(4px); box-shadow: 0 0 0 #e9ecef; 
        }
        
        /* 特殊ボタンの色分け */
        .btn-ac { background: #fff5f5; color: #fa5252; box-shadow: 0 4px 0 #ffe3e3; font-size: 24px; font-weight: 700;}
        .btn-ac:active { box-shadow: 0 0 0 #ffe3e3; }
        
        .btn-enter { background: #228be6; color: white; box-shadow: 0 4px 0 #1971c2; grid-column: span 2; font-size: 24px; font-weight: 700;}
        .btn-enter:active { box-shadow: 0 0 0 #1971c2; }
    </style>
</head>
<body>

<div class="pos-machine">
    <div class="display-section">
        <div class="screen">
            <div class="screen-label">TOTAL AMOUNT</div>
            <div class="main-display" id="disp">0</div>
        </div>
        <div class="quantity-box">
            <span>数量 (QTY)</span>
            <input type="number" form="pos-form" name="quantity" value="<?= $quantity ?>" min="1">
        </div>
    </div>

    <div class="control-section">
        <form id="pos-form" method="POST" action="process.php">
            <div class="keypad">
                <?php foreach ($numpad as $num): ?>
                    <button type="button" onclick="addNum('<?= $num ?>')"><?= $num ?></button>
                <?php endforeach; ?>
                
                <button type="button" class="btn-ac" onclick="clearAll()">AC</button>
                <button type="button" onclick="addNum('0')">0</button>
                <button type="submit" name="action" value="keijo" class="btn-enter">計上</button>
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
            // 日本円らしく ¥ マークをつけるのもアリです
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
