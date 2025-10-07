// Main Application Logic
class SkyObservatoryApp {
    constructor() {
        this.baseURL = 'api/';
        this.currentCity = null;
        this.currentDate = new Date();
        this.skyData = null;
        this.weatherData = null;
        this.simulator = null;

        this.init();
    }

    async init() {
        // Initialize 3D simulator
        this.simulator = new SkySimulator('sky-canvas');
        window.app = this; // Make available globally for simulator callbacks

        // Setup UI
        this.setupEventListeners();
        this.setDefaultDateTime();

        // Load initial data
        await this.loadCities();
        await this.loadConstellations();
        await this.updateAllData();
    }

    setupEventListeners() {
        // Update button
        document.getElementById('update-btn').addEventListener('click', () => {
            this.updateAllData();
        });

        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Modal close
        const modal = document.getElementById('object-modal');
        const closeBtn = modal.querySelector('.close-btn');
        closeBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        // Close modal on outside click
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }

    setDefaultDateTime() {
        const now = new Date();
        const dateInput = document.getElementById('date-input');
        const timeInput = document.getElementById('time-input');

        dateInput.valueAsDate = now;
        timeInput.value = now.toTimeString().substring(0, 5);
    }

    async loadCities() {
        try {
            const response = await fetch(this.baseURL + 'cities.php?action=list');
            const cities = await response.json();

            const select = document.getElementById('city-select');
            select.innerHTML = cities.map(city =>
                `<option value="${city.id}">${city.name}</option>`
            ).join('');

            this.currentCity = cities[0];
        } catch (error) {
            console.error('Error loading cities:', error);
        }
    }

    async loadConstellations() {
        try {
            const response = await fetch(this.baseURL + 'astronomy.php?action=constellations');
            const constellations = await response.json();

            const container = document.getElementById('constellations-list');
            container.innerHTML = constellations.map(c => `
                <div class="card">
                    <h3>${c.name_tr} (${c.name_en})</h3>
                    <p class="card-subtitle">${c.abbreviation}</p>
                    <p>${c.description_tr}</p>
                    <div class="legend">
                        <strong>Türk Efsanesi:</strong>
                        <p>${c.legend_tr}</p>
                    </div>
                    <p class="card-meta">Görülebilir: ${c.visible_months}</p>
                </div>
            `).join('');
        } catch (error) {
            console.error('Error loading constellations:', error);
        }
    }

    async updateAllData() {
        const cityId = document.getElementById('city-select').value;
        const date = document.getElementById('date-input').value;
        const time = document.getElementById('time-input').value;

        // Show loading
        this.showLoading();

        try {
            // Load sky data
            await this.loadSkyData(cityId, date, time);

            // Load weather data
            await this.loadWeatherData(cityId, date);

            // Load NASA APOD
            await this.loadNASAData(date);

            // Update 3D visualization
            this.updateSkyVisualization();

            // Update UI
            this.updateVisibleObjects();
            this.updateWeatherDisplay();
            this.updateSkyStats();
            this.updateAgriculturalTips();

        } catch (error) {
            console.error('Error updating data:', error);
            alert('Veri güncellenirken hata oluştu. Lütfen tekrar deneyin.');
        }
    }

    async loadSkyData(cityId, date, time) {
        const response = await fetch(
            `${this.baseURL}astronomy.php?action=sky&city_id=${cityId}&date=${date}&time=${time}`
        );
        this.skyData = await response.json();
    }

    async loadWeatherData(cityId, date) {
        try {
            const response = await fetch(
                `${this.baseURL}weather.php?action=current&city_id=${cityId}`
            );
            this.weatherData = await response.json();
        } catch (error) {
            console.error('Weather data error:', error);
            this.weatherData = null;
        }
    }

    async loadNASAData(date) {
        try {
            const apodResponse = await fetch(
                `${this.baseURL}nasa.php?action=apod&date=${date}`
            );
            const apodData = await apodResponse.json();

            const eventsResponse = await fetch(
                `${this.baseURL}nasa.php?action=events&date=${date}`
            );
            const eventsData = await eventsResponse.json();

            this.displayNASAAPOD(apodData);
            this.displaySpaceEvents(eventsData);
        } catch (error) {
            console.error('NASA data error:', error);
        }
    }

    updateSkyVisualization() {
        if (!this.skyData) return;

        // Clear existing objects
        this.simulator.clearAll();

        // Add Sun
        if (this.skyData.sun) {
            this.simulator.addSun(this.skyData.sun);
        }

        // Add Moon
        if (this.skyData.moon) {
            this.simulator.addMoon(this.skyData.moon);
        }

        // Add Planets
        if (this.skyData.planets) {
            Object.entries(this.skyData.planets).forEach(([name, data]) => {
                this.simulator.addPlanet(name, data);
            });
        }

        // Add Stars
        if (this.skyData.stars) {
            this.skyData.stars.forEach(star => {
                this.simulator.addStar(star);
            });
        }
    }

