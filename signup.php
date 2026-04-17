<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/database.php';

$db = getDB();
$dbError = '';
$crops = [];

if (!$db) {
    $dbError = 'Database connection failed. Please run <a href="setup.php">setup.php</a> first.';
} else {
    try {
        $stmt = $db->query("SELECT id, name, variety FROM crops ORDER BY name");
        $crops = $stmt->fetchAll();
    } catch(PDOException $e) {
        $dbError = 'Database error: ' . $e->getMessage() . '. Please run <a href="setup.php">setup.php</a> first.';
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

            <?php if ($dbError): ?>
                <div class="db-error" style="background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    ⚠️ <?php echo $dbError; ?>
                </div>
            <?php endif; ?>

            <form id="signupForm" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name / पूरा नाम *</label>
                        <input type="text" id="name" name="name" placeholder="Your name" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone / फोन *</label>
                        <input type="tel" id="phone" name="phone" placeholder="9876543210" pattern="[0-9]{10}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="village">Village / गांव</label>
                        <input type="text" id="village" name="village" placeholder="Your village">
                    </div>
                    <div class="form-group">
                        <label for="district">District / जिला</label>
                        <input type="text" id="district" name="district" placeholder="Your district">
                    </div>
                </div>

                <div class="form-group">
                    <label for="preferred_crop">Preferred Crop / पसंदीदा फसल</label>
                    <select id="preferred_crop" name="preferred_crop">
                        <option value="">Select crop</option>
                        <?php foreach ($crops as $crop): ?>
                            <option value="<?php echo $crop['id']; ?>"><?php echo $crop['name']; ?> (<?php echo $crop['variety']; ?>)</option>
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

                <button type="submit" class="btn btn-primary btn-block" <?php echo $dbError ? 'disabled' : ''; ?>>
                    <?php echo $dbError ? 'Database Not Ready' : 'Create Account / खाता बनाएं'; ?>
                </button>
                
                <div id="errorMsg" class="error-message"></div>
            </form>

            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login / लॉगिन</a></p>
                <a href="index.php" class="back-link">← Back to Home</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('signupForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const errorDiv = document.getElementById('errorMsg');
            errorDiv.textContent = '';
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                errorDiv.textContent = '❌ Passwords do not match!';
                return;
            }
            
            if (password.length < 6) {
                errorDiv.textContent = '❌ Password must be at least 6 characters';
                return;
            }

            const formData = {
                name: document.getElementById('name').value.trim(),
                phone: document.getElementById('phone').value.trim(),
                village: document.getElementById('village').value.trim(),
                district: document.getElementById('district').value.trim(),
                preferred_crop: document.getElementById('preferred_crop').value,
                password: password
            };
            
            // Show loading
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating Account...';

            try {
                const response = await fetch('api/auth.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    const text = await response.text();
                    console.error('Server response:', text);
                    throw new Error('Server returned invalid JSON. Check that setup.php has been run.');
                }

                if (data.success) {
                    alert('✅ Registration successful! Welcome to Smart Mandi!');
                    window.location.href = 'dashboard.php';
                } else {
                    errorDiv.textContent = '❌ ' + (data.message || 'Registration failed');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                errorDiv.innerHTML = '❌ ' + error.message + '<br><small>Make sure you have run <a href="setup.php">setup.php</a> first</small>';
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    </script>

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

        .error-message {
            color: #dc2626;
            text-align: center;
            margin-top: 15px;
            font-size: 0.95rem;
            min-height: 24px;
        }
        
        .error-message a {
            color: #dc2626;
            text-decoration: underline;
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .db-error a {
            color: #dc2626;
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
