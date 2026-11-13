<?php
// Siguroa nga naay session para mo-gana ang cart count
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Count total items sa cart
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    $cartCount = array_sum($_SESSION['cart']);
}
?>

<style>
    /* ===== Navbar Polish ===== */
    .site-navbar {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid #F2DCE5;
        transition: box-shadow 0.3s ease, background 0.3s ease;
        padding: 0.8rem 0;
    }
    
    /* Shadow inig scroll (optional JS muniubos) */
    .site-navbar.scrolled {
        box-shadow: 0 4px 20px rgba(117, 76, 97, 0.08);
        background: rgba(255, 255, 255, 0.95);
    }

    .brand-name {
        font-family: 'Fraunces', 'Quicksand', serif;
        color: #C86691 !important;
        font-size: 1.4rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        transition: transform 0.3s ease;
    }
    .brand-name:hover {
        transform: scale(1.02);
    }

    /* Nav Links */
    .site-navbar .nav-link {
        color: var(--ink);
        font-weight: 700;
        font-size: 0.95rem;
        padding: 0.5rem 1rem !important;
        border-radius: 999px;
        position: relative;
        transition: color 0.2s ease, background 0.2s ease;
    }
    
    .site-navbar .nav-link:hover,
    .site-navbar .nav-link.active {
        color: var(--poppy);
        background: #FFF0F6;
    }

    /* Cart Link with Badge */
    .cart-link {
        display: flex !important;
        align-items: center;
        gap: 0.4rem;
    }
    
    .cart-badge {
        background: var(--poppy);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 800;
        min-width: 20px;
        height: 20px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 6px;
        box-shadow: 0 2px 6px rgba(216, 121, 159, 0.4);
        animation: popIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Admin Link distinct styling */
    .nav-link.admin-link {
        font-size: 0.85rem;
        color: var(--muted);
        border: 1px solid #F2DCE5;
    }
    .nav-link.admin-link:hover {
        border-color: var(--poppy);
        background: transparent;
    }

    /* Mobile Toggler */
    .navbar-toggler {
        border: none;
        padding: 0.4rem;
    }
    .navbar-toggler:focus {
        box-shadow: none;
        outline: 2px solid var(--poppy);
        outline-offset: 2px;
        border-radius: 0.5rem;
    }

    @keyframes popIn {
        0% { transform: scale(0); }
        70% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    /* Adjust spacing on mobile */
    @media (max-width: 420px) {
        .brand-name { font-size: 1.15rem !important; }
    }

    @media (max-width: 991px) {
        .site-navbar .navbar-collapse {
            background: #fff;
            border: 1px solid #F2DCE5;
            border-radius: 1rem;
            padding: 1rem;
            margin-top: 0.8rem;
            box-shadow: 0 10px 25px rgba(117, 76, 97, 0.08);
        }
        .site-navbar .nav-link {
            padding: 0.7rem 1rem !important;
            border-radius: 0.75rem;
        }
        .nav-link.admin-link {
            margin-top: 0.5rem;
            text-align: center;
            justify-content: center;
            display: flex;
        }
    }
</style>

<?php $navPage = basename($_SERVER['PHP_SELF'] ?? ''); ?>
<nav class="navbar navbar-expand-lg navbar-light site-navbar sticky-top" id="mainNavbar">
    <div class="container">
        
        <!-- Brand -->
        <a class="navbar-brand brand-name" href="index.php">
            ✿ Poppy &amp; Paper
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Links -->
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                
                <li class="nav-item">
                    <a class="nav-link <?= $navPage === 'index.php' ? 'active' : '' ?>" href="index.php">Home</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= in_array($navPage, ['shop.php', 'product.php'], true) ? 'active' : '' ?>" href="shop.php">Shop</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link cart-link <?= $navPage === 'cart.php' ? 'active' : '' ?>" href="cart.php">
                        ♡ Cart
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-item ms-lg-3">
                    <a class="nav-link admin-link" href="admin/login.php">
                        Admin
                    </a>
                </li>

            </ul>
        </div>
        
    </div>
</nav>

<!-- Optional: Add shadow on scroll -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const navbar = document.getElementById('mainNavbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 10) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    });
</script>