<?php
// Database Setup Script - Run this first!
// This creates the database and all required tables

require_once 'config/database.php';

header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Smart Mandi - Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #166534; }
        .success { background: #dcfce7; padding: 15px; border-radius: 8px; color: #166534; }
        .error { background: #fee2e2; padding: 15px; border-radius: 8px; color: #dc2626; }
        .step { background: #f3f4f6; padding: 15px; margin: 10px 0; border-radius: 8px; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; }
        .btn { display: inline-block; background: #10b981; color: white; padding: 12px 24px; 
               text-decoration: none; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🌾 Smart Mandi Database Setup</h1>
    
    <?php
    $db = getDB();
    
    if (!$db) {
        echo '<div class="error">❌ Failed to connect to MySQL. Please check:<br>';
        echo '1. XAMPP MySQL is running<br>';
        echo '2. Username is "root" and password is empty (default)<br>';
        echo '3. MySQL port is 3306</div>';
        exit;
    }
    
    echo '<div class="success">✅ Connected to MySQL</div>';
    
    try {
        // Create database
        $db->exec("CREATE DATABASE IF NOT EXISTS mandi_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo '<div class="step">✅ Database created: mandi_system</div>';
        
        // Use the database
        $db->exec("USE mandi_system");
        
        // Create tables
        $tables = [
            'users' => "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                phone VARCHAR(20) UNIQUE NOT NULL,
                email VARCHAR(100),
                password VARCHAR(255) NOT NULL,
                village VARCHAR(100),
                district VARCHAR(100),
                state VARCHAR(50) DEFAULT 'Uttar Pradesh',
                preferred_crop VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            'mandis' => "CREATE TABLE IF NOT EXISTS mandis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                district VARCHAR(100) NOT NULL,
                state VARCHAR(50) DEFAULT 'Uttar Pradesh',
                latitude DECIMAL(10, 8),
                longitude DECIMAL(11, 8),
                distance_from_lucknow INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            'crops' => "CREATE TABLE IF NOT EXISTS crops (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                variety VARCHAR(50),
                base_price DECIMAL(10, 2) DEFAULT 0,
                unit VARCHAR(20) DEFAULT 'quintal',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            'farmer_crops' => "CREATE TABLE IF NOT EXISTS farmer_crops (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                crop_id INT NOT NULL,
                variety VARCHAR(50),
                quantity DECIMAL(10, 2) NOT NULL,
                quality_grade ENUM('A', 'B', 'C') DEFAULT 'B',
                expected_price DECIMAL(10, 2),
                status ENUM('active', 'sold', 'expired') DEFAULT 'active',
                entry_date DATE DEFAULT (CURRENT_DATE),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (crop_id) REFERENCES crops(id) ON DELETE CASCADE
            )",
            'price_data' => "CREATE TABLE IF NOT EXISTS price_data (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mandi_id INT NOT NULL,
                crop_id INT NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                arrival_quantity DECIMAL(10, 2) DEFAULT 0,
                demand_level ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
                price_date DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (mandi_id) REFERENCES mandis(id) ON DELETE CASCADE,
                FOREIGN KEY (crop_id) REFERENCES crops(id) ON DELETE CASCADE,
                UNIQUE KEY unique_price (mandi_id, crop_id, price_date)
            )",
            'alerts' => "CREATE TABLE IF NOT EXISTS alerts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                mandi_id INT,
                crop_id INT,
                alert_type ENUM('price_up', 'price_down', 'high_demand', 'best_price', 'system') DEFAULT 'system',
                title VARCHAR(200) NOT NULL,
                message TEXT,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (mandi_id) REFERENCES mandis(id) ON DELETE CASCADE,
                FOREIGN KEY (crop_id) REFERENCES crops(id) ON DELETE CASCADE
            )",
            'activity_logs' => "CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )"
        ];
        
        foreach ($tables as $name => $sql) {
            $db->exec($sql);
            echo "<div class='step'>✅ Table created: {$name}</div>";
        }
        
        // Insert sample mandis
        $mandis = [
            ['Lucknow Mandi', 'Lucknow', 0, 26.8467, 80.9462],
            ['Kanpur Mandi', 'Kanpur', 80, 26.4499, 80.3319],
            ['Varanasi Mandi', 'Varanasi', 320, 25.3176, 83.0109],
            ['Sitapur Mandi', 'Sitapur', 85, 27.5706, 80.6812],
            ['Barabanki Mandi', 'Barabanki', 55, 26.9276, 81.1940],
            ['Raebareli Mandi', 'Raebareli', 82, 26.2236, 81.2400],
            ['Hardoi Mandi', 'Hardoi', 110, 27.4043, 80.1167],
            ['Unnao Mandi', 'Unnao', 50, 26.5403, 80.4883],
            ['Faizabad Mandi', 'Faizabad', 128, 26.7735, 82.1442],
            ['Gorakhpur Mandi', 'Gorakhpur', 275, 26.7606, 83.3732]
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO mandis (name, district, distance_from_lucknow, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
        foreach ($mandis as $mandi) {
            $stmt->execute($mandi);
        }
        echo '<div class="step">✅ Sample mandis inserted</div>';
        
        // Insert sample crops
        $crops = [
            ['Wheat', 'HD-2967', 2400],
            ['Wheat', 'PBW-550', 2450],
            ['Rice', 'Basmati', 3200],
            ['Rice', 'Pusa', 2800],
            ['Potato', 'Chipsona', 800],
            ['Potato', 'Kufri Jyoti', 750],
            ['Onion', 'Red', 1200],
            ['Tomato', 'Hybrid', 1500],
            ['Mustard', 'Yellow', 4200],
            ['Sugarcane', 'Co-0238', 350]
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO crops (name, variety, base_price) VALUES (?, ?, ?)");
        foreach ($crops as $crop) {
            $stmt->execute($crop);
        }
        echo '<div class="step">✅ Sample crops inserted</div>';
        
        // Generate sample price data for last 30 days
        echo '<div class="step">⏳ Generating sample price data (30 days)...</div>';
        
        $mandiIds = $db->query("SELECT id FROM mandis")->fetchAll(PDO::FETCH_COLUMN);
        $cropIds = $db->query("SELECT id, base_price FROM crops")->fetchAll(PDO::FETCH_KEY_PAIR);
        $demandLevels = ['Low', 'Medium', 'High'];
        
        $insertPrice = $db->prepare("
            INSERT INTO price_data (mandi_id, crop_id, price, price_date, demand_level, arrival_quantity)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE price = VALUES(price), demand_level = VALUES(demand_level), arrival_quantity = VALUES(arrival_quantity)
        ");
        
        for ($i = 0; $i < 30; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            foreach ($mandiIds as $mandiId) {
                foreach ($cropIds as $cropId => $basePrice) {
                    // Add variation to base price (±100 rupees)
                    $variation = rand(-100, 100);
                    $price = max(100, $basePrice + $variation);
                    $demand = $demandLevels[array_rand($demandLevels)];
                    $arrival = rand(500, 5000);
                    
                    $insertPrice->execute([$mandiId, $cropId, $price, $date, $demand, $arrival]);
                }
            }
        }
        
        echo '<div class="step">✅ Sample price data generated</div>';
        
        echo '<div class="success" style="margin-top: 30px;">🎉 Setup Complete! Database is ready.</div>';
        echo '<a href="index.php" class="btn">Go to Homepage</a>';
        
    } catch(PDOException $e) {
        echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
        echo '<p>Please check your MySQL configuration in <code>config/database.php</code></p>';
    }
    ?>
</body>
</html>
