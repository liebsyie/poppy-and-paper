
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/orders.php');
    exit;
}

if (
    empty($_SESSION['csrf_token']) ||
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['order_error'] = 'Invalid request. Please refresh the page and try again.';
    header('Location: ../admin/orders.php');
    exit;
}

$action = $_POST['action'] ?? '';
$orderId = filter_var($_POST['order_id'] ?? '', FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    $_SESSION['order_error'] = 'Invalid order selected.';
    header('Location: ../admin/orders.php');
    exit;
}

if (!in_array($action, ['update_status', 'cancel'], true)) {
    $_SESSION['order_error'] = 'Invalid action.';
    header('Location: ../admin/orders.php');
    exit;
}

$nextStatus = [
    'Pending' => 'Confirmed',
    'Confirmed' => 'Preparing',
    'Preparing' => 'Shipped',
    'Shipped' => 'Completed'
];

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT order_id, order_number, status
        FROM orders
        WHERE order_id = :id
        FOR UPDATE
    ");

    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new RuntimeException('Order not found.');
    }

    $currentStatus = $order['status'];

    if ($action === 'update_status') {

        if (!isset($nextStatus[$currentStatus])) {
            throw new RuntimeException(
                'This order cannot be advanced to another status.'
            );
        }

        $newStatus = $nextStatus[$currentStatus];

        $stmt = $pdo->prepare("
            UPDATE orders
            SET status = :status
            WHERE order_id = :id
        ");

        $stmt->execute([
            'status' => $newStatus,
            'id' => $orderId
        ]);

        $successMessage = 'Order ' . $order['order_number']
            . ' status updated to ' . $newStatus . '.';
    }

    elseif ($action === 'cancel') {

        if (!in_array($currentStatus, ['Pending', 'Confirmed'], true)) {
            throw new RuntimeException(
                'Only Pending or Confirmed orders can be cancelled.'
            );
        }

        $stmt = $pdo->prepare("
            SELECT product_id, quantity
            FROM order_items
            WHERE order_id = :order_id
            FOR UPDATE
        ");

        $stmt->execute(['order_id' => $orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            throw new RuntimeException(
                'No order items were found. The order was not cancelled.'
            );
        }

        foreach ($items as $item) {

            $stmt = $pdo->prepare("
                UPDATE products
                SET stock_quantity = stock_quantity + :quantity
                WHERE product_id = :product_id
            ");

            $stmt->execute([
                'quantity' => (int) $item['quantity'],
                'product_id' => (int) $item['product_id']
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Unable to restore stock for an order item.'
                );
            }
        }

        $stmt = $pdo->prepare("
            UPDATE orders
            SET status = 'Cancelled'
            WHERE order_id = :id
        ");

        $stmt->execute(['id' => $orderId]);

        $successMessage = 'Order ' . $order['order_number']
            . ' was cancelled and product stock was restored.';
    }

    $pdo->commit();

    $_SESSION['order_success'] = $successMessage;

    header('Location: ../admin/orders.php');
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    $_SESSION['order_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Unable to process the order. Please try again.';

    header('Location: ../admin/orders.php');
    exit;
}
