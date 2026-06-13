<?php
require_once 'db.php';

$product_id = (int)$_POST['product_id'];
$name       = $_POST['name'];
$price      = (float)$_POST['price'];

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['qty']++;
} else {
    $_SESSION['cart'][$product_id] = [
        'name'  => $name,
        'price' => $price,
        'qty'   => 1
    ];
}

echo json_encode(['count' => count($_SESSION['cart'])]);
?>
