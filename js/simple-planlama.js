// Simple Planning Module - No Authentication Required
let map;
let areaMap;
let marker;
let polygon;
let polygonPoints = [];
let selectedArea = null;
let currentStep = 1;
let analysisData = null;
let analysisResult = null; // Store full analysis result for Step 5

// Initialize map
document.addEventListener('DOMContentLoaded', function() {
    initMap();
});

// Update step indicators
function updateStepIndicators() {
    for (let i = 1; i <= 5; i++) {
        const stepElement = document.getElementById(`step${i}`);
        if (stepElement) {
            stepElement.classList.remove('active', 'completed');
            if (i === currentStep) {
                stepElement.classList.add('active');
            } else if (i < currentStep) {
                stepElement.classList.add('completed');
            }
        }
    }
}

function initMap() {
    // Initialize Leaflet map centered on Turkey
    map = L.map('map').setView([39.0, 35.0], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(map);

    // Map click event
    map.on('click', function(e) {
        selectLocation(e.latlng.lat, e.latlng.lng);
    });
}

function selectLocation(lat, lon) {
    // Remove existing marker
    if (marker) {
        map.removeLayer(marker);
    }

    // Add new marker
    marker = L.marker([lat, lon]).addTo(map);
    map.setView([lat, lon], 12);

    // Update input fields
    document.getElementById('latitude').value = lat.toFixed(4);
    document.getElementById('longitude').value = lon.toFixed(4);

    // Enable next button
    document.getElementById('nextBtn1').disabled = false;
}

function selectQuickLocation(lat, lon, name) {
    selectLocation(lat, lon);

    // Add info to marker
    if (marker) {
        marker.bindPopup(`<b>${name}</b><br>Enlem: ${lat}<br>Boylam: ${lon}`).openPopup();
    }
}

// Step Navigation
function goToStep1() {
    currentStep = 1;
    document.getElementById('locationCard').classList.remove('hidden');
    document.getElementById('areaCard').classList.add('hidden');
    document.getElementById('resultsCard').classList.add('hidden');

    updateSteps();
}

function goToStep2() {
    const lat = parseFloat(document.getElementById('latitude').value);
    const lon = parseFloat(document.getElementById('longitude').value);

    if (isNaN(lat) || isNaN(lon)) {
        alert('Lütfen geçerli bir konum seçin');
        return;
    }

    currentStep = 2;
    document.getElementById('locationCard').classList.add('hidden');
    document.getElementById('areaCard').classList.remove('hidden');
    document.getElementById('resultsCard').classList.add('hidden');

    // Show selected location
    const locationText = `📍 Enlem: ${lat.toFixed(4)}, Boylam: ${lon.toFixed(4)}`;
    document.getElementById('selectedLocation').textContent = locationText;

    // Initialize area map for polygon drawing
    setTimeout(() => {
        initAreaMap(lat, lon);
    }, 100);

    updateSteps();
}

function initAreaMap(lat, lon) {
    // Remove existing map if any
    if (areaMap) {
        areaMap.remove();
        areaMap = null;
    }

    // Initialize new map centered on selected location
    areaMap = L.map('areaMap').setView([lat, lon], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(areaMap);

    // Fix map size after container is visible
    setTimeout(() => {
        if (areaMap) {
            areaMap.invalidateSize();
        }
    }, 100);

    // Add center marker
    L.marker([lat, lon]).addTo(areaMap)
        .bindPopup('<b>Seçili Konum</b>')
        .openPopup();

    // Reset polygon data
    polygonPoints = [];
    if (polygon) {
        polygon = null;
    }

    // Click event for polygon drawing
    areaMap.on('click', function(e) {
        addPolygonPoint(e.latlng);
    });
}

function addPolygonPoint(latlng) {
    // Stop at 4 points
    if (polygonPoints.length >= 4) return;

    // Add point to array
    polygonPoints.push([latlng.lat, latlng.lng]);

    // Update point counter
    const pointCounter = document.getElementById('pointCount');
    if (pointCounter) {
        pointCounter.textContent = polygonPoints.length;
    }

    // Add marker for visual feedback
    L.circleMarker([latlng.lat, latlng.lng], {
        radius: 6,
        fillColor: '#2196F3',
        color: '#fff',
        weight: 2,
        fillOpacity: 0.8
    }).addTo(areaMap);

    // If we have 4 points, create polygon
    if (polygonPoints.length === 4) {
        createPolygon();
        // Disable further clicks
        areaMap.off('click');
    }
}

function createPolygon() {
    // Close the polygon (add first point at the end)
    const closedPoints = [...polygonPoints, polygonPoints[0]];

    // Remove existing polygon if any
    if (polygon) {
        areaMap.removeLayer(polygon);
    }

    // Create polygon
    polygon = L.polygon(polygonPoints, {
        color: '#2196F3',
        fillColor: '#2196F3',
        fillOpacity: 0.3,
        weight: 2
    }).addTo(areaMap);

    // Calculate area using Turf.js
    const turfPolygon = turf.polygon([[...polygonPoints.map(p => [p[1], p[0]]), [polygonPoints[0][1], polygonPoints[0][0]]]]);
    const area = turf.area(turfPolygon);

    // Calculate center
    const bounds = polygon.getBounds();
    const center = bounds.getCenter();

    // Save selected area data
    selectedArea = {
        points: polygonPoints,
        area: Math.round(area),
        center: {
            lat: center.lat,
            lng: center.lng
        }
    };

    // Update UI
    const areaElement = document.getElementById('polygonArea');
    const centerElement = document.getElementById('polygonCenter');
    const confirmBtn = document.getElementById('confirmBtn');

    if (areaElement) {
        areaElement.textContent = `Alan: ${selectedArea.area.toLocaleString()} m² (${(selectedArea.area / 10000).toFixed(2)} hektar)`;
    }
    if (centerElement) {
        centerElement.textContent = `Merkez: ${center.lat.toFixed(4)}°K, ${center.lng.toFixed(4)}°D`;
    }
    if (confirmBtn) {
        confirmBtn.disabled = false;
    }

    // Zoom to polygon
    areaMap.fitBounds(polygon.getBounds(), { padding: [50, 50] });
}

function clearPolygon() {
    // Clear points array
    polygonPoints = [];
    selectedArea = null;

    // Remove polygon
    if (polygon) {
        areaMap.removeLayer(polygon);
        polygon = null;
    }

    // Remove all circle markers
    areaMap.eachLayer(layer => {
        if (layer instanceof L.CircleMarker) {
            areaMap.removeLayer(layer);
        }
    });

    // Reset UI
    const pointCounter = document.getElementById('pointCount');
    const confirmBtn = document.getElementById('confirmBtn');
    const polygonInfo = document.getElementById('polygonInfo');

    if (pointCounter) pointCounter.textContent = '0';
    if (confirmBtn) confirmBtn.disabled = true;
    if (polygonInfo) polygonInfo.style.display = 'none';

    // Re-enable clicks
    areaMap.on('click', function(e) {
        addPolygonPoint(e.latlng);
    });
}

function confirmPolygon() {
    if (!selectedArea) {
        alert('Lütfen önce 4 nokta tıklayarak alanı belirleyin');
        return;
    }

    // Show confirmation
    const polygonInfo = document.getElementById('polygonInfo');
    if (polygonInfo) {
        polygonInfo.style.display = 'block';
    }

    // Go to weight adjustment step
    goToStep3();
}

function goToStep3() {
    if (!selectedArea) {
        alert('Lütfen önce alanı seçip onaylayın');
        return;
    }

    currentStep = 3;
    document.getElementById('locationCard').classList.add('hidden');
    document.getElementById('areaCard').classList.add('hidden');
    document.getElementById('weightsCard').classList.remove('hidden');
    document.getElementById('resultsCard').classList.add('hidden');

    // Initialize weight sliders
    initWeightSliders();

    updateSteps();
}

function goToStep4() {
    currentStep = 4;
    updateStepIndicators();

    document.getElementById('locationCard').classList.add('hidden');
    document.getElementById('areaCard').classList.add('hidden');
    document.getElementById('weightsCard').classList.add('hidden');
    document.getElementById('resultsCard').classList.remove('hidden');
    document.getElementById('decisionCard').classList.add('hidden');

    updateSteps();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function initWeightSliders() {
    const sliders = document.querySelectorAll('.weight-slider');
    sliders.forEach(slider => {
        // Update value display on input
        slider.addEventListener('input', function() {
            const valueId = this.id.replace('weight_', 'val_');
            document.getElementById(valueId).textContent = this.value + '%';
        });
    });
}

function updateSteps() {
    // Reset all steps
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active', 'completed');
    });

    // Mark completed steps
    for (let i = 1; i < currentStep; i++) {
        document.getElementById(`step${i}`).classList.add('completed');
    }

    // Mark current step
    document.getElementById(`step${currentStep}`).classList.add('active');
}

// Start Analysis
async function startAnalysis() {
    if (!selectedArea) {
        alert('Lütfen haritada 4 nokta tıklayarak alanı belirleyin');
        return;
    }

    const lat = selectedArea.center.lat;
    const lon = selectedArea.center.lng;
    const areaSize = selectedArea.area;

    // Get weight values
    const weights = {
        temperature: parseInt(document.getElementById('weight_temperature').value),
        precipitation: parseInt(document.getElementById('weight_precipitation').value),
        earthquake: parseInt(document.getElementById('weight_earthquake').value),
        ndvi: parseInt(document.getElementById('weight_ndvi').value),
        solar: parseInt(document.getElementById('weight_solar').value),
        fire: parseInt(document.getElementById('weight_fire').value),
        elevation: parseInt(document.getElementById('weight_elevation').value),
        greenarea: parseInt(document.getElementById('weight_greenarea').value)
    };

    // Go to results page
    goToStep4();

    // Show loading
    document.getElementById('loadingSection').classList.remove('hidden');
    document.getElementById('reportSection').classList.add('hidden');

    try {
        // Build weights query string
        const weightsQuery = Object.entries(weights)
            .map(([key, val]) => `weight_${key}=${val}`)
            .join('&');

        // Call API (check if we're in subdirectory or root)
        const apiPath = window.location.pathname.includes('/nsa/') ? 'api/urban-planning.php' : '/api/urban-planning.php';
        const response = await fetch(`${apiPath}?action=analyze&lat=${lat}&lon=${lon}&area_size=${areaSize}&${weightsQuery}`);
        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        analysisData = data;
        analysisResult = data; // Store for Step 5
        displayReport(data);

    } catch (error) {
        console.error('Analysis error:', error);
        document.getElementById('loadingSection').innerHTML = `
            <div style="color: #d32f2f; padding: 40px; text-align: center;">
                <h3>❌ Analiz Başarısız</h3>
                <p>${error.message || 'Bir hata oluştu. Lütfen tekrar deneyin.'}</p>
                <button class="btn btn-secondary" onclick="goToStep2()" style="margin-top: 20px;">← Geri Dön</button>
            </div>
        `;
    }
}

// Display Report
function displayReport(data) {
    document.getElementById('loadingSection').classList.add('hidden');
    document.getElementById('reportSection').classList.remove('hidden');

    const reportContent = document.getElementById('reportContent');

    // Build report HTML
    let html = '';

    // Introduction
    if (data.textual_report && data.textual_report.introduction) {
        html += `<div class="report-content">${data.textual_report.introduction}</div>`;
    }

    // Probability Chart (Chart.js)
    html += '<h3 style="margin: 30px 0 20px 0; color: #1a1a1a;">📊 Kullanım Uygunluk Oranları</h3>';
    html += '<div style="max-width: 800px; margin: 0 auto;">';
    html += '<canvas id="probabilityChart" width="400" height="300"></canvas>';
    html += '</div>';

    const useNames = {
        'agriculture': '🌾 Tarım',
        'residential': '🏘️ Konut',
        'green_area': '🌳 Yeşil Alan',
        'solar_energy': '☀️ Güneş Enerjisi',
        'wind_energy': '💨 Rüzgar Enerjisi',
        'tourism': '🏔️ Turizm',
        'geothermal': '♨️ Jeotermal'
    };

    // Sort by probability
    const sortedProbs = Object.entries(data.probabilities || {})
        .sort((a, b) => b[1] - a[1]);

    // Prepare data for Chart.js
    setTimeout(() => {
        const ctx = document.getElementById('probabilityChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: sortedProbs.map(([use]) => useNames[use] || use),
                    datasets: [{
                        label: 'Uygunluk Oranı (%)',
                        data: sortedProbs.map(([, prob]) => prob),
                        backgroundColor: sortedProbs.map(([use]) =>
                            use === data.primary_recommendation ? '#2196F3' : '#4CAF50'
                        ),
                        borderColor: sortedProbs.map(([use]) =>
                            use === data.primary_recommendation ? '#1976D2' : '#388E3C'
                        ),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Uygunluk: %' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return '%' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
    }, 100);

    // Analysis Header
    if (data.textual_report && data.textual_report.analysis_header) {
        html += `<div class="report-content">${data.textual_report.analysis_header}</div>`;
    }

    // Probability Analysis
    if (data.textual_report && data.textual_report.probability_analysis) {
        html += `<div class="report-content">${data.textual_report.probability_analysis}</div>`;
    }

    // NASA Data Analysis
    if (data.textual_report && data.textual_report.nasa_data_analysis) {
        html += `<div class="report-content">${data.textual_report.nasa_data_analysis}</div>`;
    }

    // Results Header
    if (data.textual_report && data.textual_report.results_header) {
        html += `<div class="report-content">${data.textual_report.results_header}</div>`;
    }

    // Recommendation
    if (data.textual_report && data.textual_report.recommendation) {
        html += `<div class="report-content">${data.textual_report.recommendation}</div>`;
    }

    // Alternatives
    if (data.textual_report && data.textual_report.alternatives) {
        html += `<div class="report-content">${data.textual_report.alternatives}</div>`;
    }

    // Secondary Analysis (dual recommendation)
    if (data.textual_report && data.textual_report.secondary_analysis) {
        html += `<div class="report-content" style="background: #fff8e1; padding: 20px; border-left: 4px solid #ffa000; margin: 20px 0;">${data.textual_report.secondary_analysis}</div>`;
    }

    // SWOT Analysis Matrix
    if (data.swot_matrix_html) {
        html += '<h3 style="margin: 40px 0 20px 0; color: #1a1a1a;">📊 SWOT Analizi</h3>';
        html += data.swot_matrix_html;
    }

    // Conclusion
    if (data.textual_report && data.textual_report.conclusion) {
        html += `<div class="report-content">${data.textual_report.conclusion}</div>`;
    }

    // Implementation Notes
    if (data.textual_report && data.textual_report.implementation) {
        html += `<div class="report-content">${data.textual_report.implementation}</div>`;
    }

    // Footer
    if (data.textual_report && data.textual_report.footer) {
        html += `<div class="report-content">${data.textual_report.footer}</div>`;
    }

    reportContent.innerHTML = html;
}

// Generate AI Report using Gemini
async function generateAIReport() {
    if (!analysisData) {
        alert('Önce bir analiz yapmalısınız');
        return;
    }

    const aiReportBtn = document.getElementById('aiReportBtn');
    const originalText = aiReportBtn.innerHTML;
    aiReportBtn.innerHTML = '<span style="margin-right: 8px;">⏳</span>Gemini AI Raporu Oluşturuluyor...';
    aiReportBtn.disabled = true;

    try {
        const apiPath = window.location.pathname.includes('/nsa/') ? 'api/gemini-report.php' : '/api/gemini-report.php';
        const response = await fetch(apiPath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                lat: analysisData.location?.latitude || selectedArea.center.lat,
                lon: analysisData.location?.longitude || selectedArea.center.lng,
                area_size: analysisData.area_size || selectedArea.area,
                scores: analysisData.scores || {},
                probabilities: analysisData.probabilities || {},
                nasa_data: analysisData.nasa_data || {},
                primary_use: analysisData.primary_recommendation || ''
            })
        });

        const result = await response.json();

        if (result.success && result.ai_report) {
            // Display AI report in modal
            showAIReportModal(result.ai_report);
        } else {
            // Show fallback report
            if (result.fallback_report) {
                showAIReportModal(result.fallback_report);
            } else {
                alert('Gemini AI raporu oluşturulamadı: ' + (result.error || 'Bilinmeyen hata'));
            }
        }
    } catch (error) {
        alert('Gemini API hatası: ' + error.message);
    } finally {
        aiReportBtn.innerHTML = originalText;
        aiReportBtn.disabled = false;
    }
}

// Create static map image URL for selected area
function createStaticMapUrl() {
    if (!selectedArea || !selectedArea.polygon || selectedArea.polygon.length < 4) {
        return null;
    }

    const points = selectedArea.polygon;
    const center = selectedArea.center;

    // Calculate bounds with padding
    const lats = points.map(p => p.lat);
    const lngs = points.map(p => p.lng);
    const minLat = Math.min(...lats) - 0.01; // Add padding
    const maxLat = Math.max(...lats) + 0.01;
    const minLng = Math.min(...lngs) - 0.01;
    const maxLng = Math.max(...lngs) + 0.01;

    // Use Mapbox Static Images API (free tier)
    // Format: https://api.mapbox.com/styles/v1/mapbox/satellite-v9/static/[lon,lat,zoom]/[width]x[height]
    // Or use OpenStreetMap Static Map
    const zoom = 14;
    const width = 800;
    const height = 500;

    // Create polygon path for overlay
    const pathCoords = points.map(p => `${p.lng},${p.lat}`).join('|');

    // Use StaticMap.me or similar service (free)
    const mapUrl = `https://staticmap.openstreetmap.de/staticmap.php?center=${center.lat},${center.lng}&zoom=${zoom}&size=${width}x${height}&maptype=mapnik&markers=${center.lat},${center.lng},red-dot`;

    return mapUrl;
}

// Show AI Report in Modal
async function showAIReportModal(reportText) {
    // Get map image
    const mapImageUrl = createStaticMapUrl();

    // Create modal overlay
    const modal = document.createElement('div');
    modal.id = 'aiReportModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        padding: 20px;
    `;

    // Create modal content
    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        background: white;
        border-radius: 12px;
        max-width: 900px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    `;

    // Convert markdown to HTML with proper formatting
    let htmlContent = reportText
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // Bold
        .replace(/\*(.*?)\*/g, '<em>$1</em>'); // Italic

    // Process line by line for proper structure
    const lines = htmlContent.split('\n');
    const processedLines = [];
    let inList = false;

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();

        if (line.startsWith('# ')) {
            processedLines.push(`<h1 style="color: #1976D2; margin: 25px 0 15px 0; font-size: 26px; font-weight: 700;">${line.substring(2)}</h1>`);
        } else if (line.startsWith('## ')) {
            processedLines.push(`<h2 style="color: #424242; margin: 20px 0 12px 0; font-size: 22px; font-weight: 600;">${line.substring(3)}</h2>`);
        } else if (line.startsWith('### ')) {
            processedLines.push(`<h3 style="color: #616161; margin: 15px 0 10px 0; font-size: 18px; font-weight: 600;">${line.substring(4)}</h3>`);
        } else if (line.startsWith('- ') || line.startsWith('* ')) {
            if (!inList) {
                processedLines.push('<ul style="margin: 10px 0; padding-left: 25px; line-height: 1.8;">');
                inList = true;
            }
            processedLines.push(`<li style="margin: 5px 0;">${line.substring(2)}</li>`);
        } else if (line === '') {
            if (inList) {
                processedLines.push('</ul>');
                inList = false;
            }
            processedLines.push('<br>');
        } else {
            if (inList) {
                processedLines.push('</ul>');
                inList = false;
            }
            if (line.length > 0) {
                processedLines.push(`<p style="margin: 10px 0; line-height: 1.8;">${line}</p>`);
            }
        }
    }

    if (inList) {
        processedLines.push('</ul>');
    }

    htmlContent = processedLines.join('\n');

    // Add map image if available
    const mapSection = mapImageUrl ? `
        <div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 8px; border: 2px solid #e0e0e0;">
            <h3 style="margin: 0 0 15px 0; color: #424242; font-size: 18px;">
                📍 Seçilen Alan Haritası
            </h3>
            <img src="${mapImageUrl}" alt="Seçilen Alan" style="width: 100%; max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <p style="display: none; color: #666; margin: 10px 0;">Harita görseli yüklenemedi.</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">
                📏 Alan: ${selectedArea?.area ? selectedArea.area.toFixed(0) + ' m²' : 'Bilinmiyor'} |
                📍 Merkez: ${selectedArea?.center ? `${selectedArea.center.lat.toFixed(6)}, ${selectedArea.center.lng.toFixed(6)}` : 'Bilinmiyor'}
            </p>
        </div>
    ` : '';

    modalContent.innerHTML = `
        <div style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px;">
                <h2 style="margin: 0; color: #1976D2; font-size: 26px;">
                    <span style="margin-right: 10px;">🤖</span>Gemini AI Şehir Planlama Raporu
                </h2>
                <button onclick="closeAIReportModal()" style="background: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold;">
                    ✕ Kapat
                </button>
            </div>
            ${mapSection}
            <div style="line-height: 1.8; color: #424242;">
                ${htmlContent}
            </div>
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0; text-align: right;">
                <button onclick="printAIReport()" style="background: #4CAF50; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 16px; margin-right: 10px;">
                    <span style="margin-right: 8px;">🖨️</span>Yazdır
                </button>
                <button onclick="closeAIReportModal()" style="background: #9e9e9e; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 16px;">
                    Kapat
                </button>
            </div>
        </div>
    `;

    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    // Close on overlay click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeAIReportModal();
        }
    });
}

