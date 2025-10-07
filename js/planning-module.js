// Urban Planning Module - Smart Decision Support
class UrbanPlanningModule {
    constructor(app) {
        this.app = app;
        this.baseURL = app.baseURL;
        this.currentAnalysis = null;
    }

    // Analyze area for land use
    async analyzeArea(lat, lon, areaSize = 10000) {
        const analysisDiv = document.getElementById('planningResult');
        if (!analysisDiv) return;

        analysisDiv.innerHTML = '<div class="loading-big">🛰️ NASA verileri analiz ediliyor...</div>';

        try {
            const response = await fetch(
                `${this.baseURL}urban-planning.php?action=analyze&lat=${lat}&lon=${lon}&area_size=${areaSize}`
            );
            const data = await response.json();

            if (data.error) {
                analysisDiv.innerHTML = `<div class="error">${data.error}</div>`;
                return;
            }

            this.currentAnalysis = data;
            this.displayAnalysisResults(data);

        } catch (error) {
            console.error('Analysis error:', error);
            analysisDiv.innerHTML = '<div class="error">Analiz başarısız oldu</div>';
        }
    }

    displayAnalysisResults(data) {
        const analysisDiv = document.getElementById('planningResult');

        // Prepare scores visualization
        const scoresHTML = Object.entries(data.scores)
            .sort((a, b) => b[1] - a[1])
            .map(([use, score]) => {
                const useNames = {
                    'agriculture': '🌾 Tarım',
                    'residential': '🏘️ Konut',
                    'green_area': '🌳 Yeşil Alan',
                    'solar_energy': '☀️ Güneş Enerjisi',
                    'wind_energy': '💨 Rüzgar Enerjisi'
                };

                const colorClass = score >= 70 ? 'excellent' : score >= 50 ? 'good' : score >= 30 ? 'fair' : 'poor';
                const probability = data.probabilities[use];

                return `
                    <div class="score-bar-item ${data.primary_recommendation === use ? 'recommended' : ''}">
                        <div class="score-label">
                            ${useNames[use]}
                            <span class="probability-badge">%${probability}</span>
                        </div>
                        <div class="score-bar-container">
                            <div class="score-bar ${colorClass}" style="width: ${score}%">
                                <span class="score-value">${score}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

        // Get detailed plan based on primary use
        const detailedPlan = this.formatDetailedPlan(data.primary_recommendation, data.detailed_analysis);

        analysisDiv.innerHTML = `
            <div class="analysis-results">
                <!-- Header Summary -->
                <div class="analysis-header">
                    <h2>📊 Akıllı Arazi Kullanım Analizi</h2>
                    <div class="location-info">
                        <span>📍 ${data.location.latitude.toFixed(4)}°N, ${data.location.longitude.toFixed(4)}°E</span>
                        <span>📐 Alan: ${(data.area_size / 10000).toFixed(2)} hektar (${data.area_size.toLocaleString()} m²)</span>
                    </div>
                </div>

                <!-- Primary Recommendation -->
                <div class="primary-recommendation">
                    <div class="recommendation-badge">
                        ${this.getUseIcon(data.primary_recommendation)}
                    </div>
                    <div class="recommendation-text">
                        <h3>ÖNERİLEN KULLANIM</h3>
                        <h2>${this.getUseName(data.primary_recommendation)}</h2>
                        <p class="confidence">Güven Skoru: <strong>${data.recommendation_confidence}%</strong></p>
                    </div>
                </div>

                <!-- Scores Comparison -->
                <div class="scores-section">
                    <h3>🎯 Kullanım Uygunluk Skorları</h3>
                    <div class="scores-grid">
                        ${scoresHTML}
                    </div>
                </div>

                <!-- Textual Report -->
                ${this.formatTextualReport(data.textual_report)}

                <!-- Detailed Plan -->
                ${detailedPlan}

                <!-- NASA Data Summary -->
                <div class="nasa-data-summary">
                    <h3>🛰️ NASA Veri Özeti</h3>
                    <div class="data-grid">
                        ${data.nasa_data.ndvi ? `
                            <div class="data-item">
                                <span class="data-label">🌿 Bitki Örtüsü (NDVI)</span>
                                <span class="data-value">${(data.nasa_data.ndvi.ndvi_estimate * 100).toFixed(0)}%</span>
                                <span class="data-sub">${data.nasa_data.ndvi.vegetation_health}</span>
                            </div>
                        ` : ''}
                        ${data.nasa_data.temperature ? `
                            <div class="data-item">
                                <span class="data-label">🌡️ Sıcaklık</span>
                                <span class="data-value">${data.nasa_data.temperature.air_temp?.toFixed(1)}°C</span>
                                <span class="data-sub">Min: ${data.nasa_data.temperature.min_temp?.toFixed(1)}°C / Max: ${data.nasa_data.temperature.max_temp?.toFixed(1)}°C</span>
                            </div>
                        ` : ''}
                        ${data.nasa_data.elevation ? `
                            <div class="data-item">
                                <span class="data-label">📏 Rakım</span>
                                <span class="data-value">${data.nasa_data.elevation.elevation}m</span>
                                <span class="data-sub">Sel Riski: ${data.nasa_data.elevation.flood_risk}</span>
                            </div>
                        ` : ''}
                        ${data.nasa_data.fire_risk ? `
                            <div class="data-item">
                                <span class="data-label">🔥 Yangın</span>
                                <span class="data-value">${data.nasa_data.fire_risk.count} aktif</span>
                                <span class="data-sub">Risk: ${data.nasa_data.fire_risk.risk_level.toUpperCase()}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button onclick="planningModule.downloadReport()" class="btn-action">📥 PDF İndir</button>
                    <button onclick="planningModule.shareAnalysis()" class="btn-action">📤 Paylaş</button>
                    <button onclick="planningModule.newAnalysis()" class="btn-action">🔄 Yeni Analiz</button>
                </div>
            </div>
        `;
    }

    formatDetailedPlan(useType, analysis) {
        switch (useType) {
            case 'residential':
                return this.formatResidentialPlan(analysis);
            case 'agriculture':
                return this.formatAgriculturePlan(analysis);
            case 'green_area':
                return this.formatGreenAreaPlan(analysis);
            case 'solar_energy':
                return this.formatSolarPlan(analysis);
            default:
                return '<div class="detailed-plan"><p>Detaylı plan hazırlanıyor...</p></div>';
        }
    }

    formatResidentialPlan(plan) {
        return `
            <div class="detailed-plan residential">
                <h3>🏘️ Konut Alanı Detay Planı</h3>

                <!-- Capacity with Population Projection -->
                <div class="plan-section">
                    <h4>👥 Kapasite ve Nüfus Projeksiyonu</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Maksimum Konut</span>
                            <span class="info-value">${plan.capacity.max_houses} ev</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Başlangıç Nüfusu</span>
                            <span class="info-value">${plan.capacity.estimated_population.toLocaleString()} kişi</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nüfus (5 yıl)</span>
                            <span class="info-value">${plan.capacity.population_5years?.toLocaleString() || 'N/A'} kişi</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nüfus (10 yıl)</span>
                            <span class="info-value">${plan.capacity.population_10years?.toLocaleString() || 'N/A'} kişi</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nüfus Yoğunluğu</span>
                            <span class="info-value">${plan.capacity.population_density}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Hane/Hektar</span>
                            <span class="info-value">${plan.capacity.households_per_hectare || 'N/A'}</span>
                        </div>
                    </div>
                    <small style="color: #666; margin-top: 10px; display: block;">📈 Projeksiyon: %1.5 yıllık nüfus artışı (Türkiye ortalaması)</small>
                </div>

                <!-- WHO Green Standards -->
                <div class="plan-section highlight">
                    <h4>🌳 WHO Yeşil Alan Standartları</h4>
                    <div class="who-standards">
                        <div class="standard-item">
                            <div class="standard-icon">✅</div>
                            <div class="standard-content">
                                <strong>WHO İdeal Standart</strong>
                                <p>${plan.who_green_standards.who_standard}</p>
                            </div>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">İdeal Yeşil Alan</span>
                                <span class="info-value">${plan.who_green_standards.ideal_green_area_m2.toLocaleString()} m²</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Minimum Gereksinim</span>
                                <span class="info-value">${plan.who_green_standards.minimum_green_area_m2.toLocaleString()} m²</span>
                            </div>
                            <div class="info-item green">
                                <span class="info-label">Önerilen Alan</span>
                                <span class="info-value">${plan.who_green_standards.recommended_green_area_m2.toLocaleString()} m²</span>
                            </div>
                            <div class="info-item green">
                                <span class="info-label">Yeşil Alan Oranı</span>
                                <span class="info-value">${plan.who_green_standards.green_area_percentage}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current vs Target Tree Coverage -->
                ${plan.who_green_standards.current_vs_target ? `
                    <div class="plan-section highlight">
                        <h4>🌲 Mevcut vs Hedef Ağaçlandırma</h4>
                        <div class="comparison-grid">
                            <div class="comparison-item current">
                                <div class="comp-icon">📊</div>
                                <div class="comp-label">Mevcut</div>
                                <div class="comp-value">${plan.who_green_standards.current_vs_target.current_trees.toLocaleString()} ağaç</div>
                                <div class="comp-sub">${plan.who_green_standards.current_vs_target.current_tree_coverage_percent}% örtü</div>
                            </div>
                            <div class="comparison-arrow">→</div>
                            <div class="comparison-item target">
                                <div class="comp-icon">🎯</div>
                                <div class="comp-label">Hedef</div>
                                <div class="comp-value">${plan.who_green_standards.current_vs_target.target_trees.toLocaleString()} ağaç</div>
                                <div class="comp-sub">WHO standartları</div>
                            </div>
                        </div>
                        <div class="gap-analysis">
                            <strong>📋 Gap Analizi:</strong>
                            <p>${plan.who_green_standards.current_vs_target.gap_analysis}</p>
                            ${plan.who_green_standards.current_vs_target.additional_trees_needed > 0 ?
                                `<div class="action-required">
                                    ⚠️ Ek Dikilmesi Gereken: <strong>${plan.who_green_standards.current_vs_target.additional_trees_needed.toLocaleString()} ağaç</strong>
                                </div>` :
                                `<div class="goal-met">✅ Mevcut ağaç sayısı WHO standartlarını karşılıyor!</div>`
                            }
                        </div>
                    </div>
                ` : ''}

                <!-- Tree Planting Plan -->
                <div class="plan-section highlight">
                    <h4>🌳 Ağaçlandırma Planı Detayı</h4>
                    <div class="tree-plan">
                        <div class="tree-stat-big">
                            <div class="stat-number">${plan.tree_planting_plan.total_trees_needed.toLocaleString()}</div>
                            <div class="stat-label">Toplam Hedef Ağaç</div>
                        </div>
                        <div class="info-grid">
                            ${plan.tree_planting_plan.current_trees !== undefined ? `
                                <div class="info-item">
                                    <span class="info-label">Mevcut Ağaç</span>
                                    <span class="info-value">${plan.tree_planting_plan.current_trees.toLocaleString()}</span>
                                </div>
                                <div class="info-item warning">
                                    <span class="info-label">Dikilecek Ağaç</span>
                                    <span class="info-value">${plan.tree_planting_plan.additional_trees?.toLocaleString() || 0}</span>
                                </div>
                            ` : ''}
                            <div class="info-item">
                                <span class="info-label">Ev Başına Ağaç</span>
                                <span class="info-value">${plan.tree_planting_plan.trees_per_house} ağaç/ev</span>
                            </div>
                            <div class="info-item green">
                                <span class="info-label">Yıllık CO₂ Azaltma</span>
                                <span class="info-value">${plan.tree_planting_plan.co2_reduction_kg_year.toLocaleString()} kg</span>
                            </div>
                            <div class="info-item green">
                                <span class="info-label">10 Yıllık CO₂ Azaltma</span>
                                <span class="info-value">${plan.tree_planting_plan.co2_reduction_tons_10years} ton</span>
                            </div>
                        </div>

                        <!-- Tree Species Details -->
                        <div class="species-details">
                            <strong>🌲 Önerilen Ağaç Türleri (İklime Uygun):</strong>
                            <div class="species-table">
                                ${Array.isArray(plan.tree_planting_plan.tree_species) && typeof plan.tree_planting_plan.tree_species[0] === 'object' ?
                                    plan.tree_planting_plan.tree_species.map(species => `
                                        <div class="species-row">
                                            <div class="species-name">🌳 ${species.name}</div>
                                            <div class="species-stats">
                                                <span>CO₂: ${species.co2}</span>
                                                <span>Su: ${species.water}</span>
                                                <span>Büyüme: ${species.growth}</span>
                                            </div>
                                        </div>
                                    `).join('') :
                                    `<div class="species-chips">
                                        ${plan.tree_planting_plan.tree_species.map(s => `<span class="chip">${s}</span>`).join('')}
                                    </div>`
                                }
                            </div>
                        </div>

                        ${plan.tree_planting_plan.planting_priority ? `
                            <div class="planting-priority">
                                <strong>⏰ Öncelik Durumu:</strong>
                                <pre style="margin-top: 10px; font-size: 0.9rem;">${plan.tree_planting_plan.planting_priority}</pre>
                            </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Earthquake Analysis -->
                ${plan.earthquake_analysis ? `
                    <div class="plan-section warning">
                        <h4>🏗️ Deprem Riski ve Yapı Sınırlamaları</h4>
                        <div class="earthquake-info">
                            <div class="risk-badge risk-${plan.earthquake_analysis.risk_level.toLowerCase()}">
                                ${plan.earthquake_analysis.risk_level} Risk
                            </div>
                            <div class="earthquake-details">
                                <p><strong>📍 Bölge:</strong> ${plan.earthquake_analysis.risk_description}</p>
                                <p><strong>🏢 Maksimum Kat Sayısı:</strong> ${plan.earthquake_analysis.max_building_floors}</p>
                                <p><strong>📜 Yönetmelik:</strong> ${plan.earthquake_analysis.building_code}</p>
                                <div class="structural-requirements">
                                    <strong>🔨 Yapısal Gereksinimler:</strong>
                                    <ul>
                                        ${plan.earthquake_analysis.structural_requirements.map(req => `<li>${req}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                <!-- Infrastructure -->
                <div class="plan-section">
                    <h4>🏗️ Altyapı Dağılımı</h4>
                    <div class="infrastructure-chart">
                        ${Object.entries(plan.infrastructure_plan).map(([key, value]) => {
                            const labels = {
                                'roads_parking': '🛣️ Yollar & Otopark',
                                'green_areas': '🌳 Yeşil Alanlar',
                                'buildings': '🏘️ Binalar',
                                'public_spaces': '🏛️ Kamu Alanları',
                                'transportation_note': '🚌 Ulaşım Notu'
                            };
                            if (key === 'transportation_note') {
                                return `
                                    <div class="infrastructure-item note">
                                        <span class="infra-label">${labels[key]}</span>
                                        <span class="infra-value"><em>${value}</em></span>
                                    </div>
                                `;
                            }
                            return `
                                <div class="infrastructure-item">
                                    <span class="infra-label">${labels[key] || key}</span>
                                    <span class="infra-value">${value}</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>

                <!-- Building Recommendations -->
                <div class="plan-section">
                    <h4>🏠 Yapı Önerileri</h4>
                    <ul class="recommendations-list">
                        ${plan.building_recommendations.map(rec => `<li>✓ ${rec}</li>`).join('')}
                    </ul>
                </div>

                <!-- Energy Efficiency -->
                <div class="plan-section">
                    <h4>⚡ Enerji Verimliliği</h4>
                    <div class="energy-recommendations">
                        ${Object.entries(plan.energy_efficiency).map(([key, value]) => `
                            <div class="energy-item">
                                <strong>${key}:</strong> ${value}
                            </div>
                        `).join('')}
                    </div>
                </div>

                <!-- Sustainability Score -->
                <div class="plan-section highlight">
                    <h4>♻️ Sürdürülebilirlik Skoru</h4>
                    <div class="sustainability-score">
                        <div class="score-circle ${plan.sustainability_score >= 70 ? 'excellent' : plan.sustainability_score >= 50 ? 'good' : 'fair'}">
                            <span class="score-big">${plan.sustainability_score}</span>
                            <span class="score-label">/100</span>
                        </div>
                        <p class="score-desc">
                            ${plan.sustainability_score >= 70 ? '🌟 Mükemmel sürdürülebilir proje!' :
                              plan.sustainability_score >= 50 ? '👍 İyi sürdürülebilirlik seviyesi' :
                              '⚠️ Sürdürülebilirlik artırılabilir'}
                        </p>
                    </div>
                </div>

                <!-- Cost Estimate -->
                <div class="plan-section">
                    <h4>💰 Maliyet Tahmini</h4>
                    <div class="cost-breakdown">
                        ${Object.entries(plan.estimated_cost).map(([key, value]) => {
                            const labels = {
                                'infrastructure': 'Altyapı',
                                'green_area_development': 'Yeşil Alan Geliştirme',
                                'tree_planting': 'Ağaçlandırma',
                                'total_estimated': 'TOPLAM TAHMİN'
                            };
                            const isTotal = key === 'total_estimated';
                            return `
                                <div class="cost-item ${isTotal ? 'total' : ''}">
                                    <span class="cost-label">${labels[key] || key}</span>
                                    <span class="cost-value">${value}</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>

                <!-- Challenges -->
                ${plan.challenges && plan.challenges.length > 0 ? `
                    <div class="plan-section warning">
                        <h4>⚠️ Dikkat Edilmesi Gerekenler</h4>
                        <ul class="challenges-list">
                            ${plan.challenges.map(c => `<li>${c}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
            </div>
        `;
    }

    formatAgriculturePlan(plan) {
        return `
            <div class="detailed-plan agriculture">
                <h3>🌾 Tarımsal Alan Detay Planı</h3>

                ${plan.climate_suitability ? `
                    <div class="plan-section highlight">
                        <h4>🌡️ İklim Uygunluk Analizi</h4>
                        <pre style="line-height: 1.6; font-size: 0.95rem;">${plan.climate_suitability}</pre>
                    </div>
                ` : ''}

                <div class="plan-section">
                    <h4>🌱 Önerilen Ürünler (İklime Göre Seçildi)</h4>
                    ${plan.crop_details && Array.isArray(plan.crop_details) && plan.crop_details.length > 0 ? `
                        <div class="crop-details-table">
                            ${plan.crop_details.map(crop => `
                                <div class="crop-detail-row">
                                    <div class="crop-name">🌾 ${crop.name}</div>
                                    <div class="crop-info">
                                        <span class="crop-stat">📊 Verim: ${crop.yield}</span>
                                        <span class="crop-stat">📅 Sezon: ${crop.season}</span>
                                        <span class="crop-stat">💧 Su: ${crop.water}</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : `
                        <div class="crop-list">
                            ${plan.recommended_crops.map(crop => `<span class="crop-chip">${crop}</span>`).join('')}
                        </div>
                    `}
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Tahmini Verim</span>
                        <span class="info-value">${plan.estimated_yield}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Sulama İhtiyacı</span>
                        <span class="info-value">${plan.irrigation_need}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Toprak Sağlığı</span>
                        <span class="info-value">${plan.soil_health}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Ekonomik Potansiyel</span>
                        <span class="info-value">${plan.economic_potential}</span>
                    </div>
                </div>

                <div class="plan-section">
                    <h4>💡 Öneriler</h4>
                    <ul class="recommendations-list">
                        ${plan.recommendations.map(rec => `<li>✓ ${rec}</li>`).join('')}
                    </ul>
                </div>

                ${plan.challenges.length > 0 ? `
                    <div class="plan-section warning">
                        <h4>⚠️ Zorluklar</h4>
                        <ul class="challenges-list">
                            ${plan.challenges.map(c => `<li>${c}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
            </div>
        `;
    }

    formatGreenAreaPlan(plan) {
        return `
            <div class="detailed-plan green-area">
                <h3>🌳 Yeşil Alan / Park Detay Planı</h3>

                <div class="plan-section">
                    <h4>🎨 Park Tasarımı</h4>
                    <div class="info-grid">
                        ${Object.entries(plan.park_design).map(([key, value]) => {
                            const labels = {
                                'total_area_m2': 'Toplam Alan',
                                'total_trees': 'Toplam Ağaç',
                                'walking_paths_m': 'Yürüyüş Yolu',
                                'playground_m2': 'Oyun Alanı',
                                'sports_area_m2': 'Spor Alanı',
                                'picnic_areas': 'Piknik Alanı',
                                'parking_spaces': 'Park Yeri'
                            };
                            return `
                                <div class="info-item">
                                    <span class="info-label">${labels[key] || key}</span>
                                    <span class="info-value">${typeof value === 'number' ? value.toLocaleString() : value}</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>

                <div class="plan-section highlight">
                    <h4>🌳 Ağaç Tür Dağılımı</h4>
                    <div class="species-distribution">
                        ${Object.entries(plan.tree_species_distribution).map(([species, count]) => `
                            <div class="species-bar">
                                <span class="species-name">${species}</span>
                                <div class="species-bar-fill" style="width: ${(count / plan.park_design.total_trees) * 100}%">
                                    <span class="species-count">${count} ağaç</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div class="plan-section highlight">
                    <h4>🌍 Çevresel Etki</h4>
                    <div class="impact-grid">
                        ${Object.entries(plan.environmental_impact).map(([key, value]) => {
                            const labels = {
                                'annual_co2_absorption_kg': 'Yıllık CO₂ Emilimi (kg)',
                                'annual_co2_absorption_tons': 'Yıllık CO₂ Emilimi (ton)',
                                'oxygen_production_kg_year': 'Oksijen Üretimi (kg/yıl)',
                                'air_pollution_reduction': 'Hava Kirliliği Azaltma',
                                'urban_heat_island_effect': 'Şehir Isı Adası Etkisi'
                            };
                            return `
                                <div class="impact-item">
                                    <span class="impact-label">${labels[key] || key}</span>
                                    <span class="impact-value">${typeof value === 'number' ? value.toLocaleString() : value}</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>

                <div class="plan-section">
                    <h4>👥 Toplum Faydaları</h4>
                    <div class="benefits-list">
                        ${Object.entries(plan.community_benefits).map(([key, value]) => `
                            <div class="benefit-item">✓ ${key}: ${value}</div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    formatTextualReport(report) {
        if (!report) return '';

        return `
            <div class="textual-report-section">
                <h3>📋 Detaylı NASA Veri Raporu</h3>

                <div class="report-card">
                    ${report.introduction ? `
                        <div class="report-introduction">
                            <pre>${report.introduction}</pre>
                        </div>
                    ` : ''}

                    ${report.analysis_header ? `
                        <div class="report-analysis-header">
                            <pre>${report.analysis_header}</pre>
                        </div>
                    ` : ''}

                    <div class="report-probabilities">
                        <pre>${report.probability_analysis}</pre>
                    </div>

                    <div class="report-nasa-data">
                        <pre>${report.nasa_data_analysis}</pre>
                    </div>

                    ${report.results_header ? `
                        <div class="report-results-header">
                            <pre>${report.results_header}</pre>
                        </div>
                    ` : ''}

                    <div class="report-recommendation">
                        <pre>${report.recommendation}</pre>
                    </div>

                    ${report.alternatives ? `
                        <div class="report-alternatives">
                            <pre>${report.alternatives}</pre>
                        </div>
                    ` : ''}

                    <div class="report-conclusion">
                        <pre>${report.conclusion}</pre>
                    </div>

                    ${report.implementation ? `
                        <div class="report-implementation">
                            <pre>${report.implementation}</pre>
                        </div>
                    ` : ''}

                    ${report.footer ? `
                        <div class="report-footer">
                            <pre>${report.footer}</pre>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    formatSolarPlan(plan) {
        return `
            <div class="detailed-plan solar">
                <h3>☀️ Güneş Enerjisi Santrali Planı</h3>

                <div class="plan-section">
                    <h4>⚡ Enerji Üretimi</h4>
                    <div class="info-grid">
                        ${Object.entries(plan.energy_production).map(([key, value]) => {
                            const labels = {
                                'panel_area_m2': 'Panel Alanı',
                                'installed_capacity_mw': 'Kurulu Güç',
                                'annual_production_mwh': 'Yıllık Üretim',
                                'daily_average_kwh': 'Günlük Ortalama',
                                'homes_powered': 'Ev Eşdeğeri'
                            };
                            return `
                                <div class="info-item">
                                    <span class="info-label">${labels[key] || key}</span>
                                    <span class="info-value">${typeof value === 'number' ? value.toLocaleString() : value}</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>

                <div class="plan-section highlight">
                    <h4>🌍 Çevresel Etki</h4>
                    <div class="impact-stats">
                        <div class="impact-big">
                            <div class="stat-number">${plan.environmental_impact.co2_avoided_tons_year.toLocaleString()}</div>
                            <div class="stat-label">ton CO₂ azaltma/yıl</div>
                        </div>
                        <div class="impact-big">
                            <div class="stat-number">${plan.environmental_impact.equivalent_trees.toLocaleString()}</div>
                            <div class="stat-label">ağaç eşdeğeri</div>
                        </div>
                    </div>
                </div>

                <div class="plan-section">
                    <h4>💰 Ekonomik Analiz</h4>
                    <div class="info-grid">
                        ${Object.entries(plan.economic).map(([key, value]) => {
                            const labels = {
                                'investment_estimate': 'Yatırım Tahmini',
                                'payback_period_years': 'Geri Ödeme Süresi',
                                'annual_revenue': 'Yıllık Gelir'
                            };
                            return `
                                <div class="info-item">
                                    <span class="info-label">${labels[key] || key}</span>
                                    <span class="info-value">${value}</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    getUseIcon(use) {
        const icons = {
            'agriculture': '🌾',
            'residential': '🏘️',
            'green_area': '🌳',
            'solar_energy': '☀️',
            'wind_energy': '💨'
        };
        return icons[use] || '📊';
    }

    getUseName(use) {
        const names = {
            'agriculture': 'Tarımsal Alan',
            'residential': 'Konut Alanı',
            'green_area': 'Yeşil Alan / Park',
            'solar_energy': 'Güneş Enerjisi Santrali',
            'wind_energy': 'Rüzgar Enerjisi Santrali'
        };
        return names[use] || use;
    }

    downloadReport() {
        if (!this.currentAnalysis) {
            alert('Önce bir analiz yapın');
            return;
        }

        // Generate PDF content (simplified - in production use jsPDF)
        const reportText = JSON.stringify(this.currentAnalysis, null, 2);
        const blob = new Blob([reportText], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `urban-planning-report-${Date.now()}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    }

    shareAnalysis() {
        if (!this.currentAnalysis) {
            alert('Önce bir analiz yapın');
            return;
        }

        const text = `Akıllı Şehir Planlaması - ${this.getUseName(this.currentAnalysis.primary_recommendation)} önerildi (Güven: ${this.currentAnalysis.recommendation_confidence}%)`;

        if (navigator.share) {
            navigator.share({
                title: 'Akıllı Şehir Planlaması',
                text: text
            });
        } else {
            alert(text);
        }
    }

    newAnalysis() {
        document.getElementById('planningResult').innerHTML = '';
        document.getElementById('planningLat').value = '';
        document.getElementById('planningLon').value = '';
        document.getElementById('planningArea').value = '10000';
    }
}

// Initialize when DOM is ready
let planningModule;
document.addEventListener('DOMContentLoaded', () => {
    if (window.advancedApp) {
        planningModule = new UrbanPlanningModule(window.advancedApp);
        window.planningModule = planningModule;
    }
});
