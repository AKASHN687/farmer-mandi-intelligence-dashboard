<?php
// Admin page to update market data
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Update Market Data - Smart Mandi</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #166534; }
        .card { background: #f0fdf4; padding: 20px; border-radius: 12px; margin: 20px 0; }
        button { background: #10b981; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; }
        button:hover { background: #059669; }
        .result { background: #f3f4f6; padding: 15px; border-radius: 8px; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; }
        .price-up { color: #22c55e; }
        .price-down { color: #ef4444; }
    </style>
</head>
<body>
    <h1>🌾 Update Market Data</h1>
    <p>Fetch and update real-time Uttar Pradesh mandi prices</p>

    <div class="card">
        <h3>Fetch Today's Real Market Data</h3>
        <p>This will update prices for all crops in all UP mandis based on current AGMARKNET rates.</p>
        <button onclick="updateTodayData()">🔄 Update Today's Data</button>
        <div id="todayResult" class="result" style="display: none;"></div>
    </div>

    <div class="card">
        <h3>Fetch Last 30 Days History</h3>
        <p>Generate realistic price history based on actual UP market trends.</p>
        <button onclick="updateHistoryData()">📊 Generate 30-Day History</button>
        <div id="historyResult" class="result" style="display: none;"></div>
    </div>

    <div class="card">
        <h3>Data Status</h3>
        <button onclick="checkStatus()">📈 Check Current Data</button>
        <div id="statusResult" class="result" style="display: none;"></div>
    </div>

    <div class="card">
        <h3>Current Market Snapshot (Sample)</h3>
        <div id="snapshot"></div>
    </div>

    <p><a href="index.php">← Back to Homepage</a></p>

    <script>
        async function updateTodayData() {
            const resultDiv = document.getElementById('todayResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '⏳ Fetching today\'s data...';

            try {
                const response = await fetch('api/fetch-real-data.php?action=today');
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <strong style="color: #22c55e;">✅ ${data.message}</strong><br>
                        Records updated: <strong>${data.records_updated}</strong><br>
                        Date: ${data.date}
                    `;
                    loadSnapshot();
                } else {
                    resultDiv.innerHTML = `<strong style="color: #ef4444;">❌ ${data.message}</strong>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<strong style="color: #ef4444;">❌ Error: ${error.message}</strong>`;
            }
        }

        async function updateHistoryData() {
            const resultDiv = document.getElementById('historyResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '⏳ Generating 30-day history...';

            try {
                const response = await fetch('api/fetch-real-data.php?action=fetch');
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <strong style="color: #22c55e;">✅ ${data.message}</strong><br>
                        New records: <strong>${data.inserted}</strong><br>
                        Updated records: <strong>${data.updated}</strong><br>
                        Source: ${data.source}
                    `;
                } else {
                    resultDiv.innerHTML = `<strong style="color: #ef4444;">❌ ${data.message}</strong>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<strong style="color: #ef4444;">❌ Error: ${error.message}</strong>`;
            }
        }

        async function checkStatus() {
            const resultDiv = document.getElementById('statusResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '⏳ Checking status...';

            try {
                const response = await fetch('api/fetch-real-data.php?action=status');
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.statistics;
                    let html = `
                        <strong>Database Statistics:</strong><br>
                        Total records: <strong>${stats.total_records}</strong><br>
                        Active mandis: <strong>${stats.mandis_count}</strong><br>
                        Crop types: <strong>${stats.crops_count}</strong><br>
                        Date range: <strong>${stats.earliest_date} to ${stats.latest_date}</strong>
                    `;
                    
                    if (data.today_prices_sample.length > 0) {
                        html += '<br><br><strong>Today\'s Prices (Sample):</strong><br>';
                        html += '<table><tr><th>Mandi</th><th>Crop</th><th>Price (₹)</th></tr>';
                        data.today_prices_sample.forEach(p => {
                            html += `<tr><td>${p.mandi}</td><td>${p.crop}</td><td class="price-up">₹${p.price}</td></tr>`;
                        });
                        html += '</table>';
                    } else {
                        html += '<br><br><em>No data for today yet. Click "Update Today\'s Data" above.</em>';
                    }
                    
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<strong style="color: #ef4444;">❌ ${data.message}</strong>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<strong style="color: #ef4444;">❌ Error: ${error.message}</strong>`;
            }
        }

        async function loadSnapshot() {
            const snapshotDiv = document.getElementById('snapshot');
            
            // Sample snapshot data
            const sampleData = [
                { mandi: 'Kanpur Mandi', crop: 'Wheat', price: 2470, trend: 'up' },
                { mandi: 'Lucknow Mandi', crop: 'Wheat', price: 2445, trend: 'up' },
                { mandi: 'Barabanki Mandi', crop: 'Potato', price: 875, trend: 'up' },
                { mandi: 'Kanpur Mandi', crop: 'Rice', price: 3320, trend: 'stable' },
                { mandi: 'Unnao Mandi', crop: 'Wheat', price: 2440, trend: 'up' }
            ];
            
            let html = '<table><tr><th>Mandi</th><th>Crop</th><th>Price (₹/q)</th><th>Trend</th></tr>';
            sampleData.forEach(d => {
                const trendIcon = d.trend === 'up' ? '📈' : d.trend === 'down' ? '📉' : '➡️';
                const trendClass = d.trend === 'up' ? 'price-up' : d.trend === 'down' ? 'price-down' : '';
                html += `<tr><td>${d.mandi}</td><td>${d.crop}</td><td class="${trendClass}">₹${d.price}</td><td>${trendIcon}</td></tr>`;
            });
            html += '</table>';
            
            snapshotDiv.innerHTML = html;
        }

        // Load snapshot on page load
        loadSnapshot();
    </script>
</body>
</html>
