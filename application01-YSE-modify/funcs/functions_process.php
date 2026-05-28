<?php
function redirectToRegister()
{
    header('Location: index.php');
    exit;
}

function redirectToLogin()
{
    header('Location: login.php');
    exit;
}

function setNotice($message, $type = 'info')
{
    $_SESSION['notice'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function getCart()
{
    return $_SESSION['cart'] ?? [];
}

function setCart(array $cart)
{
    $_SESSION['cart'] = array_values($cart);
}

function rememberCart()
{
    $_SESSION['undo_cart'] = getCart();
}

function cartSubtotal(array $cart)
{
    $subtotal = 0;
    foreach ($cart as $line) {
        $subtotal += lineSubtotal($line);
    }
    return $subtotal;
}

function lineDiscountRate(array $line)
{
    return max(0, min(100, (float)($line['discount_rate'] ?? 0)));
}

function lineDiscountAmount(array $line)
{
    return max(0, (int)($line['discount_amount'] ?? ($line['discount'] ?? 0)));
}

function lineDiscountTotal(array $line)
{
    $base = (int)$line['price'] * (int)$line['quantity'];
    $rateDiscount = (int)floor($base * (lineDiscountRate($line) / 100));
    return min($base, $rateDiscount + lineDiscountAmount($line));
}

function lineSubtotal(array $line)
{
    $base = (int)$line['price'] * (int)$line['quantity'];
    return max(0, $base - lineDiscountTotal($line));
}

function cartTotalWithTax(array $cart, $taxRate)
{
    $subtotal = cartSubtotal($cart);
    return (int)floor($subtotal * (1 + ($taxRate / 100)));
}

function cleanInt($value, $min = 0, $max = 999999999)
{
    $value = preg_replace('/[^0-9]/', '', (string)$value);
    if ($value === '') {
        return $min;
    }
    return max($min, min($max, (int)$value));
}
?>