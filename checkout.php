
<?php
session_start();

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Checkout | Poppy & Paper';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$errors = $_SESSION['checkout_errors'] ?? [];
$oldInput = $_SESSION['checkout_old_input'] ?? [];

unset($_SESSION['checkout_errors'], $_SESSION['checkout_old_input']);

$customerName = $oldInput['customer_name'] ?? '';
$customerEmail = $oldInput['customer_email'] ?? '';
$customerContact = $oldInput['customer_contact'] ?? '';
$deliveryAddress = $oldInput['delivery_address'] ?? '';
$paymentMethod = $oldInput['payment_method'] ?? '';
$orderNotes = $oldInput['order_notes'] ?? '';

function getCheckoutItems(PDO $pdo, array $cart): array
{
    $items = [];

    if (empty($cart)) {
        return $items;
    }

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT product_id, product_name, price, stock_quantity
        FROM products
        WHERE product_id IN ($placeholders)
          AND status = 'active'
    ");

    $stmt->execute($ids);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $product) {
        $id = (int) $product['product_id'];
        $quantity = (int) $cart[$id];

        $product['quantity'] = $quantity;
        $product['line_total'] = (float) $product['price'] * $quantity;

        $items[] = $product;
    }

    return $items;
}

$cartItems = getCheckoutItems($pdo, $_SESSION['cart']);
$subtotal = 0;

foreach ($cartItems as $item) {
    $subtotal += $item['line_total'];
}

if (empty($cartItems)) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

