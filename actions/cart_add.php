<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../shop.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!$productId || !$quantity || $productId < 1 || $quantity < 1) {
    $_SESSION['cart_error'] = 'Invalid product or quantity selected.';
    header('Location: ../shop.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT product_id, product_name, stock_quantity
        FROM products
        WHERE product_id = :id
          AND status = 'active'
        LIMIT 1
    ");

    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['cart_error'] = 'This product is no longer available.';
        header('Location: ../shop.php');
        exit;
    }

    $availableStock = (int) $product['stock_quantity'];

    if ($availableStock <= 0) {
        $_SESSION['cart_error'] = 'Sorry, this item is currently out of stock.';
        header('Location: ../product.php?id=' . $productId);
        exit;
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $currentQuantity = (int) ($_SESSION['cart'][$productId] ?? 0);
    $newQuantity = $currentQuantity + $quantity;

    if ($newQuantity > $availableStock) {
        $_SESSION['cart_error'] = 'You already have the maximum available stock of this item in your cart.';
        header('Location: ../product.php?id=' . $productId);
        exit;
    }

    $_SESSION['cart'][$productId] = $newQuantity;

    $_SESSION['cart_success'] = 'Lovely! The item was added to your basket.';

    header('Location: ../cart.php?added=1');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['cart_error'] = 'Something went wrong. Please try again.';
    header('Location: ../shop.php');
    exit;
}