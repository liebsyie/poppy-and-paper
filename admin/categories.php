
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Categories | Poppy & Paper';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$message = $_SESSION['category_success'] ?? '';
$error = $_SESSION['category_error'] ?? '';
unset($_SESSION['category_success'], $_SESSION['category_error']);

$form = [
    'category_id' => '',
    'category_name' => ''
];

if (isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($editId) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM categories
            WHERE category_id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $editId]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($category) {
            $form = $category;
        } else {
            $error = 'Category not found.';
        }
    }
}

if (isset($_GET['added'])) {
    $message = 'Category successfully added!';
} elseif (isset($_GET['updated'])) {
    $message = 'Category successfully updated!';
} elseif (isset($_GET['deleted'])) {
    $message = 'Category successfully deleted!';
}

$stmt = $pdo->query("
    SELECT
        c.category_id,
        c.category_name,
        c.created_at,
        COUNT(p.product_id) AS product_count
    FROM categories c
    LEFT JOIN products p
        ON c.category_id = p.category_id
    GROUP BY
        c.category_id,
        c.category_name,
        c.created_at
    ORDER BY c.category_name ASC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .cat-page { animation: fadeUp .6s ease both; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .cat-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.5rem;
        padding: 1.75rem;
        background: linear-gradient(125deg, #FCE0E9 0%, #FFF0C9 60%, #E8DDF8 100%);
        margin-bottom: 1.5rem;
        isolation: isolate;
    }
    .cat-hero::before,
    .cat-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        opacity: .5;
        z-index: -1;
        animation: floaty 9s ease-in-out infinite;
    }
    .cat-hero::before {
        width: 200px; height: 200px;
        background: #F7C9D9;
        top: -70px; left: -60px;
    }
    .cat-hero::after {
        width: 220px; height: 220px;
        background: #D9C9F3;
        bottom: -90px; right: -60px;
        animation-delay: -4.5s;
    }
    @keyframes floaty {
        0%, 100% { transform: translateY(0) scale(1); }
        50%      { transform: translateY(-18px) scale(1.05); }
    }

    .cat-hero .eyebrow {
        letter-spacing: .16em;
        color: #A65E7D;
    }
    .cat-hero h1 {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #754C61;
        font-weight: 700;
        letter-spacing: -.015em;
        font-size: 1.65rem;
        margin-bottom: .2rem;
    }
    .cat-hero p {
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

    .inline-action {
        display: flex;
        align-items: flex-end;
        gap: .75rem;
    }
    .inline-field {
        flex: 1 1 auto;
        min-width: 0;
    }
    .inline-field .form-control {
        height: 48px;
    }
    .inline-buttons {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex: 0 0 auto;
    }
    .inline-buttons .btn-save,
    .inline-buttons .btn-cancel {
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }

    .btn-save {
        background: var(--poppy);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: .75rem 1.4rem;
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
        padding: .7rem 1.2rem;
        font-weight: 700;
        transition: border-color .2s ease, color .2s ease;
    }
    .btn-cancel:hover {
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
        padding: .8rem 1rem;
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
    .admin-table th:nth-child(2),
    .admin-table td:nth-child(2) {
        width: 100%;
        white-space: normal;
    }
    .admin-table tbody tr {
        transition: background .2s ease;
    }
    .admin-table tbody tr:hover {
        background: #FFFBF5;
    }

    .cat-name-cell {
        font-weight: 800;
        color: #85566D;
    }

    .id-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 26px;
        padding: 0 .5rem;
        border-radius: 999px;
        background: var(--cream);
        border: 1px solid #F2DCE5;
        font-size: .75rem;
        font-weight: 800;
        color: var(--muted);
    }

    .count-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-weight: 800;
        font-size: .78rem;
        padding: .3rem .7rem;
        border-radius: 999px;
        background: #EFE4FA;
        color: #6C4A9C;
    }
    .count-pill.zero {
        background: #F2ECEE;
        color: #81747A;
    }
    .count-pill::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        opacity: .85;
    }

    .action-group {
        display: flex;
        gap: .35rem;
        flex-wrap: nowrap;
        justify-content: flex-end;
    }
    .btn-action {
        border-radius: 999px;
        padding: .35rem .85rem;
        font-weight: 800;
        font-size: .75rem;
        border: 1.5px solid transparent;
        transition: transform .2s ease, background .2s ease, color .2s ease, box-shadow .2s ease;
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
    .btn-action.delete {
        background: #FBE4E9;
        color: #9A2D4B;
        border-color: #F6CBD5;
    }
    .btn-action.delete:hover:not(:disabled) {
        background: #F6CBD5;
    }
    .btn-action.delete:disabled {
        opacity: .55;
        cursor: not-allowed;
        transform: none;
    }

    .cat-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--muted);
    }
    .cat-empty .emoji {
        font-size: 2.5rem;
        display: block;
        margin-bottom: .5rem;
        opacity: .7;
    }
    .cat-empty .headline {
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
        .cat-hero { padding: 1.25rem; border-radius: 1.15rem; }
        .cat-hero h1 { font-size: 1.35rem; }
        .admin-card .card-body { padding: 1rem; }
        .admin-card .card-head { padding: .85rem 1rem; font-size: .98rem; }
        .admin-table thead th,
        .admin-table tbody td { padding: .7rem .75rem; font-size: .82rem; }
        .btn-action { font-size: .7rem; padding: .3rem .65rem; }
        .inline-action { flex-direction: column; align-items: stretch; }
        .inline-buttons .btn-save,
        .inline-buttons .btn-cancel { width: 100%; }
    }
