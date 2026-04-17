<?php
// Dashboard WITHOUT API - Direct PHP database queries
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

// Get all crops for dropdown
$crops = [];
try {
    $stmt = $db->query("SELECT id, name, variety FROM crops ORDER BY name");
    $crops = $stmt->fetchAll();
} catch(PDOException $e) {
    // Handle error
}

// Get user's recent crop entries
$myCrops = [];
try {
    $stmt = $db->prepare("
        SELECT fc.*, c.name as crop_name, c.variety 
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

// Handle crop addition
$addCropError = '';
$addCropSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_crop') {
    $crop_id = $_POST['crop_id'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $quality = $_POST['quality'] ?? 'B';
    
    if (empty($crop_id) || empty($quantity)) {
        $addCropError = 'Please select crop and enter quantity';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO farmer_crops (user_id, crop_id, quantity, quality_grade) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $crop_id, $quantity, $quality]);
            $addCropSuccess = 'Crop added successfully!';
            // Refresh the page to show new crop
            header('Location: dashboard-direct.php?added=1');
            exit;
        } catch(PDOException $e) {
            $addCropError = 'Failed to add crop: ' . $e->getMessage();
        }
    }
}

// Handle crop deletion
if (isset($_GET['delete_crop'])) {
    $crop_entry_id = $_GET['delete_crop'];
    try {
        $stmt = $db->prepare("DELETE FROM farmer_crops WHERE id = ? AND user_id = ?");
        $stmt->execute([$crop_entry_id, $_SESSION['user_id']]);
        header('Location: dashboard-direct.php');
        exit;
    } catch(PDOException $e) {
        // Handle error
    }
}

// Get selected crop prices if requested
$selectedCropId = $_GET['crop_id'] ?? '';
$priceData = [];
$insights = [];
$bestMandi = null;

