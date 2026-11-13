
<?php
session_start();

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Your cart | Poppy & Paper';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = $_SESSION['cart_success'] ?? '';
$error = $_SESSION['cart_error'] ?? '';

unset($_SESSION['cart_success'], $_SESSION['cart_error']);

if (isset($_GET['added'])) {
    $message = 'Lovely! Your item has been added to your basket.';
} elseif (isset($_GET['updated'])) {
    $message = 'Your cart has been updated.';
} elseif (isset($_GET['removed'])) {
    $message = 'Item successfully removed from your basket.';
}

$cartItems = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {

    $productIds = array_keys($_SESSION['cart']);

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $stmt = $pdo->prepare("
        SELECT product_id, product_name, price, stock_quantity, image_path
        FROM products
        WHERE product_id IN ($placeholders)
          AND status = 'active'
    ");

    $stmt->execute($productIds);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {

        $id = (int) $product['product_id'];
        $quantity = (int) $_SESSION['cart'][$id];

        $price = (float) $product['price'];
        $stock = (int) $product['stock_quantity'];

        if ($stock <= 0) {
            unset($_SESSION['cart'][$id]);
            continue;
        }

        if ($quantity > $stock) {
            $quantity = $stock;
            $_SESSION['cart'][$id] = $quantity;
        }

        $itemSubtotal = $price * $quantity;
        $subtotal += $itemSubtotal;

        $product['quantity'] = $quantity;
        $product['subtotal'] = $itemSubtotal;

        $cartItems[] = $product;
    }

    $validIds = array_column($cartItems, 'product_id');

    foreach (array_keys($_SESSION['cart']) as $id) {
        if (!in_array($id, $validIds)) {
            unset($_SESSION['cart'][$id]);
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .cart-head {
        animation: fadeUp .55s ease both;
    }
    .cart-head .section-title {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #85566D;
        letter-spacing: -.015em;
    }
    .cart-head .eyebrow {
        letter-spacing: .16em;
    }

    .cart-alert {
        border: 0;
        border-radius: 1rem;
        padding: .9rem 1.15rem;
        font-weight: 600;
        animation: fadeUp .5s ease both;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .cart-alert.alert-success {
        background: #E7F4EC;
        color: #2E6B45;
    }
    .cart-alert.alert-danger {
        background: #FBE4E9;
        color: #9A2D4B;
    }

    .cart-item {
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.25rem;
        box-shadow: 0 4px 14px rgba(117, 76, 97, .05);
        padding: 1rem;
        margin-bottom: 1rem;
        transition: transform .25s ease, box-shadow .25s ease;
        animation: fadeUp .55s ease both;
    }
    .cart-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 26px rgba(117, 76, 97, .12);
    }

    .cart-item .thumb {
        width: 100%;
        aspect-ratio: 1 / 1;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #FCE8EF, #FFF0D9);
        border-radius: .9rem;
        font-size: 2rem;
        transition: transform .35s ease;
        overflow: hidden;
        position: relative;
    }
    
    .cart-item .thumb img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 10px;
        transition: transform .35s ease;
    }

    .cart-item:hover .thumb {
        transform: scale(1.05) rotate(-3deg);
    }
    .cart-item:hover .thumb img {
        transform: scale(1.08);
    }

    .cart-item .item-name {
        font-family: 'Nunito', sans-serif;
        font-weight: 800;
        color: #85566D;
        margin-bottom: .25rem;
        line-height: 1.3;
    }
    .cart-item .item-price {
        color: var(--ink);
        font-weight: 700;
        margin-bottom: .15rem;
    }
    .cart-item .item-stock {
        font-size: .8rem;
        color: var(--muted);
    }

    .qty-control {
        display: flex;
        align-items: stretch;
        background: var(--cream);
        border: 1px solid #F2DCE5;
        border-radius: 999px;
        overflow: hidden;
        max-width: 150px;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .qty-control:focus-within {
        border-color: var(--poppy);
        box-shadow: 0 0 0 .2rem rgba(216, 121, 159, .18);
    }
    .qty-control input {
        border: 0;
        background: transparent;
        text-align: center;
        font-weight: 800;
        color: var(--ink);
        width: 100%;
        padding: .45rem .25rem;
        -moz-appearance: textfield;
    }
    .qty-control input::-webkit-outer-spin-button,
    .qty-control input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .qty-control input:focus {
        outline: none;
    }
    .qty-control .btn-update {
        border: 0;
        background: var(--poppy);
        color: #fff;
        font-weight: 800;
        font-size: .8rem;
        padding: 0 .9rem;
        transition: background .2s ease;
        white-space: nowrap;
    }
    .qty-control .btn-update:hover {
        background: #C86691;
    }

    .item-subtotal {
        font-weight: 800;
        color: var(--ink);
        font-size: 1.05rem;
    }
    .btn-remove {
        color: var(--poppy);
        font-weight: 700;
        background: none;
        border: 0;
        padding: 0;
        font-size: .85rem;
        transition: color .2s ease;
    }
    .btn-remove:hover {
        color: #C86691;
        text-decoration: underline;
    }

    .summary-card {
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.25rem;
        box-shadow: 0 6px 20px rgba(117, 76, 97, .07);
        animation: fadeUp .6s ease .1s both;
        overflow: hidden;
    }
    .summary-card .summary-head {
        background: linear-gradient(125deg, #FCE0E9, #FFF0C9);
        padding: 1.25rem 1.4rem;
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        font-weight: 700;
        font-size: 1.15rem;
        color: #754C61;
    }
    .summary-card .summary-body {
        padding: 1.25rem 1.4rem 1.5rem;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: .9rem;
        color: var(--muted);
        font-weight: 600;
    }
    .summary-row strong {
        color: var(--ink);
    }
    .summary-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: .9rem;
        border-top: 1px dashed #F2DCE5;
        margin-bottom: 1.25rem;
    }
    .summary-total .label {
        font-weight: 800;
        color: var(--ink);
    }
    .summary-total .value {
        font-family: 'Quicksand', sans-serif;
        font-weight: 700;
        font-size: 1.5rem;
        color: #C86691;
    }

    .btn-checkout {
        background: var(--poppy);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: .8rem 1.25rem;
        font-weight: 800;
        font-size: 1rem;
        width: 100%;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .btn-checkout:hover {
        background: #C86691;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(216, 121, 159, .35);
    }
    .btn-continue {
        border: 1.5px solid #F2DCE5;
        background: #fff;
        color: var(--ink);
        border-radius: 999px;
        padding: .6rem 1.25rem;
        font-weight: 700;
        transition: border-color .2s ease, color .2s ease, transform .2s ease;
    }
    .btn-continue:hover {
        border-color: var(--poppy);
        color: var(--poppy);
        transform: translateX(-3px);
    }

    .empty-cart {
        background: #fff;
        border: 1px dashed #F2DCE5;
        border-radius: 1.5rem;
        padding: 3.5rem 1.5rem;
        text-align: center;
        animation: fadeUp .6s ease both;
    }
    .empty-cart .emoji {
        font-size: 4rem;
        display: block;
        margin-bottom: .5rem;
        animation: sway 3s ease-in-out infinite;
    }
    @keyframes sway {
        0%, 100% { transform: rotate(-6deg); }
        50%      { transform: rotate(6deg); }
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .cart-items-list > .cart-item:nth-child(1) { animation-delay: .05s; }
    .cart-items-list > .cart-item:nth-child(2) { animation-delay: .10s; }
    .cart-items-list > .cart-item:nth-child(3) { animation-delay: .15s; }
    .cart-items-list > .cart-item:nth-child(4) { animation-delay: .20s; }
    .cart-items-list > .cart-item:nth-child(5) { animation-delay: .25s; }

    @media (min-width: 992px) {
        .summary-sticky {
            position: sticky;
            top: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .cart-item { padding: .85rem; }
        .cart-item .thumb { font-size: 1.6rem; }
        .cart-item .item-name { font-size: .95rem; }
        .qty-control { max-width: 100%; }
        .summary-total .value { font-size: 1.3rem; }
    }
</style>

<main class="container py-4 py-md-5">

    <div class="cart-head mb-4">
        <p class="eyebrow mb-1">ALMOST YOURS</p>
        <h1 class="section-title mb-2">Your little basket</h1>
        <p class="text-muted mb-0">
            A few lovely things are waiting for you.
        </p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success cart-alert mb-3">
            <span>✨</span>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger cart-alert mb-3">
            <span>⚠️</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>

        <div class="empty-cart">
            <span class="emoji">🛍️</span>
            <h3 class="mb-2">Your basket is empty!</h3>
            <p class="text-muted">
                Looks like you haven't added anything yet.
            </p>
            <a href="shop.php" class="btn btn-poppy mt-2">
                Explore the Shop
            </a>
        </div>

    <?php else: ?>

        <div class="row g-4">

            <div class="col-lg-8">

                <div class="cart-items-list">

                    <?php foreach ($cartItems as $item): ?>

                        <div class="cart-item">
                            <div class="row align-items-center g-3">

                                <div class="col-3 col-md-2">
                                    <div class="thumb">
                                        <?php if (!empty($item['image_path'])): ?>
                                            <img 
                                                src="<?= htmlspecialchars($item['image_path']) ?>" 
                                                alt="<?= htmlspecialchars($item['product_name']) ?>"
                                            >
                                        <?php else: ?>
                                            🎀
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-9 col-md-4">
                                    <h5 class="item-name">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </h5>

                                    <p class="item-price mb-1">
                                        ₱<?= number_format((float) $item['price'], 2) ?>
                                    </p>

                                    <small class="item-stock">
                                        <?= $item['stock_quantity'] ?> in stock
                                    </small>
                                </div>

                                <div class="col-12 col-md-3">
                                    <form method="POST" action="actions/update-cart.php">
                                        <div class="qty-control">
                                            <input
                                                type="number"
                                                name="quantity"
                                                value="<?= $item['quantity'] ?>"
                                                min="1"
                                                max="<?= $item['stock_quantity'] ?>"
                                                required
                                                aria-label="Quantity"
                                            >

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= $item['product_id'] ?>"
                                            >
                                            
                                            <input type="hidden" name="action" value="update">

                                            <button
                                                type="submit"
                                                class="btn-update"
                                            >
                                                Update
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <div class="col-12 col-md-3 text-md-end d-flex d-md-block justify-content-between align-items-center">
                                    <div class="item-subtotal">
                                        ₱<?= number_format($item['subtotal'], 2) ?>
                                    </div>

                                    <form method="POST" action="actions/update-cart.php" class="mt-0 mt-md-2 ms-3 ms-md-0">
                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= $item['product_id'] ?>"
                                        >
                                        
                                        <input type="hidden" name="action" value="remove">

                                        <button
                                            type="submit"
                                            class="btn-remove"
                                        >
                                            Remove
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>

                <a href="shop.php" class="btn btn-continue mt-2">
                    ← Continue Shopping
                </a>

            </div>

            <div class="col-lg-4">
                <div class="summary-sticky">
                    <div class="summary-card">

                        <div class="summary-head">
                            Order Summary
                        </div>

                        <div class="summary-body">

                            <div class="summary-row">
                                <span>Subtotal</span>
                                <strong>
                                    ₱<?= number_format($subtotal, 2) ?>
                                </strong>
                            </div>

                            <div class="summary-total">
                                <span class="label">Total</span>
                                <span class="value">
                                    ₱<?= number_format($subtotal, 2) ?>
                                </span>
                            </div>

                            <a href="checkout.php" class="btn btn-checkout">
                                Proceed to Checkout →
                            </a>

                        </div>

                    </div>
                </div>
            </div>

        </div>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
