// Basit ve Profesyonel Gökyüzü Gözlem Uygulaması
class SkyObservatoryApp {
    constructor() {
        this.baseURL = 'api/';
        this.map = null;
        this.marker = null;
        this.currentLocation = null;
        this.currentDate = new Date();

        this.init();
    }

    async init() {
        // Haritayı başlat (Türkiye merkezi)
        this.map = L.map('map').setView([39.0, 35.0], 6);

        // OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(this.map);

        // Harita tıklama eventi
        this.map.on('click', (e) => {
            this.selectLocation(e.latlng.lat, e.latlng.lng);
        });

        // Tarih inputunu bugüne ayarla
        document.getElementById('dateInput').valueAsDate = new Date();

        // Şehirleri yükle
        await this.loadCities();

        // Takımyıldızları yükle
        await this.loadConstellations();

        // NASA APOD yükle
        await this.loadAPOD();
    }

    async loadCities() {
        try {
            const response = await fetch(this.baseURL + 'cities.php?action=list');
            const cities = await response.json();

            const select = document.getElementById('citySelect');
            select.innerHTML = '<option value="">Seçiniz...</option>';

            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = JSON.stringify({
                    id: city.id,
                    lat: city.latitude,
                    lon: city.longitude,
                    name: city.name
                });
                option.textContent = city.name;
                select.appendChild(option);
            });

