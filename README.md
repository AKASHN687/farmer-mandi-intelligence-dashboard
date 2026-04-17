# 🌾 Smart Mandi Intelligence System

A complete web application for farmers to compare crop prices across different mandis (markets), predict price trends, and get smart selling recommendations.

## ✨ Features

### For Farmers
- 📍 **Mandi Comparison** - Compare prices across all nearby mandis with transport cost calculation
- 🔮 **Price Prediction** - AI-powered 7-day price forecast using moving average algorithm
- 🏆 **Best Price Finder** - Automatically finds the mandi offering highest net profit
- 💡 **Smart Insights** - Get recommendations like "Sell now" or "Wait 2 days"
- 🔔 **Alert System** - Notifications when prices rise or high demand is detected
- 🌾 **My Crops** - Track crops you want to sell

### Technical
- 🔐 Secure login/signup system
- 📱 Fully mobile responsive
- 🗣️ Hindi/English bilingual support
- 📊 Interactive charts (Chart.js)
- 🗄️ MySQL database

## 🏗️ Tech Stack

| Layer | Technology |
|-------|------------|
| Frontend | HTML, CSS, JavaScript, Chart.js |
| Backend | PHP 7.4+ |
| Database | MySQL 5.7+ |
| Server | Apache (XAMPP) |

## 📁 Project Structure

```
mandi/
├── api/                    # API endpoints
│   ├── auth.php           # Login/Register APIs
│   ├── mandi.php          # Mandi data & prediction APIs
│   └── crop-entry.php     # Crop management APIs
├── assets/
│   └── css/
│       └── style.css      # Main stylesheet
├── config/
│   └── database.php       # Database connection
├── database/
│   └── schema.sql         # Database schema (optional)
├── index.php              # Homepage
├── login.php              # Login page
├── signup.php             # Registration page
├── dashboard.php          # Main dashboard
├── setup.php              # Database setup script
├── logout.php             # Logout handler
└── README.md              # This file
```

## 🚀 Installation Steps

### Prerequisites
- XAMPP installed (Apache + MySQL)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Step 1: Place Files in XAMPP
Copy this entire `mandi` folder to:
```
C:\xampp\htdocs\mandi\
```

### Step 2: Start XAMPP Services
1. Open **XAMPP Control Panel**
2. Start **Apache**
3. Start **MySQL**

### Step 3: Run Database Setup
Open browser and go to:
```
http://localhost/mandi/setup.php
```

This will:
- ✅ Create the database `mandi_system`
- ✅ Create all tables (users, mandis, crops, prices, etc.)
- ✅ Insert sample mandis (10 UP mandis)
- ✅ Insert sample crops (Wheat, Rice, Potato, etc.)
- ✅ Generate 30 days of price data

### Step 4: Access the Application
After setup completes, go to:
```
http://localhost/mandi/
```

## 👤 How to Use

### 1. Register
- Click "Sign Up" on homepage
- Enter: Name, Phone, Village, District
- Select preferred crop
- Set password

### 2. Login
- Use phone number and password
- Demo: Create any account with any phone

### 3. Dashboard
- Select your crop from dropdown
- Click "Check Prices"
- View:
  - **Comparison Table** - All mandis with prices, distances, net profit
  - **Insights** - Smart recommendations
  - **Best Recommendation** - Top suggestion with prediction
  - **Price Chart** - 30-day trend graph

### 4. Add Your Crops
- Click "Add Your First Crop"
- Select crop type and enter quantity
- View your crops list
- Click "Check Prices" on any crop card

## 🔌 API Endpoints

### Authentication
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/auth.php?action=login` | POST | Login with phone/password |
| `/api/auth.php?action=register` | POST | Register new user |
| `/api/auth.php?action=logout` | GET | Logout |

### Mandi Data
| Endpoint | Description |
|----------|-------------|
| `/api/mandi.php?action=crops` | Get all crops |
| `/api/mandi.php?action=mandis` | Get all mandis |
| `/api/mandi.php?action=compare&crop_id=1` | Compare prices for crop |
| `/api/mandi.php?action=insights&crop_id=1` | Get smart insights |
| `/api/mandi.php?action=predict&crop_id=1&mandi_id=1` | Price prediction |
| `/api/mandi.php?action=price-trend&crop_id=1&mandi_id=1&days=30` | Historical prices |

### Crop Management
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/crop-entry.php` | POST | Add new crop entry |
| `/api/crop-entry.php?action=delete&id=1` | DELETE | Remove crop entry |

## 🧮 Price Prediction Algorithm

The system uses a **3-Day Moving Average with Trend Analysis**:

```
Prediction = MA3 + (Trend × Days) + RandomVariation

Where:
- MA3 = Average of last 3 days prices
- Trend = (Day3 - Day1) / 2
- RandomVariation = ±10 (market noise)
```

### Recommendation Logic
- **UP Trend**: "Prices likely to increase. Wait 2-3 days for better rates."
- **DOWN Trend**: "Prices may decline. Sell now for maximum profit."

## 📊 Sample Data Included

### Mandis (10 UP Markets)
- Lucknow Mandi (0 km from Lucknow)
- Kanpur Mandi (80 km)
- Varanasi Mandi (320 km)
- Sitapur Mandi (85 km)
- Barabanki Mandi (55 km)
- And 5 more...

### Crops (10 Types)
- Wheat (HD-2967, PBW-550)
- Rice (Basmati, Pusa)
- Potato (Chipsona, Kufri Jyoti)
- Onion, Tomato, Mustard, Sugarcane

## 🔧 Configuration

### Database Config
Edit `config/database.php` if needed:
```php
$host = 'localhost';
$db_name = 'mandi_system';
$username = 'root';    // Change if different
$password = '';        // Change if different
```

## 🐛 Troubleshooting

### "Failed to connect to MySQL"
- Ensure MySQL is running in XAMPP
- Check username/password in `config/database.php`

### "Access denied" errors
- Run `setup.php` first to create database
- Ensure database `mandi_system` exists

### Pages not loading
- Check Apache is running in XAMPP
- Verify files are in `C:\xampp\htdocs\mandi\`
- Access via `http://localhost/mandi/` not `file://`

## 🚀 Future Enhancements

- 📍 Map view with mandi locations
- 📱 PWA for offline access
- 🔗 Integration with real AGMARKNET API
- 📧 Email/WhatsApp price alerts
- 🌦️ Weather impact on prices
- 🤖 Advanced ML prediction model

## 📄 License

This project is for educational/hackathon purposes.

---

**Made for Indian Farmers 🇮🇳**

Questions? Contact: [Your Name]
