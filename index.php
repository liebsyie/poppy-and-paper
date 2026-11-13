
<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Poppy & Paper | Little things, lovely days.';

$stmt = $pdo->prepare("
    SELECT 
        p.product_id,
        p.product_name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image_path,
        c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.status = 'active'
    ORDER BY p.product_id DESC
    LIMIT 8
");

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("
    SELECT category_id, category_name
    FROM categories
    ORDER BY category_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .home-hero {
        position: relative;
        overflow: hidden;
        background: linear-gradient(125deg, #FCE0E9 0%, #FFF0C9 55%, #E8DDF8 100%);
        border-radius: 2rem;
        padding: 5rem 1.5rem;
        isolation: isolate;
    }

    .home-hero::before,
    .home-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        opacity: .55;
        z-index: -1;
        animation: floaty 9s ease-in-out infinite;
    }
    .home-hero::before {
        width: 260px; height: 260px;
        background: #F7C9D9;
        top: -80px; left: -60px;
    }
    .home-hero::after {
        width: 300px; height: 300px;
        background: #D9C9F3;
        bottom: -120px; right: -80px;
        animation-delay: -4.5s;
    }

    @keyframes floaty {
        0%, 100% { transform: translateY(0) scale(1); }
        50%      { transform: translateY(-22px) scale(1.06); }
    }

    .home-hero .eyebrow {
        letter-spacing: .18em;
        animation: fadeUp .7s ease both;
    }
    .home-hero h1 {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #754C61;
        animation: fadeUp .7s ease .1s both;
    }
    .home-hero .lead {
        color: var(--muted);
        animation: fadeUp .7s ease .2s both;
    }
    .home-hero .btn-poppy {
        animation: fadeUp .7s ease .3s both;
        transition: transform .25s ease, box-shadow .25s ease, background .25s ease;
    }
    .home-hero .btn-poppy:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(216, 121, 159, .35);
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .section-head .section-title {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        letter-spacing: -.01em;
    }
    .view-all {
        color: var(--poppy);
        font-weight: 800;
        transition: gap .25s ease, color .25s ease;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
    }
    .view-all:hover {
        color: #C86691;
        gap: .6rem;
    }

    .category-link {
        display: inline-flex;
        align-items: center;
        padding: .55rem 1rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid #F2DCE5;
        color: #85566D;
        font-weight: 800;
        text-decoration: none;
    }
    .category-link:hover {
        background: #FFF0F6;
        color: var(--poppy);
        border-color: #E9B6C9;
    }

    .product-card {
        border: 0;
        border-radius: 1.25rem;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 4px 14px rgba(117, 76, 97, .06);
        transition: transform .3s ease, box-shadow .3s ease;
        height: 100%;
        animation: cardIn .6s ease both;
    }
    .product-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 32px rgba(117, 76, 97, .16);
    }

    @keyframes cardIn {
        from { opacity: 0; transform: translateY(24px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .row > [class*="col-"]:nth-child(1) .product-card { animation-delay: .05s; }
    .row > [class*="col-"]:nth-child(2) .product-card { animation-delay: .10s; }
    .row > [class*="col-"]:nth-child(3) .product-card { animation-delay: .15s; }
    .row > [class*="col-"]:nth-child(4) .product-card { animation-delay: .20s; }
    .row > [class*="col-"]:nth-child(5) .product-card { animation-delay: .25s; }
    .row > [class*="col-"]:nth-child(6) .product-card { animation-delay: .30s; }
    .row > [class*="col-"]:nth-child(7) .product-card { animation-delay: .35s; }
    .row > [class*="col-"]:nth-child(8) .product-card { animation-delay: .40s; }

    .product-card .product-image-wrapper {
        position: relative;
        height: 200px;
        background: var(--cream);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .product-card .product-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 14px;
        transition: transform .45s ease;
    }
    .product-card:hover .product-image {
        transform: scale(1.08) rotate(-1deg);
    }
    .product-card .placeholder-emoji {
        font-size: 2.75rem;
        transition: transform .35s ease;
    }
    .product-card:hover .placeholder-emoji {
        transform: scale(1.15) rotate(6deg);
    }

    .product-card .card-body {
        padding: 1.1rem 1.15rem 1.25rem;
    }
    .product-card .category-tag {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
    }
    .product-card .card-title {
        font-family: 'Nunito', sans-serif;
        font-weight: 800;
        color: #85566D;
        font-size: 1.02rem;
        line-height: 1.3;
        margin: .35rem 0 .5rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.6em;
    }
    .product-card .price {
        color: var(--ink);
        font-weight: 800;
        font-size: 1.05rem;
    }
    .product-card .badge {
        border-radius: 999px;
        font-weight: 700;
        padding: .35rem .7rem;
    }
    .product-card .view-hint {
        display: inline-block;
        margin-top: .7rem;
        color: var(--poppy);
        font-weight: 800;
        font-size: .82rem;
    }

    .empty-state {
        background: #fff;
        border: 1px dashed #F2DCE5;
        border-radius: 1.5rem;
        padding: 3rem 1.5rem;
    }

    @media (max-width: 768px) {
        .home-hero {
            padding: 3.25rem 1.25rem;
            border-radius: 1.5rem;
        }
        .home-hero h1 { font-size: 2rem; }
        .product-card .product-image-wrapper { height: 160px; }
    }
    @media (max-width: 576px) {
        .home-hero { padding: 2.75rem 1rem; }
        .home-hero h1 { font-size: 1.7rem; }
        .product-card .product-image-wrapper { height: 135px; }
        .product-card .card-title { font-size: .9rem; min-height: 2.4em; }
        .product-card .price { font-size: .95rem; }
        .product-card .card-body { padding: .85rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        * { animation: none !important; transition: none !important; }
    }
</style>

<main class="container py-5">

    <section class="home-hero text-center">
        <p class="eyebrow mb-2">A LITTLE HAPPINESS IN EVERY PACKAGE</p>

        <h1 class="display-4 fw-bold mb-3">
            Make every page<br> a little prettier.
        </h1>

        <p class="lead mt-3 mb-0">
            Lovely stationery for your everyday little moments.
        </p>

        <a class="btn btn-poppy mt-4" href="shop.php">
            Explore the shop
        </a>
    </section>

    <?php if (!empty($categories)): ?>
        <section class="pt-5 pb-2">
            <p class="eyebrow mb-3">SHOP BY CATEGORY</p>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($categories as $category): ?>
                    <a class="category-link" href="shop.php?category=<?= (int) $category['category_id'] ?>">
                        <?= htmlspecialchars($category['category_name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="py-5">

        <div class="section-head d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
            <div>
                <p class="eyebrow mb-1">MADE FOR YOUR LITTLE MOMENTS</p>
                <h2 class="section-title mb-0">Our lovely finds</h2>
            </div>

            <a href="shop.php" class="view-all text-decoration-none">
                View all <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="row g-4">

            <?php if (count($products) > 0): ?>

                <?php foreach ($products as $product): ?>

                    <div class="col-6 col-md-4 col-lg-3">

                        <a class="stretched-card" href="product.php?id=<?= (int) $product['product_id'] ?>">
                        <div class="product-card">

                            <?php if (!empty($product['image_path'])): ?>

                                <div class="product-image-wrapper">
                                    <img
                                        src="<?= htmlspecialchars($product['image_path']) ?>"
                                        class="product-image"
                                        alt="<?= htmlspecialchars($product['product_name']) ?>"
                                        loading="lazy"
                                    >
                                </div>

                            <?php else: ?>

                                <div class="product-image-wrapper">
                                    <span class="placeholder-emoji">🎀</span>
                                </div>

                            <?php endif; ?>

                            <div class="card-body">

                                <small class="category-tag">
                                    <?= htmlspecialchars($product['category_name'] ?? 'Stationery') ?>
                                </small>

                                <h5 class="card-title">
                                    <?= htmlspecialchars($product['product_name']) ?>
                                </h5>

                                <p class="price mb-2">
                                    ₱<?= number_format((float) $product['price'], 2) ?>
                                </p>

                                <?php if ((int) $product['stock_quantity'] > 0): ?>

                                    <span class="badge bg-success-subtle text-success">
                                        In Stock
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary-subtle text-secondary">
                                        Out of Stock
                                    </span>

                                <?php endif; ?>

                                <span class="view-hint">See details →</span>

                            </div>
                        </div>
                        </a>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12">
                    <div class="empty-state text-center">
                        <p class="text-muted mb-0">
                            No products available at the moment.
                        </p>
                    </div>
                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
