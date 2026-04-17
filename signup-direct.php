<?php
// Signup page WITHOUT API - Direct PHP form submission
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard-direct.php');
    exit;
}

require_once 'config/database.php';

$db = getDB();
$error = '';
$success = '';

// Get crops for dropdown
$crops = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT id, name, variety FROM crops ORDER BY name");
        $crops = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
} else {
    $error = 'Database connection failed. Please import the SQL file first.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $preferred_crop_id = $_POST['preferred_crop_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($phone) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Please enter a valid 10-digit phone number';
    } else {
        try {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $db->prepare("INSERT INTO users (name, phone, password, village, district, preferred_crop_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $hashedPassword, $village, $district, $preferred_crop_id]);
            
            $userId = $db->lastInsertId();
            
            // Set session
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_phone'] = $phone;
            
            // Redirect to dashboard
            header('Location: dashboard-direct.php');
            exit;
            
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Phone number already registered. Please login.';
            } else {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Smart Mandi Intelligence</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box" style="max-width: 500px;">
            <div class="auth-header">
                <span class="logo-icon">🌾</span>
                <h1>Join Smart Mandi</h1>
                <p>Create your farmer account</p>
                <p class="hindi">किसान खाता बनाएं</p>
            </div>

            <?php if ($error): ?>
                <div class="db-error" style="background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message" style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name / पूरा नाम *</label>
                        <input type="text" id="name" name="name" placeholder="Your name" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone / फोन *</label>
                        <input type="tel" id="phone" name="phone" placeholder="9876543210" pattern="[0-9]{10}" required
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="village">Village / गांव</label>
                        <input type="text" id="village" name="village" placeholder="Your village"
                               value="<?php echo htmlspecialchars($_POST['village'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="district">District / जिला</label>
                        <input type="text" id="district" name="district" placeholder="Your district"
                               value="<?php echo htmlspecialchars($_POST['district'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="preferred_crop">Preferred Crop / पसंदीदा फसल</label>
                    <select id="preferred_crop" name="preferred_crop_id">
                        <option value="">Select crop</option>
                        <?php foreach ($crops as $crop): ?>
                            <option value="<?php echo $crop['id']; ?>" <?php echo ($_POST['preferred_crop_id'] ?? '') == $crop['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($crop['name'] . ' (' . $crop['variety'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password / पासवर्ड *</label>
                    <input type="password" id="password" name="password" placeholder="Min 6 characters" minlength="6" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password / पासवर्ड की पुष्टि *</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" <?php echo !$db ? 'disabled' : ''; ?>>
                    <?php echo !$db ? 'Database Not Ready' : 'Create Account / खाता बनाएं'; ?>
                </button>
            </form>

            <div class="auth-links">
                <p>Already have an account? <a href="login-direct.php">Login / लॉगिन</a></p>
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
            max-width: 500px;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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

        .auth-form input,
        .auth-form select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .auth-form input:focus,
        .auth-form select:focus {
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

        .db-error a, .success-message a {
            color: inherit;
            text-decoration: underline;
            font-weight: bold;
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

        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
