// Advanced Sky Observatory App with Community Features
class AdvancedSkyApp {
    constructor() {
        this.baseURL = 'api/';
        this.map = null;
        this.markers = [];
        this.currentLocation = null;

        this.init();
    }

    async init() {
        // Initialize map
        this.initMap();

        // Load initial data
        await this.loadCities();
        await this.loadCommunityStats();
        await this.loadAPOD();

        // Setup event listeners
        this.setupEventListeners();
    }

    initMap() {
        this.map = L.map('mainMap').setView([39.0, 35.0], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(this.map);

        this.map.on('click', (e) => {
            this.selectLocation(e.latlng.lat, e.latlng.lng);
        });
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.switchView(e.target.dataset.view);
            });
        });

        // Tree proposal form
        document.getElementById('treeProposalForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.submitTreeProposal(e.target);
        });

        // Report issue form
        document.getElementById('reportIssueForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.submitReport(e.target);
        });

        // Leaderboard tabs
        document.querySelectorAll('.leaderboard-tabs .tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.loadLeaderboard(e.target.dataset.period);
            });
        });
    }

    switchView(viewName) {
        // Update nav buttons
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === viewName);
        });

        // Update view panels
        document.querySelectorAll('.view-panel').forEach(panel => {
            panel.classList.remove('active');
        });
        document.getElementById(viewName + '-view')?.classList.add('active');

        // Load view-specific data
        if (viewName === 'leaderboard') this.loadLeaderboard('all');
        if (viewName === 'tree-planting') this.loadProposals();
    }

    async loadCities() {
        try {
            const response = await fetch(this.baseURL + 'cities.php?action=list');
            const cities = await response.json();

            const select = document.getElementById('citySelector');
            select.innerHTML = '<option value="">Şehir seçin...</option>';

            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = JSON.stringify({lat: city.latitude, lon: city.longitude, name: city.name});
                option.textContent = city.name;
                select.appendChild(option);
            });

            select.addEventListener('change', (e) => {
                if (e.target.value) {
                    const city = JSON.parse(e.target.value);
                    this.selectLocation(city.lat, city.lon, city.name);
                }
            });
        } catch (error) {
            console.error('Cities error:', error);
        }
    }

    async selectLocation(lat, lon, name = null) {
        // Clear existing markers
        this.markers.forEach(m => this.map.removeLayer(m));
        this.markers = [];

        // Add marker
        const marker = L.marker([lat, lon]).addTo(this.map);
        this.markers.push(marker);
        this.map.setView([lat, lon], 13);

        // Update coords
        document.getElementById('coordLat').value = lat.toFixed(4);
        document.getElementById('coordLon').value = lon.toFixed(4);

        this.currentLocation = {lat, lon, name};

        // Load location analysis
        await this.loadLocationAnalysis(lat, lon);
    }

    gotoCoords() {
        const lat = parseFloat(document.getElementById('coordLat').value);
        const lon = parseFloat(document.getElementById('coordLon').value);

        if (!isNaN(lat) && !isNaN(lon)) {
            this.selectLocation(lat, lon);
        }
    }

    async loadLocationAnalysis(lat, lon) {
        const analysisDiv = document.getElementById('locationAnalysis');
        analysisDiv.innerHTML = '<div class="loading-indicator">NASA verileri yükleniyor...</div>';

        try {
            const response = await fetch(
                `${this.baseURL}nasa-advanced.php?action=complete-analysis&lat=${lat}&lon=${lon}`
            );
            const data = await response.json();

            // NDVI
            if (data.vegetation) {
                document.getElementById('ndviData').innerHTML = `
                    <div class="data-value ${this.getNDVIClass(data.vegetation.ndvi_estimate)}">
                        ${(data.vegetation.ndvi_estimate * 100).toFixed(0)}%
                    </div>
                    <p>${data.vegetation.vegetation_health}</p>
                    <p class="small-text">${data.vegetation.recommendation}</p>
                `;
            }

            // Temperature
            if (data.temperature) {
                document.getElementById('temperatureData').innerHTML = `
                    <div class="data-value temp">
                        ${data.temperature.air_temp?.toFixed(1)}°C
                    </div>
                    <p>Max: ${data.temperature.max_temp?.toFixed(1)}°C</p>
                    <p>Min: ${data.temperature.min_temp?.toFixed(1)}°C</p>
                    <p class="small-text">${data.temperature.heat_island_risk}</p>
                `;
            }

            // Fire Risk
            if (data.fire_data) {
                document.getElementById('fireRiskData').innerHTML = `
                    <div class="data-value ${data.fire_data.risk_level}">
                        ${data.fire_data.count}
                    </div>
                    <p>Aktif Yangın</p>
                    <p class="small-text">Risk: ${data.fire_data.risk_level.toUpperCase()}</p>
                `;
            }

            // Combined Analysis
            analysisDiv.innerHTML = `
                <div class="analysis-summary">
                    <h4>🌿 Bitki Örtüsü: ${data.vegetation?.vegetation_health || 'N/A'}</h4>
                    <h4>🌡️ Sıcaklık: ${data.temperature?.air_temp?.toFixed(1) || 'N/A'}°C</h4>
                    <h4>🔥 Yangın: ${data.fire_data?.count || 0} aktif</h4>
                    <h4>📏 Rakım: ${data.elevation?.elevation || 'N/A'}m</h4>
                </div>
            `;

        } catch (error) {
            console.error('Analysis error:', error);
            analysisDiv.innerHTML = '<p>Veri yüklenemedi</p>';
        }
    }

    getNDVIClass(ndvi) {
        if (ndvi > 0.7) return 'excellent';
        if (ndvi > 0.5) return 'good';
        if (ndvi > 0.3) return 'fair';
        return 'poor';
    }

    async analyzeTreeSuitability() {
        const lat = parseFloat(document.getElementById('analysisLat').value);
        const lon = parseFloat(document.getElementById('analysisLon').value);

        if (!lat || !lon) {
            alert('Lütfen koordinat girin');
            return;
        }

        const resultDiv = document.getElementById('treeSuitabilityResult');
        resultDiv.innerHTML = '<div class="loading-indicator">Analiz ediliyor...</div>';

        try {
            const response = await fetch(
                `${this.baseURL}nasa-advanced.php?action=tree-planting&lat=${lat}&lon=${lon}`
            );
            const data = await response.json();

            resultDiv.innerHTML = `
                <div class="suitability-result">
                    <div class="score-big ${this.getSuitabilityClass(data.suitability_score)}">
                        ${data.suitability_score}/100
                    </div>
                    <h4>${data.suitability_level}</h4>
                    <ul>
                        ${data.factors.map(f => `<li>✓ ${f}</li>`).join('')}
                    </ul>
                    <p><strong>Önerilen Türler:</strong> ${data.recommended_species.join(', ')}</p>
                    <p><strong>En İyi Zaman:</strong> ${data.best_planting_time}</p>
                </div>
            `;
        } catch (error) {
            console.error('Suitability error:', error);
            resultDiv.innerHTML = '<p>Analiz başarısız</p>';
        }
    }

    getSuitabilityClass(score) {
        if (score >= 80) return 'excellent';
        if (score >= 60) return 'good';
        if (score >= 40) return 'fair';
        return 'poor';
    }

    async submitTreeProposal(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch(this.baseURL + 'community.php?action=add-tree-proposal', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                alert(`✅ ${result.message}\n🎯 ${result.points_earned} puan kazandınız!`);
                form.reset();
                this.loadProposals();
            } else {
                alert('❌ ' + result.message);
            }
        } catch (error) {
            alert('Hata: ' + error.message);
        }
    }

    async loadProposals() {
        const listDiv = document.getElementById('proposalsList');
        listDiv.innerHTML = '<div class="loading-indicator">Yükleniyor...</div>';

        try {
            const response = await fetch(this.baseURL + 'community.php?action=proposals');
            const proposals = await response.json();

            listDiv.innerHTML = proposals.map(p => `
                <div class="proposal-card ${p.status}">
                    <h4>${p.location_description}</h4>
                    <p><strong>${p.tree_count} ${p.species}</strong></p>
                    <p>${p.reason}</p>
                    <div class="proposal-meta">
                        <span>👤 ${p.user_name}</span>
                        <span>👍 ${p.votes} oy</span>
                        <span class="status-badge ${p.status}">${p.status}</span>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            listDiv.innerHTML = '<p>Yüklenemedi</p>';
        }
    }

    async submitReport(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch(this.baseURL + 'community.php?action=report-issue', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                alert(`✅ ${result.message}\n🎯 ${result.points_earned} puan kazandınız!`);
                form.reset();
            } else {
                alert('❌ ' + result.message);
            }
        } catch (error) {
            alert('Hata: ' + error.message);
        }
    }

    async loadCommunityStats() {
        try {
            const response = await fetch(this.baseURL + 'community.php?action=stats');
            const stats = await response.json();

            document.getElementById('totalTrees').textContent = stats.approved_trees || 0;
            document.getElementById('totalMembers').textContent = stats.community_members || 0;
            document.getElementById('totalReports').textContent = stats.total_reports || 0;
        } catch (error) {
            console.error('Stats error:', error);
        }
    }

    async loadLeaderboard(period = 'all') {
        const listDiv = document.getElementById('leaderboardList');
        listDiv.innerHTML = '<div class="loading-indicator">Yükleniyor...</div>';

        try {
            const response = await fetch(this.baseURL + `community.php?action=leaderboard&period=${period}`);
            const users = await response.json();

            listDiv.innerHTML = users.map(u => `
                <div class="leaderboard-item rank-${u.rank}">
                    <span class="rank">#${u.rank}</span>
                    <span class="user-info">
                        <span class="rank-icon">${u.rank_info.icon}</span>
                        ${u.email}
                        <small>${u.rank_info.title}</small>
                    </span>
                    <span class="user-stats">
                        <strong>${u.points}</strong> puan
                        <small>${u.total_contributions} katkı</small>
                    </span>
                </div>
            `).join('');
        } catch (error) {
            listDiv.innerHTML = '<p>Yüklenemedi</p>';
        }
    }

    async loadUserProfile() {
        const email = document.getElementById('userEmail').value;
        if (!email) {
            alert('Lütfen e-posta girin');
            return;
        }

        try {
            const response = await fetch(this.baseURL + `community.php?action=profile&email=${encodeURIComponent(email)}`);
            const profile = await response.json();

            if (!profile || !profile.user) {
                document.getElementById('userProfileData').innerHTML = '<p>Kullanıcı bulunamadı</p>';
                return;
            }

            document.getElementById('userProfileData').innerHTML = `
                <div class="profile-info">
                    <h4>${profile.rank.icon} ${profile.rank.title}</h4>
                    <p><strong>Puan:</strong> ${profile.user.points}</p>
                    <p><strong>Katkı:</strong> ${profile.user.total_contributions}</p>
                    <div class="badges">
                        ${profile.badges.map(b => `<span class="badge">${b.badge_id}</span>`).join('')}
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Profile error:', error);
        }
    }

    async loadAPOD() {
        try {
            const response = await fetch(this.baseURL + 'nasa.php?action=apod');
            const apod = await response.json();

            if (!apod.error) {
                document.getElementById('apodDisplay').innerHTML = `
                    <h4>${apod.title}</h4>
                    <img src="${apod.url}" alt="${apod.title}" style="width:100%; border-radius:8px; margin:10px 0;">
                    <p>${apod.explanation.substring(0, 200)}...</p>
                `;
            }
        } catch (error) {
            console.error('APOD error:', error);
        }
    }

    async loadFireData() {
        if (!this.currentLocation) {
            alert('Önce bir konum seçin');
            return;
        }

        const div = document.getElementById('firmsData');
        div.innerHTML = '<div class="loading-indicator">Yükleniyor...</div>';

        try {
            const response = await fetch(
                `${this.baseURL}nasa-advanced.php?action=fire&lat=${this.currentLocation.lat}&lon=${this.currentLocation.lon}&days=7`
            );
            const data = await response.json();

            div.innerHTML = `
                <p><strong>${data.count}</strong> aktif yangın tespit edildi (7 gün)</p>
                <p>Risk Seviyesi: <span class="${data.risk_level}">${data.risk_level.toUpperCase()}</span></p>
            `;
        } catch (error) {
            div.innerHTML = '<p>Veri alınamadı</p>';
        }
    }

    async loadNDVIData() {
        if (!this.currentLocation) {
            alert('Önce bir konum seçin');
            return;
        }

        const div = document.getElementById('ndviDataFull');
        div.innerHTML = '<div class="loading-indicator">Yükleniyor...</div>';

        try {
            const response = await fetch(
                `${this.baseURL}nasa-advanced.php?action=ndvi&lat=${this.currentLocation.lat}&lon=${this.currentLocation.lon}`
            );
            const data = await response.json();

            div.innerHTML = `
                <p><strong>NDVI Değeri:</strong> ${(data.ndvi_estimate * 100).toFixed(0)}%</p>
                <p><strong>Sağlık:</strong> ${data.vegetation_health}</p>
                <p>${data.recommendation}</p>
            `;
        } catch (error) {
            div.innerHTML = '<p>Veri alınamadı</p>';
        }
    }

    async loadTempData() {
        if (!this.currentLocation) {
            alert('Önce bir konum seçin');
            return;
        }

        const div = document.getElementById('tempDataFull');
        div.innerHTML = '<div class="loading-indicator">Yükleniyor...</div>';

        try {
            const response = await fetch(
                `${this.baseURL}nasa-advanced.php?action=temperature&lat=${this.currentLocation.lat}&lon=${this.currentLocation.lon}`
            );
            const data = await response.json();

            div.innerHTML = `
                <p><strong>Hava Sıcaklığı:</strong> ${data.air_temp?.toFixed(1)}°C</p>
                <p><strong>Yüzey Sıcaklığı:</strong> ${data.surface_temp?.toFixed(1)}°C</p>
                <p><strong>Isı Adası Riski:</strong> ${data.heat_island_risk}</p>
            `;
        } catch (error) {
            div.innerHTML = '<p>Veri alınamadı</p>';
        }
    }
}

// Initialize app
let advancedApp;
document.addEventListener('DOMContentLoaded', () => {
    advancedApp = new AdvancedSkyApp();
});
