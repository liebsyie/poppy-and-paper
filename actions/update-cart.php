
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../cart.php');
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';

if ($action === 'remove') {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    
    if ($productId && isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        $_SESSION['cart_success'] = 'Item successfully removed from your basket.';
    }
    
    header('Location: ../cart.php?removed=1');
    exit;
}

if ($action === 'update') {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity  = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$productId || !$quantity || $quantity < 1) {
        $_SESSION['cart_error'] = 'Please enter a valid quantity.';
        header('Location: ../cart.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT stock_quantity
            FROM products
            WHERE product_id = :id
              AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['cart_error'] = 'This product is no longer available and was removed from your cart.';
            header('Location: ../cart.php');
            exit;
        }

        $availableStock = (int) $product['stock_quantity'];

        if ($availableStock <= 0) {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['cart_error'] = 'This product is out of stock and was removed from your cart.';
            header('Location: ../cart.php');
            exit;
        }

        if ($quantity > $availableStock) {
            $_SESSION['cart_error'] = 'Requested quantity exceeds available stock. Only ' . $availableStock . ' left.';
            header('Location: ../cart.php');
            exit;
        }

        $_SESSION['cart'][$productId] = $quantity;
        $_SESSION['cart_success'] = 'Cart successfully updated.';

        header('Location: ../cart.php?updated=1');
        exit;

    } catch (PDOException $e) {
        error_log($e->getMessage());
        $_SESSION['cart_error'] = 'Something went wrong. Please try again.';
        header('Location: ../cart.php');
        exit;
    }
}

header('Location: ../cart.php');
exit;
