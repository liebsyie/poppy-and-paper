
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Products | Poppy & Paper';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$message = $_SESSION['product_success'] ?? '';
$error = $_SESSION['product_error'] ?? '';
$oldInput = $_SESSION['product_old_input'] ?? [];

unset($_SESSION['product_success'], $_SESSION['product_error'], $_SESSION['product_old_input']);

$categories = $pdo->query("
    SELECT category_id, category_name
    FROM categories
    ORDER BY category_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$form = [
    'product_id' => $oldInput['product_id'] ?? '',
    'category_id' => $oldInput['category_id'] ?? '',
    'product_name' => $oldInput['product_name'] ?? '',
    'description' => $oldInput['description'] ?? '',
    'price' => $oldInput['price'] ?? '',
    'stock_quantity' => $oldInput['stock_quantity'] ?? 0,
    'image_path' => $oldInput['image_path'] ?? '',
    'status' => $oldInput['status'] ?? 'active'
];

if (isset($_GET['edit']) && empty($oldInput)) {

    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($editId) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM products
            WHERE product_id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $editId]);
        $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingProduct) {
            $form = $existingProduct;
        } else {
            $error = 'Product not found.';
        }
    }
}

if (isset($_GET['added'])) {
    $message = 'Product successfully added!';
} elseif (isset($_GET['updated'])) {
    $message = 'Product successfully updated!';
} elseif (isset($_GET['deleted'])) {
    $message = 'Product successfully deleted!';
} elseif (isset($_GET['status_changed'])) {
    $message = 'Product status successfully changed!';
}

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        p.*,
        c.category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.category_id
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE p.product_name LIKE :search
           OR c.category_name LIKE :search
    ";

    $params['search'] = '%' . $search . '%';
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .prod-page {
        animation: fadeUp .6s ease both;
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .prod-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.5rem;
        padding: 1.75rem;
        background: linear-gradient(125deg, #FCE0E9 0%, #FFF0C9 60%, #E8DDF8 100%);
        margin-bottom: 1.5rem;
        isolation: isolate;
    }
    .prod-hero::before,
    .prod-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        opacity: .5;
        z-index: -1;
        animation: floaty 9s ease-in-out infinite;
    }
    .prod-hero::before {
        width: 200px; height: 200px;
        background: #F7C9D9;
        top: -70px; left: -60px;
    }
    .prod-hero::after {
        width: 220px; height: 220px;
        background: #D9C9F3;
        bottom: -90px; right: -60px;
        animation-delay: -4.5s;
    }
    @keyframes floaty {
        0%, 100% { transform: translateY(0) scale(1); }
        50%      { transform: translateY(-18px) scale(1.05); }
    }

    .prod-hero .eyebrow {
        letter-spacing: .16em;
        color: #A65E7D;
    }
    .prod-hero h1 {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #754C61;
        font-weight: 700;
        letter-spacing: -.015em;
        font-size: 1.65rem;
        margin-bottom: .2rem;
    }
    .prod-hero p {
        color: #8C7B84;
        font-weight: 600;
        font-size: .9rem;
        margin: 0;
    }

    .btn-back-dash {
        border: 1.5px solid rgba(255, 255, 255, .7);
        background: rgba(255, 255, 255, .65);
        color: #754C61;
        border-radius: 999px;
        padding: .55rem 1.15rem;
        font-weight: 800;
        font-size: .85rem;
        backdrop-filter: blur(6px);
        transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
        text-decoration: none;
        white-space: nowrap;
    }
    .btn-back-dash:hover {
        background: #fff;
        color: #C86691;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(117, 76, 97, .15);
    }

    .admin-alert {
        border: 0;
        border-radius: 1rem;
        padding: .85rem 1.15rem;
        font-weight: 700;
        animation: fadeUp .5s ease both;
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: 1rem;
    }
    .admin-alert.success { background: #E7F4EC; color: #2E6B45; }
    .admin-alert.danger  { background: #FBE4E9; color: #9A2D4B; }

    .admin-card {
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.25rem;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(117, 76, 97, .05);
        margin-bottom: 1.25rem;
        animation: fadeUp .6s ease .05s both;
    }
    .admin-card .card-head {
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
    .admin-card .card-head .icon { font-size: 1.15rem; }
    .admin-card .card-body { padding: 1.4rem; }

    .admin-card .form-label {
        font-weight: 800;
        font-size: .78rem;
        color: #85566D;
        letter-spacing: .05em;
        text-transform: uppercase;
        margin-bottom: .35rem;
    }
    .admin-card .form-label .optional {
        font-weight: 600;
        color: var(--muted);
        text-transform: none;
        letter-spacing: 0;
        font-size: .78rem;
    }
    .admin-card .form-control,
    .admin-card .form-select {
        border-radius: .85rem;
        border: 1.5px solid #F2DCE5;
        background: var(--cream);
        padding: .7rem 1rem;
        font-weight: 600;
        color: var(--ink);
        transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
    }
    .admin-card .form-control::placeholder {
        color: #B3A6AC;
        font-weight: 500;
    }
    .admin-card .form-control:focus,
    .admin-card .form-select:focus {
        border-color: var(--poppy);
        background: #fff;
        box-shadow: 0 0 0 .22rem rgba(216, 121, 159, .18);
    }
    .admin-card textarea.form-control {
        min-height: 85px;
        resize: vertical;
    }
    .admin-card small.text-muted {
        display: block;
        margin-top: .35rem;
        font-weight: 600;
        color: var(--muted);
    }

    .btn-save {
        background: var(--poppy);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: .75rem 1.5rem;
        font-weight: 800;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .btn-save:hover {
        background: #C86691;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(216, 121, 159, .35);
    }
    .btn-cancel {
        border: 1.5px solid #F2DCE5;
        background: #fff;
        color: var(--ink);
        border-radius: 999px;
        padding: .7rem 1.25rem;
        font-weight: 700;
        transition: border-color .2s ease, color .2s ease;
    }
    .btn-cancel:hover {
        border-color: var(--poppy);
        color: var(--poppy);
    }

    .list-toolbar {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .list-toolbar h4 {
        margin: 0;
        white-space: nowrap;
        font-family: 'Fraunces', Georgia, serif;
        color: #754C61;
        font-size: 1.15rem;
    }
    .search-row {
        display: flex;
        gap: .5rem;
        flex-wrap: nowrap;
        align-items: center;
        flex: 1 1 auto;
        min-width: 0;
    }
    .search-row .form-control {
        border-radius: 999px;
        flex: 1 1 auto;
        width: 1%;
        min-width: 0;
        height: 44px;
    }
    .search-row .btn-search,
    .search-row .btn-clear {
        height: 44px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }
    .search-row .btn-search {
        background: var(--poppy);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: .55rem 1.15rem;
        font-weight: 800;
        transition: background .2s ease, transform .2s ease;
    }
    .search-row .btn-search:hover {
        background: #C86691;
        color: #fff;
        transform: translateY(-1px);
    }
    .search-row .btn-clear {
        border: 1.5px solid #F2DCE5;
        background: #fff;
        color: var(--ink);
        border-radius: 999px;
        padding: .55rem 1rem;
        font-weight: 700;
        transition: border-color .2s ease, color .2s ease;
        text-decoration: none;
    }
    .search-row .btn-clear:hover {
        border-color: var(--poppy);
        color: var(--poppy);
    }

    .admin-table {
        margin: 0;
    }
    .admin-table thead th {
        background: var(--cream);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
        border-bottom: 1px solid #F7E7EE;
        padding: .85rem 1rem;
        white-space: nowrap;
    }
    .admin-table tbody td {
        padding: .75rem .9rem;
        border-color: #F7E7EE;
        font-weight: 600;
        color: var(--ink);
        vertical-align: middle;
        font-size: .92rem;
    }
    .admin-table th,
    .admin-table td {
        white-space: nowrap;
    }
    .admin-table th:first-child,
    .admin-table td:first-child {
        width: 100%;
        white-space: normal;
    }
    .admin-table tbody tr {
        transition: background .2s ease;
    }
    .admin-table tbody tr:hover {
        background: #FFFBF5;
    }
    .product-name-cell {
        font-weight: 800;
        color: #85566D;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-weight: 800;
        font-size: .74rem;
        letter-spacing: .03em;
        padding: .32rem .7rem;
        border-radius: 999px;
        text-transform: uppercase;
    }
    .status-pill::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }
    .status-pill.active   { background: #E7F4EC; color: #2E6B45; }
    .status-pill.inactive { background: #F2ECEE; color: #81747A; }

    .stock-pill {
        font-weight: 800;
        font-size: .85rem;
        color: var(--ink);
    }
    .stock-pill.low { color: #9A6B1A; }
    .stock-pill.out { color: #9A2D4B; }

    .action-group {
        display: flex;
        gap: .35rem;
        flex-wrap: nowrap;
        justify-content: flex-end;
    }
    .btn-action {
        border-radius: 999px;
        padding: .35rem .8rem;
        font-weight: 800;
        font-size: .75rem;
        border: 1.5px solid transparent;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease, color .2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        white-space: nowrap;
    }
    .btn-action:hover {
        transform: translateY(-1px);
    }
    .btn-action.edit {
        background: #EFE4FA;
        color: #6C4A9C;
        border-color: #E1CFF4;
    }
    .btn-action.edit:hover {
        background: #E1CFF4;
        color: #5A3884;
    }
    .btn-action.toggle-on {
        background: #FFF3D1;
        color: #9A6B1A;
        border-color: #F5E0A2;
    }
    .btn-action.toggle-on:hover {
        background: #F5E0A2;
    }
    .btn-action.toggle-off {
        background: #E7F4EC;
        color: #2E6B45;
        border-color: #CBE6D4;
    }
    .btn-action.toggle-off:hover {
        background: #CBE6D4;
    }
    .btn-action.delete {
        background: #FBE4E9;
        color: #9A2D4B;
        border-color: #F6CBD5;
    }
    .btn-action.delete:hover {
        background: #F6CBD5;
    }

    .prod-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--muted);
    }
    .prod-empty .emoji {
        font-size: 2.5rem;
        display: block;
        margin-bottom: .5rem;
        opacity: .7;
    }
    .prod-empty .headline {
        font-weight: 800;
        color: #85566D;
        margin-bottom: .25rem;
    }

    .results-note {
        padding: .85rem 1.4rem;
        background: var(--cream);
        font-size: .82rem;
        font-weight: 700;
        color: var(--muted);
        border-top: 1px solid #F7E7EE;
    }

    @media (max-width: 576px) {
        .prod-hero { padding: 1.25rem; border-radius: 1.15rem; }
        .prod-hero h1 { font-size: 1.35rem; }
        .admin-card .card-body { padding: 1rem; }
        .admin-card .card-head { padding: .85rem 1rem; font-size: .98rem; }
        .admin-table thead th,
        .admin-table tbody td { padding: .7rem .75rem; font-size: .82rem; }
        .btn-action { font-size: .7rem; padding: .3rem .65rem; }
        .list-toolbar { flex-direction: column; align-items: stretch; }
        .search-row { flex-wrap: wrap; }
        .search-row .form-control { width: 100%; flex: 1 1 100%; }
    }
</style>

<main class="container py-4 py-md-5 prod-page">

    <div class="prod-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <p class="eyebrow mb-1">CATALOG</p>
            <h1 class="mb-1">Product Management</h1>
            <p>Manage your Poppy &amp; Paper products.</p>
        </div>
        <a href="dashboard.php" class="btn-back-dash">
            ← Dashboard
        </a>
    </div>

    <?php if ($message): ?>
        <div class="admin-alert success">
            <span>✨</span>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="admin-alert danger">
            <span>⚠️</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="card-head">
            <span class="icon"><?= !empty($form['product_id']) ? '✏️' : '🌸' ?></span>
            <?= !empty($form['product_id']) ? 'Edit Product' : 'Add New Product' ?>
        </div>
        <div class="card-body">

            <form method="POST" action="actions/product-actions.php">

                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrfToken) ?>">

                <input type="hidden" name="action" value="save">

                <input type="hidden" name="product_id"
                       value="<?= htmlspecialchars((string) $form['product_id']) ?>">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Product Name</label>

                        <input
                            type="text"
                            name="product_name"
                            class="form-control"
                            maxlength="150"
                            value="<?= htmlspecialchars($form['product_name']) ?>"
                            placeholder="e.g. Daisy Sticker Set"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Category</label>

                        <select name="category_id" class="form-select" required>

                            <option value="">Select category</option>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= $category['category_id'] ?>"
                                    <?= (string) $form['category_id'] === (string) $category['category_id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($category['category_name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="3"
                            placeholder="Describe this lovely product…"
                            required
                        ><?= htmlspecialchars($form['description']) ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Price (₱)</label>

                        <input
                            type="number"
                            name="price"
                            class="form-control"
                            min="0.01"
                            max="99999999.99"
                            step="0.01"
                            value="<?= htmlspecialchars((string) $form['price']) ?>"
                            placeholder="0.00"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Stock Quantity</label>

                        <input
                            type="number"
                            name="stock_quantity"
                            class="form-control"
                            min="0"
                            step="1"
                            value="<?= htmlspecialchars((string) $form['stock_quantity']) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>

                        <select name="status" class="form-select">

                            <option value="active"
                                <?= $form['status'] === 'active' ? 'selected' : '' ?>>
                                Active
                            </option>

                            <option value="inactive"
                                <?= $form['status'] === 'inactive' ? 'selected' : '' ?>>
                                Inactive
                            </option>

                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">
                            Image Path
                            <span class="optional">(optional)</span>
                        </label>

                        <input
                            type="text"
                            name="image_path"
                            class="form-control"
                            maxlength="255"
                            placeholder="e.g. assets/images/product.jpg"
                            value="<?= htmlspecialchars((string) ($form['image_path'] ?? '')) ?>"
                        >

                        <small class="text-muted">
                            You can add product images later.
                        </small>
                    </div>

                    <div class="col-12 d-flex gap-2 flex-wrap pt-2">

                        <button type="submit" class="btn btn-save">
                            <?= !empty($form['product_id']) ? '💾 Save Changes' : '🌸 Add Product' ?>
                        </button>

                        <?php if (!empty($form['product_id'])): ?>

                            <a href="products.php" class="btn btn-cancel">
                                Cancel Edit
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </form>

        </div>
    </div>

    <div class="admin-card">

        <div class="card-head">
            <span class="icon">📋</span> Product List
        </div>

        <div class="card-body">

            <div class="list-toolbar">

                <h4>All products</h4>

                <form method="GET" class="search-row">

                    <input
                        type="search"
                        name="search"
                        class="form-control"
                        placeholder="Search products…"
                        value="<?= htmlspecialchars($search) ?>"
                    >

                    <button class="btn-search">
                        Search
                    </button>

                    <?php if ($search !== ''): ?>
                        <a href="products.php" class="btn-clear">
                            Clear
                        </a>
                    <?php endif; ?>

                </form>

            </div>

            <div class="table-responsive">

                <table class="table admin-table align-middle">

                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($products)): ?>

                            <tr>
                                <td colspan="6">
                                    <div class="prod-empty">
                                        <span class="emoji">🌸</span>
                                        <div class="headline">No products found</div>
                                        <small>
                                            <?= $search !== '' ? 'Try a different search term.' : 'Add your first product above.' ?>
                                        </small>
                                    </div>
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($products as $product): ?>

                                <?php
                                $stock = (int) $product['stock_quantity'];
                                $stockClass = $stock === 0 ? 'out' : ($stock <= 5 ? 'low' : '');
                                ?>

                                <tr>

                                    <td>
                                        <span class="product-name-cell">
                                            <?= htmlspecialchars($product['product_name']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                                    </td>

                                    <td>
                                        <strong>₱<?= number_format((float) $product['price'], 2) ?></strong>
                                    </td>

                                    <td>
                                        <span class="stock-pill <?= $stockClass ?>">
                                            <?= $stock ?>
                                            <?php if ($stock === 0): ?> · out<?php endif; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="status-pill <?= $product['status'] === 'active' ? 'active' : 'inactive' ?>">
                                            <?= htmlspecialchars(ucfirst($product['status'])) ?>
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="action-group">

                                            <a
                                                href="products.php?edit=<?= $product['product_id'] ?>"
                                                class="btn-action edit"
                                            >
                                                ✏️ Edit
                                            </a>

                                            <form method="POST" action="actions/product-actions.php" class="d-inline">

                                                <input type="hidden"
                                                       name="csrf_token"
                                                       value="<?= htmlspecialchars($csrfToken) ?>">

                                                <input type="hidden"
                                                       name="action"
                                                       value="toggle">

                                                <input type="hidden"
                                                       name="product_id"
                                                       value="<?= $product['product_id'] ?>">

                                                <input type="hidden"
                                                       name="status"
                                                       value="<?= $product['status'] === 'active' ? 'inactive' : 'active' ?>">

                                                <button
                                                    type="submit"
                                                    class="btn-action <?= $product['status'] === 'active' ? 'toggle-on' : 'toggle-off' ?>"
                                                >
                                                    <?= $product['status'] === 'active' ? '⏸ Deactivate' : '▶ Activate' ?>
                                                </button>

                                            </form>

                                            <form method="POST"
                                                  action="actions/product-actions.php"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this product?');">

                                                <input type="hidden"
                                                       name="csrf_token"
                                                       value="<?= htmlspecialchars($csrfToken) ?>">

                                                <input type="hidden"
                                                       name="action"
                                                       value="delete">

                                                <input type="hidden"
                                                       name="product_id"
                                                       value="<?= $product['product_id'] ?>">

                                                <button type="submit" class="btn-action delete">
                                                    🗑 Delete
                                                </button>

                                            </form>

                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="results-note">
            Showing <strong><?= count($products) ?></strong> product(s).
            <?php if ($search !== ''): ?>
                · filtered by “<?= htmlspecialchars($search) ?>”
            <?php endif; ?>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
