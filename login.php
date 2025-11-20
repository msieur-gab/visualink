<?php
// login.php - Admin login page
require_once 'config.php';

session_start();

$error = '';
$success = false;

// Check if already logged in
if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
    header('Location: /index-modern.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($password === ADMIN_PASSWORD) {
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_login_time'] = time();
        $success = true;
        header('Location: /index-modern.php');
        exit;
    } else {
        $error = 'Invalid password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Manager - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg: #ffffff;
            --fg: #09090b;
            --card-bg: #ffffff;
            --card-border: #e4e4e7;
            --muted: #71717a;
            --input-bg: #fafafa;
            --input-border: #e4e4e7;
            --accent: #09090b;
            --error: #ef4444;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #09090b;
                --fg: #fafafa;
                --card-bg: #18181b;
                --card-border: #27272a;
                --input-bg: #18181b;
                --input-border: #27272a;
            }
        }

        body {
            font-family: -apple-system, "Segoe UI", "Roboto", sans-serif;
            background-color: var(--bg);
            color: var(--fg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            transition: background-color 0.2s;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            background-color: var(--card-bg);
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .login-header p {
            color: var(--muted);
            font-size: 14px;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-weight: 500;
            font-size: 13px;
        }

        input[type="password"] {
            padding: 10px 12px;
            border: 1px solid var(--input-border);
            border-radius: 6px;
            background-color: var(--input-bg);
            color: var(--fg);
            font-size: 13px;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(9, 9, 11, 0.1);
        }

        @media (prefers-color-scheme: dark) {
            input[type="password"]:focus {
                box-shadow: 0 0 0 2px rgba(250, 250, 250, 0.1);
            }
        }

        button {
            padding: 10px 16px;
            border: 1px solid var(--card-border);
            border-radius: 6px;
            background-color: var(--fg);
            color: var(--bg);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        button:active {
            transform: translateY(0);
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .error {
            padding: 12px;
            background-color: #fee2e2;
            color: #991b1b;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        @media (prefers-color-scheme: dark) {
            .error {
                background-color: #7f1d1d;
                color: #fecaca;
            }
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--card-border);
            color: var(--muted);
            font-size: 12px;
        }

        .qr-icon {
            font-size: 32px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="qr-icon">🔐</div>
            <h1>QR Manager</h1>
            <p>Admin Login</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter admin password"
                    autofocus
                    required
                >
            </div>
            <button type="submit">Login</button>
        </form>

        <div class="login-footer">
            <p>Secure admin access to QR code management</p>
        </div>
    </div>
</body>
</html>