            // Şehir seçildiğinde
            select.addEventListener('change', (e) => {
                if (e.target.value) {
                    const city = JSON.parse(e.target.value);
                    this.selectLocation(parseFloat(city.lat), parseFloat(city.lon), city.name, city.id);
                }
            });
        } catch (error) {
            console.error('Şehirler yüklenemedi:', error);
        }
    }

    selectLocation(lat, lon, cityName = null, cityId = null) {
        // Haritada marker göster
        if (this.marker) {
            this.map.removeLayer(this.marker);
        }

        this.marker = L.marker([lat, lon]).addTo(this.map);
        this.map.setView([lat, lon], 12);

        // Koordinatları input'lara yaz
        document.getElementById('latInput').value = lat.toFixed(4);
        document.getElementById('lonInput').value = lon.toFixed(4);

        // Konum bilgisini sakla
        this.currentLocation = { lat, lon, cityName, cityId };

        // Konum bilgisini göster
        document.getElementById('locationInfo').style.display = 'block';
        document.getElementById('currentCoords').textContent = `${lat.toFixed(4)}, ${lon.toFixed(4)}`;
        document.getElementById('currentCity').textContent = cityName || 'Özel Konum';

        // Verileri güncelle
        this.updateData();
    }

    gotoCoordinates() {
        const lat = parseFloat(document.getElementById('latInput').value);
        const lon = parseFloat(document.getElementById('lonInput').value);

        if (!isNaN(lat) && !isNaN(lon)) {
            if (lat >= 36 && lat <= 42 && lon >= 26 && lon <= 45) {
                this.selectLocation(lat, lon);
            } else {
                alert('Lütfen Türkiye sınırları içinde bir koordinat girin.\nEnlem: 36-42, Boylam: 26-45');
            }
        } else {
            alert('Lütfen geçerli koordinat değerleri girin.');
        }
    }

    async updateData() {
        if (!this.currentLocation) {
            alert('Lütfen önce bir konum seçin (haritadan veya şehir listesinden)');
            return;
        }

        const date = document.getElementById('dateInput').value;

        // Hava durumu yükle
        await this.loadWeather();

        // Astronomi verileri yükle
        await this.loadAstronomy(date);

        // Uydu görüntüsü göster
        this.showSatelliteImage(date);

        // Gece gökyüzünü çiz
        this.drawNightSky();
    }

    async loadWeather() {
        const weatherDiv = document.getElementById('weatherData');
        weatherDiv.innerHTML = '<p class="loading">Yükleniyor...</p>';

        try {
            const url = `https://api.openweathermap.org/data/2.5/weather?lat=${this.currentLocation.lat}&lon=${this.currentLocation.lon}&appid=a83911bc3f9db1d83729aa49f0248670&units=metric&lang=tr`;

            const response = await fetch(url);
            const data = await response.json();

            weatherDiv.innerHTML = `
                <div class="weather-item">
                    <strong>🌡️ Sıcaklık:</strong>
                    <span>${data.main.temp.toFixed(1)}°C</span>
                </div>
                <div class="weather-item">
                    <strong>☁️ Bulutluluk:</strong>
                    <span>${data.clouds.all}%</span>
                </div>
                <div class="weather-item">
                    <strong>💧 Nem:</strong>
                    <span>${data.main.humidity}%</span>
                </div>
                <div class="weather-item">
                    <strong>👁️ Görüş:</strong>
                    <span>${(data.visibility / 1000).toFixed(1)} km</span>
                </div>
                <div class="weather-item">
                    <strong>📝 Durum:</strong>
                    <span>${data.weather[0].description}</span>
                </div>
            `;

            // Gözlem kalitesi hesapla
            this.calculateObservationQuality(data);

            // Tarımsal ipuçları oluştur
            this.generateAgricultureTips(data);

        } catch (error) {
            console.error('Hava durumu hatası:', error);
            weatherDiv.innerHTML = '<p style="color: red;">Hava durumu verisi alınamadı</p>';
        }
    }

    async loadAstronomy(date) {
        const astroDiv = document.getElementById('astronomyData');
        astroDiv.innerHTML = '<p class="loading">Yükleniyor...</p>';

        try {
            const time = new Date().toTimeString().substring(0, 8);
            const cityId = this.currentLocation.cityId || 1;

            const response = await fetch(
                `${this.baseURL}astronomy.php?action=sky&city_id=${cityId}&date=${date}&time=${time}&lat=${this.currentLocation.lat}&lon=${this.currentLocation.lon}`
            );
            const data = await response.json();

            let html = '';

            if (data.sun) {
                html += `<p><strong>☀️ Güneş:</strong> ${data.sun.visible ? 'Görünür' : 'Görünmez'} (${data.sun.altitude.toFixed(1)}°)</p>`;
            }

            if (data.moon) {
                html += `<p><strong>🌙 Ay:</strong> ${data.moon.phase_name} (%${(data.moon.phase * 100).toFixed(0)})</p>`;
            }

            if (data.stars) {
                html += `<p><strong>⭐ Görünür Yıldız:</strong> ${data.stars.length}</p>`;
            }

            astroDiv.innerHTML = html || '<p>Veri yok</p>';

            // Gökyüzü verisini sakla
            this.skyData = data;

        } catch (error) {
            console.error('Astronomi hatası:', error);
            astroDiv.innerHTML = '<p style="color: red;">Astronomi verisi alınamadı</p>';
        }
    }

    calculateObservationQuality(weatherData) {
        let score = 100;

        // Bulut cezası
        score -= weatherData.clouds.all * 0.8;

        // Nem cezası
        if (weatherData.main.humidity > 80) score -= 20;
        else if (weatherData.main.humidity > 60) score -= 10;

        // Görüş mesafesi bonusu
        const visibility = weatherData.visibility / 1000;
        if (visibility < 5) score -= 30;
        else if (visibility < 8) score -= 15;

        score = Math.max(0, Math.min(100, Math.round(score)));

        const scoreElement = document.getElementById('qualityScore');
        const textElement = document.getElementById('qualityText');
        const circleElement = document.querySelector('.score-circle');

        scoreElement.textContent = score;

        // Renk ve metin
        if (score >= 80) {
            circleElement.className = 'score-circle';
            textElement.textContent = '✅ Mükemmel gözlem koşulları!';
        } else if (score >= 60) {
            circleElement.className = 'score-circle good';
            textElement.textContent = '👍 İyi, gözlem yapılabilir';
        } else if (score >= 40) {
            circleElement.className = 'score-circle fair';
            textElement.textContent = '⚠️ Orta kalite';
        } else {
            circleElement.className = 'score-circle poor';
            textElement.textContent = '❌ Gözlem için uygun değil';
        }
    }

    generateAgricultureTips(weatherData) {
        const tipsDiv = document.getElementById('agricultureTips');
        let tips = [];

        // Sıcaklık bazlı
        if (weatherData.main.temp < 5) {
            tips.push({
                icon: '❄️',
                title: 'Don Uyarısı',
                text: 'Düşük sıcaklık! Bitkileri dondan koruyun.'
            });
        }

        // Bulut bazlı
        if (weatherData.clouds.all < 30) {
            tips.push({
                icon: '☀️',
                title: 'Sulama',
                text: 'Açık hava. Sabah erken veya akşam geç saatlerde sulama yapın.'
            });
        }

        // Nem bazlı
        if (weatherData.main.humidity > 80) {
            tips.push({
                icon: '💧',
                title: 'Yüksek Nem',
                text: 'Mantar hastalıklarına dikkat edin.'
            });
        }

        // Varsayılan
        if (tips.length === 0) {
            tips.push({
                icon: '🌱',
                title: 'Normal Koşullar',
                text: 'Tarımsal faaliyetler için uygun koşullar.'
            });
        }

        tipsDiv.innerHTML = tips.map(tip => `
            <div class="tip">
                <strong>${tip.icon} ${tip.title}</strong>
                <p>${tip.text}</p>
            </div>
        `).join('');
    }

    showSatelliteImage(date) {
        const satelliteDiv = document.getElementById('satelliteView');

        // NASA Worldview URL
        const worldviewURL = `https://worldview.earthdata.nasa.gov/?v=${this.currentLocation.lon - 2},${this.currentLocation.lat - 2},${this.currentLocation.lon + 2},${this.currentLocation.lat + 2}&t=${date}`;

        // Basit uydu görüntü simülasyonu
        satelliteDiv.innerHTML = `
            <div style="background: #f0f4ff; padding: 20px; border-radius: 8px; text-align: center;">
                <p style="color: #1e3c72; margin-bottom: 15px;">
                    <strong>Seçili Konum:</strong> ${this.currentLocation.lat.toFixed(4)}°N, ${this.currentLocation.lon.toFixed(4)}°E
                </p>
                <p style="margin-bottom: 15px;">NASA Worldview gerçek zamanlı uydu görüntüsü için:</p>
                <a href="${worldviewURL}" target="_blank" class="btn" style="display: inline-block; background: #1e3c72; color: white; text-decoration: none;">
                    🛰️ NASA Worldview'de Aç
                </a>
                <p style="margin-top: 20px; color: #666; font-size: 0.9rem;">
                    Bu bağlantı, seçtiğiniz konumun NASA uydu görüntülerini gösterir.
                </p>
            </div>
        `;
    }

    drawNightSky() {
        const canvas = document.getElementById('skyCanvas');
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;

        // Gece gökyüzü gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, height);
        gradient.addColorStop(0, '#000033');
        gradient.addColorStop(0.5, '#000055');
        gradient.addColorStop(1, '#000022');

        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, width, height);

        // Yıldızlar çiz
        if (this.skyData && this.skyData.stars) {
            this.skyData.stars.forEach(star => {
                if (star.visible) {
                    // Basitleştirilmiş pozisyon (altitude ve azimuth'tan x,y)
                    const x = (star.azimuth / 360) * width;
                    const y = height - (star.altitude / 90) * height;

                    // Parlaklığa göre boyut
                    const size = Math.max(1, 4 - star.magnitude);

                    ctx.beginPath();
                    ctx.arc(x, y, size, 0, Math.PI * 2);
                    ctx.fillStyle = '#ffffff';
                    ctx.fill();

                    // Parlak yıldızlar için ışıma
                    if (star.magnitude < 2) {
                        ctx.beginPath();
                        ctx.arc(x, y, size * 2, 0, Math.PI * 2);
                        ctx.fillStyle = 'rgba(255, 255, 255, 0.3)';
                        ctx.fill();
                    }
                }
            });
        } else {
            // Rastgele yıldızlar
            for (let i = 0; i < 200; i++) {
                const x = Math.random() * width;
                const y = Math.random() * height;
                const size = Math.random() * 2;

                ctx.beginPath();
                ctx.arc(x, y, size, 0, Math.PI * 2);
                ctx.fillStyle = '#ffffff';
                ctx.fill();
            }
        }

        // Bilgi göster
        const infoDiv = document.getElementById('skyInfo');
        if (this.skyData && this.skyData.stars) {
            infoDiv.innerHTML = `
                <p><strong>Görünür Yıldız Sayısı:</strong> ${this.skyData.stars.length}</p>
                <p><strong>Tarih:</strong> ${document.getElementById('dateInput').value}</p>
                <p><strong>Konum:</strong> ${this.currentLocation.lat.toFixed(2)}°N, ${this.currentLocation.lon.toFixed(2)}°E</p>
            `;
        }
    }

    async loadConstellations() {
        try {
            const response = await fetch(this.baseURL + 'astronomy.php?action=constellations');
            const constellations = await response.json();

            const container = document.getElementById('constellations');
            container.innerHTML = constellations.slice(0, 5).map(c => `
                <div class="constellation" title="${c.legend_tr}">
                    <h4>${c.name_tr} (${c.name_en})</h4>
                    <p>${c.description_tr}</p>
                </div>
            `).join('');
        } catch (error) {
            console.error('Takımyıldızlar yüklenemedi:', error);
        }
    }

    async loadAPOD() {
        try {
            const date = document.getElementById('dateInput').value;
            const response = await fetch(`${this.baseURL}nasa.php?action=apod&date=${date}`);
            const data = await response.json();

            const apodDiv = document.getElementById('apodView');

            if (data.error) {
                apodDiv.innerHTML = '<p>APOD verisi yüklenemedi</p>';
                return;
            }

            apodDiv.innerHTML = `
                <div class="apod-card">
                    <h4>${data.title}</h4>
                    <p style="color: #999; font-size: 0.9rem; margin: 5px 0 15px 0;">${data.date}</p>
                    ${data.media_type === 'image'
                        ? `<img src="${data.url}" alt="${data.title}">`
                        : `<iframe src="${data.url}" width="100%" height="400" style="border: none; border-radius: 8px;"></iframe>`
                    }
                    <p style="margin-top: 15px; line-height: 1.6;">${data.explanation}</p>
                </div>
            `;
        } catch (error) {
            console.error('APOD hatası:', error);
        }
    }

    switchTab(tabName) {
        // Tab butonlarını güncelle
        document.querySelectorAll('.tab').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');

        // Tab içeriklerini güncelle
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        document.getElementById(tabName + '-content').classList.add('active');

        // APOD sekmesine geçildiğinde yeniden yükle
        if (tabName === 'apod') {
            this.loadAPOD();
        }
    }
}

// Uygulamayı başlat
let app;
document.addEventListener('DOMContentLoaded', () => {
    app = new SkyObservatoryApp();
});
