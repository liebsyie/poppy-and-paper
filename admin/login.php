
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Admin login | Poppy & Paper';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {

        try {
            $stmt = $pdo->prepare("
                SELECT admin_id, username, password_hash
                FROM admin_users
                WHERE username = :username
                LIMIT 1
            ");

            $stmt->execute(['username' => $username]);

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify(
                $password,
                $admin['password_hash']
            )) {

                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];

                header('Location: dashboard.php');
                exit;

            } else {
                $error = 'Invalid username or password.';
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Unable to process your login. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .admin-login-wrap {
        min-height: calc(100vh - 200px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
        position: relative;
        isolation: isolate;
        animation: fadeUp .6s ease both;
    }

    .admin-login-wrap::before,
    .admin-login-wrap::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        opacity: .5;
        z-index: -1;
        animation: floaty 10s ease-in-out infinite;
    }
    .admin-login-wrap::before {
        width: 320px; height: 320px;
        background: #F7C9D9;
        top: -60px; left: -80px;
    }
    .admin-login-wrap::after {
        width: 340px; height: 340px;
        background: #D9C9F3;
        bottom: -100px; right: -80px;
        animation-delay: -5s;
    }
    @keyframes floaty {
        0%, 100% { transform: translateY(0) scale(1); }
        50%      { transform: translateY(-20px) scale(1.05); }
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .admin-login-card {
        width: 100%;
        max-width: 440px;
        background: #fff;
        border: 1px solid #F2DCE5;
        border-radius: 1.5rem;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(117, 76, 97, .12);
        animation: fadeUp .6s ease .05s both;
    }

    .admin-login-head {
        background: linear-gradient(125deg, #FCE0E9, #FFF0C9 60%, #E8DDF8);
        padding: 2rem 1.75rem 1.5rem;
        text-align: center;
        position: relative;
    }

    .lock-badge {
        width: 62px;
        height: 62px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 8px 20px rgba(117, 76, 97, .15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        animation: popIn .5s cubic-bezier(.34, 1.56, .64, 1) both .2s;
    }
    @keyframes popIn {
        0%   { transform: scale(0); opacity: 0; }
        70%  { transform: scale(1.12); }
        100% { transform: scale(1); opacity: 1; }
    }

    .admin-login-head h1 {
        font-family: 'Quicksand', 'Fredoka', sans-serif;
        color: #754C61;
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: .25rem;
        letter-spacing: -.01em;
    }
    .admin-login-head p {
        color: #8C7B84;
        font-size: .88rem;
        font-weight: 600;
        margin: 0;
    }

    .admin-login-body {
        padding: 1.5rem 1.75rem 1.75rem;
    }

    .login-error {
        background: #FBE4E9;
        color: #9A2D4B;
        border: 1px solid #F6CBD5;
        border-radius: .9rem;
        padding: .8rem 1rem;
        font-weight: 700;
        font-size: .9rem;
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: 1.15rem;
        animation: shake .4s ease both;
    }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25%      { transform: translateX(-5px); }
        75%      { transform: translateX(5px); }
    }

    .login-field {
        margin-bottom: 1rem;
        position: relative;
    }
    .login-field .form-label {
        font-weight: 800;
        font-size: .8rem;
        color: #85566D;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: .35rem;
    }
    .login-field .form-control {
        border-radius: 1rem;
        border: 1.5px solid #F2DCE5;
        background: var(--cream);
        padding: .75rem 1rem .75rem 2.6rem;
        font-weight: 600;
        color: var(--ink);
        transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
    }
    .login-field .form-control::placeholder {
        color: #B3A6AC;
        font-weight: 500;
    }
    #password.form-control {
        padding-right: 2.8rem;
    }
    .login-field .form-control:focus {
        border-color: var(--poppy);
        background: #fff;
        box-shadow: 0 0 0 .22rem rgba(216, 121, 159, .18);
    }
    .login-field .input-shell {
        position: relative;
    }
    .login-field .field-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: .95rem;
        opacity: .6;
        pointer-events: none;
    }

    .pw-toggle {
        position: absolute;
        right: .75rem;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: 0;
        font-size: .95rem;
        cursor: pointer;
        padding: .3rem;
        border-radius: .5rem;
        color: var(--muted);
        transition: background .2s ease, color .2s ease;
    }
    .pw-toggle:hover {
        background: var(--cream);
        color: var(--poppy);
    }

    .btn-sign-in {
        background: var(--poppy);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: .85rem 1.25rem;
        font-weight: 800;
        font-size: 1rem;
        width: 100%;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .btn-sign-in:hover {
        background: #C86691;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(216, 121, 159, .35);
    }
    .btn-sign-in:disabled {
        background: #E9DEE3;
        color: #A998A0;
        box-shadow: none;
        transform: none;
        cursor: not-allowed;
    }

    .link-store {
        display: flex;
        justify-content: center;
        margin-top: 1rem;
        font-size: .88rem;
        font-weight: 700;
        color: var(--muted);
        text-decoration: none;
        transition: color .2s ease, gap .2s ease;
        gap: .25rem;
    }
    .link-store:hover {
        color: var(--poppy);
        gap: .55rem;
    }

    .admin-login-note {
        text-align: center;
        font-size: .74rem;
        color: #B3A6AC;
        margin-top: 1.25rem;
    }

    @media (max-width: 576px) {
        .admin-login-body { padding: 1.25rem 1.25rem 1.5rem; }
        .admin-login-head { padding: 1.75rem 1.25rem 1.25rem; }
        .lock-badge { width: 56px; height: 56px; font-size: 1.5rem; }
        .admin-login-head h1 { font-size: 1.25rem; }
    }
</style>

<main class="admin-login-wrap">

    <div class="admin-login-card">

        <div class="admin-login-head">

            <div class="lock-badge">🔐</div>

            <h1>Admin sign in</h1>
            <p>Welcome back to Poppy &amp; Paper</p>

        </div>

        <div class="admin-login-body">

            <?php if ($error): ?>
                <div class="login-error">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">

                <div class="login-field">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-shell">
                        <span class="field-icon">👤</span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            autocomplete="username"
                            placeholder="Enter your username"
                            required
                        >
                    </div>
                </div>

                <div class="login-field">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-shell">
                        <span class="field-icon">🔑</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            required
                        >
                        <button
                            type="button"
                            class="pw-toggle"
                            id="pwToggle"
                            aria-label="Show password"
                            title="Show password"
                        >
                            👁️
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-sign-in" id="signInBtn">
                    Sign In
                </button>

                <a href="../index.php" class="link-store">
                    ← Back to Store
                </a>

            </form>

            <p class="admin-login-note">
                🔒 Secure access · Authorized personnel only
            </p>

        </div>

    </div>

</main>

<script>
    (function () {
        const toggle = document.getElementById('pwToggle');
        const pw = document.getElementById('password');
        if (!toggle || !pw) return;

        toggle.addEventListener('click', () => {
            const showing = pw.type === 'text';
            pw.type = showing ? 'password' : 'text';
            toggle.textContent = showing ? '👁️' : '🙈';
            toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            toggle.setAttribute('title', showing ? 'Show password' : 'Hide password');
            pw.focus();
        });
    })();

    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('signInBtn');
        btn.disabled = true;
        btn.textContent = 'Signing in…';
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