if ($selectedCropId) {
    // Get prices from all mandis for this crop
    try {
        $stmt = $db->prepare("
            SELECT 
                m.id as mandi_id,
                m.name as mandi_name,
                m.district,
                m.distance_from_lucknow,
                pd.price,
                pd.demand_level,
                pd.arrival_quantity,
                pd.price_date
            FROM mandis m
            JOIN price_data pd ON m.id = pd.mandi_id
            WHERE pd.crop_id = ? AND pd.price_date = CURDATE()
            ORDER BY pd.price DESC
        ");
        $stmt->execute([$selectedCropId]);
        $priceData = $stmt->fetchAll();
        
        // If no data for today, get latest available
        if (empty($priceData)) {
            $stmt = $db->prepare("
                SELECT 
                    m.id as mandi_id,
                    m.name as mandi_name,
                    m.district,
                    m.distance_from_lucknow,
                    pd.price,
                    pd.demand_level,
                    pd.arrival_quantity,
                    pd.price_date
                FROM mandis m
                JOIN price_data pd ON m.id = pd.mandi_id
                WHERE pd.crop_id = ?
                ORDER BY pd.price_date DESC, pd.price DESC
                LIMIT 10
            ");
            $stmt->execute([$selectedCropId]);
            $priceData = $stmt->fetchAll();
        }
        
        // Calculate transport cost and net profit
        foreach ($priceData as &$row) {
            // Transport cost: ₹2 per km per quintal
            $row['transport_cost'] = $row['distance_from_lucknow'] * 2;
            $row['net_profit'] = $row['price'] - $row['transport_cost'];
        }
        
        // Find best mandi (highest net profit)
        if (!empty($priceData)) {
            $bestMandi = $priceData[0];
            foreach ($priceData as $mandi) {
                if ($mandi['net_profit'] > $bestMandi['net_profit']) {
                    $bestMandi = $mandi;
                }
            }
        }
        
        // Generate insights
        if (!empty($priceData)) {
            $prices = array_column($priceData, 'price');
            $avgPrice = array_sum($prices) / count($prices);
            $maxPrice = max($prices);
            $minPrice = min($prices);
            $priceRange = $maxPrice - $minPrice;
            
            $insights = [
                [
                    'icon' => '💰',
                    'title' => 'Price Range',
                    'message' => "Prices vary by ₹{$priceRange} across mandis. Highest: ₹{$maxPrice}, Lowest: ₹{$minPrice}"
                ],
                [
                    'icon' => '📍',
                    'title' => 'Best Mandi',
                    'message' => $bestMandi ? "{$bestMandi['mandi_name']} offers best net profit of ₹{$bestMandi['net_profit']}/q" : 'No data available'
                ],
                [
                    'icon' => '🚛',
                    'title' => 'Transport Tip',
                    'message' => 'Consider transport costs. Nearby mandis may give better net profit despite lower prices.'
                ]
            ];
        }
        
    } catch(PDOException $e) {
        // Handle error
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Mandi Intelligence</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .welcome-section { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; border-radius: 16px; margin-bottom: 30px; }
        .welcome-section h1 { margin: 0 0 10px 0; }
        .section { background: white; padding: 25px; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section h2 { margin-top: 0; color: #166534; border-bottom: 2px solid #e5e7eb; padding-bottom: 15px; }
        .crop-selector { display: flex; gap: 15px; margin: 20px 0; }
        .crop-selector select { flex: 1; padding: 12px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; }
        .btn { padding: 12px 24px; border-radius: 10px; border: none; cursor: pointer; font-weight: 600; }
        .btn-primary { background: #10b981; color: white; }
        .btn-primary:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .data-table th { background: #f9fafb; font-weight: 600; }
        .price { font-weight: bold; color: #059669; }
        .profit { font-weight: bold; color: #7c3aed; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-primary { background: #dbeafe; color: #1e40af; }
        .highlight-best { background: #dcfce7 !important; }
        .highlight-profit { background: #eff6ff !important; }
        .insights-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .insight-card { display: flex; gap: 15px; padding: 20px; background: #f9fafb; border-radius: 12px; }
        .insight-icon { font-size: 2rem; }
        .insight-card h4 { margin: 0 0 5px 0; color: #374151; }
        .insight-card p { margin: 0; color: #6b7280; }
        .recommendation-box { background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 25px; border-radius: 12px; }
        .recommendation-main { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .mandi-info h3 { margin: 0; color: #92400e; }
        .price-info { text-align: right; }
        .price-info .value { font-size: 1.5rem; font-weight: bold; color: #059669; display: block; }
        .crop-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .crop-card { background: #f9fafb; padding: 20px; border-radius: 12px; border-left: 4px solid #10b981; }
        .crop-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .crop-header h3 { margin: 0; color: #166534; }
        .quantity { background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9rem; }
        .crop-actions { display: flex; gap: 10px; }
        .btn-sm { padding: 8px 16px; font-size: 0.9rem; }
        .empty-state { text-align: center; padding: 40px; color: #6b7280; }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 16px; max-width: 500px; width: 90%; position: relative; }
        .close { position: absolute; top: 15px; right: 20px; font-size: 1.5rem; cursor: pointer; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #374151; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 10px; }
        .mt-3 { margin-top: 20px; }
        .navbar { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .nav-brand { display: flex; align-items: center; gap: 10px; font-size: 1.3rem; font-weight: bold; color: #166534; }
        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-links a { text-decoration: none; color: #6b7280; font-weight: 500; }
        .nav-links a.active, .nav-links a:hover { color: #10b981; }
        .user-menu { display: flex; align-items: center; gap: 15px; }
        .user-name { font-weight: 600; color: #374151; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span>🌾</span>
            <span>Smart Mandi</span>
        </div>
        <div class="nav-links">
            <a href="dashboard-direct.php" class="active">Dashboard</a>
            <a href="profile-detailed.php">My Profile</a>
            <div class="user-menu">
                <span class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'Farmer'); ?></span>
                <a href="logout.php" style="color: #ef4444;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>नमस्ते <?php echo htmlspecialchars($user['name'] ?? 'Kisan'); ?>! 🙏</h1>
            <p>Check today's mandi prices and get smart selling recommendations</p>
        </div>

        <!-- Quick Price Check -->
        <div class="section">
            <h2>🔍 Quick Price Check</h2>
            <form method="GET" action="" class="crop-selector">
                <select name="crop_id" id="quickCropSelect">
                    <option value="">Select your crop</option>
                    <?php foreach ($crops as $crop): ?>
                        <option value="<?php echo $crop['id']; ?>" <?php echo $selectedCropId == $crop['id'] ? 'selected' : ''; ?>>
                            <?php echo $crop['name']; ?> (<?php echo $crop['variety']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Check Prices</button>
            </form>
        </div>

        <?php if ($selectedCropId && !empty($priceData)): ?>
        <!-- Price Comparison Results -->
        <div class="section">
            <h2>📊 Mandi Price Comparison</h2>
            <div class="comparison-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mandi</th>
                            <th>District</th>
                            <th>Distance</th>
                            <th>Price (₹/q)</th>
                            <th>Transport</th>
                            <th>Net Profit</th>
                            <th>Demand</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($priceData as $row): 
                            $isBestPrice = ($row['price'] == max(array_column($priceData, 'price')));
                            $isBestProfit = ($row['net_profit'] == max(array_column($priceData, 'net_profit')));
                            $rowClass = '';
                            if ($isBestPrice) $rowClass = 'highlight-best';
                            elseif ($isBestProfit) $rowClass = 'highlight-profit';
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><strong><?php echo htmlspecialchars($row['mandi_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['district']); ?></td>
                            <td><?php echo $row['distance_from_lucknow']; ?> km</td>
                            <td class="price">₹<?php echo $row['price']; ?></td>
                            <td>₹<?php echo $row['transport_cost']; ?></td>
                            <td class="profit">₹<?php echo $row['net_profit']; ?></td>
                            <td><?php echo $row['demand_level']; ?></td>
                            <td><?php echo $row['price_date']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($bestMandi): ?>
            <div style="margin-top: 20px; padding: 15px; background: #dcfce7; border-radius: 10px;">
                <strong>🏆 Best Recommendation:</strong> Sell at <strong><?php echo htmlspecialchars($bestMandi['mandi_name']); ?></strong> 
                for net profit of <strong>₹<?php echo $bestMandi['net_profit']; ?>/quintal</strong>
                (Price: ₹<?php echo $bestMandi['price']; ?> - Transport: ₹<?php echo $bestMandi['transport_cost']; ?>)
            </div>
            <?php endif; ?>
        </div>

        <!-- Insights Section -->
        <?php if (!empty($insights)): ?>
        <div class="section">
            <h2>💡 Smart Insights</h2>
            <div class="insights-grid">
                <?php foreach ($insights as $insight): ?>
                <div class="insight-card">
                    <div class="insight-icon"><?php echo $insight['icon']; ?></div>
                    <div class="insight-content">
                        <h4><?php echo htmlspecialchars($insight['title']); ?></h4>
                        <p><?php echo htmlspecialchars($insight['message']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- My Crops Section -->
        <div class="section">
            <h2>🌾 My Crops</h2>
            
            <?php if (empty($myCrops)): ?>
                <div class="empty-state">
                    <p>You haven't added any crops yet.</p>
                    <button onclick="showAddCropForm()" class="btn btn-primary">Add Your First Crop</button>
                </div>
            <?php else: ?>
                <div class="crop-grid">
                    <?php foreach ($myCrops as $myCrop): ?>
                        <div class="crop-card">
                            <div class="crop-header">
                                <h3><?php echo htmlspecialchars($myCrop['crop_name']); ?> (<?php echo htmlspecialchars($myCrop['variety']); ?>)</h3>
                                <span class="quantity"><?php echo $myCrop['quantity']; ?> q</span>
                            </div>
                            <div class="crop-actions">
                                <a href="?crop_id=<?php echo $myCrop['crop_id']; ?>" class="btn btn-sm btn-primary">Check Prices</a>
                                <a href="?delete_crop=<?php echo $myCrop['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove this crop?')">Remove</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button onclick="showAddCropForm()" class="btn btn-primary mt-3">+ Add More Crops</button>
            <?php endif; ?>
        </div>

        <!-- Add Crop Modal -->
        <div id="addCropModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" onclick="hideAddCropForm()">&times;</span>
                <h3>Add Crop to Sell</h3>
                <?php if ($addCropError): ?>
                    <div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($addCropError); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_crop">
                    <div class="form-group">
                        <label>Select Crop</label>
                        <select name="crop_id" required>
                            <option value="">Choose crop</option>
                            <?php foreach ($crops as $crop): ?>
                                <option value="<?php echo $crop['id']; ?>"><?php echo htmlspecialchars($crop['name'] . ' - ' . $crop['variety']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantity (Quintals)</label>
                        <input type="number" name="quantity" step="0.1" min="0.1" required>
                    </div>
                    <div class="form-group">
                        <label>Quality Grade</label>
                        <select name="quality">
                            <option value="A">Grade A (Premium)</option>
                            <option value="B" selected>Grade B (Standard)</option>
                            <option value="C">Grade C (Basic)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Crop</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddCropForm() {
            document.getElementById('addCropModal').style.display = 'flex';
        }

        function hideAddCropForm() {
            document.getElementById('addCropModal').style.display = 'none';
        }

        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('addCropModal');
            if (event.target === modal) {
                hideAddCropForm();
            }
        }
        
        // Show success message if crop was added
        <?php if (isset($_GET['added'])): ?>
        alert('✅ Crop added successfully!');
        <?php endif; ?>
    </script>
</body>
</html>
