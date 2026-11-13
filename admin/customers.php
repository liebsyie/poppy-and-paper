
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Customers | Poppy & Paper';

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        customer_name,
        customer_email,
        customer_contact,
        COUNT(order_id) AS total_orders,
        COALESCE(
            SUM(
                CASE
                    WHEN status != 'Cancelled' THEN total
                    ELSE 0
                END
            ), 0
        ) AS total_spent,
        MAX(created_at) AS last_order
    FROM orders
    WHERE
        customer_name LIKE :search_name
        OR customer_email LIKE :search_email
        OR customer_contact LIKE :search_contact
    GROUP BY
        customer_name,
        customer_email,
        customer_contact
    ORDER BY last_order DESC
";

$searchTerm = '%' . $search . '%';

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'search_name' => $searchTerm,
    'search_email' => $searchTerm,
    'search_contact' => $searchTerm
]);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCustomers = count($customers);
$totalOrders = 0;
$totalSpent = 0;

foreach ($customers as $customer) {
    $totalOrders += (int) $customer['total_orders'];
    $totalSpent += (float) $customer['total_spent'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .cust-page { animation: fadeUp .6s ease both; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .cust-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.5rem;
        padding: 1.75rem;
        background: linear-gradient(125deg, #FCE0E9 0%, #FFF0C9 60%, #E8DDF8 100%);
        margin-bottom: 1.5rem;
        isolation: isolate;
    }
    .cust-hero::before,
    .cust-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        opacity: .5;
        z-index: -1;
        animation: floaty 9s ease-in-out infinite;
    }
    .cust-hero::before {
        width: 200px; height: 200px;
        background: #F7C9D9;
        top: -70px; left: -60px;
    }
    .cust-hero::after {
        width: 220px; height: 220px;
        background: #D9C9F3;
        bottom: -90px; right: -60px;
        animation-delay: -4.5s;
    }
    @keyframes floaty {
        0%, 100% { transform: translateY(0) scale(1); }
        50%      { transform: translateY(-18px) scale(1.05); }
    }
    .cust-hero .eyebrow {
        letter-spacing: .16em;
        color: #A65E7D;
    }
    .cust-hero h1 {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #754C61;
        font-weight: 700;
        letter-spacing: -.015em;
        font-size: 1.65rem;
        margin-bottom: .2rem;
    }
    .cust-hero p {
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

    .summary-card {
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.15rem;
        padding: 1.1rem 1.15rem;
        box-shadow: 0 4px 14px rgba(117, 76, 97, .05);
        position: relative;
        overflow: hidden;
        height: 100%;
        transition: transform .25s ease, box-shadow .25s ease;
        animation: fadeUp .55s ease both;
    }
    .summary-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 28px rgba(117, 76, 97, .12);
    }
    .summary-card::after {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: var(--accent, #F7C9D9);
    }
    .summary-card .icon-chip {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        background: var(--chip-bg, #FCE8EF);
        margin-bottom: .7rem;
    }
    .summary-card .stat-label {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: .35rem;
    }
    .summary-card .stat-value {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        font-weight: 700;
        color: var(--ink);
        font-size: 1.65rem;
        line-height: 1;
    }
    .summary-row > [class*="col-"]:nth-child(1) .summary-card { animation-delay: .05s; }
    .summary-row > [class*="col-"]:nth-child(2) .summary-card { animation-delay: .10s; }
    .summary-row > [class*="col-"]:nth-child(3) .summary-card { animation-delay: .15s; }

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

    .admin-card .form-control {
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
    .admin-card .form-control:focus {
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
        width: 100%;
    }
    .btn-search:hover {
        background: #C86691;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(216, 121, 159, .3);
    }
    .btn-clear-search {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        margin-top: .6rem;
        font-size: .82rem;
        font-weight: 700;
        color: var(--poppy);
        text-decoration: none;
        transition: color .2s ease, gap .2s ease;
    }
    .btn-clear-search:hover {
        color: #C86691;
        gap: .55rem;
    }

    .admin-table { margin: 0; }
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
        padding: .95rem 1rem;
        border-color: #F7E7EE;
        font-weight: 600;
        color: var(--ink);
        vertical-align: middle;
        font-size: .92rem;
    }
    .admin-table tbody tr {
        transition: background .2s ease;
    }
    .admin-table tbody tr:hover {
        background: #FFFBF5;
    }

    .customer-cell {
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .customer-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: 'Quicksand', sans-serif;
        font-weight: 700;
        font-size: .95rem;
        color: #fff;
        background: var(--avatar-bg, #D8799F);
        flex: 0 0 auto;
        box-shadow: 0 4px 10px rgba(117, 76, 97, .18);
    }
    .customer-name {
        font-weight: 800;
        color: var(--ink);
        line-height: 1.2;
    }
    .customer-email {
        font-size: .78rem;
        color: var(--muted);
        font-weight: 600;
    }

    .orders-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-weight: 800;
        font-size: .78rem;
        padding: .32rem .7rem;
        border-radius: 999px;
        background: #EFE4FA;
        color: #6C4A9C;
    }
    .orders-pill::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        opacity: .85;
    }

    .spent-amount {
        font-family: 'Nunito', sans-serif;
        font-weight: 800;
        color: #85566D;
    }
    .spent-amount.high { color: #C86691; }

    .cust-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--muted);
    }
    .cust-empty .emoji {
        font-size: 2.5rem;
        display: block;
        margin-bottom: .5rem;
        opacity: .7;
    }
    .cust-empty .headline {
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
        .admin-table thead { display: none; }
        .admin-table tbody tr {
            display: block;
            border-bottom: 1px solid #F7E7EE;
            padding: .75rem 1rem .5rem;
        }
        .admin-table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            padding: .4rem 0;
            border: 0;
            text-align: right;
        }
        .admin-table tbody td::before {
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
        .admin-table tbody td[data-label="Customer"] {
            text-align: left;
            padding-bottom: .5rem;
        }
        .admin-table tbody td[data-label="Customer"]::before {
            display: none;
        }
    }

    @media (max-width: 576px) {
        .cust-hero { padding: 1.25rem; border-radius: 1.15rem; }
        .cust-hero h1 { font-size: 1.35rem; }
        .admin-card .card-body { padding: 1rem; }
        .admin-card .card-head { padding: .85rem 1rem; font-size: .98rem; }
        .summary-card .stat-value { font-size: 1.35rem; }
    }
</style>

<main class="container py-4 py-md-5 cust-page">

    <div class="cust-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <p class="eyebrow mb-1">PEOPLE</p>
            <h1 class="mb-1">Customers</h1>
            <p>View customer information and order summaries.</p>
        </div>
        <a href="dashboard.php" class="btn-back-dash">
            ← Dashboard
        </a>
    </div>

    <div class="row g-3 mb-4 summary-row">

        <div class="col-md-4">
            <div class="summary-card" style="--accent:#F7C9D9; --chip-bg:#FCE8EF;">
                <div class="icon-chip">👥</div>
                <div class="stat-label">Customers Found</div>
                <div class="stat-value"><?= number_format($totalCustomers) ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card" style="--accent:#FFE6A7; --chip-bg:#FFF3D1;">
                <div class="icon-chip">🧾</div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= number_format($totalOrders) ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card" style="--accent:#CDE5D1; --chip-bg:#E7F4EC;">
                <div class="icon-chip">💰</div>
                <div class="stat-label">Total Customer Spending</div>
                <div class="stat-value">₱<?= number_format($totalSpent, 2) ?></div>
            </div>
        </div>

    </div>

    <div class="admin-card">
        <div class="card-head">
            <span class="icon">🔍</span> Find customers
        </div>
        <div class="card-body">

            <form method="GET" action="customers.php" class="row g-2 align-items-center">

                <div class="col-12 col-md-10">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search by name, email, or contact…"
                        value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-12 col-md-2">
                    <button type="submit" class="btn-search">
                        Search
                    </button>
                </div>

            </form>

            <?php if ($search !== ''): ?>
                <a href="customers.php" class="btn-clear-search">
                    ← Clear search
                </a>
            <?php endif; ?>

        </div>
    </div>

    <div class="admin-card">

        <div class="card-head">
            <span class="icon">📋</span> Customer List
        </div>

        <div class="card-body" style="padding-bottom:.5rem;">
            <div class="table-responsive">

                <table class="table admin-table align-middle">

                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Total Orders</th>
                            <th>Total Spent</th>
                            <th>Last Order</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($customers)): ?>

                            <tr>
                                <td colspan="5">
                                    <div class="cust-empty">
                                        <span class="emoji">👥</span>
                                        <div class="headline">No customers found</div>
                                        <small>
                                            <?= $search !== ''
                                                ? 'Try a different search term.'
                                                : 'Customers will appear here once orders are placed.' ?>
                                        </small>
                                    </div>
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php
                            $avatarPalette = [
                                '#D8799F', '#A78BC9', '#8FBCA0',
                                '#E0A870', '#7FA9D0', '#CE8FA0'
                            ];
                            ?>

                            <?php foreach ($customers as $i => $customer): ?>

                                <?php
                                $name = (string) $customer['customer_name'];
                                $initials = '';
                                $parts = preg_split('/\s+/', trim($name));
                                if (!empty($parts[0])) {
                                    $initials .= mb_strtoupper(mb_substr($parts[0], 0, 1));
                                }
                                if (!empty($parts[1])) {
                                    $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
                                }
                                if ($initials === '') { $initials = '?'; }

                                $avatarBg = $avatarPalette[$i % count($avatarPalette)];

                                $spent = (float) $customer['total_spent'];
                                $spentClass = $spent >= 5000 ? 'high' : '';
                                ?>

                                <tr>

                                    <td data-label="Customer">
                                        <div class="customer-cell">
                                            <span class="customer-avatar" style="--avatar-bg: <?= $avatarBg ?>;">
                                                <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <div>
                                                <div class="customer-name">
                                                    <?= htmlspecialchars($customer['customer_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <div class="customer-email">
                                                    <?= htmlspecialchars($customer['customer_email'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td data-label="Contact">
                                        <?= htmlspecialchars($customer['customer_contact'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td data-label="Orders">
                                        <span class="orders-pill">
                                            <?= number_format((int) $customer['total_orders']) ?>
                                            <?= (int) $customer['total_orders'] === 1 ? 'order' : 'orders' ?>
                                        </span>
                                    </td>

                                    <td data-label="Spent">
                                        <span class="spent-amount <?= $spentClass ?>">
                                            ₱<?= number_format($spent, 2) ?>
                                        </span>
                                    </td>

                                    <td data-label="Last Order">
                                        <?= htmlspecialchars(
                                            date('M d, Y', strtotime($customer['last_order'])),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>
        </div>

        <div class="results-note">
            Showing <strong><?= count($customers) ?></strong> customer(s)
            <?php if ($search !== ''): ?>
                · matching “<?= htmlspecialchars($search) ?>”
            <?php endif; ?>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