    updateVisibleObjects() {
        if (!this.skyData) return;

        const container = document.getElementById('visible-objects');
        let html = '<h4>☀️ Güneş</h4>';

        if (this.skyData.sun) {
            html += `<p>${this.skyData.sun.visible ? '✅ Görünür' : '❌ Görünmez'}
                     (Yükseklik: ${this.skyData.sun.altitude.toFixed(1)}°)</p>`;
        }

        html += '<h4>🌙 Ay</h4>';
        if (this.skyData.moon) {
            html += `<p>${this.skyData.moon.visible ? '✅ Görünür' : '❌ Görünmez'}<br>
                     Faz: ${this.skyData.moon.phase_name}<br>
                     Doluluk: ${(this.skyData.moon.phase * 100).toFixed(0)}%</p>`;
        }

        html += '<h4>🪐 Gezegenler</h4><ul>';
        if (this.skyData.planets) {
            Object.entries(this.skyData.planets).forEach(([name, data]) => {
                if (data && data.visible) {
                    html += `<li>${name}: ${data.altitude.toFixed(1)}°</li>`;
                }
            });
        }
        html += '</ul>';

        html += `<h4>⭐ Parlak Yıldızlar</h4><p>${this.skyData.stars ? this.skyData.stars.length : 0} yıldız görünür</p>`;

        container.innerHTML = html;
    }

    updateWeatherDisplay() {
        const container = document.getElementById('weather-info');

        if (!this.weatherData || this.weatherData.error) {
            container.innerHTML = '<p>Hava durumu verisi yüklenemedi</p>';
            return;
        }

        container.innerHTML = `
            <div class="weather-item">
                <strong>🌡️ Sıcaklık:</strong> ${this.weatherData.temperature?.toFixed(1)}°C
            </div>
            <div class="weather-item">
                <strong>☁️ Bulutluluk:</strong> ${this.weatherData.clouds}%
            </div>
            <div class="weather-item">
                <strong>💧 Nem:</strong> ${this.weatherData.humidity}%
            </div>
            <div class="weather-item">
                <strong>👁️ Görüş:</strong> ${this.weatherData.visibility?.toFixed(1)} km
            </div>
            <div class="weather-item">
                <strong>📝 Durum:</strong> ${this.weatherData.description}
            </div>
        `;
    }

    updateSkyStats() {
        const container = document.getElementById('sky-stats');

        if (!this.skyData) return;

        const observationScore = this.calculateObservationScore();

        container.innerHTML = `
            <div class="stat-card">
                <h4>Gözlem Kalitesi</h4>
                <div class="score ${this.getScoreClass(observationScore)}">
                    ${observationScore}/100
                </div>
                <p>${this.getQualityText(observationScore)}</p>
            </div>
            <div class="stat-card">
                <h4>Görünür Cisimler</h4>
                <p>Yıldızlar: ${this.skyData.stars?.length || 0}</p>
                <p>Gezegenler: ${this.countVisiblePlanets()}</p>
            </div>
            <div class="stat-card">
                <h4>Güneş/Ay Durumu</h4>
                <p>Güneş: ${this.skyData.sun?.visible ? 'Görünür' : 'Görünmez'}</p>
                <p>Ay: ${this.skyData.moon?.phase_name}</p>
            </div>
        `;
    }

    updateAgriculturalTips() {
        const container = document.getElementById('agricultural-tips');

        if (!this.skyData || !this.weatherData) {
            container.innerHTML = '<p>Veri yükleniyor...</p>';
            return;
        }

        const tips = this.generateAgriculturalTips();

        container.innerHTML = tips.map(tip => `
            <div class="tip-item">
                <strong>${tip.icon} ${tip.type}:</strong>
                <p>${tip.message}</p>
            </div>
        `).join('');
    }

    generateAgriculturalTips() {
        const tips = [];

        // Sun-based tips
        if (this.skyData.sun && this.skyData.sun.visible && this.skyData.sun.altitude > 45) {
            tips.push({
                icon: '☀️',
                type: 'Sulama',
                message: 'Güçlü güneş ışığı var. Sabah erken veya akşam geç saatlerde sulama yapın.'
            });
        }

        // Moon-based tips
        if (this.skyData.moon) {
            if (this.skyData.moon.phase < 0.25) {
                tips.push({
                    icon: '🌑',
                    type: 'Ekim',
                    message: 'Yeni ay döneminde kök bitkileri ekmek için uygun zamandır.'
                });
            } else if (this.skyData.moon.phase > 0.75) {
                tips.push({
                    icon: '🌕',
                    type: 'Hasat',
                    message: 'Dolunay döneminde üst kısmı hasat etmek için iyi bir zamandır.'
                });
            }
        }

        // Weather-based tips
        if (this.weatherData && this.weatherData.clouds < 30) {
            tips.push({
                icon: '🌌',
                type: 'Gözlem',
                message: 'Düşük bulutluluk! Astronomi gözlemi için mükemmel koşullar.'
            });
        }

        if (this.weatherData && this.weatherData.temperature < 5) {
            tips.push({
                icon: '❄️',
                type: 'Uyarı',
                message: 'Düşük sıcaklık! Bitkileri dondan koruyun.'
            });
        }

        return tips;
    }

