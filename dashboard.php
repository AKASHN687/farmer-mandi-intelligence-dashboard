<?php
session_start();
require_once 'config/database.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();

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

// Get unread alerts count
$alertCount = 0;
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM alerts WHERE (user_id = ? OR user_id IS NULL) AND is_read = FALSE");
    $stmt->execute([$_SESSION['user_id']]);
    $alertCount = $stmt->fetchColumn();
} catch(PDOException $e) {
    // Handle error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Mandi Intelligence</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="dashboard">
    <nav class="navbar">
        <div class="nav-brand">
            <span class="logo">🌾</span>
            <span class="brand-text">Smart Mandi</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="analytics.php">Analytics</a>
            <a href="profile.php">Profile</a>
            <div class="alert-icon">
                🔔
                <?php if ($alertCount > 0): ?>
                    <span class="badge"><?php echo $alertCount; ?></span>
                <?php endif; ?>
            </div>
            <div class="user-menu">
                <span class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'Farmer'); ?></span>
                <a href="logout.php" class="btn btn-sm">Logout</a>
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
            <div class="crop-selector">
                <select id="quickCropSelect">
                    <option value="">Select your crop</option>
                    <?php foreach ($crops as $crop): ?>
                        <option value="<?php echo $crop['id']; ?>" data-name="<?php echo $crop['name']; ?>">
                            <?php echo $crop['name']; ?> (<?php echo $crop['variety']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button onclick="checkPrices()" class="btn btn-primary">Check Prices</button>
            </div>
        </div>

        <!-- Price Comparison Results -->
        <div id="priceResults" class="section" style="display: none;">
            <h2>📊 Mandi Price Comparison</h2>
            <div class="comparison-table-container">
                <table id="comparisonTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Mandi</th>
                            <th>Distance</th>
                            <th>Price (₹/q)</th>
                            <th>Transport</th>
                            <th>Net Profit</th>
                            <th>Demand</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Insights Section -->
        <div id="insightsSection" class="section" style="display: none;">
            <h2>💡 Smart Insights</h2>
            <div id="insightsContainer" class="insights-grid"></div>
        </div>

        <!-- Recommendation Card -->
        <div id="recommendationCard" class="section recommendation" style="display: none;">
            <h2>🎯 Best Recommendation</h2>
            <div id="recommendationContent" class="recommendation-box"></div>
        </div>

        <!-- Price Trend Chart -->
        <div id="chartSection" class="section" style="display: none;">
            <h2>📈 Price Trend (Last 30 Days)</h2>
            <div class="chart-container">
                <canvas id="priceChart"></canvas>
            </div>
        </div>

        <!-- My Crops Section -->
        <div class="section">
            <h2>🌾 My Crops</h2>
            
            <?php if (empty($myCrops)): ?>
                <div class="empty-state">
                    <p>You haven't added any crops yet.</p>
                    <button onclick="showAddCropForm()" class="btn btn-secondary">Add Your First Crop</button>
                </div>
            <?php else: ?>
                <div class="crop-grid">
                    <?php foreach ($myCrops as $myCrop): ?>
                        <div class="crop-card" data-crop-id="<?php echo $myCrop['crop_id']; ?>">
                            <div class="crop-header">
                                <h3><?php echo $myCrop['crop_name']; ?> (<?php echo $myCrop['variety']; ?>)</h3>
                                <span class="quantity"><?php echo $myCrop['quantity']; ?> q</span>
                            </div>
                            <div class="crop-actions">
                                <button onclick="checkPricesForCrop(<?php echo $myCrop['crop_id']; ?>)" class="btn btn-sm">Check Prices</button>
                                <button onclick="deleteCrop(<?php echo $myCrop['id']; ?>)" class="btn btn-sm btn-danger">Remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button onclick="showAddCropForm()" class="btn btn-secondary mt-3">+ Add More Crops</button>
            <?php endif; ?>
        </div>

        <!-- Add Crop Modal -->
        <div id="addCropModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" onclick="hideAddCropForm()">&times;</span>
                <h3>Add Crop to Sell</h3>
                <form id="addCropForm">
                    <div class="form-group">
                        <label>Select Crop</label>
                        <select id="newCropId" required>
                            <option value="">Choose crop</option>
                            <?php foreach ($crops as $crop): ?>
                                <option value="<?php echo $crop['id']; ?>"><?php echo $crop['name']; ?> - <?php echo $crop['variety']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantity (Quintals)</label>
                        <input type="number" id="newCropQuantity" step="0.1" min="0.1" required>
                    </div>
                    <div class="form-group">
                        <label>Quality Grade</label>
                        <select id="newCropQuality">
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
        let currentChart = null;

        function checkPrices() {
            const cropId = document.getElementById('quickCropSelect').value;
            if (!cropId) {
                alert('Please select a crop');
                return;
            }
            checkPricesForCrop(cropId);
        }

        async function checkPricesForCrop(cropId) {
            try {
                // Show loading state
                document.getElementById('priceResults').style.display = 'block';
                document.getElementById('insightsSection').style.display = 'block';
                document.getElementById('recommendationCard').style.display = 'block';
                document.getElementById('chartSection').style.display = 'block';
                
                // Fetch comparison data
                const compareRes = await fetch(`api/mandi.php?action=compare&crop_id=${cropId}`);
                const compareData = await compareRes.json();
                
                if (compareData.success) {
                    renderComparisonTable(compareData.comparison);
                }
                
                // Fetch insights
                const insightsRes = await fetch(`api/mandi.php?action=insights&crop_id=${cropId}`);
                const insightsData = await insightsRes.json();
                
                if (insightsData.success) {
                    renderInsights(insightsData.insights);
                }
                
                // Fetch prediction for recommendation
                // Use first mandi for prediction (usually best one)
                if (compareData.comparison && compareData.comparison.length > 0) {
                    const bestMandi = compareData.comparison[0];
                    const predictRes = await fetch(`api/mandi.php?action=predict&crop_id=${cropId}&mandi_id=${bestMandi.id}`);
                    const predictData = await predictRes.json();
                    
                    if (predictData.success) {
                        renderRecommendation(bestMandi, predictData);
                    }
                }
                
                // Load chart for first mandi
                loadPriceChart(cropId, compareData.comparison[0]?.id);
                
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to fetch price data. Please try again.');
            }
        }

        function renderComparisonTable(data) {
            const tbody = document.querySelector('#comparisonTable tbody');
            tbody.innerHTML = '';
            
            data.forEach(row => {
                const tr = document.createElement('tr');
                
                let statusBadge = '';
                if (row.price_status === 'best_price') {
                    statusBadge = '<span class="badge badge-success">Best Price</span>';
                    tr.classList.add('highlight-best');
                }
                if (row.profit_status === 'best_profit') {
                    statusBadge += ' <span class="badge badge-primary">Best Profit</span>';
                    tr.classList.add('highlight-profit');
                }
                
                tr.innerHTML = `
                    <td><strong>${row.name}</strong><br><small>${row.district}</small></td>
                    <td>${row.distance} km</td>
                    <td class="price">₹${row.price}</td>
                    <td>₹${row.transport_cost}</td>
                    <td class="profit">₹${row.net_profit}</td>
                    <td><span class="demand-${row.demand_level.toLowerCase()}">${row.demand_level}</span></td>
                    <td>${statusBadge}</td>
                `;
                
                tbody.appendChild(tr);
            });
        }

        function renderInsights(insights) {
            const container = document.getElementById('insightsContainer');
            container.innerHTML = '';
            
            insights.forEach(insight => {
                const card = document.createElement('div');
                card.className = `insight-card ${insight.type}`;
                card.innerHTML = `
                    <div class="insight-icon">${insight.icon}</div>
                    <div class="insight-content">
                        <h4>${insight.title}</h4>
                        <p>${insight.message}</p>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function renderRecommendation(mandi, prediction) {
            const container = document.getElementById('recommendationContent');
            const trendIcon = prediction.trend === 'up' ? '📈' : '📉';
            
            container.innerHTML = `
                <div class="recommendation-main">
                    <div class="mandi-info">
                        <h3>${mandi.name}</h3>
                        <p class="district">${mandi.district}</p>
                    </div>
                    <div class="price-info">
                        <div class="current">
                            <span class="label">Today's Price</span>
                            <span class="value">₹${mandi.price}/q</span>
                        </div>
                        <div class="profit">
                            <span class="label">Your Profit</span>
                            <span class="value">₹${mandi.net_profit}/q</span>
                        </div>
                    </div>
                </div>
                <div class="prediction-info">
                    <div class="trend ${prediction.trend}">
                        <span class="trend-icon">${trendIcon}</span>
                        <span>${prediction.trend.toUpperCase()} Trend</span>
                    </div>
                    <p class="recommendation-text">${prediction.recommendation}</p>
                    <div class="prediction-details">
                        <p>Current: ₹${prediction.current_price} → Predicted (3 days): ₹${prediction.predictions[2].predicted_price}</p>
                    </div>
                </div>
                <button onclick="alert('Feature coming soon: Book transport to ${mandi.name}')" class="btn btn-success btn-lg">
                    🚜 Plan Trip to ${mandi.name}
                </button>
            `;
        }

        async function loadPriceChart(cropId, mandiId) {
            if (!mandiId) return;
            
            try {
                const res = await fetch(`api/mandi.php?action=price-trend&crop_id=${cropId}&mandi_id=${mandiId}&days=30`);
                const data = await res.json();
                
                if (!data.success) return;
                
                const ctx = document.getElementById('priceChart').getContext('2d');
                
                if (currentChart) {
                    currentChart.destroy();
                }
                
                currentChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.trend.map(t => t.price_date),
                        datasets: [{
                            label: 'Price (₹/quintal)',
                            data: data.trend.map(t => t.price),
                            borderColor: '#10b981',
                            backgroundColor: '#10b98120',
                            fill: true,
                            tension: 0.3
                        }, {
                            label: 'Arrival (quintals)',
                            data: data.trend.map(t => t.arrival_quantity),
                            borderColor: '#3b82f6',
                            backgroundColor: '#3b82f620',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                title: { display: true, text: 'Price (₹)' }
                            },
                            y1: {
                                position: 'right',
                                beginAtZero: true,
                                title: { display: true, text: 'Arrival' },
                                grid: { drawOnChartArea: false }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Chart error:', error);
            }
        }

        function showAddCropForm() {
            document.getElementById('addCropModal').style.display = 'flex';
        }

        function hideAddCropForm() {
            document.getElementById('addCropModal').style.display = 'none';
        }

        document.getElementById('addCropForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                crop_id: document.getElementById('newCropId').value,
                quantity: document.getElementById('newCropQuantity').value,
                quality: document.getElementById('newCropQuality').value
            };
            
            try {
                const res = await fetch('api/crop-entry.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const data = await res.json();
                
                if (data.success) {
                    alert('Crop added successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to add crop');
                }
            } catch (error) {
                alert('Error adding crop');
            }
        });

        function deleteCrop(entryId) {
            if (!confirm('Remove this crop from your list?')) return;
            
            fetch(`api/crop-entry.php?action=delete&id=${entryId}`, { method: 'DELETE' })
                .then(() => location.reload())
                .catch(() => alert('Failed to remove crop'));
        }

        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('addCropModal');
            if (event.target === modal) {
                hideAddCropForm();
            }
        }
    </script>
</body>
</html>