</style>

<main class="container py-4 py-md-5 cat-page">

    <div class="cat-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <p class="eyebrow mb-1">ORGANIZATION</p>
            <h1 class="mb-1">Category Management</h1>
            <p>Organize your Poppy &amp; Paper product categories.</p>
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
            <span class="icon"><?= !empty($form['category_id']) ? '✏️' : '🏷️' ?></span>
            <?= !empty($form['category_id']) ? 'Edit Category' : 'Add New Category' ?>
        </div>
        <div class="card-body">

            <form method="POST" action="actions/category-actions.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken) ?>"
                >

                <input type="hidden" name="action" value="save">

                <input
                    type="hidden"
                    name="category_id"
                    value="<?= htmlspecialchars((string) $form['category_id']) ?>"
                >

                <div class="inline-action">

                    <div class="inline-field">
                        <label class="form-label">Category Name</label>

                        <input
                            type="text"
                            name="category_name"
                            class="form-control"
                            maxlength="100"
                            placeholder="e.g. Writing"
                            value="<?= htmlspecialchars($form['category_name']) ?>"
                            required
                        >
                    </div>

                    <div class="inline-buttons">
                        <button type="submit" class="btn btn-save">
                            <?= !empty($form['category_id']) ? '💾 Save Changes' : '🏷️ Add Category' ?>
                        </button>

                        <?php if (!empty($form['category_id'])): ?>
                            <a href="categories.php" class="btn btn-cancel">
                                Cancel
                            </a>
                        <?php endif; ?>
                    </div>

                </div>

            </form>

        </div>
    </div>

    <div class="admin-card">

        <div class="card-head">
            <span class="icon">📋</span> Category List
        </div>

        <div class="card-body" style="padding-bottom:.5rem;">
            <div class="table-responsive">

                <table class="table admin-table align-middle">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Products</th>
                            <th>Date Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($categories)): ?>

                            <tr>
                                <td colspan="5">
                                    <div class="cat-empty">
                                        <span class="emoji">🏷️</span>
                                        <div class="headline">No categories yet</div>
                                        <small>Add your first category above.</small>
                                    </div>
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($categories as $category): ?>

                                <?php $count = (int) $category['product_count']; ?>

                                <tr>

                                    <td>
                                        <span class="id-chip">
                                            #<?= (int) $category['category_id'] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="cat-name-cell">
                                            <?= htmlspecialchars($category['category_name']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="count-pill <?= $count === 0 ? 'zero' : '' ?>">
                                            <?= $count ?> <?= $count === 1 ? 'product' : 'products' ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= date(
                                            'M d, Y',
                                            strtotime($category['created_at'])
                                        ) ?>
                                    </td>

                                    <td class="text-end">
                                        <div class="action-group">

                                            <a
                                                href="categories.php?edit=<?= (int) $category['category_id'] ?>"
                                                class="btn-action edit"
                                            >
                                                ✏️ Edit
                                            </a>

                                            <form
                                                method="POST"
                                                action="actions/category-actions.php"
                                                onsubmit="return confirm('Are you sure you want to delete this category?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= htmlspecialchars($csrfToken) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="category_id"
                                                    value="<?= (int) $category['category_id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn-action delete"
                                                    <?= $count > 0 ? 'disabled title="This category has assigned products."' : '' ?>
                                                >
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
            Total categories: <strong><?= count($categories) ?></strong>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