    calculateObservationScore() {
        let score = 100;

        if (this.weatherData) {
            score -= (this.weatherData.clouds || 0) * 0.8;
            if (this.weatherData.humidity > 80) score -= 20;
            if (this.weatherData.visibility < 5) score -= 30;
        }

        if (this.skyData?.moon) {
            score -= this.skyData.moon.phase * 20;
        }

        return Math.max(0, Math.min(100, Math.round(score)));
    }

    getScoreClass(score) {
        if (score >= 80) return 'excellent';
        if (score >= 60) return 'good';
        if (score >= 40) return 'fair';
        return 'poor';
    }

    getQualityText(score) {
        if (score >= 80) return 'Mükemmel gözlem koşulları!';
        if (score >= 60) return 'İyi gözlem yapılabilir';
        if (score >= 40) return 'Orta kalite gözlem';
        return 'Gözlem için uygun değil';
    }

    countVisiblePlanets() {
        if (!this.skyData?.planets) return 0;
        return Object.values(this.skyData.planets).filter(p => p && p.visible).length;
    }

    displayNASAAPOD(data) {
        const container = document.getElementById('nasa-apod');

        if (data.error) {
            container.innerHTML = '<p>NASA APOD verisi yüklenemedi</p>';
            return;
        }

        container.innerHTML = `
            <div class="apod-card">
                <h3>${data.title}</h3>
                <p class="apod-date">${data.date}</p>
                ${data.media_type === 'image' ?
                    `<img src="${data.url}" alt="${data.title}" class="apod-image">` :
                    `<iframe src="${data.url}" class="apod-video"></iframe>`
                }
                <p class="apod-explanation">${data.explanation}</p>
                ${data.copyright ? `<p class="apod-copyright">© ${data.copyright}</p>` : ''}
            </div>
        `;
    }

    displaySpaceEvents(data) {
        const container = document.getElementById('space-events');

        if (!data || !data.events || data.events.length === 0) {
            container.innerHTML = '<p>Bu tarih için özel uzay olayı bulunamadı.</p>';
            return;
        }

        container.innerHTML = data.events.map(event => `
            <div class="event-card">
                <h4>${event.type}: ${event.name || event.title}</h4>
                <p>${event.description}</p>
                ${event.image_url ? `<img src="${event.image_url}" alt="${event.name}" style="max-width: 200px;">` : ''}
                ${event.distance_km ? `<p>Mesafe: ${parseFloat(event.distance_km).toLocaleString()} km</p>` : ''}
            </div>
        `).join('');
    }

    showObjectDetails(objectData) {
        const modal = document.getElementById('object-modal');
        const modalBody = document.getElementById('modal-body');

        let content = `<h2>${objectData.name}</h2>`;

        if (objectData.type === 'star') {
            content += `
                <p><strong>Takımyıldız:</strong> ${objectData.data.constellation_name || 'Bilinmiyor'}</p>
                <p><strong>Parlaklık:</strong> ${objectData.data.magnitude}</p>
                <p><strong>Spektral Tip:</strong> ${objectData.data.spectral_type}</p>
                <p><strong>Uzaklık:</strong> ${objectData.data.distance_ly} ışık yılı</p>
                <p><strong>Yükseklik:</strong> ${objectData.data.altitude.toFixed(1)}°</p>
                <p><strong>Azimut:</strong> ${objectData.data.azimuth.toFixed(1)}°</p>
                ${objectData.data.description_tr ? `<p>${objectData.data.description_tr}</p>` : ''}
            `;
        } else if (objectData.type === 'planet') {
            content += `
                <p><strong>Yükseklik:</strong> ${objectData.data.altitude.toFixed(1)}°</p>
                <p><strong>Azimut:</strong> ${objectData.data.azimuth.toFixed(1)}°</p>
                <p><strong>Sağ Açıklık:</strong> ${objectData.data.ra.toFixed(2)}°</p>
                <p><strong>Sapma:</strong> ${objectData.data.dec.toFixed(2)}°</p>
            `;
        } else if (objectData.type === 'moon') {
            content += `
                <p><strong>Faz:</strong> ${objectData.data.phase_name}</p>
                <p><strong>Doluluk:</strong> ${(objectData.data.phase * 100).toFixed(0)}%</p>
                <p><strong>Yükseklik:</strong> ${objectData.data.altitude.toFixed(1)}°</p>
                <p><strong>Azimut:</strong> ${objectData.data.azimuth.toFixed(1)}°</p>
            `;
        } else if (objectData.type === 'sun') {
            content += `
                <p><strong>Yükseklik:</strong> ${objectData.data.altitude.toFixed(1)}°</p>
                <p><strong>Azimut:</strong> ${objectData.data.azimuth.toFixed(1)}°</p>
            `;
        }

        modalBody.innerHTML = content;
        modal.classList.remove('hidden');
    }

    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });

        // Update tab panes
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });

        document.getElementById(`${tabName}-tab`).classList.add('active');
    }

    showLoading() {
        // Could add loading indicators here
    }
}

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SkyObservatoryApp();
});
