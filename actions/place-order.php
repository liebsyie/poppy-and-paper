
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../cart.php');
    exit;
}

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: ../cart.php');
    exit;
}

$customerName = trim($_POST['customer_name'] ?? '');
$customerEmail = trim($_POST['customer_email'] ?? '');
$customerContact = trim($_POST['customer_contact'] ?? '');
$deliveryAddress = trim($_POST['delivery_address'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? '');
$orderNotes = trim($_POST['order_notes'] ?? '');

$errors = [];

if ($customerName === '' || strlen($customerName) > 150) {
    $errors[] = 'Please enter a valid customer name.';
}

if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || strlen($customerEmail) > 190) {
    $errors[] = 'Please enter a valid email address.';
}

if ($customerContact === '' || strlen($customerContact) > 40) {
    $errors[] = 'Please enter a valid contact number.';
}

if ($deliveryAddress === '') {
    $errors[] = 'Please enter your delivery address.';
}

if (!in_array($paymentMethod, ['Cash on Delivery', 'E-Wallet', 'Bank Transfer'], true)) {
    $errors[] = 'Please select a valid payment method.';
}

if (strlen($orderNotes) > 5000) {
    $errors[] = 'Order notes are too long.';
}

if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    $_SESSION['checkout_old_input'] = $_POST;
    header('Location: ../checkout.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $verifiedItems = [];
    $verifiedSubtotal = 0;
    $quote = $_SESSION['checkout_quote'] ?? [];
    $changes = [];

    if ($quote === []) {
        throw new Exception('Please review your order total before placing it.');
    }

    foreach ($_SESSION['cart'] as $productId => $quantity) {

        $stmt = $pdo->prepare("
            SELECT product_id, product_name, price, stock_quantity
            FROM products
            WHERE product_id = :id
              AND status = 'active'
            FOR UPDATE
        ");

        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        $productId = (int) $productId;
        $quantity = (int) $quantity;

        if (!$product) {
            unset($_SESSION['cart'][$productId]);
            $changes[] = 'One item is no longer available and was removed from your basket.';
            continue;
        }

        $stock = (int) $product['stock_quantity'];
        $price = (float) $product['price'];
        $seen = $quote[$productId] ?? null;
        $seenCents = $seen ? (int) round(((float) $seen['price']) * 100) : null;
        $currentCents = (int) round($price * 100);

        if ($seenCents !== null && $seenCents !== $currentCents) {
            $changes[] = $product['product_name'] . ' changed from ₱'
                . number_format($seenCents / 100, 2)
                . ' to ₱' . number_format($price, 2) . '.';
        }

        if ($stock < 1) {
            unset($_SESSION['cart'][$productId]);
            $changes[] = $product['product_name'] . ' is now out of stock and was removed from your basket.';
            continue;
        }

        if ($quantity > $stock) {
            $_SESSION['cart'][$productId] = $stock;
            $changes[] = $product['product_name'] . ' now has only ' . $stock . ' in stock. Your quantity was updated.';
            $quantity = $stock;
        }

        if ($quantity < 1) {
            unset($_SESSION['cart'][$productId]);
            $changes[] = $product['product_name'] . ' could not be ordered.';
            continue;
        }

        $lineTotal = $price * $quantity;

        $product['quantity'] = $quantity;
        $product['line_total'] = $lineTotal;

        $verifiedItems[] = $product;
        $verifiedSubtotal += $lineTotal;
    }

    if ($changes !== []) {
        throw new Exception(
            implode(' ', $changes) . ' Please review the updated total, then place your order again.'
        );
    }

    if (empty($verifiedItems)) {
        throw new Exception('Your cart is empty.');
    }

    $orderNumber = 'PP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_number,
            customer_name,
            customer_email,
            customer_contact,
            delivery_address,
            payment_method,
            order_notes,
            subtotal,
            total,
            status
        ) VALUES (
            :order_number,
            :customer_name,
            :customer_email,
            :customer_contact,
            :delivery_address,
            :payment_method,
            :order_notes,
            :subtotal,
            :total,
            'Pending'
        )
    ");

    $stmt->execute([
        'order_number' => $orderNumber,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_contact' => $customerContact,
        'delivery_address' => $deliveryAddress,
        'payment_method' => $paymentMethod,
        'order_notes' => $orderNotes !== '' ? $orderNotes : null,
        'subtotal' => $verifiedSubtotal,
        'total' => $verifiedSubtotal
    ]);

    $orderId = $pdo->lastInsertId();

    foreach ($verifiedItems as $item) {

        $stmt = $pdo->prepare("
            INSERT INTO order_items (
                order_id,
                product_id,
                product_name_snapshot,
                unit_price_snapshot,
                quantity,
                line_total
            ) VALUES (
                :order_id,
                :product_id,
                :product_name,
                :unit_price,
                :quantity,
                :line_total
            )
        ");

        $stmt->execute([
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'product_name' => $item['product_name'],
            'unit_price' => $item['price'],
            'quantity' => $item['quantity'],
            'line_total' => $item['line_total']
        ]);

        $stmt = $pdo->prepare("
            UPDATE products
            SET stock_quantity = stock_quantity - :quantity
            WHERE product_id = :id
        ");

        $stmt->execute([
            'quantity' => $item['quantity'],
            'id' => $item['product_id']
        ]);
    }

    $pdo->commit();

    $_SESSION['cart'] = [];
    unset($_SESSION['checkout_errors'], $_SESSION['checkout_old_input']);

    header('Location: ../confirmation.php?order=' . urlencode($orderNumber));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log($e->getMessage());
    $_SESSION['checkout_errors'] = [$e->getMessage()];
    $_SESSION['checkout_old_input'] = $_POST;
    header('Location: ../checkout.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log($e->getMessage());
    $_SESSION['checkout_errors'] = ['Something went wrong while processing your order. Please try again.'];
    $_SESSION['checkout_old_input'] = $_POST;
    header('Location: ../checkout.php');
    exit;
}
