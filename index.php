<?php
session_start();
$amount = $_SESSION['amount'] ?? '';
$_SESSION['amount'] = "";
//追加↑空箱に
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>YSEレジ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="pos-machine">
    <h1>YSEレジ</h1>
    
    <div class="screen">
        <div class="op-indicator" id="op-ind"></div>
        <div id="disp">0</div>
    </div>

    <form id="pos-form" method="POST" action="process.php">
        <input type="hidden" name="amount" id="hidden_amount" value="<?= htmlspecialchars($amount) ?>">
        <input type="hidden" name="quantity" value="1">
        <input type="hidden" name="action" id="hidden_action" value="">
        
        <div class="keypad">
            <button type="button" class="span-2" onclick="clearAll()">AC</button>
            <button type="button" class="span-2" onclick="applyTax()">税込み</button>
            
            <button type="button" onclick="addNum('7')">7</button>
            <button type="button" onclick="addNum('8')">8</button>
            <button type="button" onclick="addNum('9')">9</button>
            <button type="button" onclick="handleOp('*')">×</button>
            
            <button type="button" onclick="addNum('4')">4</button>
            <button type="button" onclick="addNum('5')">5</button>
            <button type="button" onclick="addNum('6')">6</button>
            <button type="button" onclick="handleOp('+')">+</button>
            
            <button type="button" onclick="addNum('1')">1</button>
            <button type="button" onclick="addNum('2')">2</button>
            <button type="button" onclick="addNum('3')">3</button>
            <button type="button" class="btn-equal" onclick="pressEqual()">＝</button>
            
            <button type="button" onclick="addNum('0')">0</button>
            <button type="button" onclick="submitAction('calc')">売上</button>
            <button type="button" onclick="submitAction('keijo')">計上</button>
        </div>
    </form>
</div>

<script>
    const disp = document.getElementById('disp');
    const opInd = document.getElementById('op-ind');
    const hiddenInput = document.getElementById('hidden_amount');
    const hiddenAction = document.getElementById('hidden_action');
    const form = document.getElementById('pos-form');
    
    let currentVal = "";
    // PHPからの初期値読み込み
    let initialAmount = "<?= ($amount ?: '0') ?>";
    let runningTotal = BigInt(initialAmount); 
    let formulaParts = [];
    let isTaxApplied = false;
    //BigInt型に変換 →　初期値を文字列として扱い、後ろにｎをつける
    let preTaxTotal = 0n; 

    const MAX_DIGITS = 18; // 👈追加　変数で文字数の限界を設定。

    function updateDisplay(val) {
        //val.toString()BigIntがparseIntできないため、toString()で変換
        let strVal = val.toString();
        disp.innerText = strVal === "" ? "0" : strVal;
        hiddenInput.value = strVal;
    }

    function addNum(n) {
        if (isTaxApplied) return;
        if (currentVal.length < 8) {
            if (currentVal === "" && n === "0") return;
            currentVal += n;
            updateDisplay(currentVal);
        }
    }

    function clearAll() {
        currentVal = "";
        runningTotal = 0n; //0n
        formulaParts = [];
        isTaxApplied = false;
        preTaxTotal = 0n; //0n
        opInd.innerText = "";
        updateDisplay(0n); //0n
    }

    function pushCurrentItem() {
        if (currentVal === "") return;
        //☟単純に値がばかでかいので、BigIntに変換する
        let price = BigInt(currentVal);
        let nextTotal = runningTotal; // 計算後の値を保持

        if (formulaParts.length === 0) {
            nextTotal = price;
        } else {
            let lastOp = formulaParts[formulaParts.length - 1];
            if (lastOp === "+") nextTotal += price;
            if (lastOp === "*") nextTotal *= price;
        }

        // ☟追加　最大桁を超えたら、アラートを出す☟18字数を超え。
        if (nextTotal.toString().length > MAX_DIGITS) {
            alert("桁数が大きすぎます。");
            // ☟更新はしない。currentVal = "";は前の処理を継続する
            currentVal = ""; 
            return false; // 失敗を知らせる
        }

        // 成功したらそのままなので、 runningTotalを更新する。それ以外はfalse
        runningTotal = nextTotal;
        formulaParts.push(price);
        currentVal = "";
        return true; // 成功
    }

    function handleOp(op) {
        if (currentVal !== "") {
            // 計算がfalseなら、計算はリセットされ、手前までリセット。四則演算はなし。
            if (!pushCurrentItem()) return; 
        } else if (formulaParts.length > 0) {
            let last = formulaParts[formulaParts.length-1];
            // 演算子と判定☟
            if (typeof last === 'string') formulaParts.pop();
        } else {
            return;
        }
        formulaParts.push(op);
        opInd.innerText = op === '*' ? '×' : op;
        updateDisplay(runningTotal);
    }

    function pressEqual() {
        if (currentVal !== "") {
            pushCurrentItem();
        }
        if (formulaParts.length > 0) {
            let last = formulaParts[formulaParts.length-1];
            if (last === "+" || last === "*") formulaParts.pop();
        }
        opInd.innerText = "";
        updateDisplay(runningTotal);
    }

    function applyTax() {
        if (currentVal !== "") {
            //税込み押したときに、桁数オーバーをキャンセル。
            if (!pushCurrentItem()) return;
        }
        
        if (!isTaxApplied && runningTotal > 0n) {
            // BigIntは、1.1は使えないらしい？　11/10でできるらしい。
            let taxTotal = (runningTotal * 11n + 5n) / 10n;

            // 税込計算でもチェック
            if (taxTotal.toString().length > MAX_DIGITS) {
                alert("税込金額が大きすぎます。");
                return;
            }

            preTaxTotal = runningTotal;
            runningTotal = taxTotal;
            isTaxApplied = true;
            opInd.innerText = "税込";
            updateDisplay(runningTotal);
        } else if (isTaxApplied) {
            runningTotal = preTaxTotal;
            isTaxApplied = false;
            opInd.innerText = "";
            updateDisplay(runningTotal);
        }
    }

    function submitAction(actionStr) {
        pressEqual(); 
        hiddenAction.value = actionStr;
        form.submit();
    }

    updateDisplay(runningTotal);
</script>

</body>
</html>


