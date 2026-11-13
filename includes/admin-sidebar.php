<?php
// Kuhaon ang current filename para ma-highlight ang active link
$currentPage = basename($_SERVER['PHP_SELF']);

// Helper function para sa active state
function isActive($page, $current) {
    return $page === $current ? 'active' : '';
}
?>

<style>
    /* ===== Admin Sidebar Polish ===== */
    .admin-sidebar {
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.25rem;
        padding: 1.25rem 1rem !important;
        box-shadow: 0 10px 30px rgba(117, 76, 97, 0.06);
        position: sticky;
        top: 2rem;
        animation: sidebarIn 0.5s ease both;
    }

    @keyframes sidebarIn {
        from { opacity: 0; transform: translateX(-15px); }
        to   { opacity: 1; transform: translateX(0); }
    }

    /* Brand */
    .admin-sidebar .brand-name {
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #C86691 !important;
        font-size: 1.2rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        padding: 0.5rem 0;
        border-radius: 0.75rem;
        transition: background 0.2s ease, transform 0.2s ease;
    }
    .admin-sidebar .brand-name:hover {
        background: #FFF0F6;
        transform: scale(1.02);
    }

    /* Divider */
    .admin-sidebar hr {
        border-color: #F2DCE5;
        border-style: dashed;
        opacity: 1;
        margin: 1rem 0;
    }

    /* Nav Links */
    .admin-sidebar .nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #6F6068;
        font-weight: 700;
        font-size: 0.9rem;
        padding: 0.65rem 0.85rem !important;
        border-radius: 0.85rem;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .admin-sidebar .nav-link:hover {
        background: var(--cream);
        color: var(--poppy);
        transform: translateX(3px);
    }

    /* Active state */
    .admin-sidebar .nav-link.active {
        background: linear-gradient(125deg, #FFF0F6, #FFF3D1);
        color: #C86691;
        border-color: #FCE8EF;
        box-shadow: 0 4px 12px rgba(216, 121, 159, 0.1);
    }

    /* Icon Chips */
    .admin-sidebar .nav-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        background: var(--cream);
        flex: 0 0 auto;
        transition: transform 0.2s ease;
    }
    
    .admin-sidebar .nav-link:hover .nav-icon,
    .admin-sidebar .nav-link.active .nav-icon {
        transform: scale(1.1) rotate(-4deg);
    }

    /* Individual Icon Colors */
    .icon-dash   { background: #FCE8EF; }
    .icon-prod   { background: #EFE4FA; }
    .icon-cat    { background: #FFF3D1; }
    .icon-ord    { background: #E0EEF7; }
    .icon-cust   { background: #E7F4EC; }

    /* Logout Button */
    .admin-sidebar .logout-link {
        color: #9A2D4B;
        margin-top: 0.5rem;
    }
    .admin-sidebar .logout-link:hover {
        background: #FBE4E9;
        color: #9A2D4B;
    }
    .admin-sidebar .logout-link .nav-icon {
        background: #FBE4E9;
        color: #9A2D4B;
    }

    /* Mobile adjustments */
    @media (max-width: 991px) {
        .admin-sidebar {
            position: static;
            margin-bottom: 1.5rem;
        }
        .admin-sidebar .nav.flex-column {
            flex-direction: row !important;
            flex-wrap: wrap;
            gap: 0.5rem !important;
        }
        .admin-sidebar .nav-link {
            flex: 1 1 auto;
            justify-content: center;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.85rem;
        }
        .admin-sidebar .logout-link {
            flex: 1 1 100%;
            margin-top: 0.5rem;
        }
    }
</style>

<aside class="admin-sidebar p-3">

    <a class="brand-name text-decoration-none" href="dashboard.php">
        ✿ Poppy &amp; Paper
    </a>

    <hr>

    <nav class="nav flex-column gap-2">

        <a class="nav-link <?= isActive('dashboard.php', $currentPage) ?>" href="dashboard.php">
            <span class="nav-icon icon-dash">📊</span> Dashboard
        </a>

        <a class="nav-link <?= isActive('products.php', $currentPage) ?>" href="products.php">
            <span class="nav-icon icon-prod">🌸</span> Products
        </a>

        <a class="nav-link <?= isActive('categories.php', $currentPage) ?>" href="categories.php">
            <span class="nav-icon icon-cat">🏷️</span> Categories
        </a>

        <a class="nav-link <?= isActive('orders.php', $currentPage) ?>" href="orders.php">
            <span class="nav-icon icon-ord">🧾</span> Orders
        </a>

        <a class="nav-link <?= isActive('customers.php', $currentPage) ?>" href="customers.php">
            <span class="nav-icon icon-cust">👥</span> Customers
        </a>

        <hr class="my-2">

        <a class="nav-link logout-link" href="logout.php">
            <span class="nav-icon">🚪</span> Log out
        </a>

    </nav>

</aside>