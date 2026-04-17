<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
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

            <form id="loginForm" class="auth-form">
                <div class="form-group">
                    <label for="phone">Phone Number / फोन नंबर</label>
                    <input type="tel" id="phone" name="phone" placeholder="+91 9876543210" required>
                </div>

                <div class="form-group">
                    <label for="password">Password / पासवर्ड</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login / लॉगिन</button>
                
                <div id="errorMsg" class="error-message"></div>
            </form>

            <div class="auth-links">
                <p>Don't have an account? <a href="signup.php">Sign up / पंजीकरण करें</a></p>
                <a href="index.php" class="back-link">← Back to Home</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const errorDiv = document.getElementById('errorMsg');
            errorDiv.textContent = '';
            
            const formData = {
                phone: document.getElementById('phone').value.trim(),
                password: document.getElementById('password').value
            };
            
            // Show loading
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in...';

            try {
                const response = await fetch('api/auth.php?action=login', {
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
                    throw new Error('Server error. Make sure you have run <a href="setup.php">setup.php</a> first.');
                }

                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    errorDiv.innerHTML = '❌ ' + (data.message || 'Login failed');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                errorDiv.innerHTML = '❌ ' + error.message;
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
    </style>
</body>
</html>
