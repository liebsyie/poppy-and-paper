
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Dashboard | Poppy & Paper';

$totalProducts = (int) $pdo->query("
    SELECT COUNT(*) FROM products
")->fetchColumn();

$activeProducts = (int) $pdo->query("
    SELECT COUNT(*) FROM products
    WHERE status = 'active'
")->fetchColumn();

$totalCategories = (int) $pdo->query("
    SELECT COUNT(*) FROM categories
")->fetchColumn();

$totalOrders = (int) $pdo->query("
    SELECT COUNT(*) FROM orders
")->fetchColumn();

$pendingOrders = (int) $pdo->query("
    SELECT COUNT(*) FROM orders
    WHERE status = 'Pending'
")->fetchColumn();

$totalCustomers = (int) $pdo->query("
    SELECT COUNT(DISTINCT customer_email)
    FROM orders
")->fetchColumn();

$totalSales = (float) $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM orders
    WHERE status != 'Cancelled'
")->fetchColumn();

$stmt = $pdo->query("
    SELECT
        order_id,
        order_number,
        customer_name,
        total,
        status,
        created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 5
");

$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .admin-dash {
        animation: fadeUp .6s ease both;
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .dash-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.5rem;
        padding: 2rem 1.75rem;
        background: linear-gradient(125deg, #FCE0E9 0%, #FFF0C9 60%, #E8DDF8 100%);
        margin-bottom: 1.5rem;
        isolation: isolate;
    }
    .dash-hero::before,
    .dash-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        opacity: .5;
        z-index: -1;
        animation: floaty 9s ease-in-out infinite;
    }
    .dash-hero::before {
        width: 220px; height: 220px;
        background: #F7C9D9;
        top: -80px; left: -60px;
    }
    .dash-hero::after {
        width: 240px; height: 240px;
        background: #D9C9F3;
        bottom: -100px; right: -60px;
        animation-delay: -4.5s;
    }
    @keyframes floaty {
        0%, 100% { transform: translateY(0) scale(1); }
        50%      { transform: translateY(-18px) scale(1.05); }
    }

    .dash-hero .eyebrow {
        letter-spacing: .16em;
        color: #A65E7D;
    }
    .dash-hero h1 {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #754C61;
        font-weight: 700;
        letter-spacing: -.015em;
        font-size: 1.85rem;
        margin-bottom: .25rem;
    }
    .dash-hero .welcome {
        color: #8C7B84;
        font-weight: 600;
        margin: 0;
    }
    .dash-hero .welcome strong {
        color: #C86691;
    }

    .btn-signout {
        border: 1.5px solid rgba(255, 255, 255, .7);
        background: rgba(255, 255, 255, .65);
        color: #754C61;
        border-radius: 999px;
        padding: .6rem 1.15rem;
        font-weight: 800;
        font-size: .88rem;
        backdrop-filter: blur(6px);
        transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
        white-space: nowrap;
    }
    .btn-signout:hover {
        background: #fff;
        color: #C86691;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(117, 76, 97, .15);
    }

    .stat-card {
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.25rem;
        padding: 1.15rem 1.15rem 1.1rem;
        box-shadow: 0 4px 14px rgba(117, 76, 97, .05);
        position: relative;
        overflow: hidden;
        transition: transform .3s ease, box-shadow .3s ease;
        height: 100%;
        animation: fadeUp .55s ease both;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 28px rgba(117, 76, 97, .12);
    }
    .stat-card::after {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: var(--accent, #F7C9D9);
    }

    .stat-card .icon-chip {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        background: var(--chip-bg, #FCE8EF);
        margin-bottom: .75rem;
    }

    .stat-card .stat-label {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: .35rem;
    }
    .stat-card .stat-value {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        font-weight: 700;
        color: var(--ink);
        font-size: 1.85rem;
        line-height: 1;
        margin-bottom: .45rem;
    }
    .stat-card .stat-sub {
        font-size: .78rem;
        font-weight: 700;
        color: var(--muted);
        display: flex;
        align-items: center;
        gap: .3rem;
    }
    .stat-card .stat-sub .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--accent, #F7C9D9);
    }

    .stat-row > [class*="col-"]:nth-child(1) .stat-card { animation-delay: .05s; }
    .stat-row > [class*="col-"]:nth-child(2) .stat-card { animation-delay: .10s; }
    .stat-row > [class*="col-"]:nth-child(3) .stat-card { animation-delay: .15s; }
    .stat-row > [class*="col-"]:nth-child(4) .stat-card { animation-delay: .20s; }

    .sales-panel {
        background: linear-gradient(125deg, #FFF0F6, #FFF8ED);
        border: 1px solid #F2DCE5;
        border-radius: 1.25rem;
        padding: 1.5rem 1.65rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        animation: fadeUp .6s ease .15s both;
        margin-bottom: 1.5rem;
    }
    .sales-panel .label {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .09em;
        text-transform: uppercase;
        color: #A65E7D;
        margin-bottom: .35rem;
    }
    .sales-panel .amount {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        font-weight: 700;
        color: #C86691;
        font-size: 2.1rem;
        line-height: 1;
        letter-spacing: -.01em;
    }
    .sales-panel .note {
        font-size: .8rem;
        color: var(--muted);
        font-weight: 600;
        margin-top: .35rem;
    }
    .sales-panel .icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        box-shadow: 0 6px 16px rgba(216, 121, 159, .2);
        flex: 0 0 auto;
    }

    .quick-head {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #85566D;
        font-weight: 700;
        margin-bottom: .9rem;
        font-size: 1.15rem;
    }
    .quick-tile {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: 1rem 1.15rem;
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.15rem;
        color: var(--ink);
        text-decoration: none;
        font-weight: 800;
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease, color .25s ease;
        animation: fadeUp .6s ease both;
        height: 100%;
    }
    .quick-tile:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 26px rgba(117, 76, 97, .12);
        border-color: #E9B6C9;
        color: #C86691;
    }
    .quick-tile .q-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        background: var(--chip-bg, #FCE8EF);
        flex: 0 0 auto;
        transition: transform .3s ease;
    }
    .quick-tile:hover .q-icon {
        transform: scale(1.1) rotate(-4deg);
    }
    .quick-tile .q-text {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }
    .quick-tile .q-label {
        font-size: .95rem;
    }
    .quick-tile .q-hint {
        font-size: .72rem;
        font-weight: 600;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-top: .15rem;
    }

    .quick-row > [class*="col-"]:nth-child(1) .quick-tile { animation-delay: .05s; }
    .quick-row > [class*="col-"]:nth-child(2) .quick-tile { animation-delay: .10s; }
    .quick-row > [class*="col-"]:nth-child(3) .quick-tile { animation-delay: .15s; }
    .quick-row > [class*="col-"]:nth-child(4) .quick-tile { animation-delay: .20s; }

    .orders-card {
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.25rem;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(117, 76, 97, .05);
        animation: fadeUp .6s ease .25s both;
    }
    .orders-card .card-head {
        padding: 1.15rem 1.4rem;
        border-bottom: 1px solid #F7E7EE;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
    }
    .orders-card .card-head h4 {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #754C61;
        font-weight: 700;
        margin: 0;
        font-size: 1.15rem;
    }
    .view-all-link {
        color: var(--poppy);
        font-weight: 800;
        font-size: .85rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        transition: color .2s ease, gap .2s ease;
    }
    .view-all-link:hover {
        color: #C86691;
        gap: .55rem;
    }

    .orders-card .table {
        margin: 0;
    }
    .orders-card .table thead th {
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
    .orders-card .table tbody td {
        padding: .9rem 1rem;
        border-color: #F7E7EE;
        font-weight: 600;
        color: var(--ink);
        vertical-align: middle;
        font-size: .92rem;
    }
    .orders-card .table tbody tr {
        transition: background .2s ease;
    }
    .orders-card .table tbody tr:hover {
        background: #FFFBF5;
    }
    .order-num {
        font-family: 'Nunito', sans-serif;
        font-weight: 800;
        color: #85566D;
        font-size: .88rem;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-weight: 800;
        font-size: .75rem;
        letter-spacing: .03em;
        padding: .32rem .7rem;
        border-radius: 999px;
    }
    .status-pill::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }
    .status-pill.pending    { background: #FFF3D1; color: #9A6B1A; }
    .status-pill.processing { background: #E3ECFA; color: #3A5A94; }
    .status-pill.shipped    { background: #E0EEF7; color: #2C6E94; }
    .status-pill.completed  { background: #E7F4EC; color: #2E6B45; }
    .status-pill.cancelled  { background: #F2ECEE; color: #81747A; }

    .orders-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--muted);
    }
    .orders-empty .emoji {
        font-size: 2.5rem;
        display: block;
        margin-bottom: .5rem;
        opacity: .7;
    }

    @media (max-width: 576px) {
        .dash-hero { padding: 1.5rem 1.25rem; border-radius: 1.15rem; }
        .dash-hero h1 { font-size: 1.5rem; }
        .sales-panel { padding: 1.25rem; }
        .sales-panel .amount { font-size: 1.7rem; }
        .sales-panel .icon { width: 48px; height: 48px; font-size: 1.4rem; }
        .stat-card .stat-value { font-size: 1.55rem; }
        .orders-card .table thead th,
        .orders-card .table tbody td { padding: .7rem .75rem; font-size: .85rem; }
    }
</style>

<main class="container py-4 py-md-5 admin-dash">

    <div class="dash-hero d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>
            <p class="eyebrow mb-1">ADMIN DASHBOARD</p>
            <h1>Hello there 👋</h1>
            <p class="welcome">
                Welcome back,
                <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></strong>!
                Here's what's happening in your shop today.
            </p>
        </div>

        <a href="logout.php" class="btn btn-signout">
            Sign Out →
        </a>

    </div>

    <div class="row g-3 mb-4 stat-row">

        <div class="col-6 col-lg-3">
            <div class="stat-card" style="--accent:#F7C9D9; --chip-bg:#FCE8EF;">
                <div class="icon-chip">🌸</div>
                <div class="stat-label">Total Products</div>
                <div class="stat-value"><?= $totalProducts ?></div>
                <div class="stat-sub">
                    <span class="dot"></span>
                    <?= $activeProducts ?> active
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card" style="--accent:#D9C9F3; --chip-bg:#EFE4FA;">
                <div class="icon-chip">🏷️</div>
                <div class="stat-label">Categories</div>
                <div class="stat-value"><?= $totalCategories ?></div>
                <div class="stat-sub">
                    <span class="dot"></span>
                    Product groups
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card" style="--accent:#FFE6A7; --chip-bg:#FFF3D1;">
                <div class="icon-chip">🧾</div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= $totalOrders ?></div>
                <div class="stat-sub">
                    <span class="dot"></span>
                    <?= $pendingOrders ?> pending
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card" style="--accent:#CDE5D1; --chip-bg:#E7F4EC;">
                <div class="icon-chip">👥</div>
                <div class="stat-label">Customers</div>
                <div class="stat-value"><?= $totalCustomers ?></div>
                <div class="stat-sub">
                    <span class="dot"></span>
                    Unique buyers
                </div>
            </div>
        </div>

    </div>

    <div class="sales-panel">
        <div>
            <div class="label">💰 Recorded Sales</div>
            <div class="amount">₱<?= number_format($totalSales, 2) ?></div>
            <div class="note">Excludes cancelled orders</div>
        </div>
        <div class="icon">📈</div>
    </div>

    <h4 class="quick-head">Quick management</h4>

    <div class="row g-3 mb-5 quick-row">

        <div class="col-12 col-sm-6 col-md-3">
            <a href="products.php" class="quick-tile" style="--chip-bg:#FCE8EF;">
                <span class="q-icon">🌸</span>
                <span class="q-text">
                    <span class="q-label">Products</span>
                    <span class="q-hint">Manage items</span>
                </span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
            <a href="categories.php" class="quick-tile" style="--chip-bg:#EFE4FA;">
                <span class="q-icon">🏷️</span>
                <span class="q-text">
                    <span class="q-label">Categories</span>
                    <span class="q-hint">Organize shop</span>
                </span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
            <a href="orders.php" class="quick-tile" style="--chip-bg:#FFF3D1;">
                <span class="q-icon">🧾</span>
                <span class="q-text">
                    <span class="q-label">Orders</span>
                    <span class="q-hint"><?= $pendingOrders ?> pending</span>
                </span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
            <a href="customers.php" class="quick-tile" style="--chip-bg:#E7F4EC;">
                <span class="q-icon">👥</span>
                <span class="q-text">
                    <span class="q-label">Customers</span>
                    <span class="q-hint"><?= $totalCustomers ?> total</span>
                </span>
            </a>
        </div>

    </div>

    <div class="orders-card">

        <div class="card-head">
            <h4>Recent Orders</h4>
            <a href="orders.php" class="view-all-link">
                View all <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="table-responsive">

            <table class="table align-middle">

                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($recentOrders)): ?>

                        <tr>
                            <td colspan="5">
                                <div class="orders-empty">
                                    <span class="emoji">📭</span>
                                    <div class="fw-bold" style="color:#85566D;">No orders yet</div>
                                    <small>New orders will appear here.</small>
                                </div>
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($recentOrders as $order): ?>

                            <?php
                            $statusClass = strtolower($order['status']);
                            $allowedStatuses = ['pending','processing','shipped','completed','cancelled'];
                            if (!in_array($statusClass, $allowedStatuses, true)) {
                                $statusClass = 'pending';
                            }
                            ?>

                            <tr>
                                <td>
                                    <span class="order-num">
                                        <?= htmlspecialchars($order['order_number']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($order['customer_name']) ?>
                                </td>

                                <td>
                                    <strong>₱<?= number_format((float) $order['total'], 2) ?></strong>
                                </td>

                                <td>
                                    <span class="status-pill <?= $statusClass ?>">
                                        <?= htmlspecialchars($order['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= date(
                                        'M d, Y',
                                        strtotime($order['created_at'])
                                    ) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