// Close AI Report Modal
function closeAIReportModal() {
    const modal = document.getElementById('aiReportModal');
    if (modal) {
        modal.remove();
    }
}

// Print AI Report
function printAIReport() {
    window.print();
}

// Download Report as PDF (simplified - opens print dialog)
function downloadReport() {
    window.print();
}

// Reset Analysis
function resetAnalysis() {
    // Reset data
    analysisData = null;
    analysisResult = null;
    selectedArea = null;
    polygonPoints = [];
    currentStep = 1;

    // Reset form fields if they exist
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');
    const areaSizeInput = document.getElementById('areaSize');
    const nextBtn = document.getElementById('nextBtn1');

    if (latInput) latInput.value = '';
    if (lonInput) lonInput.value = '';
    if (areaSizeInput) areaSizeInput.value = '10000';
    if (nextBtn) nextBtn.disabled = true;

    // Remove marker from map
    if (marker) {
        map.removeLayer(marker);
        marker = null;
    }

    // Remove polygon from area map
    if (polygon && areaMap) {
        areaMap.removeLayer(polygon);
        polygon = null;
    }

    // Hide all cards and show step 1
    document.getElementById('locationCard').classList.remove('hidden');
    document.getElementById('areaCard').classList.add('hidden');
    document.getElementById('weightsCard').classList.add('hidden');
    document.getElementById('resultsCard').classList.add('hidden');
    document.getElementById('decisionCard').classList.add('hidden');

    // Update step indicators
    updateStepIndicators();

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================
// STEP 5: DECISION SUPPORT PANEL
// ============================================

let selectedLandUse = null;

function goToStep5() {
    currentStep = 5;
    updateStepIndicators();

    document.getElementById('resultsCard').classList.add('hidden');
    document.getElementById('decisionCard').classList.remove('hidden');

    // Populate land use selector
    populateLandUseSelector();

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goBackToStep4() {
    currentStep = 4;
    updateStepIndicators();

    document.getElementById('locationCard').classList.add('hidden');
    document.getElementById('areaCard').classList.add('hidden');
    document.getElementById('weightsCard').classList.add('hidden');
    document.getElementById('decisionCard').classList.add('hidden');
    document.getElementById('resultsCard').classList.remove('hidden');

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function populateLandUseSelector() {
    if (!analysisResult || !analysisResult.probabilities) return;

    const useNames = {
        'agriculture': 'Tarım',
        'residential': 'Konut/Yerleşim',
        'green_area': 'Yeşil Alan/Park',
        'solar_energy': 'Güneş Enerjisi',
        'wind_energy': 'Rüzgar Enerjisi',
        'tourism': 'Turizm',
        'geothermal': 'Jeotermal Enerji'
    };

    const useIcons = {
        'agriculture': '🌾',
        'residential': '🏘️',
        'green_area': '🌳',
        'solar_energy': '☀️',
        'wind_energy': '💨',
        'tourism': '🏔️',
        'geothermal': '♨️'
    };

    // Sort by probability
    const sorted = Object.entries(analysisResult.probabilities)
        .sort((a, b) => b[1] - a[1]);

    let html = '<div class="land-use-grid">';

    sorted.forEach(([useKey, prob]) => {
        const isRecommended = useKey === analysisResult.primary_recommendation;
        html += `
            <div class="land-use-card ${isRecommended ? 'recommended' : ''}"
                 onclick="selectLandUse('${useKey}')">
                <div class="land-use-icon">${useIcons[useKey]}</div>
                <div class="land-use-title">${useNames[useKey]}</div>
                <div class="land-use-probability">Uygunluk: %${prob}</div>
            </div>
        `;
    });

    html += '</div>';
    document.getElementById('landUseSelector').innerHTML = html;
}

function selectLandUse(useKey) {
    selectedLandUse = useKey;

    // Update selected state
    document.querySelectorAll('.land-use-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.target.closest('.land-use-card').classList.add('selected');

    // Show recommendations
    showDetailedRecommendations(useKey);
}

function showDetailedRecommendations(useKey) {
    const container = document.getElementById('detailedRecommendations');
    container.classList.remove('hidden');

    const useNames = {
        'agriculture': 'Tarım',
        'residential': 'Konut/Yerleşim',
        'green_area': 'Yeşil Alan/Park',
        'solar_energy': 'Güneş Enerjisi',
        'wind_energy': 'Rüzgar Enerjisi',
        'tourism': 'Turizm',
        'geothermal': 'Jeotermal Enerji'
    };

    let html = `
        <div class="recommendations-box">
            <h3>${useNames[useKey]} - Detaylı Öneriler</h3>
    `;

    switch(useKey) {
        case 'agriculture':
            html += getAgricultureRecommendations();
            break;
        case 'residential':
            html += getResidentialRecommendations();
            break;
        case 'green_area':
            html += getGreenAreaRecommendations();
            break;
        case 'solar_energy':
            html += getSolarRecommendations();
            break;
        case 'wind_energy':
            html += getWindRecommendations();
            break;
        case 'tourism':
            html += getTourismRecommendations();
            break;
        case 'geothermal':
            html += getGeothermalRecommendations();
            break;
    }

    html += '</div>';
    container.innerHTML = html;

    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function getAgricultureRecommendations() {
    const tempRaw = analysisResult.nasa_data?.temperature?.air_temp;
    const temp = (tempRaw && tempRaw > -999) ? tempRaw : 20;
    const precipitation = analysisResult.nasa_data?.ndvi?.precipitation || 2;
    const soilMoisture = analysisResult.nasa_data?.soil_moisture;
    const areaSize = analysisResult.area_size;
    const dekar = areaSize / 1000;

    let html = `
        <div class="info-row">
            <div class="info-label">Alan:</div>
            <div class="info-value">${areaSize.toLocaleString()} m² (${dekar.toFixed(1)} dekar)</div>
        </div>
        <div class="info-row">
            <div class="info-label">Ortalama Sıcaklık:</div>
            <div class="info-value">${temp.toFixed(1)}°C</div>
        </div>
        <div class="info-row">
            <div class="info-label">Yağış:</div>
            <div class="info-value">${precipitation.toFixed(1)} mm/gün</div>
        </div>
    `;

    // SMAP Soil Moisture Data
    if (soilMoisture && soilMoisture.root_zone_moisture !== null) {
        html += `
        <div class="info-row">
            <div class="info-label">🌍 Toprak Nemi (SMAP):</div>
            <div class="info-value"><strong>${soilMoisture.root_zone_moisture.toFixed(1)}%</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Nem Durumu:</div>
            <div class="info-value">${soilMoisture.moisture_status}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Sulama İhtiyacı:</div>
            <div class="info-value">${soilMoisture.irrigation_need}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Ürün Uygunluğu:</div>
            <div class="info-value">${soilMoisture.crop_suitability}</div>
        </div>
        `;
    }

    html += `<hr style="margin: 20px 0;"><h4 style="margin-bottom: 15px;">Önerilen Ürünler:</h4>`;

    // Crop recommendations based on temperature
    if (temp >= 25) {
        html += `
            <div class="crop-card">
                <h4>Mısır</h4>
                <p><strong>Verim:</strong> 800-1000 kg/dekar</p>
                <p><strong>Sezon:</strong> Nisan-Eylül</p>
                <p><strong>Su ihtiyacı:</strong> Yüksek</p>
            </div>
            <div class="crop-card">
                <h4>Pamuk</h4>
                <p><strong>Verim:</strong> 400-500 kg/dekar</p>
                <p><strong>Sezon:</strong> Nisan-Ekim</p>
                <p><strong>Su ihtiyacı:</strong> Orta-Yüksek</p>
            </div>
        `;
    } else if (temp >= 20) {
        html += `
            <div class="crop-card">
                <h4>Buğday</h4>
                <p><strong>Verim:</strong> 400-600 kg/dekar</p>
                <p><strong>Sezon:</strong> Ekim-Temmuz</p>
                <p><strong>Su ihtiyacı:</strong> Orta</p>
            </div>
            <div class="crop-card">
                <h4>Domates</h4>
                <p><strong>Verim:</strong> 5000-7000 kg/dekar</p>
                <p><strong>Sezon:</strong> Mayıs-Eylül</p>
                <p><strong>Su ihtiyacı:</strong> Yüksek</p>
            </div>
        `;
    } else {
        html += `
            <div class="crop-card">
                <h4>Patates</h4>
                <p><strong>Verim:</strong> 2500-4000 kg/dekar</p>
                <p><strong>Sezon:</strong> Mart-Eylül</p>
                <p><strong>Su ihtiyacı:</strong> Orta-Yüksek</p>
            </div>
            <div class="crop-card">
                <h4>Arpa</h4>
                <p><strong>Verim:</strong> 300-450 kg/dekar</p>
                <p><strong>Sezon:</strong> Ekim-Haziran</p>
                <p><strong>Su ihtiyacı:</strong> Orta</p>
            </div>
        `;
    }

    html += `
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 10px;">Öneriler:</h4>
        <ul style="margin-left: 20px; color: #666;">
            <li>Toprak analizi yaptırın (NPK değerleri)</li>
            <li>Modern sulama sistemleri kurun (damla sulama)</li>
            <li>Organik gübre kullanın</li>
            <li>Ürün rotasyonu uygulayın</li>
        </ul>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">📊 SWOT Analizi - Tarım Kullanımı:</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px;">
            <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;">
                <h5 style="margin: 0 0 10px 0; color: #2e7d32;">💪 Güçlü Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>NASA NDVI verileri mevcut</li>
                    <li>Toprak verimliliği ölçülebilir</li>
                    <li>Düşük başlangıç maliyeti</li>
                    <li>Sürdürülebilir gelir</li>
                </ul>
            </div>
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;">
                <h5 style="margin: 0 0 10px 0; color: #e65100;">⚠️ Zayıf Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>İklim değişikliğine hassas</li>
                    <li>Su kaynağı gereksinimi</li>
                    <li>Uzman işgücü ihtiyacı</li>
                    <li>Hasat döneminde yoğun emek</li>
                </ul>
            </div>
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                <h5 style="margin: 0 0 10px 0; color: #1565c0;">🎯 Fırsatlar</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Organik tarım sertifikası</li>
                    <li>Tarım destekleri</li>
                    <li>Teknolojik tarım (akıllı sera)</li>
                    <li>Kooperatif kurma imkanı</li>
                </ul>
            </div>
            <div style="background: #ffebee; padding: 15px; border-radius: 8px; border-left: 4px solid #f44336;">
                <h5 style="margin: 0 0 10px 0; color: #c62828;">⚡ Tehditler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Kuraklık riski</li>
                    <li>Pazar fiyat dalgalanmaları</li>
                    <li>Zararlı böcekler/hastalıklar</li>
                    <li>Aşırı hava olayları</li>
                </ul>
            </div>
        </div>
    `;

    return html;
}

function getResidentialRecommendations() {
    const areaSize = analysisResult.area_size;
    const usableArea = areaSize * 0.7;
    const maxHouses = Math.floor(usableArea / 120);
    const population = Math.round(maxHouses * 3.5);
    const idealGreenArea = population * 50; // WHO ideal standard
    const minGreenArea = population * 9; // WHO minimum standard
    const trees = Math.ceil(idealGreenArea / 25);

    let html = `
        <div class="info-row">
            <div class="info-label">Toplam Alan:</div>
            <div class="info-value">${areaSize.toLocaleString()} m²</div>
        </div>
        <div class="info-row">
            <div class="info-label">Kullanılabilir Alan:</div>
            <div class="info-value">${usableArea.toLocaleString()} m² (%70)</div>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">Kapasite Analizi:</h4>
        <div class="info-row">
            <div class="info-label">Maksimum Konut:</div>
            <div class="info-value"><strong>${maxHouses} adet</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Tahmini Nüfus:</div>
            <div class="info-value"><strong>${population} kişi</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Nüfus Yoğunluğu:</div>
            <div class="info-value">${Math.round(population / (areaSize / 10000))} kişi/hektar</div>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">WHO Yeşil Alan Standartları:</h4>
        <div class="info-row">
            <div class="info-label">İdeal Standard:</div>
            <div class="info-value"><strong>50 m²/kişi</strong> (WHO İdeal)</div>
        </div>
        <div class="info-row">
            <div class="info-label">Minimum Standard:</div>
            <div class="info-value"><strong>9 m²/kişi</strong> (WHO Minimum)</div>
        </div>
        <div class="info-row">
            <div class="info-label">İdeal için Gerekli Alan:</div>
            <div class="info-value">${idealGreenArea.toLocaleString()} m²</div>
        </div>
        <div class="info-row">
            <div class="info-label">Minimum için Gerekli Alan:</div>
            <div class="info-value">${minGreenArea.toLocaleString()} m²</div>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">Ağaçlandırma Planı:</h4>
        <div class="info-row">
            <div class="info-label">Dikilmesi Gereken Ağaç:</div>
            <div class="info-value"><strong>${trees} adet</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Ev Başına Ağaç:</div>
            <div class="info-value">${(trees / maxHouses).toFixed(1)} adet</div>
        </div>
        <div class="info-row">
            <div class="info-label">CO₂ Emilimi:</div>
            <div class="info-value">${(trees * 22 / 1000).toFixed(1)} ton/yıl</div>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 10px;">Önerilen Ağaç Türleri:</h4>
        <div class="tree-card">
            <h4>Çınar</h4>
            <p>30 kg CO₂/yıl • Hızlı büyüme</p>
        </div>
        <div class="tree-card">
            <h4>Meşe</h4>
            <p>25 kg CO₂/yıl • Uzun ömür</p>
        </div>
        <div class="tree-card">
            <h4>Ihlamur</h4>
            <p>22 kg CO₂/yıl • Kokulu</p>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">📊 SWOT Analizi - Konut Yerleşimi:</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px;">
            <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;">
                <h5 style="margin: 0 0 10px 0; color: #2e7d32;">💪 Güçlü Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Planlı yerleşim imkanı</li>
                    <li>WHO standartlarına uygunluk</li>
                    <li>Yeşil alan entegrasyonu</li>
                    <li>Modern altyapı kurulumu</li>
                </ul>
            </div>
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;">
                <h5 style="margin: 0 0 10px 0; color: #e65100;">⚠️ Zayıf Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Yüksek altyapı maliyeti</li>
                    <li>Su/elektrik bağlantısı gerekli</li>
                    <li>İnşaat süreci uzun</li>
                    <li>Çevresel etki yönetimi</li>
                </ul>
            </div>
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                <h5 style="margin: 0 0 10px 0; color: #1565c0;">🎯 Fırsatlar</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Kentsel dönüşüm teşvikleri</li>
                    <li>Sosyal konut projeleri</li>
                    <li>Akıllı şehir teknolojileri</li>
                    <li>Artan konut talebi</li>
                </ul>
            </div>
            <div style="background: #ffebee; padding: 15px; border-radius: 8px; border-left: 4px solid #f44336;">
                <h5 style="margin: 0 0 10px 0; color: #c62828;">⚡ Tehditler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Deprem riski (zemin etüdü)</li>
                    <li>İmar planı değişiklikleri</li>
                    <li>Aşırı nüfus yoğunluğu</li>
                    <li>Altyapı yetersizliği</li>
                </ul>
            </div>
        </div>
    `;

    return html;
}

function getGreenAreaRecommendations() {
    const areaSize = analysisResult.area_size;
    const trees = Math.ceil(areaSize / 25);

    let html = `
        <div class="info-row">
            <div class="info-label">Toplam Alan:</div>
            <div class="info-value">${areaSize.toLocaleString()} m²</div>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">Park Tasarımı:</h4>
        <div class="info-row">
            <div class="info-label">Toplam Ağaç:</div>
            <div class="info-value">${trees} adet</div>
        </div>
        <div class="info-row">
            <div class="info-label">Yürüyüş Yolları:</div>
            <div class="info-value">${(areaSize * 0.15).toLocaleString()} m</div>
        </div>
        <div class="info-row">
            <div class="info-label">Çocuk Parkı:</div>
            <div class="info-value">${Math.floor(areaSize / 1000)} adet</div>
        </div>
        <div class="info-row">
            <div class="info-label">Spor Alanı:</div>
            <div class="info-value">${(areaSize * 0.10).toLocaleString()} m²</div>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">Çevresel Etki:</h4>
        <div class="info-row">
            <div class="info-label">CO₂ Emilimi:</div>
            <div class="info-value">${(trees * 22 / 1000).toFixed(1)} ton/yıl</div>
        </div>
        <div class="info-row">
            <div class="info-label">Oksijen Üretimi:</div>
            <div class="info-value">${(trees * 120).toLocaleString()} kg/yıl</div>
        </div>
        <div class="info-row">
            <div class="info-label">Sıcaklık Azalması:</div>
            <div class="info-value">2-3°C (şehir ısı adası etkisi)</div>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">📊 SWOT Analizi - Yeşil Alan/Park:</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px;">
            <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;">
                <h5 style="margin: 0 0 10px 0; color: #2e7d32;">💪 Güçlü Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Halk sağlığına katkı</li>
                    <li>CO₂ emilimi yüksek</li>
                    <li>Kentsel ısı azaltma</li>
                    <li>Rekreasyon alanı</li>
                </ul>
            </div>
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;">
                <h5 style="margin: 0 0 10px 0; color: #e65100;">⚠️ Zayıf Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Sürekli bakım gereksinimi</li>
                    <li>Sulama maliyeti</li>
                    <li>Personel ihtiyacı</li>
                    <li>Direkt gelir getirmez</li>
                </ul>
            </div>
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                <h5 style="margin: 0 0 10px 0; color: #1565c0;">🎯 Fırsatlar</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Çevre sertifikaları</li>
                    <li>Yerel bitki türleri</li>
                    <li>Doğal sulama sistemleri</li>
                    <li>Gönüllü bakım programları</li>
                </ul>
            </div>
            <div style="background: #ffebee; padding: 15px; border-radius: 8px; border-left: 4px solid #f44336;">
                <h5 style="margin: 0 0 10px 0; color: #c62828;">⚡ Tehditler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Vandalizm riski</li>
                    <li>Kuraklık dönemleri</li>
                    <li>İmar baskısı</li>
                    <li>Bütçe kısıtlamaları</li>
                </ul>
            </div>
        </div>
    `;

    return html;
}

function getSolarRecommendations() {
    const areaSize = analysisResult.area_size;
    const panelArea = areaSize * 0.7;
    const capacity = panelArea * 0.15 / 1000; // MW
    const annualProduction = capacity * 1500; // MWh
    const homesPowered = Math.floor(annualProduction * 1000 / 3600);

    let html = `
        <div class="info-row">
            <div class="info-label">Toplam Alan:</div>
            <div class="info-value">${areaSize.toLocaleString()} m²</div>
        </div>
        <div class="info-row">
            <div class="info-label">Panel Alanı:</div>
            <div class="info-value">${panelArea.toLocaleString()} m² (%70)</div>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">Enerji Üretimi:</h4>
        <div class="info-row">
            <div class="info-label">Kurulu Güç:</div>
            <div class="info-value"><strong>${capacity.toFixed(2)} MW</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Yıllık Üretim:</div>
            <div class="info-value">${annualProduction.toFixed(0)} MWh</div>
        </div>
        <div class="info-row">
            <div class="info-label">Günlük Ortalama:</div>
            <div class="info-value">${(annualProduction * 1000 / 365).toFixed(0)} kWh</div>
        </div>
        <div class="info-row">
            <div class="info-label">Karşılayabilir Ev:</div>
            <div class="info-value"><strong>${homesPowered} adet</strong></div>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">Çevresel Etki:</h4>
        <div class="info-row">
            <div class="info-label">CO₂ Tasarrufu:</div>
            <div class="info-value">${(annualProduction * 0.5).toFixed(0)} ton/yıl</div>
        </div>
        <div class="info-row">
            <div class="info-label">Ağaç Eşdeğeri:</div>
            <div class="info-value">${Math.round(annualProduction * 0.5 * 1000 / 22)} ağaç</div>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 10px;">Öneriler:</h4>
        <ul style="margin-left: 20px; color: #666;">
            <li>Monokristal paneller kullanın (%18-22 verim)</li>
            <li>Güneye 30-35° açılı yerleştirin</li>
            <li>Düzenli temizlik yapın</li>
            <li>İzleme sistemi kurun</li>
        </ul>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">📊 SWOT Analizi - Güneş Enerjisi:</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px;">
            <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;">
                <h5 style="margin: 0 0 10px 0; color: #2e7d32;">💪 Güçlü Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Sınırsız enerji kaynağı</li>
                    <li>Düşük işletme maliyeti</li>
                    <li>NASA POWER verileri mevcut</li>
                    <li>20-25 yıl ömür</li>
                </ul>
            </div>
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;">
                <h5 style="margin: 0 0 10px 0; color: #e65100;">⚠️ Zayıf Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Yüksek başlangıç yatırımı</li>
                    <li>Hava durumuna bağımlılık</li>
                    <li>Gece üretim yok</li>
                    <li>Batarya depolama gerekebilir</li>
                </ul>
            </div>
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                <h5 style="margin: 0 0 10px 0; color: #1565c0;">🎯 Fırsatlar</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Devlet teşvikleri (YEKDEM)</li>
                    <li>Şebekeye satış imkanı</li>
                    <li>Karbon kredisi</li>
                    <li>Panel teknolojisi gelişiyor</li>
                </ul>
            </div>
            <div style="background: #ffebee; padding: 15px; border-radius: 8px; border-left: 4px solid #f44336;">
                <h5 style="margin: 0 0 10px 0; color: #c62828;">⚡ Tehditler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Dolu/fırtına hasarı</li>
                    <li>Panel verimliliği düşebilir</li>
                    <li>Elektrik alım fiyatları</li>
                    <li>Lisans gereklilikleri</li>
                </ul>
            </div>
        </div>
    `;

    return html;
}

function getWindRecommendations() {
    const areaSize = analysisResult.area_size;
    const elevation = analysisResult.nasa_data?.elevation?.elevation || 500;
    const turbineCount = Math.max(1, Math.floor(areaSize / 50000));
    const capacity = turbineCount * 2.5;

    let html = `
        <div class="info-row">
            <div class="info-label">Alan:</div>
            <div class="info-value">${areaSize.toLocaleString()} m²</div>
        </div>
        <div class="info-row">
            <div class="info-label">Rakım:</div>
            <div class="info-value">${elevation.toFixed(0)} m</div>
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">Türbin Kapasitesi:</h4>
        <div class="info-row">
            <div class="info-label">Türbin Sayısı:</div>
            <div class="info-value">${turbineCount} adet</div>
        </div>
        <div class="info-row">
            <div class="info-label">Kurulu Güç:</div>
            <div class="info-value">${capacity.toFixed(1)} MW</div>
        </div>
        <hr style="margin: 20px 0;">
    `;

    if (elevation < 500) {
        html += `
            <div class="warning-box">
                <strong>⚠️ UYARI:</strong> Düşük rakım rüzgar enerjisi için ideal değil.
                1 yıllık rüzgar ölçümü ZORUNLU!
            </div>
        `;
    } else if (elevation >= 1000) {
        html += `
            <div class="success-box">
                <strong>✓ </strong> Yüksek rakım rüzgar enerjisi için avantaj sağlıyor.
                Detaylı fizibilite çalışması önerilir.
            </div>
        `;
    }

    html += `
        <h4 style="margin-bottom: 10px;">Gereksinimler:</h4>
        <ul style="margin-left: 20px; color: #666;">
            <li>1 yıllık rüzgar hızı ölçümü (ZORUNLU)</li>
            <li>Minimum rüzgar hızı: 6 m/s (ekonomik)</li>
            <li>İdeal rüzgar hızı: 8-12 m/s</li>
            <li>Yatırım: ~${(capacity * 1000000).toLocaleString()} TL</li>
            <li>Çevresel etki değerlendirmesi</li>
        </ul>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">📊 SWOT Analizi - Rüzgar Enerjisi:</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px;">
            <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;">
                <h5 style="margin: 0 0 10px 0; color: #2e7d32;">💪 Güçlü Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Yerli ve yenilenebilir</li>
                    <li>24 saat üretim (rüzgar varsa)</li>
                    <li>Yüksek enerji yoğunluğu</li>
                    <li>Uzun ömür (20-25 yıl)</li>
                </ul>
            </div>
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;">
                <h5 style="margin: 0 0 10px 0; color: #e65100;">⚠️ Zayıf Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Çok yüksek yatırım maliyeti</li>
                    <li>Rüzgar düzensizliği</li>
                    <li>Görsel/gürültü kirliliği</li>
                    <li>Kuş göçü etkisi</li>
                </ul>
            </div>
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                <h5 style="margin: 0 0 10px 0; color: #1565c0;">🎯 Fırsatlar</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>YEKDEM garantili alım</li>
                    <li>Yüksek rakım avantajı</li>
                    <li>Teknoloji maliyetleri düşüyor</li>
                    <li>Hibrit sistemler (güneş+rüzgar)</li>
                </ul>
            </div>
            <div style="background: #ffebee; padding: 15px; border-radius: 8px; border-left: 4px solid #f44336;">
                <h5 style="margin: 0 0 10px 0; color: #c62828;">⚡ Tehditler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Fırtına/buz hasarı</li>
                    <li>Bakım maliyeti yüksek</li>
                    <li>Yerel halk direnci olabilir</li>
                    <li>Elektrik alım fiyatları</li>
                </ul>
            </div>
        </div>
    `;

    return html;
}

function getTourismRecommendations() {
    let html = `
        <h4 style="margin-bottom: 15px;">Turizm Potansiyeli:</h4>
        <ul style="margin-left: 20px; color: #666;">
            <li>Doğa turizmi tesisleri</li>
            <li>Yürüyüş parkurları</li>
            <li>Kamp alanları</li>
            <li>Mesire yerleri</li>
            <li>Fotoğraf noktaları</li>
        </ul>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 10px;">Öneriler:</h4>
        <ul style="margin-left: 20px; color: #666;">
            <li>Doğal yapıyı koruyun</li>
            <li>Sürdürülebilir turizm uygulayın</li>
            <li>Yerel halkı sürece dahil edin</li>
            <li>Altyapıyı minimalde tutun</li>
        </ul>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">📊 SWOT Analizi - Turizm:</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px;">
            <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;">
                <h5 style="margin: 0 0 10px 0; color: #2e7d32;">💪 Güçlü Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Doğal güzellikler</li>
                    <li>Düşük altyapı ihtiyacı</li>
                    <li>Yerel ekonomiye katkı</li>
                    <li>4 mevsim potansiyeli</li>
                </ul>
            </div>
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;">
                <h5 style="margin: 0 0 10px 0; color: #e65100;">⚠️ Zayıf Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Mevsimsel dalgalanmalar</li>
                    <li>Tanıtım gereksinimi</li>
                    <li>Ulaşım altyapısı</li>
                    <li>Hizmet kalitesi standardı</li>
                </ul>
            </div>
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                <h5 style="margin: 0 0 10px 0; color: #1565c0;">🎯 Fırsatlar</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Ekoturizm trendi</li>
                    <li>Yerel ürün pazarlama</li>
                    <li>Festival/etkinlik organizasyonu</li>
                    <li>Kültür Bakanlığı destekleri</li>
                </ul>
            </div>
            <div style="background: #ffebee; padding: 15px; border-radius: 8px; border-left: 4px solid #f44336;">
                <h5 style="margin: 0 0 10px 0; color: #c62828;">⚡ Tehditler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Çevre kirliliği riski</li>
                    <li>Aşırı ziyaretçi yükü</li>
                    <li>İklim değişikliği</li>
                    <li>Rekabet bölgeleri</li>
                </ul>
            </div>
        </div>
    `;

    return html;
}

function getGeothermalRecommendations() {
    let html = `
        <div class="warning-box">
            <strong>ℹ️ BİLGİ:</strong> Jeotermal enerji için detaylı jeolojik etüt gereklidir.
            MTA (Maden Tetkik ve Arama) ile iletişime geçin.
        </div>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 10px;">Gereksinimler:</h4>
        <ul style="margin-left: 20px; color: #666;">
            <li>Jeolojik etüt (MTA)</li>
            <li>Sondaj çalışmaları</li>
            <li>Sıcaklık ve debi ölçümü</li>
            <li>Kimyasal analiz</li>
            <li>Fizibilite raporu</li>
        </ul>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 10px;">Kullanım Alanları:</h4>
        <ul style="margin-left: 20px; color: #666;">
            <li>Elektrik üretimi (>150°C)</li>
            <li>Isıtma (40-100°C)</li>
            <li>Sera ısıtması</li>
            <li>Termal turizm</li>
        </ul>
        <hr style="margin: 20px 0;">
        <h4 style="margin-bottom: 15px;">📊 SWOT Analizi - Jeotermal Enerji:</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px;">
            <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;">
                <h5 style="margin: 0 0 10px 0; color: #2e7d32;">💪 Güçlü Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>7/24 kesintisiz üretim</li>
                    <li>Yerli kaynak</li>
                    <li>Düşük işletme maliyeti</li>
                    <li>Çok amaçlı kullanım</li>
                </ul>
            </div>
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;">
                <h5 style="margin: 0 0 10px 0; color: #e65100;">⚠️ Zayıf Yönler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Çok yüksek arama maliyeti</li>
                    <li>Jeolojik belirsizlik</li>
                    <li>Sondaj riski</li>
                    <li>Uzun fizibilite süreci</li>
                </ul>
            </div>
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                <h5 style="margin: 0 0 10px 0; color: #1565c0;">🎯 Fırsatlar</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Türkiye jeotermal potansiyeli</li>
                    <li>MTA destek ve haritalar</li>
                    <li>Kombine kullanım (enerji+tesis)</li>
                    <li>Sera tarımı entegrasyonu</li>
                </ul>
            </div>
            <div style="background: #ffebee; padding: 15px; border-radius: 8px; border-left: 4px solid #f44336;">
                <h5 style="margin: 0 0 10px 0; color: #c62828;">⚡ Tehditler</h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #424242;">
                    <li>Kuyu verimi düşebilir</li>
                    <li>Kimyasal tıkanma</li>
                    <li>Deprem/tektonik hareketler</li>
                    <li>Yüksek başlangıç riski</li>
                </ul>
            </div>
        </div>
    `;

    return html;
}
