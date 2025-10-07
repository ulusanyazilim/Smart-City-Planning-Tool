# 🌍 SCPT - Smart City Planning Tool
## Akıllı Şehir Planlama Aracı

[![NASA Space Apps Challenge](https://img.shields.io/badge/NASA%20Space%20Apps-2025-blue)](https://www.spaceappschallenge.org/2025/)
[![Team](https://img.shields.io/badge/Team-Aksehir%20Explorers-green)](https://www.spaceappschallenge.org/2025/find-a-team/aksehir-explorers/)
[![Flutter](https://img.shields.io/badge/Flutter-3.x-02569B?logo=flutter)](https://flutter.dev)
[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php)](https://www.php.net)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

**NASA uydu verileri ve yapay zeka ile akıllı şehir planlama**

---

## 📋 Proje Hakkında

**SCPT (Smart City Planning Tool)**, NASA'nın gerçek zamanlı uydu verilerini kullanarak arazi kullanım uygunluğunu analiz eden, çok platformlu bir karar destek sistemidir. Gemini AI ile detaylı raporlar oluşturur ve 7 farklı kullanım türü için bilimsel öneriler sunar.

### 🎯 Proje Amacı

Şehir planlamacılarına, belediye yöneticilerine ve karar vericilere NASA'nın açık kaynak uydu verilerini kullanarak:
- Bilimsel ve veri odaklı arazi kullanım önerileri
- Sürdürülebilir şehir planlaması
- WHO (Dünya Sağlık Örgütü) standartlarına uygun yeşil alan hesaplamaları
- İklim değişikliği etkilerini dikkate alan planlama

---

## 🏆 NASA Space Apps Challenge 2025

**Challenge**: Urban Planning with Earth Observation Data
**Team**: [Aksehir Explorers](https://www.spaceappschallenge.org/2025/find-a-team/aksehir-explorers/)
**Submission Date**: Ekim 2025
**Location**: Aksehir, Turkey

### 👥 Takım Üyeleri

| Üye | Rol |
|-----|-----|
| **Betül Taş** | Team Owner & Project Manager |
| **Osman Taş** | Full Stack Developer |
| **Mehmet Ali Uluşan** | Data Analyst & NASA API Integration |
| **Zeynep AKTOP** | UI/UX Designer & Frontend Developer |

---

## 🛰️ Kullanılan NASA API'leri

### 1. **FIRMS (Fire Information for Resource Management System)** 🔥
- Yangın riski tespiti
- 30 günlük yangın geçmişi analizi
- VIIRS NOAA-20 uydu verisi

### 2. **MODIS NDVI (Normalized Difference Vegetation Index)** 🌿
- Bitki örtüsü sağlık indeksi
- Vejetasyon yoğunluğu analizi
- Tarımsal uygunluk değerlendirmesi

### 3. **NASA POWER (Prediction Of Worldwide Energy Resources)** ☀️
- İklim ve enerji verileri
- Güneş radyasyonu (W/m²)
- Sıcaklık (T2M, T2M_MAX, T2M_MIN)
- Yağış miktarı (PRECTOTCORR)

### 4. **SRTM/OpenElevation** 🏔️
- Rakım/yükseklik verileri
- Topografik analiz
- Taşkın riski değerlendirmesi

### 5. **SMAP (Soil Moisture Active Passive)** 💧
- Toprak nemi analizi
- GWETROOT: Kök bölgesi nem (0-100 cm)
- GWETTOP: Yüzey nem (0-5 cm)
- Tarımsal sulama ihtiyacı hesaplama

---

## 🎯 Analiz Edilen Kullanım Türleri

### 1. 🌾 Tarım
- SMAP toprak nemi entegrasyonu
- Ürün uygunluk analizi
- Sulama ihtiyacı hesaplama
- İklim bazlı ürün önerileri

### 2. 🏘️ Konut
- WHO yeşil alan standartları (50 m²/kişi ideal, 9 m²/kişi minimum)
- Deprem riski analizi
- Nüfus kapasitesi hesaplama
- Ağaç dikimi önerileri

### 3. 🌳 Yeşil Alan/Park
- CO₂ emilim hesaplaması
- Biyoçeşitlilik potansiyeli
- Park tasarım önerileri
- Bakım maliyeti tahmini

### 4. ☀️ Güneş Enerjisi
- Panel verim analizi
- Yıllık enerji üretim tahmini
- Ekonomik fizibilite
- Panel yerleşim optimizasyonu

### 5. 💨 Rüzgar Enerjisi
- Kıyı ve dağ bölgesi analizi
- Türbin sayısı önerisi
- Rüzgar potansiyeli haritalaması
- Kurulu güç hesaplama

### 6. 🏖️ Turizm
- Doğal güzellik skoru
- İklim uygunluğu
- Erişilebilirlik analizi
- Güvenlik değerlendirmesi

### 7. ♨️ Jeotermal Enerji
- Gerçek jeotermal bölge tespiti
- Sıcaklık ve tektonik aktivite analizi
- Denizli-Aydın, Afyon, Kütahya-Simav bölgeleri
- Ekonomik potansiyel değerlendirmesi

---

## 🚀 Platformlar

### 1. 🌐 Web Uygulaması (PHP + JavaScript)
```
Platform: Web Browser
Tech Stack: PHP 7.4+, JavaScript ES6, Leaflet.js
Features: Gerçek zamanlı analiz, PDF export, Gemini AI rapor
```

### 2. 📱 Android Mobil (Flutter)
```
Platform: Android 5.0+
Tech Stack: Flutter 3.x, Dart
Size: 52.1 MB
Features: Offline harita, konum servisi, PDF oluşturma
```

### 3. 💻 Windows Masaüstü (Flutter)
```
Platform: Windows 10/11 (64-bit)
Type: Portable Application (kurulum gerektirmez)
Size: 15 MB (ZIP)
Features: Tam özellikli desktop deneyimi
```

---

## 📦 Kurulum

### Web Uygulaması

#### 1. Gereksinimler
- PHP 7.4 veya üzeri
- Apache/Nginx web sunucusu
- cURL extension aktif

#### 2. API Anahtarlarını Ayarlayın
```bash
# Config dosyasını kopyalayın
cp api/config.example.php api/config.php
```

#### 3. API Anahtarlarını Girin
```php
// api/config.php dosyasını düzenleyin
define('NASA_API_KEY', 'YOUR_NASA_API_KEY_HERE');
define('OPENWEATHER_API_KEY', 'YOUR_OPENWEATHER_API_KEY_HERE');
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
```

#### 4. Sunucuyu Başlatın
```bash
# XAMPP/WAMP kullanıyorsanız htdocs'a taşıyın
# Veya PHP built-in server:
php -S localhost:8000
```

---

### Flutter Mobil/Masaüstü

#### 1. Platform Dosyalarını Oluşturun
```bash
cd scpt

# Gerekli platformları oluşturun (iOS, Windows, Linux, macOS, Web)
flutter create --platforms=windows,ios,linux,macos,web .

# Not: android/ klasörü zaten mevcut
```

#### 2. API Anahtarını Ayarlayın
```dart
// scpt/lib/services/gemini_service.dart
static const String apiKey = 'YOUR_GEMINI_API_KEY_HERE';
```

#### 3. Dependencies Yükleyin
```bash
cd scpt
flutter pub get
```

#### 4. Derleyin

**Android APK:**
```bash
flutter build apk --release
# Output: scpt/build/app/outputs/flutter-apk/app-release.apk
```

**Windows EXE:**
```bash
# Önce Windows platformunu ekleyin (eğer 1. adımda yapmadıysanız)
flutter create --platforms=windows .

# Sonra derleyin
flutter build windows --release
# Output: scpt/build/windows/x64/runner/Release/scpt.exe
```

---

## 🔑 API Anahtarları Nasıl Alınır?

### NASA API Key (Ücretsiz) 🚀
1. [NASA API Portal](https://api.nasa.gov/) adresine gidin
2. İsim ve email ile kaydolun
3. Onay emailini kontrol edin
4. API anahtarınızı kopyalayın

**Kullanılan API'ler:**
- FIRMS (Fire Data)
- POWER (Climate Data)
- MODIS (Vegetation Data)

---

### Google Gemini API Key (Ücretsiz) 🤖
1. [Google AI Studio](https://makersuite.google.com/app/apikey) adresine gidin
2. Google hesabınızla giriş yapın
3. "Create API Key" butonuna tıklayın
4. Projenizi seçin veya yeni proje oluşturun
5. API anahtarınızı kopyalayın

**Kullanım:**
- AI-powered raporlama
- Gemini 2.0 Flash model
- Markdown formatında çıktı

---

### OpenWeather API Key (Ücretsiz) ⛅
1. [OpenWeatherMap](https://openweathermap.org/api) adresine gidin
2. Ücretsiz hesap oluşturun
3. Email'inizi onaylayın
4. API Keys sayfasından anahtarınızı alın

**Not:** Ücretsiz plan için günlük 1,000 istek limiti vardır.

---

## 📊 Özellikler

### 🛰️ NASA Veri Entegrasyonu
- ✅ Gerçek zamanlı uydu verileri
- ✅ 5 farklı NASA API kullanımı
- ✅ SMAP toprak nemi analizi
- ✅ FIRMS yangın riski tespiti
- ✅ MODIS bitki örtüsü NDVI
- ✅ NASA POWER iklim verileri

### 🤖 AI Raporlama
- 🤖 Gemini 2.0 Flash AI entegrasyonu
- 📄 Markdown formatında profesyonel raporlar
- 🖼️ OpenStreetMap statik harita görselleri
- 📥 PDF export özelliği
- 🌐 Türkçe ve İngilizce rapor desteği

### 🌍 WHO Standartları
- 🌳 50 m²/kişi ideal yeşil alan standardı
- 🌱 9 m²/kişi minimum yeşil alan standardı
- 🌲 Ağaç dikimi hesaplamaları (25 m²/ağaç)
- ♻️ CO₂ emilim kapasitesi (22 kg CO₂/yıl/ağaç)
- 🏘️ Nüfus projeksiyonu (5-10 yıl)

### 📈 SWOT Analizi
- 💪 **Güçlü Yönler**: NASA veri desteği
- ⚠️ **Zayıf Yönler**: Risk faktörleri
- 🎯 **Fırsatlar**: Gelişim potansiyeli
- ⚡ **Tehditler**: Çevresel riskler

### 🗺️ Harita Özellikleri
- 📍 İl/İlçe seçimi (81 il, 970+ ilçe)
- 🖱️ Manuel koordinat girişi
- ✏️ 4 noktalı poligon çizimi
- 📏 Otomatik alan hesaplama
- 🌍 OpenStreetMap entegrasyonu

---

## 🏗️ Proje Yapısı

```
nsa/
├── 📂 api/                     # PHP Backend API
│   ├── config.example.php      # API key template
│   ├── config.php             # API keys (gitignore'da)
│   ├── urban-planning.php     # Ana analiz algoritması
│   ├── nasa-advanced.php      # NASA API entegrasyonu
│   ├── swot-analysis.php      # SWOT analiz motoru
│   └── gemini-report.php      # Gemini AI rapor servisi
│
├── 📂 js/                     # Frontend JavaScript
│   ├── simple-app.js          # Ana uygulama mantığı
│   └── simple-planlama.js     # Karar destek paneli
│
├── 📂 css/                    # Stylesheet dosyaları
│   └── simple-style.css       # Modern UI stilleri
│
├── 📂 scpt/                   # Flutter uygulaması
│   ├── 📂 lib/
│   │   ├── 📂 screens/        # Uygulama ekranları
│   │   │   ├── location_screen.dart
│   │   │   ├── area_screen.dart
│   │   │   ├── weight_screen.dart
│   │   │   ├── results_screen.dart
│   │   │   └── decision_screen.dart
│   │   ├── 📂 services/       # API servisleri
│   │   │   ├── api_service.dart
│   │   │   ├── gemini_service.dart
│   │   │   └── pdf_service.dart
│   │   ├── 📂 models/         # Veri modelleri
│   │   └── 📂 providers/      # State management
│   │
│   ├── 📂 android/            # Android platform
│   ├── 📂 windows/            # Windows platform
│   ├── 📂 assets/             # İl-İlçe JSON data
│   └── pubspec.yaml           # Flutter dependencies
│
├── 📄 index.php               # Web ana sayfa
├── 📄 .gitignore             # Git ignore kuralları
├── 📄 README.md              # Bu dosya
└── 📄 LICENSE                # MIT License
```

---

## 🎨 Kullanım Akışı

### 1️⃣ Konum Seçimi
```
Yöntem 1: İl/İlçe dropdown menüsü (970+ seçenek)
Yöntem 2: Manuel koordinat girişi (enlem/boylam)
Yöntem 3: Harita üzerinden tıklama
```

### 2️⃣ Alan Belirleme
```
- 4 nokta ile poligon çizimi
- Otomatik alan hesaplama (m²)
- Dekar/hektar dönüşümü
- Harita üzerinde görselleştirme
```

### 3️⃣ Ağırlık Ayarları (Opsiyonel)
```
- NASA veri önceliklendirme
- Bölgesel özelleştirme
- Varsayılan: Dengeli ağırlıklar
```

### 4️⃣ Analiz Sonuçları
```
- 7 kullanım türü skoru (0-100)
- Detaylı öneriler ve hesaplamalar
- SWOT analizi (4 kategori)
- Gemini AI raporu
- PDF indirme
```

---

## 🔬 Skorlama Algoritması

### Tarım (Agriculture)
```
Faktörler:
✓ NDVI (0-1): Bitki örtüsü sağlığı         → Max 25 puan
✓ Sıcaklık (15-30°C optimal):              → Max 20 puan
✓ Rakım (0-1500m tarım için ideal):        → Max 15 puan
✓ Yağış (>2mm/gün):                        → Max 15 puan
✓ SMAP Toprak Nemi (40-70% ideal):         → Max 25 puan
⚠ Yangın Riski:                             → -15 puan

Maksimum Skor: 100 puan
```

### Konut (Residential)
```
Faktörler:
✓ Düşük bitki örtüsü (inşaat kolaylığı):   → Max 20 puan
✓ Sıcaklık (10-30°C yaşam konforu):        → Max 25 puan
✓ Rakım (100-1500m):                        → Max 25 puan
✓ Taşkın riski düşük:                       → Max 20 puan
⚠ Yangın Riski:                             → -20 puan

Maksimum Skor: 90 puan
```

### Yeşil Alan (Green Area)
```
Faktörler:
✓ Düşük NDVI (iyileştirme potansiyeli):    → Max 25 puan
✓ Sıcaklık (15-25°C bitki büyümesi):       → Max 25 puan
✓ Rakım (<2000m):                           → Max 20 puan
✓ SMAP Nem (35-75% ağaç/çim için):         → Max 20 puan
✓ Yağış (>1mm/gün):                        → Max 15 puan

Maksimum Skor: 100 puan
```

### Güneş Enerjisi (Solar Energy)
```
Faktörler:
✓ Güneş Radyasyonu (>220 W/m²):            → Max 35 puan
✓ Açık alan (NDVI <0.3):                   → Max 20 puan
✓ Sıcaklık (15-25°C panel verimi):         → Max 20 puan
✓ Düşük yağış (<0.5mm):                    → Max 10 puan

Maksimum Skor: 85 puan
```

### Rüzgar Enerjisi (Wind Energy)
```
Faktörler:
✓ Yüksek rakım (>1500m):                   → Max 35 puan
✓ Kıyı bölgesi (<100m deniz kenarı):       → Max 25 puan
✓ Özel rüzgar bölgeleri (Çanakkale):       → Max 15 puan
⚠ İç bölge düz alan:                        → -15 puan

Maksimum Skor: 65 puan
```

### Turizm (Tourism)
```
Faktörler:
✓ Yüksek NDVI (doğal güzellik):            → Max 25 puan
✓ Sıcaklık (18-28°C turist konforu):       → Max 25 puan
✓ Rakım (800-2500m manzara):               → Max 25 puan
✓ Düşük yangın riski:                       → Max 15 puan

Maksimum Skor: 90 puan
```

### Jeotermal Enerji (Geothermal)
```
Bölgesel Faktörler:
✓ Denizli-Aydın bölgesi:                   → 35 puan
✓ Afyonkarahisar:                          → 30 puan
✓ Kütahya-Simav:                           → 25 puan
✓ İzmir (Balçova, Seferihisar):            → 20 puan
✓ Düşük rakım (<200m) jeotermal bölgede:   → Max 15 puan
✓ Yüksek sıcaklık (>25°C):                 → Max 10 puan

Maksimum Skor: 60 puan
Not: Diğer bölgelerde 5-15 puan (düşük potansiyel)
```

---

## 📸 Ekran Görüntüleri

### Web Uygulaması
```
🗺️ Konum Seçimi → 📐 Alan Çizimi → ⚖️ Ağırlıklar → 📊 Sonuçlar → 🤖 AI Rapor
```

### Mobil Uygulama
```
📱 5 Adımlı Wizard → 🌍 Harita Entegrasyonu → 📄 PDF Export → 🌙 Dark Mode
```

### Windows Masaüstü
```
💻 Portable EXE → 🖱️ Modern UI → ⚡ Hızlı Analiz → 📥 Offline Çalışma
```

---

## 🌟 Teknik Detaylar

### Backend (PHP)
- **Framework**: Vanilla PHP 8.x
- **API Calls**: cURL ile NASA, Gemini entegrasyonu
- **Data Processing**: JSON parsing, statistical calculations
- **Error Handling**: Comprehensive error management

### Frontend (Web)
- **JavaScript**: ES6+ modern syntax
- **Map Library**: Leaflet.js v1.9.4
- **UI Framework**: Custom CSS Grid/Flexbox
- **Charts**: Chart.js for data visualization

### Mobile/Desktop (Flutter)
- **Framework**: Flutter 3.24.5
- **State Management**: Provider pattern
- **HTTP**: http ^1.2.2
- **Maps**: flutter_map ^7.0.2
- **PDF**: pdf ^3.11.1, printing ^5.13.3
- **Markdown**: flutter_markdown ^0.7.4

### NASA API Integration
```dart
// Örnek API çağrısı
final response = await http.get(
  Uri.parse(
    'https://power.larc.nasa.gov/api/temporal/daily/point'
    '?parameters=GWETROOT,GWETTOP,T2M'
    '&latitude=$lat&longitude=$lon'
    '&start=$startDate&end=$endDate'
    '&format=json'
  )
);
```

---

## 🚀 Gelecek Geliştirmeler

### Kısa Vadeli (3-6 ay)
- [ ] Çoklu dil desteği (İngilizce, Arapça)
- [ ] Kullanıcı hesap sistemi
- [ ] Analiz geçmişi kaydetme
- [ ] Karşılaştırmalı analiz (2+ alan)
- [ ] Excel export

### Orta Vadeli (6-12 ay)
- [ ] Machine Learning tahmin modeli
- [ ] 3D arazi görselleştirme
- [ ] Drone görüntü entegrasyonu
- [ ] Blockchain tabanlı arazi kayıt
- [ ] API marketplace

### Uzun Vadeli (1-2 yıl)
- [ ] Sentinel-2 yüksek çözünürlüklü görüntü
- [ ] Gerçek zamanlı IoT sensör entegrasyonu
- [ ] Augmented Reality (AR) planlama
- [ ] Avrupa Birliği Copernicus entegrasyonu
- [ ] SaaS model (Belediye aboneliği)

---

## 🤝 Katkıda Bulunma

Projeye katkıda bulunmak isterseniz:

1. Fork edin
2. Feature branch oluşturun (`git checkout -b feature/AmazingFeature`)
3. Commit edin (`git commit -m 'Add some AmazingFeature'`)
4. Push edin (`git push origin feature/AmazingFeature`)
5. Pull Request açın

### Kod Standartları
- PHP: PSR-12 coding standard
- Dart: Effective Dart guidelines
- JavaScript: ESLint + Prettier
- Commit messages: Conventional Commits

---

## 🐛 Bilinen Sorunlar

### Web
- [ ] Firefox'ta harita çizim performansı
- [ ] PDF export'ta özel karakterler

### Mobil
- [ ] iOS location permission issue (düzeltilecek)
- [ ] Android 12+ arka plan konum

### Desktop
- [ ] Windows 7 uyumluluk sorunu (planlı değil)

---

## 📄 Lisans

Bu proje [MIT License](LICENSE) altında lisanslanmıştır.

```
MIT License

Copyright (c) 2025 Aksehir Explorers - NASA Space Apps Challenge

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction...
```

---

## 🙏 Teşekkürler

### NASA & Space Apps
- **NASA API Portal** - Açık kaynak uydu verileri için
- **Space Apps Challenge** - Bu harika platformu sağladığınız için
- **NASA EOSDIS** - FIRMS, MODIS, SMAP veri arşivleri
- **NASA POWER** - İklim ve enerji veri API'si

### Teknoloji Partnerleri
- **Google Gemini AI** - Ücretsiz AI API desteği
- **Flutter Team** - Mükemmel cross-platform framework
- **OpenStreetMap** - Açık kaynak harita verileri
- **Leaflet.js** - İnteraktif harita kütüphanesi

### Topluluk
- **Stack Overflow** - Teknik sorunlarda yardım
- **GitHub Community** - Açık kaynak işbirliği
- **Flutter Türkiye** - Mobil geliştirme desteği
- **PHP Türkiye** - Backend danışmanlık

### Özel Teşekkür
Projenin her aşamasında bize destek olan ailelerimize, arkadaşlarımıza ve **NASA Space Apps Challenge** organizatörlerine sonsuz teşekkürler! 🚀

---

## 📞 İletişim

**Proje Sahibi**: Aksehir Explorers Team
**Konum**: Aksehir, Turkey
**Space Apps Profile**: [View Team](https://www.spaceappschallenge.org/2025/find-a-team/aksehir-explorers/)

### Takım İletişim
- **Betül Taş** - Team Owner & Project Manager
- **Osman Taş** - Full Stack Developer
- **Mehmet Ali Uluşan** - Data Analyst
- **Zeynep AKTOP** - UI/UX Designer

### Sosyal Medya
- 🌐 Website: [Coming Soon]
- 📧 Email: [Coming Soon]
- 💼 LinkedIn: [Coming Soon]

---

## 📚 Ek Kaynaklar

### Dokümantasyon
- [NASA API Documentation](https://api.nasa.gov/)
- [Flutter Documentation](https://docs.flutter.dev/)
- [Gemini API Guide](https://ai.google.dev/docs)

### Bilimsel Referanslar
- WHO Urban Green Spaces Guidelines
- IPCC Climate Change Reports
- NASA Earth Observation Studies

### Video Tutorials
- YouTube: SCPT Kullanım Kılavuzu [Coming Soon]
- YouTube: NASA API Entegrasyonu [Coming Soon]

---

<div align="center">

### 🌍 Built with ❤️ for Earth 🌍

**NASA Space Apps Challenge 2025**

*Akıllı Şehirler, Sürdürülebilir Gelecek*

[![NASA](https://img.shields.io/badge/Powered%20by-NASA%20Data-blue?style=for-the-badge&logo=nasa)](https://www.nasa.gov/)
[![AI](https://img.shields.io/badge/AI%20Powered-Gemini-orange?style=for-the-badge&logo=google)](https://ai.google.dev/)

---

**🚀 Made with NASA satellite data & AI by Aksehir Explorers**

*"Planlayalım, Geleceği Birlikte İnşa Edelim"*

</div>
