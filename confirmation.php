
<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Order Confirmation | Poppy & Paper';

$orderNumber = trim($_GET['order'] ?? '');

if ($orderNumber === '') {
    header('Location: shop.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE order_number = :order_number
    LIMIT 1
");

$stmt->execute(['order_number' => $orderNumber]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    $notFound = true;
    $items = [];
} else {
    $notFound = false;

    $stmt = $pdo->prepare("
        SELECT
            product_name_snapshot,
            unit_price_snapshot,
            quantity,
            line_total
        FROM order_items
        WHERE order_id = :order_id
    ");

    $stmt->execute(['order_id' => $order['order_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .confirm-hero {
        position: relative;
        text-align: center;
        padding: 2rem 1rem 2.5rem;
        animation: fadeUp .6s ease both;
    }
    .confirm-hero .section-title {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #85566D;
        letter-spacing: -.015em;
    }
    .confirm-hero .eyebrow {
        letter-spacing: .16em;
    }

    .check-badge {
        width: 92px;
        height: 92px;
        margin: 0 auto 1.25rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #CDE5D1, #A8D4B3);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 24px rgba(120, 175, 140, .35);
        animation: popIn .55s cubic-bezier(.34, 1.56, .64, 1) both;
        position: relative;
    }
    .check-badge::after {
        content: "";
        position: absolute;
        inset: -8px;
        border-radius: 50%;
        border: 2px solid rgba(168, 212, 179, .5);
        animation: ripple 2.4s ease-out infinite;
    }
    .check-badge svg {
        width: 42px;
        height: 42px;
        stroke: #fff;
        stroke-width: 3.5;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-dasharray: 40;
        stroke-dashoffset: 40;
        animation: drawCheck .7s ease .35s forwards;
    }

    @keyframes popIn {
        0%   { transform: scale(0); opacity: 0; }
        70%  { transform: scale(1.12); }
        100% { transform: scale(1); opacity: 1; }
    }
    @keyframes drawCheck {
        to { stroke-dashoffset: 0; }
    }
    @keyframes ripple {
        0%   { transform: scale(1);   opacity: .9; }
        100% { transform: scale(1.5); opacity: 0; }
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 999px;
        padding: .45rem .95rem;
        font-weight: 800;
        font-size: .8rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        background: #E7F4EC;
        color: #2E6B45;
    }
    .status-pill::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #4CAF7D;
        box-shadow: 0 0 0 3px rgba(76, 175, 125, .25);
        animation: pulse 1.8s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 3px rgba(76, 175, 125, .25); }
        50%      { box-shadow: 0 0 0 6px rgba(76, 175, 125, .1); }
    }

    .confirm-card {
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.25rem;
        box-shadow: 0 4px 14px rgba(117, 76, 97, .05);
        overflow: hidden;
        animation: fadeUp .55s ease both;
    }
    .confirm-card + .confirm-card { margin-top: 1rem; }

    .confirm-card .card-head {
        padding: 1rem 1.4rem;
        border-bottom: 1px solid #F7E7EE;
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        font-weight: 700;
        color: #754C61;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .confirm-card .card-head .icon {
        font-size: 1.1rem;
    }
    .confirm-card .card-body {
        padding: 1.25rem 1.4rem 1.4rem;
    }

    .meta-chip {
        background: var(--cream);
        border: 1px solid #F2DCE5;
        border-radius: .9rem;
        padding: .85rem 1rem;
        height: 100%;
    }
    .meta-chip .label {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
        display: block;
        margin-bottom: .25rem;
    }
    .meta-chip .value {
        font-weight: 800;
        color: #85566D;
        font-size: .98rem;
        word-break: break-word;
    }

    .order-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding: .85rem 0;
        border-bottom: 1px dashed #F2DCE5;
    }
    .order-item:last-child { border-bottom: 0; }
    .order-item .name {
        font-weight: 800;
        color: var(--ink);
        line-height: 1.3;
    }
    .order-item .qty {
        font-size: .85rem;
        color: var(--muted);
        margin-top: .15rem;
    }
    .order-item .line-total {
        font-weight: 800;
        color: var(--ink);
        white-space: nowrap;
    }

    .totals-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .6rem 0;
        color: var(--muted);
        font-weight: 600;
    }
    .totals-row.grand {
        border-top: 1px dashed #F2DCE5;
        margin-top: .5rem;
        padding-top: 1rem;
        color: var(--ink);
        font-weight: 800;
    }
    .totals-row.grand .amount {
        font-family: 'Quicksand', sans-serif;
        font-size: 1.5rem;
        color: #C86691;
        font-weight: 700;
    }

    .info-list {
        display: grid;
        gap: .75rem;
    }
    .info-row {
        display: grid;
        grid-template-columns: 130px 1fr;
        gap: .75rem;
        align-items: baseline;
    }
    .info-row .label {
        font-size: .8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--muted);
    }
    .info-row .value {
        color: var(--ink);
        font-weight: 600;
        word-break: break-word;
    }

    .confirm-footer {
        text-align: center;
        padding: 2rem 1rem 1rem;
        animation: fadeUp .6s ease .2s both;
    }
    .confirm-footer p {
        color: var(--muted);
        font-weight: 600;
    }
    .btn-continue-shop {
        background: var(--poppy);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: .8rem 2.5rem;
        font-weight: 800;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .btn-continue-shop:hover {
        background: #C86691;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(216, 121, 159, .35);
    }

    .not-found {
        background: #fff;
        border: 1px dashed #F2DCE5;
        border-radius: 1.5rem;
        padding: 3.5rem 1.5rem;
        text-align: center;
        animation: fadeUp .6s ease both;
    }
    .not-found .emoji {
        font-size: 4rem;
        display: block;
        margin-bottom: .5rem;
        animation: sway 3s ease-in-out infinite;
    }
    @keyframes sway {
        0%, 100% { transform: rotate(-6deg); }
        50%      { transform: rotate(6deg); }
    }

    @media (max-width: 576px) {
        .check-badge { width: 76px; height: 76px; }
        .check-badge svg { width: 34px; height: 34px; }
        .info-row { grid-template-columns: 1fr; gap: .15rem; }
        .confirm-card .card-body { padding: 1rem; }
        .confirm-card .card-head { padding: .85rem 1rem; }
        .totals-row.grand .amount { font-size: 1.3rem; }
    }
</style>

<main class="container py-4 py-md-5">

    <?php if ($notFound): ?>

        <div class="not-found">
            <span class="emoji">🔎</span>
            <h1 class="section-title mb-2">Order not found</h1>
            <p class="text-muted">
                We couldn't find the order you're looking for.
            </p>
            <a href="shop.php" class="btn btn-poppy mt-3">
                Back to Shop
            </a>
        </div>

    <?php else: ?>

        <div class="confirm-hero">

            <div class="check-badge">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <polyline points="4,12 10,18 20,7"></polyline>
                </svg>
            </div>

            <p class="eyebrow mb-2">ORDER RECEIVED</p>

            <h1 class="section-title mb-2">
                Thank you for your order!
            </h1>

            <p class="text-muted mb-3">
                Your little order has been received.
                We can't wait for your lovely items to reach you!
            </p>

            <span class="status-pill">
                <?= htmlspecialchars($order['status']) ?>
            </span>

        </div>

        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">

                <div class="confirm-card">
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="meta-chip">
                                    <span class="label">Order Number</span>
                                    <div class="value">
                                        <?= htmlspecialchars($order['order_number']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="meta-chip">
                                    <span class="label">Order Date</span>
                                    <div class="value">
                                        <?= date(
                                            'M d, Y · h:i A',
                                            strtotime($order['created_at'])
                                        ) ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="confirm-card">
                    <div class="card-head">
                        <span class="icon">🧾</span> Order Summary
                    </div>
                    <div class="card-body">

                        <?php foreach ($items as $item): ?>
                            <div class="order-item">
                                <div>
                                    <div class="name">
                                        <?= htmlspecialchars($item['product_name_snapshot']) ?>
                                    </div>
                                    <div class="qty">
                                        ₱<?= number_format((float) $item['unit_price_snapshot'], 2) ?>
                                        × <?= (int) $item['quantity'] ?>
                                    </div>
                                </div>

                                <div class="line-total">
                                    ₱<?= number_format((float) $item['line_total'], 2) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="totals-row" style="margin-top: .75rem;">
                            <span>Subtotal</span>
                            <strong>
                                ₱<?= number_format((float) $order['subtotal'], 2) ?>
                            </strong>
                        </div>

                        <div class="totals-row grand">
                            <span>Total</span>
                            <span class="amount">
                                ₱<?= number_format((float) $order['total'], 2) ?>
                            </span>
                        </div>

                    </div>
                </div>

                <div class="confirm-card">
                    <div class="card-head">
                        <span class="icon">📦</span> Delivery Information
                    </div>
                    <div class="card-body">

                        <div class="info-list">

                            <div class="info-row">
                                <span class="label">Name</span>
                                <span class="value">
                                    <?= htmlspecialchars($order['customer_name']) ?>
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="label">Email</span>
                                <span class="value">
                                    <?= htmlspecialchars($order['customer_email']) ?>
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="label">Contact</span>
                                <span class="value">
                                    <?= htmlspecialchars($order['customer_contact']) ?>
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="label">Address</span>
                                <span class="value">
                                    <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="label">Payment</span>
                                <span class="value">
                                    <?= htmlspecialchars($order['payment_method']) ?>
                                </span>
                            </div>

                            <?php if (!empty($order['order_notes'])): ?>
                                <div class="info-row">
                                    <span class="label">Notes</span>
                                    <span class="value">
                                        <?= nl2br(htmlspecialchars($order['order_notes'])) ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                        </div>

                    </div>
                </div>

                <div class="confirm-footer">
                    <p class="mb-3">
                        Thank you for choosing Poppy &amp; Paper!<br>
                        <em>Little things, lovely days.</em> ♡
                    </p>

                    <a href="shop.php" class="btn btn-continue-shop">
                        Continue Shopping
                    </a>
                </div>

            </div>
        </div>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
