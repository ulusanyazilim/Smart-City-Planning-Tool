<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akıllı Arazi Planlama Sistemi</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #ffffff;
            color: #2c3e50;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: #fff;
            border-bottom: 2px solid #e0e0e0;
            padding: 20px 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .header h1 {
            font-size: 1.8rem;
            color: #1a1a1a;
            font-weight: 600;
        }

        .header p {
            color: #666;
            font-size: 0.95rem;
            margin-top: 5px;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Steps */
        .steps-nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .step {
            padding: 12px 24px;
            background: #f5f5f5;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #999;
            font-weight: 500;
            cursor: default;
        }

        .step.active {
            background: #2196F3;
            color: white;
        }

        .step.completed {
            background: #4CAF50;
            color: white;
        }

        /* Cards */
        .card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .card h2 {
            font-size: 1.4rem;
            margin-bottom: 20px;
            color: #1a1a1a;
            font-weight: 600;
        }

        /* Map */
        #map {
            height: 400px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input[type="number"],
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2196F3;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.85rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Buttons */
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #2196F3;
            color: white;
        }

        .btn-primary:hover {
            background: #1976D2;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
        }

        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .quick-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .quick-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .quick-btn:hover {
            background: #f5f5f5;
            border-color: #2196F3;
            color: #2196F3;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2196F3;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Report */
        .report-section {
            margin-top: 30px;
        }

        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }

        .report-header h2 {
            margin: 0;
            font-size: 1.8rem;
        }

        .report-body {
            border: 1px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 8px 8px;
            padding: 30px;
            background: white;
        }

        .report-content {
            white-space: pre-wrap;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.8;
            font-size: 0.95rem;
        }

        .probability-chart {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 20px 0;
        }

        .prob-bar {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .prob-label {
            min-width: 150px;
            font-weight: 500;
        }

        .prob-fill {
            flex: 1;
            height: 35px;
            background: #f5f5f5;
            border-radius: 6px;
            position: relative;
            overflow: hidden;
        }

        .prob-value {
            position: absolute;
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #81C784);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 12px;
            color: white;
            font-weight: 600;
            transition: width 1s ease;
        }

        .prob-value.recommended {
            background: linear-gradient(90deg, #2196F3, #64B5F6);
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 0.85rem;
            margin-top: 60px;
            border-top: 1px solid #e0e0e0;
        }

        /* Hidden class */
        .hidden {
            display: none;
        }

        /* Weight Sliders */
        .weight-item {
            margin-bottom: 25px;
        }

        .slider-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 10px 0;
        }

        .weight-slider {
            flex: 1;
            height: 8px;
            -webkit-appearance: none;
            background: #ddd;
            border-radius: 5px;
            outline: none;
            cursor: pointer;
        }

        .weight-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            background: #2196F3;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.2s;
        }

        .weight-slider::-webkit-slider-thumb:hover {
            background: #1976D2;
        }

        .weight-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            background: #2196F3;
            border-radius: 50%;
            cursor: pointer;
            border: none;
        }

        .weight-value {
            min-width: 50px;
            font-weight: 600;
            color: #2196F3;
            text-align: center;
        }

        .weight-item label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .weight-item small {
            display: block;
            color: #666;
            font-size: 0.85rem;
            margin-top: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 15px 20px;
            }

            .header h1 {
                font-size: 1.4rem;
            }

            .container {
                padding: 20px 15px;
            }
        }

        /* Decision Panel Styles */
        .land-use-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .land-use-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .land-use-card:hover {
            border-color: #2196F3;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.15);
            transform: translateY(-2px);
        }

        .land-use-card.selected {
            border-color: #2196F3;
            background: #e3f2fd;
        }

        .land-use-card.recommended::after {
            content: 'ÖNERİLEN';
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4CAF50;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .land-use-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .land-use-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1a1a1a;
        }

        .land-use-probability {
            color: #666;
            font-size: 0.9rem;
        }

        .recommendations-box {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 25px;
            margin-top: 20px;
        }

        .recommendations-box h3 {
            color: #1a1a1a;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            min-width: 180px;
            color: #333;
        }

        .info-value {
            color: #666;
            flex: 1;
        }

        .crop-card, .tree-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
        }

        .crop-card h4, .tree-card h4 {
            margin-bottom: 8px;
            color: #2196F3;
        }

        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>🌍 Akıllı Şehir Planlama Aracı</h1>
        <p>NASA Uydu Verileri ile Arazi Kullanım Analizi - Belediye & Kamu Kurumları İçin</p>
    </div>

    <!-- Container -->
    <div class="container">
        <!-- Steps Navigation -->
        <div class="steps-nav">
            <div class="step active" id="step1">1. Konum Seçimi</div>
            <div class="step" id="step2">2. Alan Seçimi</div>
            <div class="step" id="step3">3. Ağırlık Ayarları</div>
            <div class="step" id="step4">4. Analiz Sonucu</div>
            <div class="step" id="step5">5. Karar Destek</div>
        </div>

        <!-- Step 1: Location Selection -->
        <div class="card" id="locationCard">
            <h2>📍 1. Konum Seçimi</h2>

            <div class="form-group">
                <label>Haritadan Seçim Yapın veya Koordinat Girin</label>
                <div id="map"></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Enlem (Latitude)</label>
                    <input type="number" id="latitude" step="0.1" placeholder="Örn: 38.3575">
                    <small>Haritadan seçin veya manuel girin</small>
                </div>
                <div class="form-group">
                    <label>Boylam (Longitude)</label>
                    <input type="number" id="longitude" step="0.1" placeholder="Örn: 31.4161">
                    <small>Haritadan seçin veya manuel girin</small>
                </div>
            </div>

            <div class="form-group">
                <label>Hızlı Konum Seçimi</label>
                <div class="quick-buttons">
                    <button class="quick-btn" onclick="selectQuickLocation(38.3575, 31.4161, 'Akşehir')">📍 Akşehir</button>
                    <button class="quick-btn" onclick="selectQuickLocation(38.2806, 31.9139, 'Ilgın')">📍 Ilgın</button>
                    <button class="quick-btn" onclick="selectQuickLocation(37.8667, 32.4833, 'Konya')">📍 Konya</button>
                    <button class="quick-btn" onclick="selectQuickLocation(41.0082, 28.9784, 'Istanbul')">📍 İstanbul</button>
                    <button class="quick-btn" onclick="selectQuickLocation(39.9334, 32.8597, 'Ankara')">📍 Ankara</button>
                </div>
            </div>

            <button class="btn btn-primary" onclick="goToStep2()" id="nextBtn1" disabled>Devam Et →</button>
        </div>

        <!-- Step 2: Area Selection (Polygon) -->
        <div class="card hidden" id="areaCard">
            <h2>📐 2. Alan Seçimi</h2>

            <div class="form-group">
                <label>Harita Üzerinde Alanı Belirleyin</label>
                <div id="areaMap" style="height: 450px; border-radius: 6px; border: 1px solid #ddd; margin-bottom: 15px;"></div>
                <div style="background: #f0f8ff; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #2196F3;">
                    <p style="margin: 0; font-size: 0.9rem;">
                        <strong>📍 Nokta Sayısı: <span id="pointCount">0</span> / 4</strong><br>
                        🖱️ Haritada 4 farklı nokta tıklayarak alanınızı belirleyin
                    </p>
                </div>

                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <button class="btn btn-secondary" onclick="clearPolygon()" style="flex: 1;">
                        🗑️ Noktaları Temizle
                    </button>
                    <button class="btn btn-primary" onclick="confirmPolygon()" id="confirmBtn" disabled style="flex: 2;">
                        ✅ Alanı Onayla ve İlerle
                    </button>
                </div>

                <div id="polygonInfo" style="background: #e8f5e9; padding: 12px; border-radius: 6px; display: none; border-left: 4px solid #4CAF50;">
                    <p><strong>✅ Alan Onaylandı</strong></p>
                    <p id="polygonArea">Alan: - m²</p>
                    <p id="polygonCenter">Merkez: -</p>
                </div>
            </div>

            <div class="form-group">
                <label>Başlangıç Konumu</label>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 6px;">
                    <p id="selectedLocation">-</p>
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <button class="btn btn-secondary" onclick="goToStep1()">← Geri</button>
            </div>
        </div>

        <!-- Step 3: Weight Adjustments -->
        <div class="card hidden" id="weightsCard">
            <h2>⚖️ 3. Analiz Ağırlık Ayarları</h2>
            <p style="color: #666; margin-bottom: 30px;">
                Bölgenizin özelliklerine göre NASA veri kriterlerinin önem derecesini ayarlayın.
                Örneğin deprem bölgesi için "Deprem Riski" ağırlığını artırın.
            </p>

            <div id="weightsContainer">
                <div class="weight-item">
                    <label>🌡️ Sıcaklık (Isı Haritası)</label>
                    <div class="slider-container">
                        <input type="range" min="0" max="100" value="50" class="weight-slider" id="weight_temperature">
                        <span class="weight-value" id="val_temperature">50%</span>
                    </div>
                    <small>Bölgenizde sıcaklık kritik mi? (Ör: Çok sıcak/soğuk bölgeler)</small>
                </div>

                <div class="weight-item">
                    <label>💧 Yağış Miktarı</label>
                    <div class="slider-container">
                        <input type="range" min="0" max="100" value="50" class="weight-slider" id="weight_precipitation">
                        <span class="weight-value" id="val_precipitation">50%</span>
                    </div>
                    <small>Yağış önemli mi? (Ör: Karadeniz için yüksek, Güneydoğu için düşük)</small>
                </div>

                <div class="weight-item">
                    <label>🏗️ Deprem Riski</label>
                    <div class="slider-container">
                        <input type="range" min="0" max="100" value="50" class="weight-slider" id="weight_earthquake">
                        <span class="weight-value" id="val_earthquake">50%</span>
                    </div>
                    <small>Deprem bölgesi mi? (Ör: Marmara, Ege için çok yüksek)</small>
                </div>

                <div class="weight-item">
                    <label>🌿 NDVI (Bitki Örtüsü)</label>
                    <div class="slider-container">
                        <input type="range" min="0" max="100" value="50" class="weight-slider" id="weight_ndvi">
                        <span class="weight-value" id="val_ndvi">50%</span>
                    </div>
                    <small>Yeşil alan/tarım için kritik</small>
                </div>

                <div class="weight-item">
                    <label>☀️ Güneş Radyasyonu</label>
                    <div class="slider-container">
                        <input type="range" min="0" max="100" value="50" class="weight-slider" id="weight_solar">
                        <span class="weight-value" id="val_solar">50%</span>
                    </div>
                    <small>Güneş enerjisi potansiyeli önemli mi?</small>
                </div>

                <div class="weight-item">
                    <label>🔥 Yangın Riski (FIRMS)</label>
                    <div class="slider-container">
                        <input type="range" min="0" max="100" value="50" class="weight-slider" id="weight_fire">
                        <span class="weight-value" id="val_fire">50%</span>
                    </div>
                    <small>Orman yangını riski olan bölge mi?</small>
                </div>

                <div class="weight-item">
                    <label>📏 Rakım (Yükseklik)</label>
                    <div class="slider-container">
                        <input type="range" min="0" max="100" value="50" class="weight-slider" id="weight_elevation">
                        <span class="weight-value" id="val_elevation">50%</span>
                    </div>
                    <small>Rakım önemli mi? (Sel riski, inşaat maliyeti)</small>
                </div>

                <div class="weight-item">
                    <label>🌳 WHO Yeşil Alan Standardı</label>
                    <div class="slider-container">
                        <input type="range" min="0" max="100" value="50" class="weight-slider" id="weight_greenarea">
                        <span class="weight-value" id="val_greenarea">50%</span>
                    </div>
                    <small>Konut alanları için yeşil alan önceliği</small>
                </div>
            </div>

            <div style="background: #d1ecf1; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #17a2b8;">
                <p style="margin: 0; font-size: 0.9rem; color: #0c5460;">
                    <strong>ℹ️ Bilgi:</strong> Ağırlıkları bulunduğunuz bölgenin özelliklerine göre ayarlayabilirsiniz.
                </p>
            </div>

            <div style="display: flex; gap: 10px;">
                <button class="btn btn-secondary" onclick="goToStep2()">← Geri</button>
                <button class="btn btn-primary" onclick="startAnalysis()">NASA ile Analiz Başlat 🛰️</button>
            </div>
        </div>

        <!-- Step 4: Analysis Results -->
        <div class="card hidden" id="resultsCard">
            <div id="loadingSection" class="loading">
                <div class="loading-spinner"></div>
                <p><strong>NASA verileri analiz ediliyor...</strong></p>
                <p style="color: #999; font-size: 0.9rem;">NDVI, Sıcaklık, Deprem, Yağış verileri toplanıyor</p>
            </div>

            <div id="reportSection" class="hidden">
                <div class="report-header">
                    <h2>📊 Arazi Analiz Raporu</h2>
                    <p style="margin-top: 10px; opacity: 0.9;">NASA Uydu Verileri Kullanılarak Hazırlandı</p>
                </div>

                <div class="report-body">
                    <div id="reportContent"></div>

                    <div style="margin-top: 30px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <button class="btn btn-primary" onclick="downloadReport()">📥 Final Rapor (PDF)</button>
                        <button class="btn btn-success" id="aiReportBtn" onclick="generateAIReport()">🤖 Yapay Zeka Raporu (Gemini)</button>
                        <button class="btn" onclick="goToStep5()" style="background: #9C27B0; color: white;">📊 Detaylı Karar Destek Paneli</button>
                        <button class="btn btn-secondary" onclick="resetAnalysis()">🔄 Yeni Analiz</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5: Decision Support Panel -->
        <div class="card hidden" id="decisionCard">
            <h2>📊 5. Karar Destek Paneli</h2>
            <p style="color: #666; margin-bottom: 20px;">Alanınızı nasıl kullanmak istediğinizi seçin. Size özel detaylı öneriler sunulacak.</p>

            <div id="landUseSelector">
                <!-- Will be populated by JavaScript -->
            </div>

            <div id="detailedRecommendations" class="hidden" style="margin-top: 30px;">
                <!-- Detailed recommendations will appear here -->
            </div>

            <div style="margin-top: 30px;">
                <button class="btn btn-secondary" onclick="goBackToStep4()">← Geri</button>
                <button class="btn btn-secondary" onclick="resetAnalysis()">🔄 Yeni Analiz</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 Akıllı Şehir Planlama Aracı | NASA Space Apps Challenge</p>
        <p>FIRMS, MODIS, NDVI, NASA POWER verileri kullanılmaktadır</p>
        <p style="margin-top: 10px; color: #bbb; font-size: 0.8rem;">Kullanıcı girişi gerektirmez | Kişisel veri tutulmaz | Tüm belediye ve kamu çalışanları kullanabilir</p>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="js/simple-planlama.js"></script>
</body>
</html>
