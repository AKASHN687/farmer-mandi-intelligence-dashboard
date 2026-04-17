<?php
// Detailed Profile Page with Sales History
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

// Get user details with stats
$user = [];
try {
    $stmt = $db->prepare("
        SELECT u.*, c.name as preferred_crop_name, c.variety as preferred_crop_variety
        FROM users u
        LEFT JOIN crops c ON u.preferred_crop_id = c.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    // Handle error
}

// Get user's active crops
$activeCrops = [];
try {
    $stmt = $db->prepare("
        SELECT fc.*, c.name as crop_name, c.variety, c.base_price
        FROM farmer_crops fc 
        JOIN crops c ON fc.crop_id = c.id 
        WHERE fc.user_id = ? AND fc.status = 'active'
        ORDER BY fc.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $activeCrops = $stmt->fetchAll();
} catch(PDOException $e) {
    // Handle error
}

// Get sales history
$salesHistory = [];
try {
    $stmt = $db->prepare("
        SELECT 
            st.*,
            c.name as crop_name,
            c.variety,
            m.name as mandi_name,
            m.district as mandi_district
        FROM sales_transactions st
        JOIN crops c ON st.crop_id = c.id
        JOIN mandis m ON st.mandi_id = m.id
        WHERE st.user_id = ?
        ORDER BY st.sale_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $salesHistory = $stmt->fetchAll();
} catch(PDOException $e) {
    // Handle error
}

// Calculate statistics
$stats = [
    'total_sales' => $user['total_sales'] ?? 0,
    'total_quantity' => $user['total_quantity_sold'] ?? 0,
    'total_transactions' => count($salesHistory),
    'active_crops_count' => count($activeCrops),
    'avg_price_per_quintal' => 0,
    'best_mandi' => '',
    'best_crop' => ''
];

// Calculate average price from sales
if (!empty($salesHistory)) {
    $totalPrice = array_sum(array_column($salesHistory, 'price_per_quintal'));
    $stats['avg_price_per_quintal'] = $totalPrice / count($salesHistory);
    
    // Find best mandi (most sales)
    $mandiSales = [];
    foreach ($salesHistory as $sale) {
        $mandiName = $sale['mandi_name'];
        if (!isset($mandiSales[$mandiName])) {
            $mandiSales[$mandiName] = 0;
        }
        $mandiSales[$mandiName] += $sale['total_amount'];
    }
    if (!empty($mandiSales)) {
        arsort($mandiSales);
        $stats['best_mandi'] = array_key_first($mandiSales);
    }
    
    // Find best crop
    $cropSales = [];
    foreach ($salesHistory as $sale) {
        $cropName = $sale['crop_name'];
        if (!isset($cropSales[$cropName])) {
            $cropSales[$cropName] = 0;
        }
        $cropSales[$cropName] += $sale['total_amount'];
    }
    if (!empty($cropSales)) {
        arsort($cropSales);
        $stats['best_crop'] = array_key_first($cropSales);
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

// Handle profile update
$updateError = '';
$updateSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim($_POST['name'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $preferred_crop_id = $_POST['preferred_crop_id'] ?? '';
    
    try {
        $stmt = $db->prepare("UPDATE users SET name = ?, village = ?, district = ?, preferred_crop_id = ? WHERE id = ?");
        $stmt->execute([$name, $village, $district, $preferred_crop_id ?: null, $_SESSION['user_id']]);
        $updateSuccess = 'Profile updated successfully!';
        
        // Refresh user data
        $stmt = $db->prepare("
            SELECT u.*, c.name as preferred_crop_name, c.variety as preferred_crop_variety
            FROM users u
            LEFT JOIN crops c ON u.preferred_crop_id = c.id
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch(PDOException $e) {
        $updateError = 'Failed to update profile: ' . $e->getMessage();
    }
}

// Handle add new sale (demo feature)
$saleError = '';
$saleSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_sale') {
    $crop_id = $_POST['sale_crop_id'] ?? '';
    $mandi_id = $_POST['sale_mandi_id'] ?? '';
    $quantity = $_POST['sale_quantity'] ?? '';
    $price_per_quintal = $_POST['sale_price'] ?? '';
    $transport_cost = $_POST['sale_transport'] ?? 0;
    $sale_date = $_POST['sale_date'] ?? date('Y-m-d');
    $buyer_name = $_POST['sale_buyer'] ?? '';
    
    if (!empty($crop_id) && !empty($mandi_id) && !empty($quantity) && !empty($price_per_quintal)) {
        $total_amount = $quantity * $price_per_quintal;
        $net_profit = $total_amount - $transport_cost;
        
        try {
            $stmt = $db->prepare("
                INSERT INTO sales_transactions 
                (user_id, crop_id, mandi_id, quantity, price_per_quintal, transport_cost, total_amount, net_profit, sale_date, buyer_name, payment_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'received')
            ");
            $stmt->execute([
                $_SESSION['user_id'], $crop_id, $mandi_id, $quantity, $price_per_quintal,
                $transport_cost, $total_amount, $net_profit, $sale_date, $buyer_name
            ]);
            
            // Update user totals
            $stmt = $db->prepare("
                UPDATE users 
                SET total_sales = total_sales + ?, total_quantity_sold = total_quantity_sold + ?
                WHERE id = ?
            ");
            $stmt->execute([$total_amount, $quantity, $_SESSION['user_id']]);
            
            $saleSuccess = 'Sale recorded successfully!';
            
            // Refresh data
            header('Location: profile-detailed.php?sale_added=1');
            exit;
        } catch(PDOException $e) {
            $saleError = 'Failed to record sale: ' . $e->getMessage();
        }
    } else {
        $saleError = 'Please fill in all required fields';
    }
}

// Get all mandis for sale form
$allMandis = [];
try {
    $stmt = $db->query("SELECT id, name, district FROM mandis ORDER BY name");
    $allMandis = $stmt->fetchAll();
} catch(PDOException $e) {
    // Handle error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Smart Mandi Intelligence</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background: #f3f4f6; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        /* Navbar */
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .nav-brand { display: flex; align-items: center; gap: 10px; font-size: 1.3rem; font-weight: bold; color: #166534; }
        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-links a { text-decoration: none; color: #6b7280; font-weight: 500; }
        .nav-links a:hover, .nav-links a.active { color: #10b981; }
        .user-menu { display: flex; align-items: center; gap: 15px; }
        .user-name { font-weight: 600; color: #374151; }
        
        /* Welcome Section */
        .welcome-section { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; border-radius: 16px; margin-bottom: 30px; }
        .welcome-section h1 { margin: 0 0 10px 0; }
        
        /* Cards */
        .section { background: white; padding: 25px; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section h2 { margin-top: 0; color: #166534; border-bottom: 2px solid #e5e7eb; padding-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-value { font-size: 2rem; font-weight: bold; color: #059669; margin-bottom: 5px; }
        .stat-label { color: #6b7280; font-size: 0.95rem; }
        .stat-card.blue .stat-value { color: #2563eb; }
        .stat-card.purple .stat-value { color: #7c3aed; }
        .stat-card.orange .stat-value { color: #ea580c; }
        .stat-card.teal .stat-value { color: #0d9488; }
        
        /* Forms */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #374151; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #10b981; }
        .btn { padding: 12px 24px; border-radius: 10px; border: none; cursor: pointer; font-weight: 600; font-size: 1rem; }
        .btn-primary { background: #10b981; color: white; }
        .btn-primary:hover { background: #059669; }
        .btn-secondary { background: #3b82f6; color: white; }
        .btn-secondary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; }
        .btn-success:hover { background: #16a34a; }
        
        /* Tables */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .data-table th { background: #f9fafb; font-weight: 600; color: #374151; }
        .data-table tr:hover { background: #f9fafb; }
        .price { font-weight: bold; color: #059669; }
        .profit { font-weight: bold; color: #7c3aed; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-block; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        
        /* Messages */
        .success-msg { background: #dcfce7; color: #166534; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #22c55e; }
        .error-msg { background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #ef4444; }
        .info-box { background: #dbeafe; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #3b82f6; }
        
        /* Grid Layouts */
        .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        @media (max-width: 768px) { .two-column { grid-template-columns: 1fr; } }
        
        /* Crop Cards */
        .crop-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .crop-card { background: #f0fdf4; padding: 20px; border-radius: 12px; border-left: 4px solid #10b981; }
        .crop-card.sold { background: #fef3c7; border-left-color: #f59e0b; }
        .crop-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .crop-header h4 { margin: 0; color: #166534; }
        .quantity-badge { background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; }
        .status-badge { font-size: 0.8rem; padding: 2px 8px; border-radius: 10px; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-sold { background: #fef3c7; color: #92400e; }
        
        /* Modal */
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 16px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; position: relative; }
        .modal-close { position: absolute; top: 15px; right: 20px; font-size: 1.5rem; cursor: pointer; color: #6b7280; }
        .modal-close:hover { color: #374151; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .navbar { flex-direction: column; gap: 15px; }
        }
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
            <a href="profile-detailed.php" class="active">My Profile</a>
            <div class="user-menu">
                <span class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'Farmer'); ?></span>
                <a href="logout.php" style="color: #ef4444;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>👤 <?php echo htmlspecialchars($user['name'] ?? 'My Profile'); ?></h1>
            <p>View your sales history, manage your crops, and track your earnings</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">₹<?php echo number_format($stats['total_sales'], 0); ?></div>
                <div class="stat-label">Total Sales</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-value"><?php echo number_format($stats['total_quantity'], 1); ?> q</div>
                <div class="stat-label">Quantity Sold</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-value"><?php echo $stats['total_transactions']; ?></div>
                <div class="stat-label">Total Sales</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-value"><?php echo $stats['active_crops_count']; ?></div>
                <div class="stat-label">Active Crops</div>
            </div>
            <div class="stat-card teal">
                <div class="stat-value">₹<?php echo number_format($stats['avg_price_per_quintal'], 0); ?></div>
                <div class="stat-label">Avg Price/q</div>
            </div>
        </div>

        <?php if (isset($_GET['sale_added'])): ?>
            <div class="success-msg">✅ Sale recorded successfully!</div>
        <?php endif; ?>

        <!-- Two Column Layout -->
        <div class="two-column">
            <!-- Profile Edit -->
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
                        <label>Full Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" disabled style="background: #f3f4f6;">
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
                        <select name="preferred_crop_id">
                            <option value="">Select preferred crop</option>
                            <?php foreach ($allCrops as $crop): ?>
                                <option value="<?php echo $crop['id']; ?>" <?php echo ($user['preferred_crop_id'] ?? '') == $crop['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($crop['name'] . ' (' . $crop['variety'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <!-- User Info Summary -->
            <div class="section">
                <h2>📊 My Summary</h2>
                
                <div style="margin-bottom: 20px;">
                    <strong>📍 Location:</strong> <?php echo htmlspecialchars(($user['village'] ?? 'Not set') . ', ' . ($user['district'] ?? 'Not set')); ?>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <strong>🌾 Preferred Crop:</strong> 
                    <?php echo $user['preferred_crop_name'] ? htmlspecialchars($user['preferred_crop_name'] . ' (' . $user['preferred_crop_variety'] . ')') : 'Not set'; ?>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <strong>📅 Member Since:</strong> <?php echo date('d M Y', strtotime($user['registration_date'] ?? $user['created_at'])); ?>
                </div>
                
                <?php if ($stats['best_mandi']): ?>
                <div style="margin-bottom: 20px;">
                    <strong>🏪 Best Mandi:</strong> <?php echo htmlspecialchars($stats['best_mandi']); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['best_crop']): ?>
                <div style="margin-bottom: 20px;">
                    <strong>🌾 Best Selling Crop:</strong> <?php echo htmlspecialchars($stats['best_crop']); ?>
                </div>
                <?php endif; ?>
                
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;">
                
                <button onclick="showAddSaleModal()" class="btn btn-success">+ Record New Sale</button>
            </div>
        </div>

        <!-- Active Crops Section -->
        <div class="section">
            <h2>🌾 My Active Crops (For Sale)</h2>
            
            <?php if (empty($activeCrops)): ?>
                <div class="info-box">
                    No active crops. <a href="dashboard-direct.php" style="color: #3b82f6;">Go to Dashboard</a> to add crops for sale.
                </div>
            <?php else: ?>
                <div class="crop-grid">
                    <?php foreach ($activeCrops as $crop): ?>
                        <div class="crop-card">
                            <div class="crop-header">
                                <h4><?php echo htmlspecialchars($crop['crop_name']); ?></h4>
                                <span class="quantity-badge"><?php echo $crop['quantity']; ?> q</span>
                            </div>
                            <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 5px;">
                                Variety: <?php echo htmlspecialchars($crop['variety']); ?>
                            </div>
                            <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 5px;">
                                Quality: Grade <?php echo $crop['quality_grade']; ?>
                            </div>
                            <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 10px;">
                                Expected: ₹<?php echo $crop['expected_price']; ?>/q
                            </div>
                            <span class="status-badge status-active">Active</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sales History Section -->
        <div class="section">
            <h2>💰 Sales History</h2>
            
            <?php if (empty($salesHistory)): ?>
                <div class="info-box">
                    No sales recorded yet. Click "Record New Sale" to add your first sale.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Crop</th>
                                <th>Mandi</th>
                                <th>Quantity</th>
                                <th>Price/q</th>
                                <th>Transport</th>
                                <th>Total</th>
                                <th>Net Profit</th>
                                <th>Buyer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salesHistory as $sale): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($sale['sale_date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($sale['crop_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sale['mandi_name']); ?></td>
                                <td><?php echo $sale['quantity']; ?> q</td>
                                <td class="price">₹<?php echo number_format($sale['price_per_quintal']); ?></td>
                                <td>₹<?php echo number_format($sale['transport_cost']); ?></td>
                                <td class="price">₹<?php echo number_format($sale['total_amount']); ?></td>
                                <td class="profit">₹<?php echo number_format($sale['net_profit']); ?></td>
                                <td><?php echo htmlspecialchars($sale['buyer_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Sale Modal -->
    <div id="addSaleModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="hideAddSaleModal()">&times;</span>
            <h2>+ Record New Sale</h2>
            
            <?php if ($saleError): ?>
                <div class="error-msg"><?php echo htmlspecialchars($saleError); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_sale">
                
                <div class="form-group">
                    <label>Crop *</label>
                    <select name="sale_crop_id" required>
                        <option value="">Select crop</option>
                        <?php foreach ($allCrops as $crop): ?>
                            <option value="<?php echo $crop['id']; ?>">
                                <?php echo htmlspecialchars($crop['name'] . ' (' . $crop['variety'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Mandi *</label>
                    <select name="sale_mandi_id" required>
                        <option value="">Select mandi</option>
                        <?php foreach ($allMandis as $mandi): ?>
                            <option value="<?php echo $mandi['id']; ?>">
                                <?php echo htmlspecialchars($mandi['name'] . ' (' . $mandi['district'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Quantity (Quintals) *</label>
                    <input type="number" name="sale_quantity" step="0.1" min="0.1" required>
                </div>
                
                <div class="form-group">
                    <label>Price per Quintal (₹) *</label>
                    <input type="number" name="sale_price" step="0.01" min="1" required>
                </div>
                
                <div class="form-group">
                    <label>Transport Cost (₹)</label>
                    <input type="number" name="sale_transport" step="0.01" min="0" value="0">
                </div>
                
                <div class="form-group">
                    <label>Sale Date</label>
                    <input type="date" name="sale_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Buyer Name</label>
                    <input type="text" name="sale_buyer" placeholder="e.g., Aggarwal Traders">
                </div>
                
                <button type="submit" class="btn btn-success">Record Sale</button>
            </form>
        </div>
    </div>

    <script>
        function showAddSaleModal() {
            document.getElementById('addSaleModal').style.display = 'flex';
        }

        function hideAddSaleModal() {
            document.getElementById('addSaleModal').style.display = 'none';
        }

        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('addSaleModal');
            if (event.target === modal) {
                hideAddSaleModal();
            }
        }
    </script>
</body>
</html>
