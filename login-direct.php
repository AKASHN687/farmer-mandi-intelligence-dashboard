<?php
// Login page WITHOUT API - Direct PHP form submission
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard-direct.php');
    exit;
}

require_once 'config/database.php';

$db = getDB();
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($phone) || empty($password)) {
        $error = 'Please enter phone and password';
    } else {
        try {
            $stmt = $db->prepare("SELECT id, name, phone, password, village, district FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_phone'] = $user['phone'];
                
                // Redirect to dashboard
                header('Location: dashboard-direct.php');
                exit;
            } else {
                $error = 'Invalid phone number or password';
            }
        } catch(PDOException $e) {
            $error = 'Login failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Mandi Intelligence</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <span class="logo-icon">🌾</span>
                <h1>Welcome Back, Kisan!</h1>
                <p>Login to check mandi prices</p>
                <p class="hindi">मंडी कीमतों की जांच के लिए लॉगिन करें</p>
            </div>

            <?php if ($error): ?>
                <div class="db-error" style="background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="phone">Phone Number / फोन नंबर</label>
                    <input type="tel" id="phone" name="phone" placeholder="9876543210" required
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password / पासवर्ड</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" <?php echo !$db ? 'disabled' : ''; ?>>
                    <?php echo !$db ? 'Database Not Ready' : 'Login / लॉगिन'; ?>
                </button>
            </form>

            <div class="auth-links">
                <p>Don't have an account? <a href="signup-direct.php">Sign up / पंजीकरण करें</a></p>
                <p style="margin-top: 10px; font-size: 0.9rem;">
                    <strong>Demo:</strong> Phone: <code>9876543210</code>, Password: <code>password</code>
                </p>
                <a href="index.php" class="back-link">← Back to Home</a>
            </div>
        </div>
    </div>

    <style>
        .auth-page {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
        }

        .auth-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            font-size: 60px;
            display: block;
            margin-bottom: 15px;
        }

        .auth-header h1 {
            color: #166534;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .auth-header p {
            color: #65a30d;
        }

        .hindi {
            color: #a3a3a3;
            font-size: 0.95rem;
        }

        .auth-form .form-group {
            margin-bottom: 20px;
        }

        .auth-form label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .auth-form input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .auth-form input:focus {
            outline: none;
            border-color: #10b981;
        }

        .btn-block {
            width: 100%;
            padding: 16px;
            font-size: 1.1rem;
            margin-top: 10px;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .db-error {
            color: #dc2626;
        }

        .auth-links {
            text-align: center;
            margin-top: 25px;
        }

        .auth-links p {
            color: #6b7280;
            margin-bottom: 15px;
        }

        .auth-links a {
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link {
            color: #9ca3af !important;
            font-weight: 400 !important;
        }

        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
    </style>
</body>
</html>
