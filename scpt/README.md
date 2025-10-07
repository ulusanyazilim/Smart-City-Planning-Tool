# 🌍 SCPT - Smart City Planning Tool (Flutter)

**NASA Space Apps Challenge 2025 - Mobile Application**

Flutter Android app - API 35 (Android 15) with 16KB page size support

---

## 📱 Quick Start

```bash
cd G:\xampp\htdocs\nsa\scpt
flutter pub get
flutter run
```

## ✅ Configuration

- **Android API:** 24-35 (Android 7.0 - Android 15)
- **16KB Page Size:** ✅ Compatible
- **Package:** com.sah.scpt
- **APK Size:** 48.1 MB

## 🎯 Features - ✅ FULLY IMPLEMENTED

### 1️⃣ Location Selection
- 🗺️ Interactive OpenStreetMap
- 📍 Manual coordinate input
- 📱 GPS location detection
- 🎯 Tap-to-select on map

### 2️⃣ Area Selection
- 🔷 4-point polygon drawing
- 📏 Automatic area calculation (m²)
- 🧹 Clear and redraw
- ✅ Visual confirmation

### 3️⃣ Weight Adjustment
- ⚙️ 8 NASA data sliders (NDVI, Temperature, Elevation, Fire Risk, etc.)
- 🔄 Real-time adjustment (0.0 - 2.0x)
- 🚀 Analysis start button

### 4️⃣ Results & Reports
- 📊 Interactive bar charts (fl_chart)
- 🎯 Primary recommendation with confidence
- 📋 SWOT Analysis matrix
- 🤖 Gemini AI report generation
- 📥 PDF export (coming soon)

## 📂 Backend Connection

⚠️ **IMPORTANT:** Update `lib/services/api_service.dart` line 4:

```dart
static const String baseUrl = 'http://YOUR_IP/nsa/api';
```

Replace `YOUR_IP` with your server IP (e.g., `192.168.1.100` or `10.0.2.2` for emulator)

**Note:** Web version fully functional at `http://localhost/nsa/index.php`

---

## 🏗️ Build APK

```bash
flutter build apk --release
```

**Output:** `build/app/outputs/flutter-apk/app-release.apk` (48.1 MB)

---

**Status:** ✅ **FULLY FUNCTIONAL** - Ready for NASA Space Apps 2025
