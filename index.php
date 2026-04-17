<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard-direct.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Mandi Intelligence - Farmer Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-page">
    <nav class="navbar">
        <div class="logo">
            <span class="icon">🌾</span>
            <span class="brand">Smart Mandi Intelligence</span>
        </div>
        <div class="nav-links">
            <a href="login-direct.php" class="btn btn-primary">Login</a>
            <a href="signup-direct.php" class="btn btn-secondary">Sign Up</a>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <h1>🚜 Get the Best Price for Your Crops</h1>
            <p class="tagline">Compare mandi prices • Predict trends • Maximize profit</p>
            <p class="hindi">अपनी फसल का सबसे अच्छा दाम पाएं</p>
            
            <div class="hero-buttons">
                <a href="signup-direct.php" class="btn btn-large btn-primary">Check Prices Now</a>
                <a href="#features" class="btn btn-large btn-outline">Learn More</a>
            </div>
        </div>
        <div class="hero-image">
            <div class="preview-card">
                <h3>📊 Live Wheat Prices</h3>
                <table class="preview-table">
                    <tr>
                        <td>Lucknow Mandi</td>
                        <td class="price">₹2,450</td>
                    </tr>
                    <tr class="best">
                        <td>Kanpur Mandi</td>
                        <td class="price">₹2,520 ✅</td>
                    </tr>
                    <tr>
                        <td>Varanasi Mandi</td>
                        <td class="price">₹2,400</td>
                    </tr>
                </table>
            </div>
        </div>
    </header>

    <section id="features" class="features">
        <h2>🌟 Key Features</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">📍</div>
                <h3>Mandi Comparison</h3>
                <p>Compare prices across all nearby mandis with transport cost calculation</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔮</div>
                <h3>Price Prediction</h3>
                <p>AI-powered 7-day price forecast to help you decide when to sell</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <h3>Best Price Finder</h3>
                <p>Automatically finds the mandi offering the highest net profit</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔔</div>
                <h3>Smart Alerts</h3>
                <p>Get notified when prices rise or high demand is detected</p>
            </div>
        </div>
    </section>

    <section class="how-it-works">
        <h2>🔄 How It Works</h2>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h4>Enter Your Crop</h4>
                <p>Select crop type and quantity you want to sell</p>
            </div>
            <div class="step-arrow">→</div>
            <div class="step">
                <div class="step-number">2</div>
                <h4>Compare Prices</h4>
                <p>See real-time prices from all nearby mandis</p>
            </div>
            <div class="step-arrow">→</div>
            <div class="step">
                <div class="step-number">3</div>
                <h4>Get Recommendation</h4>
                <p>Receive smart suggestion on best mandi and timing</p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p>© 2024 Smart Mandi Intelligence | Made for Indian Farmers 🇮🇳</p>
        <p class="small">Powered by AGMARKNET Data | Predictive Analytics</p>
    </footer>

    <style>
        /* Landing Page Specific Styles */
        .landing-page {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        
        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            padding: 80px 10%;
            align-items: center;
        }
        
        .hero h1 {
            font-size: 3rem;
            color: #166534;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .tagline {
            font-size: 1.3rem;
            color: #15803d;
            margin-bottom: 10px;
        }
        
        .hindi {
            font-size: 1.1rem;
            color: #65a30d;
            margin-bottom: 30px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .btn-large {
            padding: 15px 40px;
            font-size: 1.1rem;
        }
        
        .preview-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 350px;
            margin-left: auto;
        }
        
        .preview-card h3 {
            color: #166534;
            margin-bottom: 20px;
        }
        
        .preview-table {
            width: 100%;
        }
        
        .preview-table td {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .preview-table .price {
            text-align: right;
            font-weight: 600;
            color: #15803d;
        }
        
        .preview-table .best {
            background: #f0fdf4;
        }
        
        .features {
            padding: 80px 10%;
            background: white;
        }
        
        .features h2 {
            text-align: center;
            color: #166534;
            font-size: 2.2rem;
            margin-bottom: 50px;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: #f0fdf4;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .feature-card h3 {
            color: #166534;
            margin-bottom: 10px;
        }
        
        .feature-card p {
            color: #4b5563;
        }
        
        .how-it-works {
            padding: 80px 10%;
            background: linear-gradient(135deg, #166534, #15803d);
            color: white;
        }
        
        .how-it-works h2 {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 50px;
        }
        
        .steps {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .step {
            text-align: center;
            max-width: 250px;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: white;
            color: #166534;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .step h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .step-arrow {
            font-size: 2rem;
            opacity: 0.7;
        }
        
        .footer {
            background: #064e3b;
            color: white;
            text-align: center;
            padding: 40px;
        }
        
        .footer p {
            margin-bottom: 10px;
        }
        
        .footer .small {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .preview-card {
                margin: 0 auto;
            }
            
            .steps {
                flex-direction: column;
            }
            
            .step-arrow {
                transform: rotate(90deg);
            }
        }
    </style>
</body>
</html>
