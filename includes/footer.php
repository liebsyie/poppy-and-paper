<footer class="site-footer mt-5">
    <div class="container">
        
        <!-- Main Footer Content -->
        <div class="footer-content text-center py-5">
            
            <!-- Brand -->
            <a href="<?= htmlspecialchars($assetBase ?? '', ENT_QUOTES, 'UTF-8') ?>index.php" class="footer-brand text-decoration-none">
                <span class="brand-icon">✿</span> Poppy &amp; Paper
            </a>
            
            <!-- Tagline -->
            <p class="footer-tagline mt-2 mb-4">
                Little things, lovely days. <span class="heart-beat" aria-hidden="true">♡</span>
            </p>

            <!-- Mini Navigation -->
            <div class="footer-links d-flex justify-content-center flex-wrap gap-3 mb-4">
                <a href="<?= htmlspecialchars($assetBase ?? '', ENT_QUOTES, 'UTF-8') ?>index.php">Home</a>
                <span class="dot-sep">•</span>
                <a href="<?= htmlspecialchars($assetBase ?? '', ENT_QUOTES, 'UTF-8') ?>shop.php">Shop</a>
                <span class="dot-sep">•</span>
                <a href="<?= htmlspecialchars($assetBase ?? '', ENT_QUOTES, 'UTF-8') ?>cart.php">Cart</a>
                <span class="dot-sep">•</span>
                <a href="<?= htmlspecialchars($assetBase ?? '', ENT_QUOTES, 'UTF-8') ?>admin/login.php">Admin</a>
            </div>

            <!-- Divider -->
            <hr class="footer-divider">

            <p class="footer-contact mb-2">
                Fictional contact only · hello@poppyandpaper.example · +63 900 000 0000 · Cebu City
            </p>
            <div class="footer-links d-flex justify-content-center flex-wrap gap-3 mb-3">
                <a href="https://instagram.com" target="_blank" rel="noopener">Instagram</a>
                <span class="dot-sep">•</span>
                <a href="https://facebook.com" target="_blank" rel="noopener">Facebook</a>
            </div>
            <p class="footer-fineprint mb-0">
                Sample social links and contact details are fictional and are not a real business.
            </p>

        </div>

    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="back-to-top" aria-label="Back to top">
    ↑
</button>

<style>
    /* ===== Footer Polish ===== */
    .site-footer {
        background: linear-gradient(to bottom, #FFF8ED, #FCE8EF);
        border-top: 1px solid #F2DCE5;
        color: var(--ink);
        position: relative;
        overflow: hidden;
    }

    /* Decorative background flower */
    .site-footer::before {
        content: "✿";
        position: absolute;
        bottom: -30px;
        right: -20px;
        font-size: 150px;
        color: #FFF0F6;
        opacity: 0.5;
        z-index: 0;
        pointer-events: none;
    }

    .footer-content {
        position: relative;
        z-index: 1;
    }

    .footer-brand {
        font-family: 'Fraunces', 'Quicksand', serif;
        color: #C86691;
        font-size: 1.4rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: transform 0.3s ease;
    }
    
    .footer-brand:hover {
        transform: scale(1.05);
        color: #D8799F;
    }

    .brand-icon {
        font-size: 1.2rem;
        color: #D8799F;
    }

    .footer-tagline {
        color: #81747A;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .heart-beat {
        display: inline-block;
        color: #D8799F;
        animation: heartBeat 1.5s ease-in-out infinite;
    }

    @keyframes heartBeat {
        0%, 100% { transform: scale(1); }
        15% { transform: scale(1.15); }
        30% { transform: scale(1); }
        45% { transform: scale(1.15); }
    }

    /* Footer Links */
    .footer-links a {
        color: var(--ink);
        text-decoration: none;
        font-weight: 700;
        font-size: 0.85rem;
        transition: color 0.2s ease;
    }

    .footer-links a:hover {
        color: var(--poppy);
    }

    .dot-sep {
        color: #D7C7CF;
        font-size: 0.8rem;
    }

    .footer-divider {
        border: 0;
        border-top: 1px dashed #F2DCE5;
        max-width: 280px;
        margin: 1.5rem auto;
        opacity: 1;
    }

    .footer-contact,
    .footer-fineprint {
        color: #A998A0;
        font-size: 0.78rem;
        font-weight: 600;
    }

    /* ===== Back to Top Button ===== */
    .back-to-top {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #fff;
        color: var(--poppy);
        border: 1.5px solid #F2DCE5;
        font-size: 1.2rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(117, 76, 97, 0.1);
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s ease;
        z-index: 999;
    }

    .back-to-top.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .back-to-top:hover {
        background: var(--poppy);
        color: #fff;
        border-color: var(--poppy);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(216, 121, 159, 0.3);
    }

    @media (max-width: 576px) {
        .back-to-top {
            bottom: 1.25rem;
            right: 1.25rem;
            width: 38px;
            height: 38px;
            font-size: 1rem;
        }
    }
</style>

<script>
    // Back to Top Logic
    document.addEventListener('DOMContentLoaded', () => {
        const backToTop = document.getElementById('backToTop');
        
        if (backToTop) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    backToTop.classList.add('show');
                } else {
                    backToTop.classList.remove('show');
                }
            });

            backToTop.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    });
</script>

<!-- Bootstrap & Main JS (Keep these at the bottom) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= htmlspecialchars($assetBase ?? '', ENT_QUOTES, 'UTF-8') ?>assets/js/main.js"></script>
</body>
</html>