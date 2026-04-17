<?php
// Profile Page - Shows user details and Lucknow Mandi prices
session_start();
require_once 'config/database.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login-direct.php');
    exit;
}

$db = getDB();
if (!$db) {
    die('Database not connected. Please import the SQL file first.');
}

// Get user details
$user = [];
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    // Handle error
}

// Get user's crops
$myCrops = [];
try {
    $stmt = $db->prepare("
        SELECT fc.*, c.name as crop_name, c.variety, c.base_price
        FROM farmer_crops fc 
        JOIN crops c ON fc.crop_id = c.id 
        WHERE fc.user_id = ? AND fc.status = 'active'
        ORDER BY fc.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $myCrops = $stmt->fetchAll();
} catch(PDOException $e) {
    // Handle error
}

// Get Lucknow Mandi prices for user's crops (or all crops if no crops added)
$lucknowPrices = [];
try {
    $cropIds = array_column($myCrops, 'crop_id');
    
    if (!empty($cropIds)) {
        // Get prices for user's specific crops at Lucknow Mandi
        $placeholders = implode(',', array_fill(0, count($cropIds), '?'));
        $stmt = $db->prepare("
            SELECT 
                c.id as crop_id,
                c.name as crop_name,
                c.variety,
                m.name as mandi_name,
                pd.price,
                pd.demand_level,
                pd.arrival_quantity,
                pd.price_date
            FROM crops c
            JOIN price_data pd ON c.id = pd.crop_id
            JOIN mandis m ON pd.mandi_id = m.id
            WHERE m.name = 'Lucknow Mandi' AND c.id IN ($placeholders)
            ORDER BY pd.price_date DESC, c.name
        ");
        $stmt->execute($cropIds);
        $lucknowPrices = $stmt->fetchAll();
    }
    
    // If no prices found or no crops, get all Lucknow Mandi prices
    if (empty($lucknowPrices)) {
        $stmt = $db->query("
            SELECT 
                c.id as crop_id,
                c.name as crop_name,
                c.variety,
                m.name as mandi_name,
                pd.price,
                pd.demand_level,
                pd.arrival_quantity,
                pd.price_date
            FROM crops c
            JOIN price_data pd ON c.id = pd.crop_id
            JOIN mandis m ON pd.mandi_id = m.id
            WHERE m.name = 'Lucknow Mandi'
            ORDER BY pd.price_date DESC, c.name
            LIMIT 10
        ");
        $lucknowPrices = $stmt->fetchAll();
    }
} catch(PDOException $e) {
    // Handle error
}

// Handle profile update
$updateError = '';
$updateSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim($_POST['name'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $preferred_crop = $_POST['preferred_crop'] ?? '';
    
    try {
        $stmt = $db->prepare("UPDATE users SET name = ?, village = ?, district = ?, preferred_crop = ? WHERE id = ?");
        $stmt->execute([$name, $village, $district, $preferred_crop, $_SESSION['user_id']]);
        $updateSuccess = 'Profile updated successfully!';
        
        // Refresh user data
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch(PDOException $e) {
        $updateError = 'Failed to update profile: ' . $e->getMessage();
    }
}

// Get all crops for dropdown
$allCrops = [];
try {
    $stmt = $db->query("SELECT id, name, variety FROM crops ORDER BY name");
    $allCrops = $stmt->fetchAll();
} catch(PDOException $e) {
    // Handle error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Smart Mandi Intelligence</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .welcome-section { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; border-radius: 16px; margin-bottom: 30px; }
        .section { background: white; padding: 25px; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section h2 { margin-top: 0; color: #166534; border-bottom: 2px solid #e5e7eb; padding-bottom: 15px; }
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        @media (max-width: 768px) { .profile-grid { grid-template-columns: 1fr; } }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #374151; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #10b981; }
        .btn { padding: 12px 24px; border-radius: 10px; border: none; cursor: pointer; font-weight: 600; }
        .btn-primary { background: #10b981; color: white; }
        .btn-primary:hover { background: #059669; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .data-table th { background: #f9fafb; font-weight: 600; }
        .price { font-weight: bold; color: #059669; }
        .demand-high { color: #16a34a; font-weight: 600; }
        .demand-medium { color: #ca8a04; font-weight: 600; }
        .demand-low { color: #dc2626; font-weight: 600; }
        .success-msg { background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 15px; }
        .error-msg { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 15px; }
        .info-box { background: #dbeafe; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #3b82f6; }
        .crop-badge { display: inline-block; background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9rem; margin: 2px; }
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .nav-brand { display: flex; align-items: center; gap: 10px; font-size: 1.3rem; font-weight: bold; color: #166534; }
        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-links a { text-decoration: none; color: #6b7280; font-weight: 500; }
        .nav-links a:hover { color: #10b981; }
        .user-menu { display: flex; align-items: center; gap: 15px; }
        .user-name { font-weight: 600; color: #374151; }
        .lucknow-header { background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .lucknow-header h3 { margin: 0; color: #92400e; }
        .lucknow-header p { margin: 5px 0 0 0; color: #a16207; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span>🌾</span>
            <span>Smart Mandi</span>
        </div>
        <div class="nav-links">
            <a href="dashboard-direct.php">Dashboard</a>
            <a href="profile.php" class="active" style="color: #10b981;">Profile</a>
            <div class="user-menu">
                <span class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'Farmer'); ?></span>
                <a href="logout.php" style="color: #ef4444;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>👤 My Profile</h1>
            <p>Manage your account and view Lucknow Mandi prices</p>
        </div>

        <div class="profile-grid">
            <!-- Profile Edit Section -->
            <div class="section">
                <h2>📝 Edit Profile</h2>
                
                <?php if ($updateSuccess): ?>
                    <div class="success-msg"><?php echo htmlspecialchars($updateSuccess); ?></div>
                <?php endif; ?>
                <?php if ($updateError): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($updateError); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" disabled style="background: #f3f4f6;">
                        <small style="color: #6b7280;">Phone cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Village</label>
                        <input type="text" name="village" value="<?php echo htmlspecialchars($user['village'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>District</label>
                        <input type="text" name="district" value="<?php echo htmlspecialchars($user['district'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Preferred Crop</label>
                        <select name="preferred_crop">
                            <option value="">Select preferred crop</option>
                            <?php foreach ($allCrops as $crop): ?>
                                <option value="<?php echo $crop['id']; ?>" <?php echo ($user['preferred_crop'] ?? '') == $crop['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($crop['name'] . ' (' . $crop['variety'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <!-- My Crops Section -->
            <div class="section">
                <h2>🌾 My Crops</h2>
                
                <?php if (empty($myCrops)): ?>
                    <div class="info-box">
                        <strong>No crops added yet!</strong><br>
                        Go to <a href="dashboard-direct.php" style="color: #10b981;">Dashboard</a> to add crops and check prices.
                    </div>
                <?php else: ?>
                    <p><strong>Your active crops:</strong></p>
                    <div style="margin-bottom: 20px;">
                        <?php foreach ($myCrops as $crop): ?>
                            <span class="crop-badge">
                                <?php echo htmlspecialchars($crop['crop_name']); ?> - <?php echo $crop['quantity']; ?>q
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <a href="dashboard-direct.php" class="btn btn-primary" style="text-decoration: none; display: inline-block;">Manage Crops</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lucknow Mandi Prices Section -->
        <div class="section">
            <div class="lucknow-header">
                <h3>🏪 Lucknow Mandi - Today's Prices</h3>
                <p>Reference prices from your nearest major mandi (<?php echo date('d M Y'); ?>)</p>
            </div>
            
            <?php if (empty($lucknowPrices)): ?>
                <div class="info-box">
                    No price data available for Lucknow Mandi. Please ensure price data has been imported.
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Crop</th>
                            <th>Variety</th>
                            <th>Price (₹/quintal)</th>
                            <th>Demand</th>
                            <th>Arrival</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lucknowPrices as $price): 
                            $demandClass = 'demand-' . strtolower($price['demand_level']);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($price['crop_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($price['variety']); ?></td>
                            <td class="price">₹<?php echo $price['price']; ?></td>
                            <td class="<?php echo $demandClass; ?>"><?php echo $price['demand_level']; ?></td>
                            <td><?php echo number_format($price['arrival_quantity']); ?> q</td>
                            <td><?php echo $price['price_date']; ?></td>
                            <td>
                                <a href="dashboard-direct.php?crop_id=<?php echo $price['crop_id']; ?>" class="btn btn-primary" style="text-decoration: none; padding: 8px 16px; font-size: 0.9rem;">
                                    Compare All Mandis
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; padding: 15px; background: #f0fdf4; border-radius: 10px;">
                    <strong>💡 Tip:</strong> Click "Compare All Mandis" to see prices across all 10 UP mandis and find the best deal for your crop!
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Stats -->
        <div class="section">
            <h2>📊 Quick Stats</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="background: #f0fdf4; padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #059669;"><?php echo count($myCrops); ?></div>
                    <div style="color: #6b7280;">My Crops</div>
                </div>
                <div style="background: #eff6ff; padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #2563eb;"><?php echo count($lucknowPrices); ?></div>
                    <div style="color: #6b7280;">Crops at Lucknow Mandi</div>
                </div>
                <div style="background: #fef3c7; padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #d97706;">10</div>
                    <div style="color: #6b7280;">Total Mandis</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
