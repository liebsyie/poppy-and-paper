
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Orders | Poppy & Paper';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$message = $_SESSION['order_success'] ?? '';
$error = $_SESSION['order_error'] ?? '';

unset($_SESSION['order_success'], $_SESSION['order_error']);

if (isset($_GET['success'])) {
    $message = $_GET['success'];
}

$statuses = [
    'Pending',
    'Confirmed',
    'Preparing',
    'Shipped',
    'Completed',
    'Cancelled'
];

$nextStatus = [
    'Pending' => 'Confirmed',
    'Confirmed' => 'Preparing',
    'Preparing' => 'Shipped',
    'Shipped' => 'Completed'
];

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$sql = "
    SELECT *
    FROM orders
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            order_number LIKE :search
            OR customer_name LIKE :search
            OR customer_email LIKE :search
        )
    ";

    $params['search'] = '%' . $search . '%';
}

if (
    $statusFilter !== '' &&
    in_array($statusFilter, $statuses, true)
) {
    $sql .= " AND status = :status";
    $params['status'] = $statusFilter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = [];

foreach ($statuses as $status) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM orders
        WHERE status = :status
    ");

    $stmt->execute(['status' => $status]);
    $counts[$status] = (int) $stmt->fetchColumn();
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .ord-page { animation: fadeUp .6s ease both; }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .ord-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.5rem;
        padding: 1.75rem;
        background: linear-gradient(125deg, #FCE0E9 0%, #FFF0C9 60%, #E8DDF8 100%);
        margin-bottom: 1.5rem;
        isolation: isolate;
    }

    .ord-hero::before,
    .ord-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        opacity: .5;
        z-index: -1;
        animation: floaty 9s ease-in-out infinite;
    }

    .ord-hero::before {
        width: 200px;
        height: 200px;
        background: #F7C9D9;
        top: -70px;
        left: -60px;
    }

    .ord-hero::after {
        width: 220px;
        height: 220px;
        background: #D9C9F3;
        bottom: -90px;
        right: -60px;
        animation-delay: -4.5s;
    }

    @keyframes floaty {
        0%, 100% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-18px) scale(1.05); }
    }

    .ord-hero .eyebrow {
        letter-spacing: .16em;
        color: #A65E7D;
    }

    .ord-hero h1 {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #754C61;
        font-weight: 700;
        letter-spacing: -.015em;
        font-size: 1.65rem;
        margin-bottom: .2rem;
    }

    .ord-hero p {
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
        align-items: flex-start;
        gap: .5rem;
        margin-bottom: 1rem;
        line-height: 1.5;
    }

    .admin-alert.success {
        background: #E7F4EC;
        color: #2E6B45;
    }

    .admin-alert.danger {
        background: #FBE4E9;
        color: #9A2D4B;
    }

    .stat-tile {
        display: block;
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1rem;
        padding: .9rem 1rem;
        text-decoration: none;
        color: var(--ink);
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        position: relative;
        overflow: hidden;
        height: 100%;
        animation: fadeUp .55s ease both;
    }

    .stat-tile::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        width: 4px;
        background: var(--accent, #F7C9D9);
    }

    .stat-tile:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 26px rgba(117, 76, 97, .12);
        border-color: #E9B6C9;
        color: inherit;
    }

    .stat-tile.active-filter {
        border-color: var(--poppy);
        box-shadow: 0 0 0 .18rem rgba(216, 121, 159, .15);
    }

    .stat-tile .stat-label {
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
        display: flex;
        align-items: center;
        gap: .3rem;
        margin-bottom: .35rem;
    }

    .stat-tile .stat-label .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--accent, #F7C9D9);
    }

    .stat-tile .stat-value {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        font-weight: 700;
        color: var(--ink);
        font-size: 1.5rem;
        line-height: 1;
    }

    .stat-row > [class*="col-"]:nth-child(1) .stat-tile { animation-delay: .05s; }
    .stat-row > [class*="col-"]:nth-child(2) .stat-tile { animation-delay: .10s; }
    .stat-row > [class*="col-"]:nth-child(3) .stat-tile { animation-delay: .15s; }
    .stat-row > [class*="col-"]:nth-child(4) .stat-tile { animation-delay: .20s; }
    .stat-row > [class*="col-"]:nth-child(5) .stat-tile { animation-delay: .25s; }
    .stat-row > [class*="col-"]:nth-child(6) .stat-tile { animation-delay: .30s; }

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

    .admin-card .card-head .icon {
        font-size: 1.15rem;
    }

    .admin-card .card-body {
        padding: 1.4rem;
    }

    .admin-card .form-label {
        font-weight: 800;
        font-size: .78rem;
        color: #85566D;
        letter-spacing: .05em;
        text-transform: uppercase;
        margin-bottom: .35rem;
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

    .btn-search {
        background: var(--poppy);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: .7rem 1.2rem;
        font-weight: 800;
        transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
        white-space: nowrap;
    }

    .btn-search:hover {
        background: #C86691;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(216, 121, 159, .3);
    }

    .btn-clear {
        border: 1.5px solid #F2DCE5;
        background: #fff;
        color: var(--ink);
        border-radius: 999px;
        padding: .7rem 1.1rem;
        font-weight: 700;
        transition: border-color .2s ease, color .2s ease;
        white-space: nowrap;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .btn-clear:hover {
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
        padding: .9rem 1rem;
        border-color: #F7E7EE;
        font-weight: 600;
        color: var(--ink);
        vertical-align: middle;
        font-size: .9rem;
    }

    .admin-table tbody tr.data-row {
        transition: background .2s ease;
    }

    .admin-table tbody tr.data-row:hover {
        background: #FFFBF5;
    }

    .admin-table tbody tr.detail-row > td {
        background: #FFFBF5;
        padding: 0 1rem .9rem;
        border-top: 0;
    }

    .order-num {
        font-family: 'Nunito', sans-serif;
        font-weight: 800;
        color: #85566D;
        font-size: .88rem;
        display: inline-block;
    }

    .customer-name {
        font-weight: 800;
        color: var(--ink);
        line-height: 1.25;
    }

    .customer-email {
        font-size: .78rem;
        color: var(--muted);
        font-weight: 600;
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
        white-space: nowrap;
    }

    .status-pill::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .status-pill.pending { background: #FFF3D1; color: #9A6B1A; }
    .status-pill.confirmed { background: #E3ECFA; color: #3A5A94; }
    .status-pill.preparing { background: #EFE4FA; color: #6C4A9C; }
    .status-pill.shipped { background: #E0EEF7; color: #2C6E94; }
    .status-pill.completed { background: #E7F4EC; color: #2E6B45; }
    .status-pill.cancelled { background: #F2ECEE; color: #81747A; }

    .action-group {
        display: flex;
        gap: .35rem;
        flex-wrap: wrap;
    }

    .btn-action {
        border-radius: 999px;
        padding: .35rem .85rem;
        font-weight: 800;
        font-size: .74rem;
        border: 1.5px solid transparent;
        transition: transform .2s ease, background .2s ease, color .2s ease;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        white-space: nowrap;
        cursor: pointer;
    }

    .btn-action:hover {
        transform: translateY(-1px);
    }

    .btn-action.advance {
        background: #E7F4EC;
        color: #2E6B45;
        border-color: #CBE6D4;
    }

    .btn-action.advance:hover {
        background: #CBE6D4;
    }

    .btn-action.cancel {
        background: #FBE4E9;
        color: #9A2D4B;
        border-color: #F6CBD5;
    }

    .btn-action.cancel:hover {
        background: #F6CBD5;
    }

    .order-details {
        border-radius: .9rem;
        background: #fff;
        border: 1px solid #F2DCE5;
        overflow: hidden;
    }

    .order-details summary {
        padding: .7rem 1rem;
        cursor: pointer;
        font-weight: 800;
        font-size: .82rem;
        color: #85566D;
        background: var(--cream);
        list-style: none;
        display: flex;
        align-items: center;
        gap: .4rem;
        transition: background .2s ease, color .2s ease;
    }

    .order-details summary::-webkit-details-marker {
        display: none;
    }

    .order-details summary::before {
        content: "▸";
        display: inline-block;
        transition: transform .2s ease;
        color: var(--poppy);
        font-weight: 900;
    }

    .order-details[open] summary::before {
        transform: rotate(90deg);
    }

    .order-details summary:hover {
        background: #FFF0F6;
        color: var(--poppy);
    }

    .order-details .details-inner {
        padding: 1rem;
    }

    .order-details table thead th {
        background: transparent;
        border-bottom: 1px solid #F7E7EE;
        font-size: .7rem;
        letter-spacing: .06em;
        color: var(--muted);
        padding: .5rem .6rem;
        text-transform: uppercase;
        font-weight: 800;
    }

    .order-details table tbody td {
        padding: .55rem .6rem;
        font-size: .85rem;
        border-color: #F7E7EE;
    }

    .order-details .info-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: .5rem;
        padding: .4rem 0;
        font-size: .88rem;
        border-top: 1px dashed #F2DCE5;
    }

    .order-details .info-row:first-of-type {
        border-top: 0;
    }

    .order-details .info-row .lbl {
        font-weight: 800;
        color: var(--muted);
        font-size: .78rem;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .order-details .info-row .val {
        color: var(--ink);
        font-weight: 600;
        word-break: break-word;
    }

    .ord-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--muted);
    }

    .ord-empty .emoji {
        font-size: 2.5rem;
        display: block;
        margin-bottom: .5rem;
        opacity: .7;
    }

    .ord-empty .headline {
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

    @media (max-width: 992px) {
        .admin-table thead {
            display: none;
        }

        .admin-table tbody tr.data-row {
            display: block;
            border-bottom: 1px solid #F7E7EE;
            padding: .75rem 1rem .25rem;
        }

        .admin-table tbody tr.data-row td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            padding: .35rem 0;
            border: 0;
            text-align: right;
        }

        .admin-table tbody tr.data-row td::before {
            content: attr(data-label);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--muted);
            text-align: left;
            flex: 0 0 auto;
            padding-top: .15rem;
        }

        .admin-table tbody tr.detail-row > td {
            display: block;
            padding: 0 1rem 1rem;
            text-align: left;
        }

        .order-details .info-row {
            grid-template-columns: 1fr;
            gap: .15rem;
        }
    }

    @media (max-width: 576px) {
        .ord-hero {
            padding: 1.25rem;
            border-radius: 1.15rem;
        }

        .ord-hero h1 {
            font-size: 1.35rem;
        }

        .admin-card .card-body {
            padding: 1rem;
        }

        .admin-card .card-head {
            padding: .85rem 1rem;
            font-size: .98rem;
        }

        .stat-tile .stat-value {
            font-size: 1.3rem;
        }
    }
</style>

<main class="container py-4 py-md-5 ord-page">

    <div class="ord-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <p class="eyebrow mb-1">FULFILLMENT</p>
            <h1 class="mb-1">Order Management</h1>
            <p>View and manage customer orders.</p>
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

    <div class="row g-3 mb-4 stat-row">

        <?php
        $statusAccents = [
            'Pending'   => ['#FFE6A7', '#FFF3D1'],
            'Confirmed' => ['#BFD4F1', '#E3ECFA'],
            'Preparing' => ['#D9C9F3', '#EFE4FA'],
            'Shipped'   => ['#B7D9EE', '#E0EEF7'],
            'Completed' => ['#CDE5D1', '#E7F4EC'],
            'Cancelled' => ['#D7C7CF', '#F2ECEE']
        ];
        ?>

        <?php foreach ($statuses as $status): ?>

            <?php
            $accent = $statusAccents[$status][0] ?? '#F7C9D9';
            $isActive = $statusFilter === $status;

            $link = 'orders.php?status=' . urlencode($status);

            if ($search !== '') {
                $link .= '&search=' . urlencode($search);
            }
            ?>

            <div class="col-6 col-md-4 col-lg-2">

                <a
                    href="<?= $isActive ? 'orders.php' : $link ?>"
                    class="stat-tile <?= $isActive ? 'active-filter' : '' ?>"
                    style="--accent: <?= $accent ?>;"
                >

                    <div class="stat-label">
                        <span class="dot"></span>
                        <?= htmlspecialchars($status) ?>
                    </div>

                    <div class="stat-value">
                        <?= $counts[$status] ?>
                    </div>

                </a>

            </div>

        <?php endforeach; ?>

    </div>

    <div class="admin-card">

        <div class="card-head">
            <span class="icon">🔍</span>
            Find orders
        </div>

        <div class="card-body">

            <form method="GET" action="orders.php">

                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-6">

                        <label class="form-label" for="searchInput">
                            Search Orders
                        </label>

                        <input
                            type="search"
                            id="searchInput"
                            name="search"
                            class="form-control"
                            placeholder="Order number, customer name, or email"
                            value="<?= htmlspecialchars($search) ?>"
                        >

                    </div>

                    <div class="col-12 col-md-4">

                        <label class="form-label" for="statusSelect">
                            Filter by Status
                        </label>

                        <select name="status" id="statusSelect" class="form-select">

                            <option value="">All Orders</option>

                            <?php foreach ($statuses as $status): ?>

                                <option
                                    value="<?= htmlspecialchars($status) ?>"
                                    <?= $statusFilter === $status ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($status) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-12 col-md-2 d-flex gap-2">

                        <button type="submit" class="btn-search flex-grow-1">
                            Search
                        </button>

                        <a href="orders.php" class="btn-clear">
                            Clear
                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <div class="admin-card">

        <div class="card-head">
            <span class="icon">🧾</span>
            Customer Orders
        </div>

        <div class="card-body" style="padding-bottom:.5rem;">

            <div class="table-responsive">

                <table class="table admin-table align-middle">

                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($orders)): ?>

                            <tr>
                                <td colspan="7">

                                    <div class="ord-empty">

                                        <span class="emoji">🧾</span>

                                        <div class="headline">
                                            No orders found
                                        </div>

                                        <small>
                                            <?= ($search !== '' || $statusFilter !== '')
                                                ? 'Try a different search or filter.'
                                                : 'New orders will appear here.' ?>
                                        </small>

                                    </div>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($orders as $order): ?>

                                <?php
                                $statusClass = strtolower($order['status']);

                                $allowed = [
                                    'pending',
                                    'confirmed',
                                    'preparing',
                                    'shipped',
                                    'completed',
                                    'cancelled'
                                ];

                                if (!in_array($statusClass, $allowed, true)) {
                                    $statusClass = 'pending';
                                }
                                ?>

                                <tr class="data-row">

                                    <td data-label="Order">

                                        <span class="order-num">
                                            <?= htmlspecialchars($order['order_number']) ?>
                                        </span>

                                    </td>

                                    <td data-label="Customer">

                                        <div>

                                            <div class="customer-name">
                                                <?= htmlspecialchars($order['customer_name']) ?>
                                            </div>

                                            <div class="customer-email">
                                                <?= htmlspecialchars($order['customer_email']) ?>
                                            </div>

                                        </div>

                                    </td>

                                    <td data-label="Date">

                                        <?= date(
                                            'M d, Y',
                                            strtotime($order['created_at'])
                                        ) ?>

                                    </td>

                                    <td data-label="Total">

                                        <strong>
                                            ₱<?= number_format((float) $order['total'], 2) ?>
                                        </strong>

                                    </td>

                                    <td data-label="Payment">

                                        <?= htmlspecialchars($order['payment_method']) ?>

                                    </td>

                                    <td data-label="Status">

                                        <span class="status-pill <?= $statusClass ?>">
                                            <?= htmlspecialchars($order['status']) ?>
                                        </span>

                                    </td>

                                    <td data-label="Actions">

                                        <div class="action-group">

                                            <?php if (isset($nextStatus[$order['status']])): ?>

                                                <form
                                                    method="POST"
                                                    action="../actions/order-actions.php"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars($csrfToken) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="update_status"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="order_id"
                                                        value="<?= (int) $order['order_id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn-action advance"
                                                    >
                                                        ▶ <?= htmlspecialchars($nextStatus[$order['status']]) ?>
                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                            <?php if (in_array($order['status'], ['Pending', 'Confirmed'], true)): ?>

                                                <form
                                                    method="POST"
                                                    action="../actions/order-actions.php"
                                                    onsubmit="return confirm('Cancel this order and restore its product stock?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= htmlspecialchars($csrfToken) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="cancel"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="order_id"
                                                        value="<?= (int) $order['order_id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn-action cancel"
                                                    >
                                                        ✕ Cancel
                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                                <tr class="detail-row">

                                    <td colspan="7">

                                        <details class="order-details">

                                            <summary>
                                                View items and delivery details
                                            </summary>

                                            <?php
                                            $itemStmt = $pdo->prepare("
                                                SELECT
                                                    product_name_snapshot,
                                                    unit_price_snapshot,
                                                    quantity,
                                                    line_total
                                                FROM order_items
                                                WHERE order_id = :order_id
                                            ");

                                            $itemStmt->execute([
                                                'order_id' => $order['order_id']
                                            ]);

                                            $orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                                            ?>

                                            <div class="details-inner">

                                                <div class="table-responsive">

                                                    <table class="table table-sm align-middle mb-3">

                                                        <thead>
                                                            <tr>
                                                                <th>Product</th>
                                                                <th>Unit Price</th>
                                                                <th>Qty</th>
                                                                <th>Subtotal</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>

                                                            <?php foreach ($orderItems as $item): ?>

                                                                <tr>

                                                                    <td>
                                                                        <?= htmlspecialchars($item['product_name_snapshot']) ?>
                                                                    </td>

                                                                    <td>
                                                                        ₱<?= number_format((float) $item['unit_price_snapshot'], 2) ?>
                                                                    </td>

                                                                    <td>
                                                                        <?= (int) $item['quantity'] ?>
                                                                    </td>

                                                                    <td>
                                                                        ₱<?= number_format((float) $item['line_total'], 2) ?>
                                                                    </td>

                                                                </tr>

                                                            <?php endforeach; ?>

                                                        </tbody>

                                                    </table>

                                                </div>

                                                <div class="info-row">

                                                    <span class="lbl">
                                                        Contact
                                                    </span>

                                                    <span class="val">
                                                        <?= htmlspecialchars($order['customer_contact']) ?>
                                                    </span>

                                                </div>

                                                <div class="info-row">

                                                    <span class="lbl">
                                                        Delivery Address
                                                    </span>

                                                    <span class="val">
                                                        <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                                                    </span>

                                                </div>

                                                <?php if (!empty($order['order_notes'])): ?>

                                                    <div class="info-row">

                                                        <span class="lbl">
                                                            Order Notes
                                                        </span>

                                                        <span class="val">
                                                            <?= nl2br(htmlspecialchars($order['order_notes'])) ?>
                                                        </span>

                                                    </div>

                                                <?php endif; ?>

                                            </div>

                                        </details>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="results-note">

            Showing <strong><?= count($orders) ?></strong> order(s).

            <?php if ($search !== ''): ?>
                · matching “<?= htmlspecialchars($search) ?>”
            <?php endif; ?>

            <?php if ($statusFilter !== ''): ?>
                · status: <?= htmlspecialchars($statusFilter) ?>
            <?php endif; ?>

        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
