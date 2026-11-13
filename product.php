
<?php
session_start();
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Product details | Poppy & Paper';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    header('Location: shop.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        p.product_id,
        p.product_name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image_path,
        p.category_id,
        c.category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.category_id
    WHERE p.product_id = :id
      AND p.status = 'active'
    LIMIT 1
");

$stmt->execute([':id' => $productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: shop.php');
    exit;
}

$pageTitle = $product['product_name'] . ' | Poppy & Paper';

$relatedStmt = $pdo->prepare("
    SELECT product_id, product_name, price, image_path, stock_quantity
    FROM products
    WHERE category_id = :category_id
      AND product_id != :id
      AND status = 'active'
    ORDER BY product_id DESC
    LIMIT 3
");
$relatedStmt->execute([
    ':category_id' => (int) $product['category_id'],
    ':id' => (int) $product['product_id'],
]);
$relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .product-page {
        animation: fadeUp .6s ease both;
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .cart-error-alert {
        background: #FBE4E9;
        color: #9A2D4B;
        border: 1px solid #F6CBD5;
        border-radius: 1rem;
        padding: .9rem 1.15rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: 1.5rem;
        animation: fadeUp .5s ease both;
    }

    .product-crumb {
        font-size: .85rem;
        color: var(--muted);
        font-weight: 600;
    }
    .product-crumb a {
        color: var(--muted);
        text-decoration: none;
        transition: color .2s ease;
    }
    .product-crumb a:hover {
        color: var(--poppy);
    }
    .product-crumb .current {
        color: var(--ink);
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .product-media {
        position: relative;
        border-radius: 1.5rem;
        overflow: hidden;
        background: linear-gradient(135deg, #FCE8EF 0%, #FFF0D9 60%, #EFE4FA 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 420px;
        box-shadow: 0 10px 30px rgba(117, 76, 97, .1);
        isolation: isolate;
    }
    .product-media::before {
        content: "";
        position: absolute;
        inset: -40px;
        background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.55), transparent 60%);
        z-index: -1;
    }
    .product-media img {
        width: 100%;
        height: 100%;
        max-height: 480px;
        object-fit: contain;
        padding: 2rem;
        transition: transform .55s ease;
    }
    .product-media:hover img {
        transform: scale(1.05) rotate(-1deg);
    }
    .product-media .placeholder {
        font-size: 6rem;
        animation: sway 4s ease-in-out infinite;
    }
    @keyframes sway {
        0%, 100% { transform: rotate(-4deg) translateY(0); }
        50%      { transform: rotate(4deg) translateY(-6px); }
    }

    .product-info {
        animation: fadeUp .6s ease .1s both;
    }
    .product-info .eyebrow {
        letter-spacing: .16em;
    }
    .product-info .section-title {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #85566D;
        letter-spacing: -.015em;
        line-height: 1.15;
    }

    .product-price {
        font-family: 'Quicksand', sans-serif;
        font-weight: 700;
        font-size: 2rem;
        color: #C86691;
        letter-spacing: -.01em;
    }

    .product-desc {
        color: #6F6068;
        line-height: 1.7;
        font-size: 1rem;
    }

    .soft-divider {
        border: 0;
        border-top: 1px dashed #F2DCE5;
        margin: 1.5rem 0;
    }

    .stock-pill {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .95rem;
        border-radius: 999px;
        font-weight: 800;
        font-size: .82rem;
    }
    .stock-pill.in {
        background: #E7F4EC;
        color: #2E6B45;
    }
    .stock-pill.out {
        background: #F2ECEE;
        color: #81747A;
    }
    .stock-pill .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
    }
    .stock-pill.in .dot {
        animation: pulse 1.8s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 3px rgba(76, 175, 125, .25); }
        50%      { box-shadow: 0 0 0 6px rgba(76, 175, 125, .1); }
    }
    .stock-available {
        font-size: .82rem;
        color: var(--muted);
        font-weight: 600;
        margin-left: .35rem;
    }

    .qty-label {
        font-weight: 800;
        font-size: .82rem;
        color: #85566D;
        letter-spacing: .03em;
        margin-bottom: .5rem;
        display: block;
    }
    .qty-stepper {
        display: inline-flex;
        align-items: stretch;
        background: var(--cream);
        border: 1.5px solid #F2DCE5;
        border-radius: 999px;
        overflow: hidden;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .qty-stepper:focus-within {
        border-color: var(--poppy);
        box-shadow: 0 0 0 .22rem rgba(216, 121, 159, .18);
    }
    .qty-stepper button {
        border: 0;
        background: transparent;
        color: #85566D;
        font-weight: 800;
        font-size: 1.15rem;
        width: 42px;
        cursor: pointer;
        transition: background .2s ease, color .2s ease;
    }
    .qty-stepper button:hover {
        background: #FCE8EF;
        color: var(--poppy);
    }
    .qty-stepper button:disabled {
        opacity: .4;
        cursor: not-allowed;
    }
    .qty-stepper input {
        border: 0;
        background: transparent;
        width: 58px;
        text-align: center;
        font-weight: 800;
        font-size: 1rem;
        color: var(--ink);
        -moz-appearance: textfield;
    }
    .qty-stepper input::-webkit-outer-spin-button,
    .qty-stepper input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .qty-stepper input:focus { outline: none; }

    .btn-add-cart {
        background: var(--poppy);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: .85rem 1.5rem;
        font-weight: 800;
        font-size: 1rem;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .btn-add-cart:hover {
        background: #C86691;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(216, 121, 159, .35);
    }
    .btn-add-cart:disabled {
        background: #E9DEE3;
        color: #A998A0;
        box-shadow: none;
        cursor: not-allowed;
    }
    .btn-out-of-stock {
        background: #F2ECEE;
        color: #81747A;
        border: 0;
        border-radius: 999px;
        padding: .85rem 1.5rem;
        font-weight: 800;
        width: 100%;
    }
    .btn-back-shop {
        border: 1.5px solid #F2DCE5;
        background: #fff;
        color: var(--ink);
        border-radius: 999px;
        padding: .7rem 1.25rem;
        font-weight: 700;
        transition: border-color .2s ease, color .2s ease, transform .2s ease;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .btn-back-shop:hover {
        border-color: var(--poppy);
        color: var(--poppy);
        transform: translateX(-3px);
    }

    .toast-hint {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .82rem;
        color: var(--muted);
        margin-top: .75rem;
    }

    @media (min-width: 992px) {
        .product-info-sticky {
            position: sticky;
            top: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .product-media { min-height: 320px; }
        .product-media .placeholder { font-size: 4.5rem; }
        .product-price { font-size: 1.65rem; }
    }
    .related-card {
        display: flex;
        flex-direction: column;
        gap: .35rem;
        height: 100%;
        padding: .85rem;
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1rem;
        color: inherit;
        text-decoration: none;
    }
    .related-thumb {
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--cream);
        border-radius: .75rem;
        overflow: hidden;
        font-size: 2rem;
    }
    .related-thumb img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: .5rem;
    }
    .related-card strong {
        color: #85566D;
    }
    .related-card span {
        font-weight: 800;
    }

    @media (max-width: 576px) {
        .product-media { min-height: 260px; border-radius: 1.15rem; }
        .product-media img { padding: 1rem; }
        .product-info .section-title { font-size: 1.6rem; }
        .qty-stepper button { width: 38px; }
        .qty-stepper input { width: 50px; }
    }
</style>

<main class="container py-4 py-md-5 product-page">

    <?php if (isset($_SESSION['cart_error'])): ?>
        <div class="cart-error-alert">
            <span>⚠️</span>
            <span><?= htmlspecialchars($_SESSION['cart_error']) ?></span>
        </div>
        <?php unset($_SESSION['cart_error']); ?>
    <?php endif; ?>

    <nav class="product-crumb mb-4" aria-label="Breadcrumb">
        <a href="index.php">Home</a>
        <span class="mx-1">/</span>
        <a href="shop.php">Shop</a>
        <span class="mx-1">/</span>
        <span class="current">
            <?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>
        </span>
    </nav>

    <div class="row g-4 g-lg-5 align-items-start">

        <div class="col-12 col-lg-6">
            <div class="product-media">

                <?php if (!empty($product['image_path'])): ?>

                    <img
                        src="<?= htmlspecialchars($product['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>"
                    >

                <?php else: ?>

                    <span class="placeholder">🎀</span>

                <?php endif; ?>

            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="product-info product-info-sticky">

                <p class="eyebrow mb-2">
                    <?= htmlspecialchars($product['category_name'] ?? 'Stationery', ENT_QUOTES, 'UTF-8') ?>
                </p>

                <h1 class="section-title mb-3">
                    <?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <div class="product-price mb-3">
                    ₱<?= number_format((float) $product['price'], 2) ?>
                </div>

                <p class="product-desc mb-4">
                    <?= htmlspecialchars(
                        $product['description'] ?: 'A lovely little addition to your everyday stationery collection.',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <hr class="soft-divider">

                <div class="mb-4 d-flex align-items-center flex-wrap">

                    <?php if ((int) $product['stock_quantity'] > 0): ?>

                        <span class="stock-pill in">
                            <span class="dot"></span> In Stock
                        </span>

                        <span class="stock-available">
                            <?= (int) $product['stock_quantity'] ?> available
                        </span>

                    <?php else: ?>

                        <span class="stock-pill out">
                            <span class="dot"></span> Out of Stock
                        </span>

                    <?php endif; ?>

                </div>

                <?php if ((int) $product['stock_quantity'] > 0): ?>

                    <form action="actions/cart_add.php" method="POST" id="addForm">

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int) $product['product_id'] ?>"
                        >

                        <span class="qty-label">Quantity</span>

                        <div class="d-flex gap-3 mb-3 flex-wrap align-items-center">

                            <div class="qty-stepper">
                                <button type="button" id="qtyMinus" aria-label="Decrease quantity">−</button>
                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    max="<?= (int) $product['stock_quantity'] ?>"
                                    required
                                    aria-label="Quantity"
                                >
                                <button type="button" id="qtyPlus" aria-label="Increase quantity">+</button>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-add-cart flex-grow-1"
                            >
                                Add to Cart 🛍️
                            </button>

                        </div>

                        <div class="toast-hint">
                            <span>✨</span>
                            <span>Wrapped with care and ready to make your day a little brighter.</span>
                        </div>

                    </form>

                <?php else: ?>

                    <button class="btn btn-out-of-stock" disabled>
                        Currently Unavailable
                    </button>

                <?php endif; ?>

                <a href="shop.php" class="btn btn-back-shop mt-3">
                    ← Back to Shop
                </a>

            </div>
        </div>

    </div>

    <?php if (!empty($relatedProducts)): ?>
        <section class="mt-5">
            <p class="eyebrow mb-2">YOU MAY ALSO LIKE</p>
            <h2 class="section-title mb-4">Related finds</h2>
            <div class="row g-3">
                <?php foreach ($relatedProducts as $related): ?>
                    <div class="col-6 col-md-4">
                        <a class="related-card" href="product.php?id=<?= (int) $related['product_id'] ?>">
                            <div class="related-thumb">
                                <?php if (!empty($related['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($related['image_path']) ?>" alt="<?= htmlspecialchars($related['product_name']) ?>">
                                <?php else: ?>
                                    <span>🎀</span>
                                <?php endif; ?>
                            </div>
                            <strong><?= htmlspecialchars($related['product_name']) ?></strong>
                            <span>₱<?= number_format((float) $related['price'], 2) ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</main>

<script>
    (function () {
        const input  = document.getElementById('quantity');
        const minus  = document.getElementById('qtyMinus');
        const plus   = document.getElementById('qtyPlus');

        if (!input || !minus || !plus) return;

        const min = parseInt(input.min, 10) || 1;
        const max = parseInt(input.max, 10) || 999;

        function clamp(v) {
            v = parseInt(v, 10) || min;
            if (v < min) v = min;
            if (v > max) v = max;
            return v;
        }

        minus.addEventListener('click', () => {
            input.value = clamp(parseInt(input.value, 10) - 1);
            toggleButtons();
        });

        plus.addEventListener('click', () => {
            input.value = clamp(parseInt(input.value, 10) + 1);
            toggleButtons();
        });

        input.addEventListener('input', () => {
            input.value = clamp(input.value);
            toggleButtons();
        });

        function toggleButtons() {
            const v = clamp(input.value);
            minus.disabled = v <= min;
            plus.disabled  = v >= max;
        }

        toggleButtons();
    })();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
