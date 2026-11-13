<?php
$pageTitle = $pageTitle ?? 'Poppy & Paper';

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$inAdmin = str_contains($scriptPath, '/admin/');
$assetBase = $inAdmin ? '../' : '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="Poppy & Paper — lovely stationery for everyday moments.">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Fraunces for titles, Nunito for reading, Quicksand as a soft fallback -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,620;9..144,700&family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Theme -->
    <link href="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>assets/css/style.css" rel="stylesheet">

    <style>
        /* ===== Global theme polish ===== */
        :root {
            --petal-pink: #F7C9D9;
            --butter-yellow: #FFE6A7;
            --lavender: #D9C9F3;
            --sage: #CDE5D1;
            --cream: #FFF8ED;
            --ink: #51434A;
            --muted: #81747A;
            --poppy: #D8799F;
        }

        html { scroll-behavior: smooth; }

        body {
            background: var(--cream);
            color: var(--ink);
            font-family: 'Nunito', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4, h5, h6,
        .brand-name, .section-title, .card-head, .summary-head {
            font-family: 'Fraunces', 'Quicksand', Georgia, serif;
            letter-spacing: -0.02em;
            font-weight: 620;
        }

        /* Selection color */
        ::selection {
            background: var(--petal-pink);
            color: var(--ink);
        }

        /* Focus ring for accessibility */
        :focus-visible {
            outline: 3px solid var(--poppy);
            outline-offset: 2px;
            border-radius: .35rem;
        }

        /* Respect reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .001ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>
<body>