$_SESSION['checkout_quote'] = [];
foreach ($cartItems as $item) {
    $_SESSION['checkout_quote'][(int) $item['product_id']] = [
        'name' => $item['product_name'],
        'price' => number_format((float) $item['price'], 2, '.', ''),
        'quantity' => (int) $item['quantity'],
    ];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .checkout-head {
        animation: fadeUp .55s ease both;
    }
    .checkout-head .section-title {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #85566D;
        letter-spacing: -.015em;
    }
    .checkout-head .eyebrow {
        letter-spacing: .16em;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .checkout-steps {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        font-size: .85rem;
        font-weight: 700;
        color: var(--muted);
    }
    .checkout-steps .step {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }
    .checkout-steps .dot {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .7rem;
        font-weight: 800;
        background: var(--cream);
        border: 1.5px solid #F2DCE5;
    }
    .checkout-steps .step.done .dot {
        background: #CDE5D1;
        border-color: #A8D4B3;
        color: #2E6B45;
    }
    .checkout-steps .step.active .dot {
        background: var(--poppy);
        border-color: var(--poppy);
        color: #fff;
        box-shadow: 0 0 0 4px rgba(216, 121, 159, .18);
    }
    .checkout-steps .step.active { color: var(--ink); }
    .checkout-steps .sep { color: #D7C7CF; }

    .error-panel {
        background: #FBE4E9;
        color: #9A2D4B;
        border: 1px solid #F6CBD5;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        animation: fadeUp .5s ease both;
    }
    .error-panel strong { font-weight: 800; }
    .error-panel ul { padding-left: 1.1rem; }
    .error-panel li + li { margin-top: .25rem; }

    .form-card {
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.25rem;
        box-shadow: 0 4px 14px rgba(117, 76, 97, .05);
        overflow: hidden;
        animation: fadeUp .6s ease both;
        margin-bottom: 1rem;
    }
    .form-card .card-head {
        padding: 1rem 1.4rem;
        background: linear-gradient(125deg, #FCE0E9, #FFF0C9);
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        font-weight: 700;
        color: #754C61;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .form-card .card-head .icon {
        font-size: 1.15rem;
    }
    .form-card .card-body {
        padding: 1.4rem;
    }

    .form-label {
        font-weight: 800;
        font-size: .82rem;
        color: #85566D;
        letter-spacing: .03em;
        margin-bottom: .35rem;
    }
    .form-label .optional {
        font-weight: 600;
        color: var(--muted);
        font-size: .78rem;
    }
    .form-card .form-control,
    .form-card .form-select {
        border-radius: 1rem;
        border: 1.5px solid #F2DCE5;
        background: var(--cream);
        padding: .7rem 1rem;
        font-weight: 600;
        color: var(--ink);
        transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
    }
    .form-card .form-control::placeholder {
        color: #B3A6AC;
        font-weight: 500;
    }
    .form-card .form-control:focus,
    .form-card .form-select:focus {
        border-color: var(--poppy);
        background: #fff;
        box-shadow: 0 0 0 .22rem rgba(216, 121, 159, .18);
    }
    .form-card textarea.form-control {
        min-height: 90px;
        resize: vertical;
    }

    .payment-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: .65rem;
        margin-top: .25rem;
    }
    .payment-option {
        position: relative;
        display: block;
        cursor: pointer;
    }
    .payment-option input {
        position: absolute;
        opacity: 0;
        inset: 0;
        cursor: pointer;
    }
    .payment-option .inner {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .85rem 1rem;
        border: 1.5px solid #F2DCE5;
        border-radius: 1rem;
        background: var(--cream);
        font-weight: 700;
        color: var(--ink);
        transition: border-color .2s ease, background .2s ease, transform .2s ease, box-shadow .2s ease;
    }
    .payment-option .inner .dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 2px solid #D7C7CF;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: border-color .2s ease;
        flex: 0 0 auto;
    }
    .payment-option .inner .dot::after {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--poppy);
        transform: scale(0);
        transition: transform .2s ease;
    }
    .payment-option:hover .inner {
        border-color: #E9B6C9;
        background: #fff;
    }
    .payment-option input:checked + .inner {
        border-color: var(--poppy);
        background: #fff;
        box-shadow: 0 6px 16px rgba(216, 121, 159, .18);
    }
    .payment-option input:checked + .inner .dot {
        border-color: var(--poppy);
    }
    .payment-option input:checked + .inner .dot::after {
        transform: scale(1);
    }
    .payment-option input:focus-visible + .inner {
        outline: 3px solid var(--poppy);
        outline-offset: 2px;
    }

    .summary-card {
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.25rem;
        box-shadow: 0 6px 20px rgba(117, 76, 97, .07);
        overflow: hidden;
        animation: fadeUp .6s ease .1s both;
    }
    .summary-card .summary-head {
        padding: 1.15rem 1.4rem;
        background: linear-gradient(125deg, #FCE0E9, #FFF0C9);
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        font-weight: 700;
        color: #754C61;
        font-size: 1.05rem;
    }
    .summary-card .summary-body {
        padding: 1.25rem 1.4rem 1.4rem;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        padding: .6rem 0;
        border-bottom: 1px dashed #F2DCE5;
    }
    .summary-item:last-of-type { border-bottom: 0; }
    .summary-item .name {
        font-weight: 700;
        color: var(--ink);
        font-size: .92rem;
        line-height: 1.3;
    }
    .summary-item .qty {
        font-size: .78rem;
        color: var(--muted);
        margin-top: .1rem;
    }
    .summary-item .amount {
        font-weight: 800;
        color: var(--ink);
        white-space: nowrap;
        font-size: .92rem;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .55rem 0;
        color: var(--muted);
        font-weight: 600;
    }
    .summary-row strong { color: var(--ink); }

    .summary-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        margin-top: .5rem;
        border-top: 1px dashed #F2DCE5;
    }
    .summary-total .label {
        font-weight: 800;
        color: var(--ink);
    }
    .summary-total .amount {
        font-family: 'Quicksand', sans-serif;
        font-weight: 700;
        font-size: 1.5rem;
        color: #C86691;
    }

    .summary-note {
        font-size: .78rem;
        color: var(--muted);
        background: var(--cream);
        border-radius: .75rem;
        padding: .6rem .85rem;
        margin-top: 1rem;
        display: flex;
        gap: .5rem;
        align-items: flex-start;
    }

    .btn-place-order {
        background: var(--poppy);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: .85rem 1.25rem;
        font-weight: 800;
        font-size: 1rem;
        width: 100%;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .btn-place-order:hover {
        background: #C86691;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(216, 121, 159, .35);
    }
    .btn-back-cart {
        border: 1.5px solid #F2DCE5;
        background: #fff;
        color: var(--ink);
        border-radius: 999px;
        padding: .7rem 1.25rem;
        font-weight: 700;
        width: 100%;
        transition: border-color .2s ease, color .2s ease;
    }
    .btn-back-cart:hover {
        border-color: var(--poppy);
        color: var(--poppy);
    }

    @media (min-width: 992px) {
        .summary-sticky {
            position: sticky;
            top: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .form-card .card-body { padding: 1rem; }
        .form-card .card-head { padding: .85rem 1rem; }
        .summary-card .summary-body { padding: 1rem; }
        .summary-total .amount { font-size: 1.3rem; }
        .checkout-steps { font-size: .78rem; }
    }
</style>

<main class="container py-4 py-md-5">

    <div class="checkout-head mb-3">
        <p class="eyebrow mb-1">ALMOST THERE</p>
        <h1 class="section-title mb-2">Complete your order</h1>
        <p class="text-muted mb-0">
            Just a few more details before your lovely items arrive.
        </p>
    </div>

    <div class="checkout-steps" aria-label="Checkout progress">
        <span class="step done">
            <span class="dot">✓</span> Basket
        </span>
        <span class="sep">›</span>
        <span class="step active">
            <span class="dot">2</span> Details
        </span>
        <span class="sep">›</span>
        <span class="step">
            <span class="dot">3</span> Confirmation
        </span>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error-panel mb-4">
            <strong>Please check the following:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="actions/place-order.php" id="checkoutForm">

        <div class="row g-4">

            <div class="col-lg-7">

                <div class="form-card">
                    <div class="card-head">
                        <span class="icon">🌸</span> Customer Information
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label" for="customer_name">Full Name</label>
                            <input
                                type="text"
                                id="customer_name"
                                name="customer_name"
                                class="form-control"
                                maxlength="150"
                                value="<?= htmlspecialchars($customerName) ?>"
                                placeholder="Your lovely name"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="customer_email">Email Address</label>
                            <input
                                type="email"
                                id="customer_email"
                                name="customer_email"
                                class="form-control"
                                maxlength="190"
                                value="<?= htmlspecialchars($customerEmail) ?>"
                                placeholder="you@example.com"
                                required
                            >
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="customer_contact">Contact Number</label>
                            <input
                                type="tel"
                                id="customer_contact"
                                name="customer_contact"
                                class="form-control"
                                maxlength="40"
                                value="<?= htmlspecialchars($customerContact) ?>"
                                placeholder="+63 9XX XXX XXXX"
                                required
                            >
                        </div>

                    </div>
                </div>

                <div class="form-card">
                    <div class="card-head">
                        <span class="icon">📦</span> Delivery Information
                    </div>
                    <div class="card-body">

                        <label class="form-label" for="delivery_address">Delivery Address</label>
                        <textarea
                            id="delivery_address"
                            name="delivery_address"
                            class="form-control"
                            rows="3"
                            placeholder="House number, street, barangay, city, postal code"
                            required
                        ><?= htmlspecialchars($deliveryAddress) ?></textarea>

                    </div>
                </div>

                <div class="form-card">
                    <div class="card-head">
                        <span class="icon">💳</span> Payment Method
                    </div>
                    <div class="card-body">

                        <?php
                        $methods = [
                            'Cash on Delivery' => '💵',
                            'E-Wallet'         => '📱',
                            'Bank Transfer'    => '🏦'
                        ];
                        ?>

                        <div class="payment-grid">
                            <?php foreach ($methods as $method => $icon): ?>
                                <label class="payment-option">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="<?= htmlspecialchars($method) ?>"
                                        <?= $paymentMethod === $method ? 'checked' : '' ?>
                                        required
                                    >
                                    <span class="inner">
                                        <span class="dot"></span>
                                        <span><?= $icon ?> <?= htmlspecialchars($method) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-3">
                            <label class="form-label" for="order_notes">
                                Order Notes <span class="optional">(optional)</span>
                            </label>
                            <textarea
                                id="order_notes"
                                name="order_notes"
                                class="form-control"
                                rows="2"
                                maxlength="5000"
                                placeholder="Any special instructions?"
                            ><?= htmlspecialchars($orderNotes) ?></textarea>
                        </div>

                    </div>
                </div>

            </div>

            <div class="col-lg-5">
                <div class="summary-sticky">
                    <div class="summary-card">

                        <div class="summary-head">
                            Order Summary
                        </div>

                        <div class="summary-body">

                            <?php foreach ($cartItems as $item): ?>
                                <div class="summary-item">
                                    <div>
                                        <div class="name">
                                            <?= htmlspecialchars($item['product_name']) ?>
                                        </div>
                                        <div class="qty">
                                            ₱<?= number_format((float) $item['price'], 2) ?> × <?= (int) $item['quantity'] ?>
                                        </div>
                                    </div>
                                    <div class="amount">
                                        ₱<?= number_format($item['line_total'], 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="summary-row" style="margin-top: .5rem;">
                                <span>Subtotal</span>
                                <strong>
                                    ₱<?= number_format($subtotal, 2) ?>
                                </strong>
                            </div>

                            <div class="summary-total">
                                <span class="label">Total</span>
                                <span class="amount">
                                    ₱<?= number_format($subtotal, 2) ?>
                                </span>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-place-order mt-3"
                                id="placeOrderBtn"
                            >
                                Place Order →
                            </button>

                            <a
                                href="cart.php"
                                class="btn btn-back-cart mt-2"
                            >
                                ← Back to Cart
                            </a>

                        </div>

                    </div>
                </div>
            </div>

        </div>

    </form>

</main>

<script>
    document.getElementById('checkoutForm').addEventListener('submit', function () {
        const btn = document.getElementById('placeOrderBtn');
        btn.disabled = true;
        btn.textContent = 'Placing your order…';
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
