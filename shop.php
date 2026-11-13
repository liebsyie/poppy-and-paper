
<?php
session_start();
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Shop | Poppy & Paper';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'default';

$allowedSorts = ['default', 'price_low', 'price_high', 'name'];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'default';
}

$categoryStmt = $pdo->query("
    SELECT category_id, category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image_path,
        c.category_name,
        c.category_id
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.category_id
    WHERE p.status = 'active'
";

$params = [];

if ($search !== '') {
    $sql .= " AND p.product_name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

if ($category !== '' && ctype_digit($category)) {
    $sql .= " AND p.category_id = :category";
    $params[':category'] = (int) $category;
}

switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC, p.product_name ASC";
        break;

    case 'price_high':
        $sql .= " ORDER BY p.price DESC, p.product_name ASC";
        break;

    case 'name':
        $sql .= " ORDER BY p.product_name ASC";
        break;

    default:
        $sql .= " ORDER BY p.product_id DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    .shop-head {
        position: relative;
        padding: 2rem 0 1.5rem;
        animation: fadeUp .6s ease both;
    }
    .shop-head .section-title {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #85566D;
        letter-spacing: -.015em;
    }
    .shop-head .eyebrow {
        letter-spacing: .16em;
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

    .filter-bar {
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.25rem;
        padding: 1rem;
        box-shadow: 0 4px 14px rgba(117, 76, 97, .05);
        animation: fadeUp .6s ease .1s both;
        transition: box-shadow .3s ease;
    }
    .filter-bar:focus-within {
        box-shadow: 0 8px 22px rgba(216, 121, 159, .15);
    }

    .filter-bar .form-control,
    .filter-bar .form-select {
        border-radius: 999px;
        border: 1px solid #F2DCE5;
        background-color: var(--cream);
        padding: .65rem 1rem;
        font-weight: 600;
        color: var(--ink);
        transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
    }
    .filter-bar .form-control::placeholder {
        color: #B3A6AC;
        font-weight: 500;
    }
    .filter-bar .form-control:focus,
    .filter-bar .form-select:focus {
        border-color: var(--poppy);
        background-color: #fff;
        box-shadow: 0 0 0 .2rem rgba(216, 121, 159, .18);
    }

    .filter-bar .btn-poppy {
        border-radius: 999px;
        padding: .65rem 1rem;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .filter-bar .btn-poppy:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(216, 121, 159, .3);
    }
    .btn-outline-poppy {
        border: 1.5px solid #F2DCE5;
        background: #fff;
        color: var(--ink);
        border-radius: 999px;
        font-weight: 800;
    }
    .btn-outline-poppy:hover {
        border-color: var(--poppy);
        color: var(--poppy);
        background: #fff;
    }
    .product-card .btn:disabled {
        background: #F2ECEE;
        color: #A998A0;
    }

    .results-meta {
        animation: fadeUp .6s ease .2s both;
    }
    .clear-filters {
        color: var(--poppy);
        font-weight: 800;
        transition: color .2s ease;
    }
    .clear-filters:hover {
        color: #C86691;
    }

    .product-card {
        border: 0;
        border-radius: 1.25rem;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 4px 14px rgba(117, 76, 97, .06);
        transition: transform .3s ease, box-shadow .3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        animation: cardIn .55s ease both;
    }
    .product-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 32px rgba(117, 76, 97, .16);
    }

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
    .product-card .product-image-placeholder {
        font-size: 2.75rem;
        transition: transform .35s ease;
    }
    .product-card:hover .product-image-placeholder {
        transform: scale(1.15) rotate(6deg);
    }

    .product-card .card-body {
        padding: 1.1rem 1.15rem 1.25rem;
        display: flex;
        flex-direction: column;
        flex: 1;
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
        align-self: flex-start;
    }
    .product-card .btn-poppy {
        border-radius: 999px;
        font-weight: 800;
        padding: .55rem 1rem;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        margin-top: auto;
    }
    .product-card .btn-poppy:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(216, 121, 159, .3);
    }

    .row > [class*="col-"]:nth-child(1) .product-card { animation-delay: .05s; }
    .row > [class*="col-"]:nth-child(2) .product-card { animation-delay: .10s; }
    .row > [class*="col-"]:nth-child(3) .product-card { animation-delay: .15s; }
    .row > [class*="col-"]:nth-child(4) .product-card { animation-delay: .20s; }
    .row > [class*="col-"]:nth-child(5) .product-card { animation-delay: .25s; }
    .row > [class*="col-"]:nth-child(6) .product-card { animation-delay: .30s; }
    .row > [class*="col-"]:nth-child(7) .product-card { animation-delay: .35s; }
    .row > [class*="col-"]:nth-child(8) .product-card { animation-delay: .40s; }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes cardIn {
        from { opacity: 0; transform: translateY(24px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .empty-state {
        background: #fff;
        border: 1px dashed #F2DCE5;
        border-radius: 1.5rem;
        padding: 3rem 1.5rem;
        animation: fadeUp .6s ease both;
    }
    .empty-state .emoji {
        font-size: 3rem;
        display: block;
        margin-bottom: .75rem;
        animation: bounce 2.4s ease-in-out infinite;
    }
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50%      { transform: translateY(-8px); }
    }

    @media (max-width: 768px) {
        .product-card .product-image-wrapper { height: 160px; }
        .shop-head { padding: 1.25rem 0 .75rem; }
        .filter-bar { padding: .85rem; border-radius: 1rem; }
    }
    @media (max-width: 576px) {
        .product-card .product-image-wrapper { height: 135px; }
        .product-card .card-title { font-size: .9rem; min-height: 2.4em; }
        .product-card .price { font-size: .95rem; }
        .product-card .card-body { padding: .85rem; }
        .product-card .btn-poppy { font-size: .85rem; padding: .5rem .75rem; }
    }
</style>

<main class="container py-4 py-md-5">

    <?php if (isset($_SESSION['cart_error'])): ?>
        <div class="cart-error-alert">
            <span>⚠️</span>
            <span><?= htmlspecialchars($_SESSION['cart_error']) ?></span>
        </div>
        <?php unset($_SESSION['cart_error']); ?>
    <?php endif; ?>

    <div class="shop-head mb-4">
        <p class="eyebrow mb-1">A LITTLE SOMETHING FOR YOU</p>

        <h1 class="section-title mb-2">The stationery shop</h1>

        <p class="text-muted mb-0">
            Find your next favorite little thing.
        </p>
    </div>

    <form method="GET" action="shop.php" id="filterForm"
          class="filter-bar row g-3 mb-4">

        <div class="col-12 col-md-5">
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Search stationery..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="col-12 col-md-3">
            <select name="category" id="categoryFilter"
                    class="form-select">

                <option value="">All Categories</option>

                <?php foreach ($categories as $cat): ?>
                    <option
                        value="<?= (int) $cat['category_id'] ?>"
                        <?= $category === (string) $cat['category_id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </div>

        <div class="col-12 col-md-2">
            <select name="sort" id="sortFilter" class="form-select">

                <option value="default"
                    <?= $sort === 'default' ? 'selected' : '' ?>>
                    Latest
                </option>

                <option value="price_low"
                    <?= $sort === 'price_low' ? 'selected' : '' ?>>
                    Price: Low to High
                </option>

                <option value="price_high"
                    <?= $sort === 'price_high' ? 'selected' : '' ?>>
                    Price: High to Low
                </option>

                <option value="name"
                    <?= $sort === 'name' ? 'selected' : '' ?>>
                    Name: A-Z
                </option>

            </select>
        </div>

        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-poppy w-100">
                Search
            </button>
        </div>

    </form>

    <div class="results-meta d-flex justify-content-between align-items-center mb-3">

        <p class="text-muted mb-0">
            <strong><?= count($products) ?></strong> product(s) found
        </p>

        <a href="shop.php" class="clear-filters small text-decoration-none">
            Clear filters
        </a>

    </div>

    <div class="row g-4">

        <?php if (!empty($products)): ?>

            <?php foreach ($products as $product): ?>

                <div class="col-6 col-md-4 col-lg-3">

                    <div class="product-card">

                        <div class="product-image-wrapper">

                            <?php if (!empty($product['image_path'])): ?>

                                <img
                                    src="<?= htmlspecialchars($product['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    class="product-image"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="product-image-placeholder">
                                    🎀
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="card-body">

                            <small class="category-tag">
                                <?= htmlspecialchars(
                                    $product['category_name'] ?? 'Stationery',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </small>

                            <h5 class="card-title">
                                <?= htmlspecialchars(
                                    $product['product_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
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

                            <div class="d-grid gap-2 mt-3">
                                <a
                                    href="product.php?id=<?= (int) $product['product_id'] ?>"
                                    class="btn btn-outline-poppy"
                                >
                                    View Details
                                </a>

                                <?php if ((int) $product['stock_quantity'] > 0): ?>
                                    <form method="POST" action="actions/cart_add.php">
                                        <input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-poppy w-100">
                                            Add to Cart
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-poppy w-100" disabled>
                                        Add to Cart
                                    </button>
                                <?php endif; ?>
                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="col-12">
                <div class="empty-state text-center">
                    <span class="emoji">🌷</span>
                    <h4 class="mb-2">No products found</h4>
                    <p class="text-muted">
                        Try another search or category.
                    </p>
                    <a href="shop.php" class="btn btn-poppy mt-2">
                        View All Products
                    </a>
                </div>
            </div>

        <?php endif; ?>

    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const filterForm = document.getElementById('filterForm');
    const categoryFilter = document.getElementById('categoryFilter');
    const sortFilter = document.getElementById('sortFilter');
    const searchInput = filterForm.querySelector('input[name="search"]');

    categoryFilter.addEventListener('change', function () {
        filterForm.submit();
    });

    sortFilter.addEventListener('change', function () {
        filterForm.submit();
    });

    let searchTimer;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            filterForm.submit();
        }, 600);
    });

});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
