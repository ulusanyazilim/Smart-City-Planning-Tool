<?php
// Start output buffering to catch any stray output
ob_start();

error_reporting(0); // Suppress PHP warnings in JSON output
ini_set('display_errors', 0);

require_once 'config.php';
require_once 'nasa-advanced.php';
require_once 'swot-analysis.php';

// Clear any output that happened before
ob_clean();

header('Content-Type: application/json; charset=utf-8');

/**
 * Smart Urban Planning Decision Support System
 * Uses NASA data to provide intelligent recommendations
 */

class UrbanPlanningAI {

    // WHO Standards
    const WHO_GREEN_AREA_PER_PERSON = 50; // m² per person (ideal)
    const WHO_MINIMUM_GREEN_AREA = 9; // m² per person (minimum)
    const TREE_CO2_ABSORPTION = 22; // kg CO2 per year per tree
    const TREE_COVERAGE_AREA = 25; // m² per mature tree
    const AVERAGE_HOUSEHOLD_SIZE = 3.5; // people per household (Turkey)

    // Normalize value to 0-100 scale
    private static function normalize($value, $min, $max, $ideal = null) {
        if ($value === null || $value === -999) return 0;

        // If ideal value exists, use bell curve
        if ($ideal !== null) {
            $distance = abs($value - $ideal);
            $range = max(abs($max - $ideal), abs($min - $ideal));
            return max(0, min(100, 100 - ($distance / $range) * 100));
        }

        // Linear normalization
        if ($max === $min) return 50;
        return max(0, min(100, (($value - $min) / ($max - $min)) * 100));
    }

    // Analyze area for optimal land use
    public static function analyzeAreaForLandUse($lat, $lon, $areaSize = 10000) {
        // Get NASA data
        $ndvi = NASAAdvancedData::getNDVI($lat, $lon, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
        $temperature = NASAAdvancedData::getLandTemperature($lat, $lon, date('Y-m-d'));
        $elevation = NASAAdvancedData::getElevation($lat, $lon);
        $fireRisk = NASAAdvancedData::getFireData($lat, $lon, 30);
        $soilMoisture = NASAAdvancedData::getSoilMoisture($lat, $lon); // SMAP Data

        // Calculate suitability scores (with SMAP soil moisture)
        $scores = [
            'agriculture' => self::calculateAgricultureScore($ndvi, $temperature, $elevation, $fireRisk, $soilMoisture),
            'residential' => self::calculateResidentialScore($ndvi, $temperature, $elevation, $fireRisk),
            'green_area' => self::calculateGreenAreaScore($ndvi, $temperature, $elevation, $soilMoisture),
            'solar_energy' => self::calculateSolarScore($temperature, $ndvi),
            'wind_energy' => self::calculateWindScore($elevation, $lat, $lon),
            'tourism' => self::calculateTourismScore($ndvi, $temperature, $elevation, $fireRisk),
            'geothermal' => self::calculateGeothermalScore($elevation, $temperature, $lat, $lon),
        ];

        // Determine primary recommendation
        arsort($scores);
        $primaryUse = array_key_first($scores);

        // Calculate percentage probabilities
        $totalScore = array_sum($scores);
        $probabilities = [];
        foreach ($scores as $use => $score) {
            $probabilities[$use] = $totalScore > 0 ? round(($score / $totalScore) * 100, 1) : 0;
        }

        // Generate SWOT Analysis
        $swot = SWOTAnalysis::generateSWOT($primaryUse, $scores, [
            'ndvi' => $ndvi,
            'temperature' => $temperature,
            'elevation' => $elevation,
            'fire_risk' => $fireRisk,
            'soil_moisture' => $soilMoisture
        ], $areaSize, ['latitude' => $lat, 'longitude' => $lon]);

        return [
            'location' => ['latitude' => $lat, 'longitude' => $lon],
            'area_size' => $areaSize,
            'scores' => $scores,
            'probabilities' => $probabilities,
            'primary_recommendation' => $primaryUse,
            'recommendation_confidence' => $scores[$primaryUse],
            'detailed_analysis' => self::generateDetailedAnalysis($primaryUse, $scores, $ndvi, $temperature, $elevation, $areaSize),
            'swot_analysis' => $swot,
            'swot_matrix_html' => SWOTAnalysis::generateSWOTMatrix($swot),
            'textual_report' => self::generateTextualReport($lat, $lon, $scores, $probabilities, $ndvi, $temperature, $elevation, $fireRisk, $areaSize),
            'nasa_data' => [
                'ndvi' => $ndvi,
                'temperature' => $temperature,
                'elevation' => $elevation,
                'fire_risk' => $fireRisk,
                'soil_moisture' => $soilMoisture // SMAP Data
            ]
        ];
    }

    // Calculate agriculture suitability
    private static function calculateAgricultureScore($ndvi, $temp, $elevation, $fireRisk, $soilMoisture = null) {
        $score = 0;

        // NDVI factor (vegetation health)
        if ($ndvi && $ndvi['ndvi_estimate'] > 0.5) {
            $score += 25;
        } elseif ($ndvi && $ndvi['ndvi_estimate'] > 0.3) {
            $score += 15;
        } elseif ($ndvi && $ndvi['ndvi_estimate'] > 0.2) {
            $score += 8;
        }

        // Temperature factor (filter invalid -999 values)
        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            if ($temp['air_temp'] >= 15 && $temp['air_temp'] <= 30) {
                $score += 20;
            } elseif ($temp['air_temp'] >= 10 && $temp['air_temp'] <= 35) {
                $score += 12;
            }
        }

        // Elevation factor (ideal 0-1500m for most crops)
        if ($elevation && $elevation['elevation'] >= 0 && $elevation['elevation'] <= 1500) {
            $score += 15;
        } elseif ($elevation && $elevation['elevation'] <= 2000) {
            $score += 10;
        }

        // Fire risk penalty
        if ($fireRisk && $fireRisk['risk_level'] === 'high') {
            $score -= 15;
        } elseif ($fireRisk && $fireRisk['risk_level'] === 'medium') {
            $score -= 5;
        }

        // Precipitation (from NDVI data)
        if ($ndvi && $ndvi['precipitation'] > 2) {
            $score += 15;
        } elseif ($ndvi && $ndvi['precipitation'] > 1) {
            $score += 8;
        }

        // SMAP Soil Moisture factor (NEW!)
        if ($soilMoisture && $soilMoisture['root_zone_moisture'] !== null) {
            $moisture = $soilMoisture['root_zone_moisture'];
            if ($moisture >= 40 && $moisture <= 70) {
                $score += 25; // İdeal toprak nemi
            } elseif ($moisture >= 30 && $moisture <= 80) {
                $score += 15; // İyi toprak nemi
            } elseif ($moisture >= 20 && $moisture <= 90) {
                $score += 8; // Kabul edilebilir
            } else {
                $score += 0; // Çok kuru veya çok ıslak
            }
        }

        return max(0, min(100, $score));
    }

    // Calculate residential suitability
    private static function calculateResidentialScore($ndvi, $temp, $elevation, $fireRisk) {
        $score = 0;

        // Low vegetation (easier to build)
        if ($ndvi && $ndvi['ndvi_estimate'] < 0.4) {
            $score += 20;
        }

        // Moderate temperature (filter invalid -999 values)
        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            if ($temp['air_temp'] >= 10 && $temp['air_temp'] <= 30) {
                $score += 25;
            }
        }

        // Elevation (flat areas better for construction)
        if ($elevation && $elevation['elevation'] >= 100 && $elevation['elevation'] <= 1500) {
            $score += 25;
        }

        // Flood risk (higher elevation = safer)
        if ($elevation && $elevation['flood_risk'] === 'Düşük') {
            $score += 20;
        } elseif ($elevation && $elevation['flood_risk'] === 'Orta - Yağış sonrası dikkat') {
            $score += 10;
        }

        // Fire risk penalty
        if ($fireRisk && $fireRisk['risk_level'] === 'high') {
            $score -= 20;
        } elseif ($fireRisk && $fireRisk['risk_level'] === 'medium') {
            $score -= 10;
        }

        // Temperature extremes penalty (filter invalid -999 values)
        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            if ($temp['air_temp'] > 35 || $temp['air_temp'] < 5) {
                $score -= 10;
            }
        }

        return max(0, min(100, $score));
    }

    // Calculate green area suitability
    private static function calculateGreenAreaScore($ndvi, $temp, $elevation, $soilMoisture = null) {
        $score = 0;

        // Low current vegetation (room for improvement)
        if ($ndvi && $ndvi['ndvi_estimate'] < 0.5) {
            $score += 25;
        }

        // Good temperature for plant growth (filter invalid -999 values)
        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            if ($temp['air_temp'] >= 15 && $temp['air_temp'] <= 25) {
                $score += 25;
            }
        }

        // Moderate elevation
        if ($elevation && $elevation['elevation'] >= 0 && $elevation['elevation'] <= 2000) {
            $score += 20;
        }

        // SMAP Soil Moisture for tree/grass growth
        if ($soilMoisture && $soilMoisture['root_zone_moisture'] !== null) {
            $moisture = $soilMoisture['root_zone_moisture'];
            if ($moisture >= 35 && $moisture <= 75) {
                $score += 20; // Ağaç/çim için ideal nem
            } elseif ($moisture >= 25 && $moisture <= 85) {
                $score += 12; // Uygun nem
            } elseif ($moisture >= 15) {
                $score += 5; // Sulama ile mümkün
            }
        }

        // Precipitation bonus
        if ($ndvi && $ndvi['precipitation'] > 1) {
            $score += 15;
        }

        return max(0, min(100, $score));
    }

    // Calculate solar energy potential
    private static function calculateSolarScore($temp, $ndvi) {
        $score = 0;

        // Solar radiation is the PRIMARY factor (NASA POWER data)
        if ($ndvi && isset($ndvi['solar_radiation'])) {
            $radiation = $ndvi['solar_radiation'];

            // W/m² to scoring (more gradual)
            if ($radiation > 250) {
                $score += 35; // Exceptional (deserts, very sunny)
            } elseif ($radiation > 220) {
                $score += 30; // Excellent (Mediterranean, South Turkey)
            } elseif ($radiation > 190) {
                $score += 25; // Very good (Central Anatolia)
            } elseif ($radiation > 160) {
                $score += 20; // Good (North regions)
            } elseif ($radiation > 130) {
                $score += 15; // Moderate
            } else {
                $score += 10; // Low potential
            }
        }

        // Clear skies bonus (low vegetation = less clouds, based on NDVI)
        if ($ndvi && isset($ndvi['ndvi_estimate'])) {
            $ndviVal = $ndvi['ndvi_estimate'];
            if ($ndviVal < 0.2) {
                $score += 20; // Desert/barren (very clear)
            } elseif ($ndviVal < 0.35) {
                $score += 15; // Semi-arid (clear)
            } elseif ($ndviVal < 0.5) {
                $score += 10; // Moderate vegetation
            } else {
                $score += 5; // Dense vegetation (more clouds/shade)
            }
        }

        // Temperature affects panel efficiency (NASA POWER temperature data)
        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            $airTemp = $temp['air_temp'];

            // Optimal range for solar panels: 15-25°C
            if ($airTemp >= 15 && $airTemp <= 25) {
                $score += 20; // Ideal efficiency
            } elseif ($airTemp > 25 && $airTemp <= 35) {
                $score += 15; // Good but panels lose some efficiency in heat
            } elseif ($airTemp > 10 && $airTemp < 15) {
                $score += 12; // Cool but acceptable
            } elseif ($airTemp > 35 && $airTemp <= 40) {
                $score += 10; // Hot - efficiency loss
            } else {
                $score += 5; // Too hot or too cold
            }
        }

        // Precipitation penalty (cloudy/rainy = less sun)
        if ($ndvi && isset($ndvi['precipitation'])) {
            $precip = $ndvi['precipitation'];
            if ($precip < 0.5) {
                $score += 10; // Very dry (clear skies)
            } elseif ($precip < 1.5) {
                $score += 5; // Low precipitation
            } elseif ($precip > 3) {
                $score -= 5; // High precipitation = more clouds
            }
        }

        return max(0, min(100, $score));
    }

    // Calculate wind energy potential (more realistic)
    private static function calculateWindScore($elevation, $lat = null, $lon = null) {
        $score = 0; // Start from 0

        $elev = $elevation && isset($elevation['elevation']) ? $elevation['elevation'] : 500;
        $isCoastal = false;

        // Coastal zone detection (approximate Turkey coastlines)
        if ($lat !== null && $lon !== null) {
            // Mediterranean coast (South)
            if ($lat >= 36.0 && $lat <= 37.5 && $lon >= 27.0 && $lon <= 36.5) {
                $isCoastal = true;
            }
            // Aegean coast (West)
            elseif ($lat >= 36.5 && $lat <= 40.5 && $lon >= 26.0 && $lon <= 28.5) {
                $isCoastal = true;
            }
            // Black Sea coast (North)
            elseif ($lat >= 40.5 && $lat <= 42.0 && $lon >= 27.0 && $lon <= 42.0) {
                $isCoastal = true;
            }
            // Marmara coast
            elseif ($lat >= 40.0 && $lat <= 41.5 && $lon >= 26.0 && $lon <= 30.0) {
                $isCoastal = true;
            }
        }

        // ELEVATION-based scoring (mountains/ridges)
        if ($elev > 1500) {
            $score += 35; // Mountain ridges - excellent wind
        } elseif ($elev > 1200) {
            $score += 30; // High mountains
        } elseif ($elev > 900) {
            $score += 25; // Mountain areas
        } elseif ($elev > 600) {
            $score += 15; // Hills
        } elseif ($elev > 300) {
            $score += 5; // Low hills
        }

        // COASTAL zones (sea breeze effect)
        if ($isCoastal) {
            if ($elev < 100) {
                $score += 25; // Coastal lowlands - excellent for offshore/coastal wind
            } elseif ($elev < 300) {
                $score += 20; // Coastal hills - very good
            } elseif ($elev < 600) {
                $score += 15; // Coastal highlands
            } else {
                $score += 10; // Coastal mountains
            }

            // Specific high-wind coastal regions in Turkey
            // North Aegean (strong Etesian winds)
            if ($lat >= 38.5 && $lat <= 40.5 && $lon >= 26.0 && $lon <= 27.5) {
                $score += 15; // Çanakkale, Balıkesir coasts
            }
            // Eastern Mediterranean (strong winds)
            elseif ($lat >= 36.0 && $lat <= 37.0 && $lon >= 32.0 && $lon <= 36.5) {
                $score += 10; // Mersin, Adana coasts
            }
        } else {
            // Non-coastal, low elevation = very poor wind
            if ($elev < 300) {
                $score -= 15; // Inland plains - very poor wind
            } elseif ($elev < 600) {
                $score -= 5; // Inland low areas
            }
        }

        // Note: Real wind assessment requires 1-year wind speed measurements
        // This is based on topography and coastal proximity
        return max(0, min(100, $score));
    }

    // Calculate tourism suitability
    private static function calculateTourismScore($ndvi, $temp, $elevation, $fireRisk) {
        $score = 0;

        // Natural beauty (NDVI)
        if ($ndvi && $ndvi['ndvi_estimate'] > 0.6) {
            $score += 25; // Lush vegetation
        } elseif ($ndvi && $ndvi['ndvi_estimate'] > 0.4) {
            $score += 15;
        }

        // Pleasant temperature (filter invalid -999 values)
        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            if ($temp['air_temp'] >= 18 && $temp['air_temp'] <= 28) {
                $score += 25; // Ideal tourism weather
            } elseif ($temp['air_temp'] >= 10 && $temp['air_temp'] <= 32) {
                $score += 15;
            }
        }

        // Scenic elevation (mountains, highlands)
        if ($elevation && $elevation['elevation'] > 800 && $elevation['elevation'] < 2500) {
            $score += 25; // Highland tourism potential
        } elseif ($elevation && $elevation['elevation'] > 200 && $elevation['elevation'] < 800) {
            $score += 10; // Hills
        }

        // Low fire risk (safety)
        if ($fireRisk && $fireRisk['risk_level'] === 'low') {
            $score += 15;
        } elseif ($fireRisk && $fireRisk['risk_level'] === 'medium') {
            $score += 5;
        }

        // Clean air (low pollution assumption if rural + high NDVI)
        if ($ndvi && $ndvi['ndvi_estimate'] > 0.5 && $elevation && $elevation['elevation'] > 500) {
            $score += 10; // Nature tourism
        }

        return max(0, min(100, $score));
    }

    // Calculate geothermal energy potential
    private static function calculateGeothermalScore($elevation, $temp, $lat, $lon) {
        $score = 0; // Start from zero - geothermal is very rare!

        // REALISTIC geothermal potential based on known Turkish geothermal zones
        // High potential zones (narrow definition)
        $isHighPotential = false;

        // 1. Denizli-Aydın region (Western Turkey - most active)
        if ($lat >= 37.5 && $lat <= 38.5 && $lon >= 27.5 && $lon <= 29.5) {
            $score += 35; // Denizli, Aydın, Nazilli
            $isHighPotential = true;
        }
        // 2. Afyonkarahisar region
        elseif ($lat >= 38.5 && $lat <= 39.0 && $lon >= 30.0 && $lon <= 31.0) {
            $score += 30; // Afyon geothermal fields
            $isHighPotential = true;
        }
        // 3. Kütahya-Simav region
        elseif ($lat >= 39.0 && $lat <= 39.5 && $lon >= 28.5 && $lon <= 29.5) {
            $score += 25; // Simav geothermal
            $isHighPotential = true;
        }
        // 4. İzmir region
        elseif ($lat >= 38.2 && $lat <= 38.8 && $lon >= 26.8 && $lon <= 27.5) {
            $score += 20; // Balçova, Seferihisar
            $isHighPotential = true;
        }
        // 5. Other regions - very low potential
        else {
            $score += 5; // Minimal baseline
        }

        // Elevation factor (geothermal in valleys and grabens)
        if ($elevation && isset($elevation['elevation'])) {
            $elev = $elevation['elevation'];
            if ($isHighPotential) {
                // In known zones, low elevation increases potential
                if ($elev < 200) {
                    $score += 15; // Graben/valley
                } elseif ($elev < 500) {
                    $score += 10; // Moderate elevation
                } elseif ($elev < 1000) {
                    $score += 5; // Higher areas
                }
            } else {
                // Outside known zones, elevation doesn't help much
                if ($elev < 200) {
                    $score += 3;
                }
            }
        }

        // Temperature factor (higher temp = better potential)
        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            $airTemp = $temp['air_temp'];
            if ($isHighPotential) {
                // In geothermal zones, temperature matters more
                if ($airTemp > 25) {
                    $score += 10; // Hot climate zones
                } elseif ($airTemp > 20) {
                    $score += 5;
                }
            } else {
                // Outside zones, temperature barely matters
                if ($airTemp > 30) {
                    $score += 2;
                }
            }
        }

        return max(0, min(100, $score));
    }

    // Generate detailed recommendations
    private static function generateDetailedAnalysis($primaryUse, $scores, $ndvi, $temp, $elevation, $areaSize) {
        $analysis = [];

        switch ($primaryUse) {
            case 'agriculture':
                $analysis = self::generateAgriculturePlan($scores, $ndvi, $temp, $elevation, $areaSize);
                break;

            case 'residential':
                $analysis = self::generateResidentialPlan($scores, $ndvi, $temp, $elevation, $areaSize);
                break;

            case 'green_area':
                $analysis = self::generateGreenAreaPlan($scores, $ndvi, $temp, $elevation, $areaSize);
                break;

            case 'solar_energy':
                $analysis = self::generateSolarPlan($scores, $ndvi, $areaSize);
                break;

            case 'wind_energy':
                $analysis = self::generateWindPlan($scores, $elevation, $areaSize);
                break;
        }

        return $analysis;
    }

    // Agriculture plan
    private static function generateAgriculturePlan($scores, $ndvi, $temp, $elevation, $areaSize) {
        $recommendedCrops = [];
        $cropDetails = [];

        // Recommend crops based on temperature, precipitation and elevation
        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            $avgTemp = $temp['air_temp'];
            $precipitation = $ndvi && isset($ndvi['precipitation']) ? $ndvi['precipitation'] : 2.0;
            $elevValue = $elevation && isset($elevation['elevation']) ? $elevation['elevation'] : 500;

            // Hot climate (>25°C)
            if ($avgTemp >= 25) {
                if ($precipitation > 2) {
                    $cropDetails = [
                        ['name' => 'Mısır', 'yield' => '800-1000 kg/dekar', 'season' => 'Nisan-Eylül', 'water' => 'Yüksek'],
                        ['name' => 'Pamuk', 'yield' => '400-500 kg/dekar', 'season' => 'Nisan-Ekim', 'water' => 'Orta-Yüksek'],
                        ['name' => 'Karpuz', 'yield' => '4000-6000 kg/dekar', 'season' => 'Mayıs-Ağustos', 'water' => 'Yüksek'],
                        ['name' => 'Domates (sera)', 'yield' => '8000-10000 kg/dekar', 'season' => 'Tüm yıl', 'water' => 'Yüksek']
                    ];
                } else {
                    $cropDetails = [
                        ['name' => 'Ayçiçeği', 'yield' => '250-350 kg/dekar', 'season' => 'Mart-Ağustos', 'water' => 'Düşük'],
                        ['name' => 'Susam', 'yield' => '80-120 kg/dekar', 'season' => 'Mayıs-Eylül', 'water' => 'Düşük'],
                        ['name' => 'Nohut', 'yield' => '200-300 kg/dekar', 'season' => 'Kasım-Temmuz', 'water' => 'Düşük'],
                        ['name' => 'Kavun (damlama sulama)', 'yield' => '3000-4000 kg/dekar', 'season' => 'Mayıs-Ağustos', 'water' => 'Orta']
                    ];
                }
            }
            // Temperate climate (20-25°C)
            elseif ($avgTemp >= 20) {
                if ($precipitation > 2) {
                    $cropDetails = [
                        ['name' => 'Buğday', 'yield' => '400-600 kg/dekar', 'season' => 'Ekim-Temmuz', 'water' => 'Orta'],
                        ['name' => 'Şeker Pancarı', 'yield' => '5000-7000 kg/dekar', 'season' => 'Mart-Ekim', 'water' => 'Yüksek'],
                        ['name' => 'Domates (açık)', 'yield' => '5000-7000 kg/dekar', 'season' => 'Mayıs-Eylül', 'water' => 'Yüksek'],
                        ['name' => 'Biber', 'yield' => '3000-4000 kg/dekar', 'season' => 'Mayıs-Ekim', 'water' => 'Orta-Yüksek'],
                        ['name' => 'Fasulye', 'yield' => '250-350 kg/dekar', 'season' => 'Mayıs-Eylül', 'water' => 'Orta']
                    ];
                } else {
                    $cropDetails = [
                        ['name' => 'Buğday (kuru tarım)', 'yield' => '250-350 kg/dekar', 'season' => 'Ekim-Temmuz', 'water' => 'Düşük'],
                        ['name' => 'Arpa', 'yield' => '300-400 kg/dekar', 'season' => 'Ekim-Haziran', 'water' => 'Düşük'],
                        ['name' => 'Mercimek', 'yield' => '150-250 kg/dekar', 'season' => 'Kasım-Temmuz', 'water' => 'Düşük'],
                        ['name' => 'Nohut', 'yield' => '200-300 kg/dekar', 'season' => 'Kasım-Temmuz', 'water' => 'Düşük']
                    ];
                }
            }
            // Cool climate (15-20°C)
            elseif ($avgTemp >= 15) {
                $cropDetails = [
                    ['name' => 'Buğday', 'yield' => '350-500 kg/dekar', 'season' => 'Ekim-Temmuz', 'water' => 'Orta'],
                    ['name' => 'Arpa', 'yield' => '300-450 kg/dekar', 'season' => 'Ekim-Haziran', 'water' => 'Orta'],
                    ['name' => 'Patates', 'yield' => '2500-4000 kg/dekar', 'season' => 'Mart-Eylül', 'water' => 'Orta-Yüksek'],
                    ['name' => 'Soğan', 'yield' => '3000-5000 kg/dekar', 'season' => 'Şubat-Ağustos', 'water' => 'Orta'],
                    ['name' => 'Havuç', 'yield' => '2500-3500 kg/dekar', 'season' => 'Mart-Ekim', 'water' => 'Orta']
                ];
            }
            // Cold climate (<15°C)
            else {
                $cropDetails = [
                    ['name' => 'Arpa', 'yield' => '250-400 kg/dekar', 'season' => 'Ekim-Haziran', 'water' => 'Düşük-Orta'],
                    ['name' => 'Çavdar', 'yield' => '200-350 kg/dekar', 'season' => 'Ekim-Temmuz', 'water' => 'Düşük'],
                    ['name' => 'Yulaf', 'yield' => '250-400 kg/dekar', 'season' => 'Mart-Temmuz', 'water' => 'Orta'],
                    ['name' => 'Lahana', 'yield' => '3000-5000 kg/dekar', 'season' => 'Temmuz-Kasım', 'water' => 'Orta'],
                    ['name' => 'Şalgam', 'yield' => '2000-3000 kg/dekar', 'season' => 'Ağustos-Kasım', 'water' => 'Orta']
                ];
            }

            // Adjust for high elevation
            if ($elevValue > 1500) {
                $cropDetails = [
                    ['name' => 'Arpa (yüksek rakım)', 'yield' => '200-300 kg/dekar', 'season' => 'Ekim-Temmuz', 'water' => 'Düşük'],
                    ['name' => 'Çavdar', 'yield' => '180-280 kg/dekar', 'season' => 'Ekim-Temmuz', 'water' => 'Düşük'],
                    ['name' => 'Patates (dağ)', 'yield' => '2000-3000 kg/dekar', 'season' => 'Nisan-Eylül', 'water' => 'Orta'],
                    ['name' => 'Yonca (hayvan yemi)', 'yield' => '800-1200 kg/dekar', 'season' => 'Mart-Ekim', 'water' => 'Orta']
                ];
            }
        }

        $recommendedCrops = array_column($cropDetails, 'name');
        $expectedYield = $areaSize * 4; // Simplified: 4 tons per hectare

        return [
            'use_type' => 'Tarımsal Alan',
            'confidence' => $scores['agriculture'],
            'recommended_crops' => $recommendedCrops,
            'crop_details' => $cropDetails,
            'climate_suitability' => self::getClimateSuitability($temp, $ndvi),
            'estimated_yield' => $expectedYield . ' kg/yıl',
            'irrigation_need' => $ndvi && $ndvi['precipitation'] < 1 ? 'Yüksek - sulama sistemi gerekli' : 'Orta - mevsimsel sulama',
            'soil_health' => $ndvi ? $ndvi['vegetation_health'] : 'Bilinmiyor',
            'challenges' => self::getAgricultureChallenges($ndvi, $temp, $elevation),
            'recommendations' => [
                'Modern sulama sistemleri kurun (damla sulama önerilir)',
                'Toprak analizi yaptırın (NPK değerleri)',
                'Organik gübre ve kompost kullanın',
                'Ürün rotasyonu uygulayın (toprak yorgunluğu önleme)',
                'İklim değişikliğine uyum: Dayanıklı çeşitler seçin'
            ],
            'economic_potential' => '⭐⭐⭐⭐ Yüksek'
        ];
    }

    // Get climate suitability analysis
    private static function getClimateSuitability($temp, $ndvi) {
        if (!$temp || !$ndvi) return 'Veri yetersiz';

        // Filter invalid temperature values
        if (!isset($temp['air_temp']) || $temp['air_temp'] === null || $temp['air_temp'] <= -999) {
            return 'Sıcaklık verisi geçersiz';
        }

        $avgTemp = $temp['air_temp'];
        $precipitation = $ndvi['precipitation'] ?? 2.0;

        $analysis = [];

        // Temperature analysis
        if ($avgTemp >= 15 && $avgTemp <= 30) {
            $analysis[] = "✅ Sıcaklık optimal aralıkta ($avgTemp°C)";
        } elseif ($avgTemp > 30) {
            $analysis[] = "⚠️ Sıcaklık yüksek ($avgTemp°C) - sıcağa dayanıklı çeşitler seçin";
        } else {
            $analysis[] = "⚠️ Sıcaklık düşük ($avgTemp°C) - soğuğa dayanıklı çeşitler seçin";
        }

        // Precipitation analysis
        if ($precipitation > 2.5) {
            $analysis[] = "✅ Yağış yeterli ({$precipitation} mm/gün)";
        } elseif ($precipitation > 1.5) {
            $analysis[] = "⚠️ Yağış orta ({$precipitation} mm/gün) - kuraklık riskine karşı önlem";
        } else {
            $analysis[] = "❌ Yağış yetersiz ({$precipitation} mm/gün) - sulama zorunlu";
        }

        // Climate change warning
        $analysis[] = "🌍 İklim değişikliği etkisi: Son 30 yılda ortalama sıcaklık +1.5°C arttı";

        return implode("\n", $analysis);
    }

    // Residential plan with WHO standards
    private static function generateResidentialPlan($scores, $ndvi, $temp, $elevation, $areaSize) {
        // Calculate housing capacity
        $usableArea = $areaSize * 0.7; // 70% usable (30% roads, infrastructure)
        $averageHouseSize = 120; // m² per house
        $maxHouses = floor($usableArea / $averageHouseSize);
        $estimatedPopulation = $maxHouses * self::AVERAGE_HOUSEHOLD_SIZE;

        // Population projection (10-year growth)
        $growthRate = 0.015; // 1.5% annual growth (Turkey average)
        $population5Years = round($estimatedPopulation * pow(1 + $growthRate, 5));
        $population10Years = round($estimatedPopulation * pow(1 + $growthRate, 10));

        // WHO Green area requirements
        $idealGreenArea = $estimatedPopulation * self::WHO_GREEN_AREA_PER_PERSON;
        $minimumGreenArea = $estimatedPopulation * self::WHO_MINIMUM_GREEN_AREA;
        $recommendedGreenArea = min($idealGreenArea, $areaSize * 0.3); // Max 30% of total area

        // Current tree coverage (estimated from NDVI)
        $currentTreeCoverage = 0;
        $currentTrees = 0;
        if ($ndvi && isset($ndvi['ndvi_estimate'])) {
            $currentTreeCoverage = max(0, ($ndvi['ndvi_estimate'] - 0.2) * 100); // Rough estimate
            $currentTrees = floor(($areaSize * $currentTreeCoverage / 100) / self::TREE_COVERAGE_AREA);
        }

        // Calculate trees needed
        $treesNeeded = ceil($recommendedGreenArea / self::TREE_COVERAGE_AREA);
        $additionalTreesNeeded = max(0, $treesNeeded - $currentTrees);

        // CO2 calculation
        $annualCO2Reduction = $treesNeeded * self::TREE_CO2_ABSORPTION;

        // Earthquake risk analysis
        $earthquakeRisk = self::getEarthquakeRisk($elevation);
        $maxFloors = self::getMaxBuildingHeight($earthquakeRisk);

        // Building recommendations based on climate
        $buildingRecommendations = self::getBuildingRecommendations($temp, $elevation);

        // Tree species recommendations
        $treeSpecies = self::getRecommendedTreeSpecies($temp, $elevation, $ndvi);

        return [
            'use_type' => 'Konut Alanı',
            'confidence' => $scores['residential'],
            'capacity' => [
                'max_houses' => $maxHouses,
                'estimated_population' => round($estimatedPopulation),
                'population_5years' => $population5Years,
                'population_10years' => $population10Years,
                'population_density' => round($estimatedPopulation / ($areaSize / 10000)) . ' kişi/hektar',
                'households_per_hectare' => round($maxHouses / ($areaSize / 10000), 1)
            ],
            'who_green_standards' => [
                'ideal_green_area_m2' => round($idealGreenArea),
                'minimum_green_area_m2' => round($minimumGreenArea),
                'recommended_green_area_m2' => round($recommendedGreenArea),
                'green_area_percentage' => round(($recommendedGreenArea / $areaSize) * 100) . '%',
                'who_standard' => 'WHO ideal: ' . self::WHO_GREEN_AREA_PER_PERSON . ' m²/kişi',
                'current_vs_target' => [
                    'current_tree_coverage_percent' => round($currentTreeCoverage, 1),
                    'current_trees' => $currentTrees,
                    'target_trees' => $treesNeeded,
                    'additional_trees_needed' => $additionalTreesNeeded,
                    'gap_analysis' => $additionalTreesNeeded > 0 ? "Hedefe ulaşmak için $additionalTreesNeeded ağaç dikilmeli" : "Mevcut ağaç sayısı yeterli"
                ]
            ],
            'tree_planting_plan' => [
                'total_trees_needed' => $treesNeeded,
                'current_trees' => $currentTrees,
                'additional_trees' => $additionalTreesNeeded,
                'trees_per_house' => round($treesNeeded / $maxHouses, 1),
                'tree_species' => $treeSpecies,
                'planting_priority' => self::getTreePlantingPriority($temp, $ndvi),
                'co2_reduction_kg_year' => round($annualCO2Reduction),
                'co2_reduction_tons_10years' => round(($annualCO2Reduction * 10) / 1000, 1)
            ],
            'earthquake_analysis' => [
                'risk_level' => $earthquakeRisk['level'],
                'risk_description' => $earthquakeRisk['description'],
                'max_building_floors' => $maxFloors,
                'building_code' => $earthquakeRisk['building_code'],
                'structural_requirements' => $earthquakeRisk['requirements']
            ],
            'infrastructure_plan' => [
                'roads_parking' => round($areaSize * 0.20) . ' m² (20%)',
                'green_areas' => round($recommendedGreenArea) . ' m² (WHO standard)',
                'buildings' => round($usableArea) . ' m² (70%)',
                'public_spaces' => round($areaSize * 0.10) . ' m² (10%)',
                'transportation_note' => 'Ulaşım altyapısı analizi: Daha sonra entegre edilebilir'
            ],
            'building_recommendations' => $buildingRecommendations,
            'energy_efficiency' => self::getEnergyRecommendations($temp, $ndvi),
            'challenges' => self::getResidentialChallenges($temp, $elevation, $ndvi),
            'sustainability_score' => self::calculateSustainabilityScore($scores, $recommendedGreenArea, $areaSize),
            'estimated_cost' => [
                'infrastructure' => round($maxHouses * 50000) . ' TL',
                'green_area_development' => round($recommendedGreenArea * 50) . ' TL',
                'tree_planting' => round($additionalTreesNeeded * 200) . ' TL',
                'total_estimated' => round(($maxHouses * 50000) + ($recommendedGreenArea * 50) + ($additionalTreesNeeded * 200)) . ' TL'
            ]
        ];
    }

    // Get earthquake risk based on location (simplified - real data would come from AFAD)
    private static function getEarthquakeRisk($elevation) {
        // This is simplified - real implementation would query AFAD earthquake zone data
        // For now, we use elevation as a rough proxy (lower elevations near fault lines)

        $elevValue = $elevation && isset($elevation['elevation']) ? $elevation['elevation'] : 500;

        // Turkey earthquake zones (simplified)
        if ($elevValue < 200) {
            return [
                'level' => 'Yüksek',
                'zone' => '1. Derece Deprem Bölgesi',
                'description' => 'Yüksek deprem riski - Özel yapı standartları gerekli',
                'building_code' => 'Türkiye Bina Deprem Yönetmeliği (TBDY 2018)',
                'requirements' => [
                    'Deprem yalıtımlı temel sistemi',
                    'Betonarme karkas sistem (çelik takviyeli)',
                    'Düzenli yapısal kontroller',
                    'Acil tahliye planı zorunlu'
                ]
            ];
        } elseif ($elevValue < 800) {
            return [
                'level' => 'Orta',
                'zone' => '2-3. Derece Deprem Bölgesi',
                'description' => 'Orta deprem riski - Standart deprem yönetmeliği uygulanmalı',
                'building_code' => 'Türkiye Bina Deprem Yönetmeliği (TBDY 2018)',
                'requirements' => [
                    'Betonarme karkas sistem',
                    'Deprem yönetmeliğine uygun tasarım',
                    'Kaliteli malzeme kullanımı',
                    'Periyodik yapısal denetim'
                ]
            ];
        } else {
            return [
                'level' => 'Düşük',
                'zone' => '4. Derece Deprem Bölgesi',
                'description' => 'Düşük deprem riski - Standart yapı normları yeterli',
                'building_code' => 'Türkiye Bina Deprem Yönetmeliği (TBDY 2018)',
                'requirements' => [
                    'Standart betonarme sistem',
                    'Temel deprem yönetmeliği uygulaması',
                    'Normal kalite kontrol'
                ]
            ];
        }
    }

    // Get maximum building height based on earthquake risk
    private static function getMaxBuildingHeight($earthquakeRisk) {
        switch ($earthquakeRisk['level']) {
            case 'Yüksek':
                return '5-6 kat (max 20m) - Deprem yalıtımlı sistemle 8 kata kadar';
            case 'Orta':
                return '8-10 kat (max 35m) - Standart yapı';
            case 'Düşük':
                return '12+ kat mümkün - Yerel yönetmeliğe göre';
            default:
                return '6-8 kat önerilir';
        }
    }

    // Get recommended tree species based on climate
    private static function getRecommendedTreeSpecies($temp, $elevation, $ndvi = null) {
        $species = [];

        if (!$temp || !isset($temp['air_temp']) || $temp['air_temp'] === null || $temp['air_temp'] <= -999) {
            return ['Çınar', 'Meşe', 'Ihlamur', 'Akasya'];
        }

        $avgTemp = $temp['air_temp'];
        $elevValue = $elevation && isset($elevation['elevation']) ? $elevation['elevation'] : 500;
        $precipitation = $ndvi && isset($ndvi['precipitation']) ? $ndvi['precipitation'] : 2.0;

        // Hot and dry climate
        if ($avgTemp > 25 && $precipitation < 1.5) {
            $species = [
                ['name' => 'Akasya', 'co2' => '22 kg/yıl', 'water' => 'Düşük', 'growth' => 'Hızlı'],
                ['name' => 'Zeytin', 'co2' => '18 kg/yıl', 'water' => 'Çok Düşük', 'growth' => 'Yavaş'],
                ['name' => 'Tesbih (Melia)', 'co2' => '25 kg/yıl', 'water' => 'Düşük', 'growth' => 'Hızlı'],
                ['name' => 'Servi', 'co2' => '15 kg/yıl', 'water' => 'Düşük', 'growth' => 'Orta']
            ];
        }
        // Hot and wet
        elseif ($avgTemp > 25 && $precipitation >= 1.5) {
            $species = [
                ['name' => 'Çınar', 'co2' => '30 kg/yıl', 'water' => 'Yüksek', 'growth' => 'Çok Hızlı'],
                ['name' => 'Kavak', 'co2' => '28 kg/yıl', 'water' => 'Yüksek', 'growth' => 'Çok Hızlı'],
                ['name' => 'Dut', 'co2' => '20 kg/yıl', 'water' => 'Orta', 'growth' => 'Hızlı'],
                ['name' => 'Çitlembik', 'co2' => '22 kg/yıl', 'water' => 'Orta', 'growth' => 'Hızlı']
            ];
        }
        // Temperate climate
        elseif ($avgTemp >= 15 && $avgTemp <= 25) {
            $species = [
                ['name' => 'Meşe', 'co2' => '25 kg/yıl', 'water' => 'Orta', 'growth' => 'Yavaş'],
                ['name' => 'Ihlamur', 'co2' => '22 kg/yıl', 'water' => 'Orta', 'growth' => 'Orta'],
                ['name' => 'Kestane', 'co2' => '24 kg/yıl', 'water' => 'Orta', 'growth' => 'Orta'],
                ['name' => 'Çam', 'co2' => '20 kg/yıl', 'water' => 'Düşük', 'growth' => 'Orta'],
                ['name' => 'Akçaağaç', 'co2' => '23 kg/yıl', 'water' => 'Orta', 'growth' => 'Hızlı']
            ];
        }
        // Cool climate
        else {
            $species = [
                ['name' => 'Çam (Karaçam)', 'co2' => '20 kg/yıl', 'water' => 'Düşük', 'growth' => 'Orta'],
                ['name' => 'Ladin', 'co2' => '18 kg/yıl', 'water' => 'Orta', 'growth' => 'Orta'],
                ['name' => 'Kayın', 'co2' => '22 kg/yıl', 'water' => 'Orta', 'growth' => 'Yavaş'],
                ['name' => 'Ardıç', 'co2' => '15 kg/yıl', 'water' => 'Düşük', 'growth' => 'Yavaş']
            ];
        }

        // Adjust for high elevation
        if ($elevValue > 1500) {
            $species = [
                ['name' => 'Karaçam', 'co2' => '18 kg/yıl', 'water' => 'Düşük', 'growth' => 'Yavaş'],
                ['name' => 'Ladin', 'co2' => '16 kg/yıl', 'water' => 'Orta', 'growth' => 'Yavaş'],
                ['name' => 'Sedir', 'co2' => '20 kg/yıl', 'water' => 'Düşük', 'growth' => 'Çok Yavaş'],
                ['name' => 'Ardıç', 'co2' => '12 kg/yıl', 'water' => 'Düşük', 'growth' => 'Yavaş']
            ];
        }

        return $species;
    }

    // Get tree planting priority
    private static function getTreePlantingPriority($temp, $ndvi) {
        $priority = [];

        if ($ndvi && $ndvi['ndvi_estimate'] < 0.3) {
            $priority[] = '🔴 ACİL: Bitki örtüsü çok zayıf - öncelikli ağaçlandırma gerekli';
        } elseif ($ndvi && $ndvi['ndvi_estimate'] < 0.5) {
            $priority[] = '🟡 ORTA: Mevcut yeşil alan yetersiz - ağaçlandırma önerilir';
        } else {
            $priority[] = '🟢 DÜŞÜK: Mevcut bitki örtüsü iyi - koruma öncelikli';
        }

        if ($temp && $temp['air_temp'] > 30) {
            $priority[] = '☀️ Sıcak iklim: Gölgeleme için ağaçlandırma kritik';
        }

        $priority[] = '📅 En iyi dikim zamanı: Mart-Nisan veya Ekim-Kasım';

        return implode("\n", $priority);
    }

    // Green area park plan
    private static function generateGreenAreaPlan($scores, $ndvi, $temp, $elevation, $areaSize) {
        $treesNeeded = ceil($areaSize / self::TREE_COVERAGE_AREA);
        $co2Reduction = $treesNeeded * self::TREE_CO2_ABSORPTION;

        return [
            'use_type' => 'Yeşil Alan / Park',
            'confidence' => $scores['green_area'],
            'park_design' => [
                'total_area_m2' => $areaSize,
                'total_trees' => $treesNeeded,
                'walking_paths_m' => round($areaSize * 0.15),
                'playground_m2' => round($areaSize * 0.10),
                'sports_area_m2' => round($areaSize * 0.10),
                'picnic_areas' => floor($areaSize / 1000),
                'parking_spaces' => floor($areaSize / 500)
            ],
            'tree_species_distribution' => [
                'Çınar (gölge)' => round($treesNeeded * 0.30),
                'Meşe (uzun ömür)' => round($treesNeeded * 0.25),
                'Ihlamur (koku)' => round($treesNeeded * 0.20),
                'Çam (her mevsim yeşil)' => round($treesNeeded * 0.15),
                'Süs ağaçları' => round($treesNeeded * 0.10)
            ],
            'environmental_impact' => [
                'annual_co2_absorption_kg' => round($co2Reduction),
                'annual_co2_absorption_tons' => round($co2Reduction / 1000, 2),
                'oxygen_production_kg_year' => round($treesNeeded * 120),
                'air_pollution_reduction' => 'Yüksek',
                'urban_heat_island_effect' => 'Sıcaklık 2-3°C azalma'
            ],
            'biodiversity' => [
                'bird_species_estimated' => round($treesNeeded / 10),
                'insect_habitat' => 'Arı ve kelebek dostu',
                'wildlife_friendly' => true
            ],
            'community_benefits' => [
                'people_capacity' => round($areaSize / 10),
                'recreation_value' => 'Yüksek',
                'property_value_increase' => '%15-20',
                'health_benefits' => 'Mental ve fiziksel sağlık iyileştirme'
            ],
            'maintenance' => [
                'annual_cost_estimate' => round($areaSize * 5) . ' TL/yıl',
                'staff_needed' => ceil($areaSize / 10000),
                'irrigation_system' => $ndvi && $ndvi['precipitation'] < 1 ? 'Otomatik damla sulama gerekli' : 'Mevsimsel sulama yeterli'
            ]
        ];
    }

    // Solar energy plan
    private static function generateSolarPlan($scores, $ndvi, $areaSize) {
        $panelEfficiency = 0.18; // 18% efficiency
        $solarRadiation = $ndvi ? $ndvi['solar_radiation'] : 200; // kWh/m²/day
        $annualEnergy = $areaSize * $solarRadiation * 365 * $panelEfficiency / 1000; // MWh

        return [
            'use_type' => 'Güneş Enerjisi Santrali',
            'confidence' => $scores['solar_energy'],
            'energy_production' => [
                'panel_area_m2' => $areaSize,
                'installed_capacity_mw' => round($areaSize * 0.15 / 1000, 2),
                'annual_production_mwh' => round($annualEnergy),
                'daily_average_kwh' => round($annualEnergy * 1000 / 365),
                'homes_powered' => round($annualEnergy * 1000 / 3600)
            ],
            'environmental_impact' => [
                'co2_avoided_tons_year' => round($annualEnergy * 0.5),
                'equivalent_trees' => round(($annualEnergy * 0.5 * 1000) / self::TREE_CO2_ABSORPTION)
            ],
            'economic' => [
                'investment_estimate' => round($areaSize * 1000) . ' TL',
                'payback_period_years' => 7,
                'annual_revenue' => round($annualEnergy * 500) . ' TL'
            ]
        ];
    }

    // Wind energy plan
    private static function generateWindPlan($scores, $elevation, $areaSize) {
        $turbineCount = floor($areaSize / 50000); // 5 hectare per turbine

        return [
            'use_type' => 'Rüzgar Enerjisi Santrali',
            'confidence' => $scores['wind_energy'],
            'turbine_count' => max(1, $turbineCount),
            'estimated_capacity_mw' => max(1, $turbineCount) * 2.5,
            'note' => 'Detaylı rüzgar ölçümü gereklidir'
        ];
    }

    // Generate comprehensive textual report
    // Generate secondary "what if" analysis
    private static function generateSecondaryAnalysis($primaryUse, $secondaryUse, $scores, $probabilities, $ndvi, $temp, $elevation, $areaSize, $useNames) {
        $analysis = "\n\n⚠️ ALTERNATİF SENARYO ANALİZİ:\n" .
                   "═══════════════════════════════════════════════════\n\n" .
                   sprintf("🔍 Bu alan birincil olarak '%s' için önerilmektedir.\n", mb_strtoupper($useNames[$primaryUse], 'UTF-8')) .
                   sprintf("ANCAK, bu alanı '%s' olarak kullanmayı planlıyorsanız:\n\n", mb_strtoupper($useNames[$secondaryUse], 'UTF-8'));

        // Generate specific requirements for each secondary use
        if ($secondaryUse === 'residential') {
            $analysis .= self::generateResidentialRequirements($ndvi, $temp, $elevation, $areaSize);
        } elseif ($secondaryUse === 'agriculture') {
            $analysis .= self::generateAgricultureRequirements($ndvi, $temp, $elevation, $areaSize);
        } elseif ($secondaryUse === 'green_area') {
            $analysis .= self::generateGreenAreaRequirements($ndvi, $temp, $areaSize);
        } elseif ($secondaryUse === 'solar_energy') {
            $analysis .= self::generateSolarRequirements($ndvi, $areaSize);
        } elseif ($secondaryUse === 'wind_energy') {
            $analysis .= self::generateWindRequirements($elevation, $areaSize);
        }

        $analysis .= sprintf("\n📊 Bu kullanım için uygunluk skoru: %d/100 (%%%s olasılık)\n",
                           $scores[$secondaryUse], $probabilities[$secondaryUse]);
        $analysis .= "\n💡 Karar: Yukarıdaki kriterleri karşılayabiliyorsanız bu alternatif kullanım da mümkündür.\n";

        return $analysis;
    }

    // Generate residential requirements for secondary analysis
    private static function generateResidentialRequirements($ndvi, $temp, $elevation, $areaSize) {
        $requirements = "📋 KONUT AMAÇLI KULLANIM İÇİN GEREKLİLİKLER:\n\n";

        // WHO green area standards
        $estimatedPopulation = ($areaSize * 0.4) / 100; // %40 konut alanı, 100m² per household
        $estimatedPeople = $estimatedPopulation * self::AVERAGE_HOUSEHOLD_SIZE;
        $requiredGreenArea = $estimatedPeople * self::WHO_GREEN_AREA_PER_PERSON;
        $requiredTrees = ceil($requiredGreenArea / self::TREE_COVERAGE_AREA);

        $requirements .= sprintf(
            "🌳 Yeşil Alan Gereksinimleri (WHO Standardı):\n" .
            "   • Tahmini nüfus: %d kişi (%d hane)\n" .
            "   • Gerekli yeşil alan: %s m² (%s hektar)\n" .
            "   • Dikilmesi gereken ağaç sayısı: %d adet\n" .
            "   • WHO standardı: 50 m²/kişi (ideal), minimum 9 m²/kişi\n\n",
            round($estimatedPeople),
            round($estimatedPopulation),
            number_format($requiredGreenArea),
            number_format($requiredGreenArea/10000, 2),
            $requiredTrees
        );

        // Tree species recommendations
        $requirements .= "🌲 Önerilen Ağaç Türleri:\n";
        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            if ($temp['air_temp'] > 25) {
                $requirements .= "   • Çınar (gölge, yüksek CO₂ emilimi)\n" .
                               "   • Akasya (hızlı büyüme)\n" .
                               "   • Zeytin (düşük su ihtiyacı)\n" .
                               "   • Palmiye (dekoratif, sıcağa dayanıklı)\n";
            } elseif ($temp['air_temp'] > 15) {
                $requirements .= "   • Kestane (gölge, 25 kg CO₂/yıl)\n" .
                               "   • Meşe (dayanıklı, 22 kg CO₂/yıl)\n" .
                               "   • Çınar (hızlı büyüme)\n" .
                               "   • Ihlamur (kokulu, arı dostu)\n";
            } else {
                $requirements .= "   • Çam (her mevsim yeşil)\n" .
                               "   • Servi (rüzgar perdesi)\n" .
                               "   • Ardıç (soğuğa dayanıklı)\n" .
                               "   • Ladin (oksijen üretimi)\n";
            }
        }
        $requirements .= sprintf("   💨 CO₂ Emilim Kapasitesi: Toplam ~%s ton/yıl\n\n",
                               number_format($requiredTrees * self::TREE_CO2_ABSORPTION / 1000, 1));

        // Earthquake requirements
        if ($elevation && isset($elevation['earthquake_zone'])) {
            $eqZone = $elevation['earthquake_zone'];
            $requirements .= "🏗️ Deprem Önlemleri:\n";
            if ($eqZone <= 2) {
                $requirements .= "   • Deprem bölgesi: YÜksek riskli (Derece $eqZone)\n" .
                               "   • Bina yüksekliği: Maksimum 6 kat (deprem yönetmeliği)\n" .
                               "   • Zemin etüdü: ZORUNLU\n" .
                               "   • Deprem yalıtım sistemi: Önerilir\n" .
                               "   • Yapı denetim: A+ sınıf\n\n";
            } elseif ($eqZone <= 3) {
                $requirements .= "   • Deprem bölgesi: Orta riskli (Derece $eqZone)\n" .
                               "   • Bina yüksekliği: Maksimum 10 kat\n" .
                               "   • Zemin etüdü: Gerekli\n" .
                               "   • Deprem yönetmeliğine uygun inşaat\n\n";
            } else {
                $requirements .= "   • Deprem bölgesi: Düşük riskli (Derece $eqZone)\n" .
                               "   • Standart deprem güvenlik önlemleri yeterli\n\n";
            }
        }

        // Infrastructure requirements
        $requirements .= "🚰 Altyapı Gereksinimleri:\n" .
                        "   • Su şebekesi: " . round($estimatedPeople * 0.15) . " m³/gün (kişi başı 150L)\n" .
                        "   • Kanalizasyon: Tam kapasite\n" .
                        "   • Elektrik: " . round($estimatedPopulation * 5) . " kVA (hane başı 5 kVA)\n" .
                        "   • Doğalgaz: " . round($estimatedPopulation) . " abone kapasitesi\n" .
                        "   • Yol genişliği: Minimum 7 metre (iki şerit)\n\n";

        // Climate adaptation
        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            $requirements .= "🌡️ İklim Uyum Önlemleri:\n";
            if ($temp['air_temp'] > 30) {
                $requirements .= "   • Binalara gölgeleme elemanları\n" .
                               "   • Açık renkli dış cepheler (ısı yansıtma)\n" .
                               "   • Klima yükü: Ortalama 12000 BTU/daire\n" .
                               "   • Su tüketimi: %30 artış beklenir\n\n";
            } elseif ($temp['air_temp'] < 10) {
                $requirements .= "   • Yalıtım: Minimum 10 cm dış cephe\n" .
                               "   • Isıtma sistemi: Merkezi veya doğalgaz\n" .
                               "   • Güneye yönelim: Pencereler büyük olmalı\n\n";
            }
        }

        return $requirements;
    }

    // Generate agriculture requirements for secondary analysis
    private static function generateAgricultureRequirements($ndvi, $temp, $elevation, $areaSize) {
        $requirements = "📋 TARIMSAL KULLANIM İÇİN GEREKLİLİKLER:\n\n";

        $requirements .= "🌾 Sulama ve Toprak Hazırlığı:\n";
        if ($ndvi && $ndvi['precipitation'] < 2) {
            $requirements .= "   • Damlama sulama sistemi: ZORUNLU (düşük yağış)\n" .
                           "   • Su deposu: Minimum " . round($areaSize * 0.02) . " m³\n" .
                           "   • Yıllık su ihtiyacı: ~" . round($areaSize * 0.5) . " m³\n";
        } else {
            $requirements .= "   • Yağış yeterli, yağmurlama sulama önerilir\n" .
                           "   • Drenaj sistemi: Aşırı yağışta gerekli\n";
        }

        $requirements .= "\n🚜 Toprak İyileştirme:\n" .
                        "   • Toprak analizi: ZORUNLU (pH, NPK, organik madde)\n" .
                        "   • Kompost ihtiyacı: ~" . round($areaSize * 0.003) . " ton\n" .
                        "   • Yeşil gübre: İlk sezonda önerilir\n\n";

        return $requirements;
    }

    // Generate green area requirements
    private static function generateGreenAreaRequirements($ndvi, $temp, $areaSize) {
        $requirements = "📋 YEŞİL ALAN/PARK KULLANIMI İÇİN GEREKLİLİKLER:\n\n";

        $treeCapacity = floor($areaSize / 100); // Her 100 m² için 1 ağaç
        $requirements .= sprintf(
            "🌳 Park Tasarımı:\n" .
            "   • Ağaç kapasitesi: ~%d adet\n" .
            "   • Çim alan: ~%s m² (%%60 alan)\n" .
            "   • Yürüyüş yolları: ~%d metre\n" .
            "   • Oturma alanları: %d adet (20 kişilik)\n" .
            "   • Çocuk parkı: %d adet (1000 m² başına 1)\n\n",
            $treeCapacity,
            number_format($areaSize * 0.6),
            round($areaSize * 0.15),
            round($areaSize / 500),
            round($areaSize / 1000)
        );

        $requirements .= "💧 Bakım Gereksinimleri:\n" .
                        "   • Otomatik sulama sistemi\n" .
                        "   • Haftalık çim biçme\n" .
                        "   • Yıllık bakım maliyeti: ~" . number_format($areaSize * 2) . " TL\n\n";

        return $requirements;
    }

    // Generate solar requirements
    private static function generateSolarRequirements($ndvi, $areaSize) {
        $panelArea = $areaSize * 0.7; // %70 panel alanı
        $capacity = $panelArea * 0.18; // 180W/m²
        $annualProduction = $capacity * 1500; // 1500 saat/yıl

        $requirements = sprintf(
            "📋 GÜNEŞ ENERJİSİ SANTRALİ İÇİN GEREKLİLİKLER:\n\n" .
            "☀️ Teknik Özellikler:\n" .
            "   • Panel alanı: %s m²\n" .
            "   • Kurulu güç: %s kWp\n" .
            "   • Yıllık üretim: ~%s MWh\n" .
            "   • Yatırım maliyeti: ~%s TL\n" .
            "   • Geri ödeme süresi: 6-8 yıl\n\n",
            number_format($panelArea),
            number_format($capacity, 0),
            number_format($annualProduction / 1000, 0),
            number_format($capacity * 4000)
        );

        return $requirements;
    }

    // Generate wind requirements
    private static function generateWindRequirements($elevation, $areaSize) {
        $turbineCapacity = 2.5; // 2.5 MW per turbine
        $turbineCount = floor($areaSize / 100000); // 1 türbin per 10 hektar

        $requirements = sprintf(
            "📋 RÜZGAR ENERJİSİ SANTRALİ İÇİN GEREKLİLİKLER:\n\n" .
            "💨 Teknik Özellikler:\n" .
            "   • Türbin sayısı: %d adet\n" .
            "   • Kurulu güç: %.1f MW\n" .
            "   • Rüzgar ölçümü: 1 yıl (ZORUNLU)\n" .
            "   • Minimum rüzgar hızı: 6 m/s (ekonomik)\n" .
            "   • Yatırım maliyeti: ~%s TL\n\n",
            max(1, $turbineCount),
            max(1, $turbineCount) * $turbineCapacity,
            number_format(max(1, $turbineCount) * $turbineCapacity * 1000000)
        );

        return $requirements;
    }

    private static function generateTextualReport($lat, $lon, $scores, $probabilities, $ndvi, $temp, $elevation, $fireRisk, $areaSize) {
        $report = [];

        // SECTION 1: INTRODUCTION - Girilen Veriler
        $report['introduction'] = "═══════════════════════════════════════════════════\n" .
                                 "📋 AKILLI ŞEHİR PLANLAMA RAPORU\n" .
                                 "═══════════════════════════════════════════════════\n\n" .
                                 "🎯 GİRİŞ - Girilen Veriler:\n\n" .
                                 "📍 Analiz Koordinatları: {$lat}°K, {$lon}°D\n" .
                                 "📐 Analiz Alanı: " . number_format($areaSize) . " m² (" . number_format($areaSize/10000, 2) . " hektar)\n" .
                                 "📅 Analiz Tarihi: " . date('d.m.Y H:i') . "\n" .
                                 "🛰️ Veri Kaynakları: NASA FIRMS, MODIS, NDVI, NASA POWER, OpenElevation\n\n" .
                                 "Bu rapor, şehir planlama müdürlükleri ve karar vericiler için hazırlanmıştır.\n" .
                                 "Teknik olmayan bir dille yazılmış olup, doğrudan uygulama kılavuzu sağlar.\n\n";

        // SECTION 2: ANALYSIS - NASA Verileri ve Değerlendirme
        $report['analysis_header'] = "═══════════════════════════════════════════════════\n" .
                                     "🔍 ANALİZ - NASA Uydu Verileri\n" .
                                     "═══════════════════════════════════════════════════\n\n";

        // Probability analysis with weighted scoring explanation
        $report['probability_analysis'] = "📊 Arazi Kullanım Uygunluk Skorları ve Olasılıklar:\n\n" .
            "Ağırlıklı puanlama sistemi kullanılarak hesaplanmıştır.\n" .
            "Her kullanım türü 10 farklı kritere göre değerlendirilmiştir:\n\n" .
            sprintf(
                "• 🌾 Tarım: %%%s olasılık (Skor: %d/100)\n" .
                "• 🏘️ Konut: %%%s olasılık (Skor: %d/100)\n" .
                "• 🌳 Yeşil Alan: %%%s olasılık (Skor: %d/100)\n" .
                "• ☀️ Güneş Enerjisi: %%%s olasılık (Skor: %d/100)\n" .
                "• 💨 Rüzgar Enerjisi: %%%s olasılık (Skor: %d/100)\n\n",
                $probabilities['agriculture'], $scores['agriculture'],
                $probabilities['residential'], $scores['residential'],
                $probabilities['green_area'], $scores['green_area'],
                $probabilities['solar_energy'], $scores['solar_energy'],
                $probabilities['wind_energy'], $scores['wind_energy']
            ) .
            "⚖️ Not: Skorlar 0-100 arasındadır. Yüksek skor o kullanım için daha uygun demektir.\n\n";

        // NASA data analysis
        $report['nasa_data_analysis'] = "🛰️ NASA Uydu Verileri - Detaylı Analiz:\n\n";

        // NDVI Analysis
        if ($ndvi) {
            $ndviValue = $ndvi['ndvi_estimate'];
            $vegStatus = $ndviValue > 0.6 ? "çok sağlıklı" :
                        ($ndviValue > 0.4 ? "sağlıklı" :
                        ($ndviValue > 0.2 ? "orta" : "zayıf"));

            $report['nasa_data_analysis'] .= sprintf(
                "📊 NDVI (Bitki Örtüsü İndeksi): %.2f\n" .
                "Bitki örtüsü durumu %s seviyededir. NDVI değeri 0.0 ile 1.0 arasında değişir; " .
                "yüksek değerler daha yoğun ve sağlıklı bitki örtüsünü gösterir.\n\n",
                $ndviValue, $vegStatus
            );

            if ($ndvi['precipitation']) {
                $report['nasa_data_analysis'] .= sprintf(
                    "💧 Yağış Miktarı: %.1f mm/gün\n" .
                    "Bu, bölgenin %s seviyede yağış aldığını göstermektedir.\n\n",
                    $ndvi['precipitation'],
                    $ndvi['precipitation'] > 3 ? "yüksek" : ($ndvi['precipitation'] > 1.5 ? "orta" : "düşük")
                );
            }

            if ($ndvi['solar_radiation']) {
                $report['nasa_data_analysis'] .= sprintf(
                    "☀️ Güneş Radyasyonu: %.1f W/m²\n" .
                    "Güneş enerjisi potansiyeli %s.\n\n",
                    $ndvi['solar_radiation'],
                    $ndvi['solar_radiation'] > 200 ? "çok yüksek" : ($ndvi['solar_radiation'] > 150 ? "yüksek" : "orta")
                );
            }
        }

        // Temperature Analysis
        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            $tempValue = $temp['air_temp'];
            $tempStatus = $tempValue > 35 ? "çok sıcak" :
                         ($tempValue > 25 ? "sıcak" :
                         ($tempValue > 15 ? "ılıman" :
                         ($tempValue > 5 ? "serin" : "soğuk")));

            $report['nasa_data_analysis'] .= sprintf(
                "🌡️ Hava Sıcaklığı: %.1f°C\n" .
                "Bölge %s iklim özelliği göstermektedir. ",
                $tempValue, $tempStatus
            );

            if ($tempValue >= 15 && $tempValue <= 30) {
                $report['nasa_data_analysis'] .= "Bu sıcaklık aralığı hem tarım hem de yerleşim için idealdir.\n\n";
            } elseif ($tempValue > 30) {
                $report['nasa_data_analysis'] .= "Yüksek sıcaklıklar su ihtiyacını artırır ve enerji tüketimini etkiler.\n\n";
            } else {
                $report['nasa_data_analysis'] .= "Düşük sıcaklıklar ısınma maliyetlerini artırabilir.\n\n";
            }
        }

        // Elevation Analysis
        if ($elevation) {
            $elevValue = $elevation['elevation'];
            $report['nasa_data_analysis'] .= sprintf(
                "📏 Rakım: %.0f metre\n",
                $elevValue
            );

            if ($elevValue < 100) {
                $report['nasa_data_analysis'] .= "Düşük rakım, sel riski açısından dikkat gerektirir ancak tarım için avantajlıdır.\n\n";
            } elseif ($elevValue < 500) {
                $report['nasa_data_analysis'] .= "Orta rakım, hem tarım hem de yerleşim için idealdir.\n\n";
            } elseif ($elevValue < 1500) {
                $report['nasa_data_analysis'] .= "Yüksek rakım, rüzgar enerjisi potansiyeli sunar.\n\n";
            } else {
                $report['nasa_data_analysis'] .= "Çok yüksek rakım, inşaat ve tarım maliyetlerini artırabilir.\n\n";
            }
        }

        // Fire Risk Analysis
        if ($fireRisk) {
            $riskLevel = $fireRisk['risk_level'];
            $riskText = $riskLevel === 'high' ? "yüksek" : ($riskLevel === 'medium' ? "orta" : "düşük");

            $report['nasa_data_analysis'] .= sprintf(
                "🔥 FIRMS Yangın Riski: %s\n" .
                "Son 30 gün içinde %d adet yangın tespit edilmiştir. ",
                ucfirst($riskText), $fireRisk['count']
            );

            if ($riskLevel === 'high') {
                $report['nasa_data_analysis'] .= "Yüksek yangın riski nedeniyle yangın söndürme sistemleri ve acil müdahale planları gereklidir.\n\n";
            } elseif ($riskLevel === 'medium') {
                $report['nasa_data_analysis'] .= "Orta düzey risk için yangın önleme tedbirleri alınmalıdır.\n\n";
            } else {
                $report['nasa_data_analysis'] .= "Yangın riski düşüktür.\n\n";
            }
        }

        // Primary recommendation with detailed reasoning
        arsort($scores);
        $topUse = array_key_first($scores);
        $topProb = $probabilities[$topUse];

        $useNames = [
            'agriculture' => 'Tarım',
            'residential' => 'Konut/Yerleşim',
            'green_area' => 'Yeşil Alan/Park',
            'solar_energy' => 'Güneş Enerjisi',
            'wind_energy' => 'Rüzgar Enerjisi',
            'tourism' => 'Turizm',
            'geothermal' => 'Jeotermal Enerji'
        ];

        $report['recommendation'] = sprintf(
            "📋 ÖNERİLEN KULLANIM: %s (%%%s olasılık)\n\n" .
            "Analiz Gerekçesi:\n",
            $useNames[$topUse], $topProb
        );

        // Detailed reasoning based on primary use
        if ($topUse === 'agriculture') {
            $report['recommendation'] .=
                "• NDVI değerleri tarımsal üretim için uygun bitki örtüsü göstermektedir\n" .
                "• Sıcaklık ve yağış değerleri tarımsal faaliyetleri desteklemektedir\n" .
                "• Rakım seviyesi çeşitli ürünler için uygundur\n" .
                "• Bölgede mevcut bitki örtüsü tarımsal kullanıma elverişlidir\n";
        } elseif ($topUse === 'residential') {
            $report['recommendation'] .=
                "• İklim koşulları yaşam kalitesi için uygundur\n" .
                "• Arazi yapısı inşaat faaliyetleri için elverişlidir\n" .
                "• Rakım seviyesi yerleşim için idealdir\n" .
                "• WHO standartlarına göre yeşil alan planlaması yapılmalıdır\n";
        } elseif ($topUse === 'green_area') {
            $report['recommendation'] .=
                "• Mevcut bitki örtüsü park ve rekreasyon alanı için uygundur\n" .
                "• Biyoçeşitlilik potansiyeli yüksektir\n" .
                "• Şehir içi yeşil alan ihtiyacını karşılayabilir\n" .
                "• Ekolojik koruma için önemli bir alan olabilir\n";
        } elseif ($topUse === 'solar_energy') {
            $report['recommendation'] .=
                "• Güneş radyasyonu seviyesi enerji üretimi için çok yüksektir\n" .
                "• Açık arazi yapısı panel kurulumu için idealdir\n" .
                "• Yıllık enerji üretim potansiyeli ekonomik olarak uygun görünmektedir\n" .
                "• Temiz enerji üretimi için stratejik bir lokasyondur\n";
        } else {
            $report['recommendation'] .=
                "• Rakım ve arazi yapısı rüzgar enerjisi için uygun olabilir\n" .
                "• Detaylı rüzgar ölçümleri yapılması önerilir\n" .
                "• Yüksek rakım rüzgar potansiyelini artırmaktadır\n";
        }

        // Alternative uses
        $alternatives = array_slice($scores, 1, 2, true);
        if (!empty($alternatives)) {
            $report['alternatives'] = "\n🔄 Alternatif Kullanımlar:\n";
            foreach ($alternatives as $use => $score) {
                $report['alternatives'] .= sprintf(
                    "• %s: %%%s olasılık (Skor: %d/100)\n",
                    $useNames[$use], $probabilities[$use], $score
                );
            }
        }

        // SECONDARY RECOMMENDATION ANALYSIS (if primary is not residential, show residential requirements)
        $secondaryUse = array_keys($scores)[1]; // Get second highest
        $report['secondary_analysis'] = self::generateSecondaryAnalysis($topUse, $secondaryUse, $scores, $probabilities, $ndvi, $temp, $elevation, $areaSize, $useNames);

        // SECTION 3: RESULTS - Sonuç ve Öneriler
        $report['results_header'] = "\n═══════════════════════════════════════════════════\n" .
                                    "✅ SONUÇ VE ÖNERİLER\n" .
                                    "═══════════════════════════════════════════════════\n\n";

        // Conclusion
        $report['conclusion'] = "🎯 Ana Sonuç:\n\n" .
            "Bu kapsamlı NASA veri analizi, " . number_format($areaSize) . " m² (" . number_format($areaSize/10000, 2) . " hektar) alanlık bölge için:\n\n" .
            "▶️ " . strtoupper($useNames[$topUse]) . " KULLANIMI önerilmektedir.\n" .
            "▶️ Uygunluk Olasılığı: %" . $topProb . "\n" .
            "▶️ Uygunluk Skoru: " . $scores[$topUse] . "/100\n\n";

        // Implementation notes
        $report['implementation'] = "📌 Uygulama Notları:\n\n" .
            "1. Karar vermeden önce mutlaka değerlendirilmeli:\n" .
            "   ✓ İmar planı ve yerel düzenlemeler\n" .
            "   ✓ Mevcut altyapı durumu\n" .
            "   ✓ Ulaşım bağlantıları (şu an entegre değil - daha sonra eklenecek)\n" .
            "   ✓ Arazi eğimi analizi (daha sonra eklenecek)\n" .
            "   ✓ Sosyo-ekonomik faktörler\n" .
            "   ✓ Bölge halkının ihtiyaçları\n\n" .
            "2. Bu rapor bilimsel veri sağlar, ancak nihai karar:\n" .
            "   → Şehir planlama uzmanları\n" .
            "   → İlgili belediye birimleri\n" .
            "   → Çevre ve Şehircilik Bakanlığı onayı\n" .
            "   ile birlikte alınmalıdır.\n\n" .
            "3. Raporun geçerlilik süresi: 6 ay\n" .
            "   (İklim verileri değişebilir, güncelleme önerilir)\n\n";

        $report['footer'] = "═══════════════════════════════════════════════════\n" .
                           "Rapor Sonu - NASA Space Apps Challenge 2025\n" .
                           "═══════════════════════════════════════════════════";

        return $report;
    }

    // Helper functions
    private static function getBuildingRecommendations($temp, $elevation) {
        $recommendations = [];

        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            if ($temp['air_temp'] > 30) {
                $recommendations[] = 'Açık renkli dış cephe (ısı yansıtma)';
                $recommendations[] = 'Geniş saçaklar (gölgeleme)';
                $recommendations[] = 'İyi yalıtım (klima maliyeti)';
            } elseif ($temp['air_temp'] < 10) {
                $recommendations[] = 'Kalın yalıtım (ısı kaybı)';
                $recommendations[] = 'Güneye bakan geniş pencereler';
                $recommendations[] = 'Rüzgar kırıcı peyzaj';
            }
        }

        if ($elevation && $elevation['elevation'] > 1000) {
            $recommendations[] = 'Kar yükü hesaplı çatı tasarımı';
            $recommendations[] = 'Depreme dayanıklı yapı';
        }

        $recommendations[] = 'Yeşil çatı (yağmur suyu toplama)';
        $recommendations[] = 'Güneş paneli entegrasyonu';
        $recommendations[] = 'Akıllı ev sistemleri';

        return $recommendations;
    }

    private static function getEnergyRecommendations($temp, $ndvi) {
        $recommendations = [];

        if ($ndvi && $ndvi['solar_radiation'] > 180) {
            $recommendations['solar'] = 'Yüksek güneş potansiyeli - Her eve panel önerilir';
        }

        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            if ($temp['air_temp'] > 25) {
                $recommendations['cooling'] = 'Merkezi soğutma sistemi veya A+ klima';
            } elseif ($temp['air_temp'] < 15) {
                $recommendations['heating'] = 'Doğalgaz veya ısı pompası sistemi';
            }
        }

        $recommendations['efficiency'] = 'A sınıfı enerji verimliliği zorunlu';

        return $recommendations;
    }

    private static function getAgricultureChallenges($ndvi, $temp, $elevation) {
        $challenges = [];

        if ($ndvi && $ndvi['precipitation'] < 1) {
            $challenges[] = 'Düşük yağış - sulama sistemi gerekli';
        }

        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            if ($temp['air_temp'] > 35) {
                $challenges[] = 'Yüksek sıcaklık - gölgeleme ve sulama';
            }
        }

        if ($elevation && $elevation['elevation'] > 1500) {
            $challenges[] = 'Yüksek rakım - don riski';
        }

        return $challenges ?: ['Önemli zorluk tespit edilmedi'];
    }

    private static function getResidentialChallenges($temp, $elevation, $ndvi) {
        $challenges = [];

        if ($temp && isset($temp['air_temp']) && $temp['air_temp'] !== null && $temp['air_temp'] > -999) {
            if ($temp['air_temp'] > 35) {
                $challenges[] = 'Yüksek sıcaklık - enerji maliyeti yüksek';
            }
        }

        if ($elevation && $elevation['flood_risk'] !== 'Düşük') {
            $challenges[] = 'Taşkın riski - drenaj sistemi şart';
        }

        if ($ndvi && $ndvi['ndvi_estimate'] < 0.2) {
            $challenges[] = 'Çok az yeşil alan - ağaçlandırma gerekli';
        }

        return $challenges ?: ['Önemli zorluk tespit edilmedi'];
    }

    private static function calculateSustainabilityScore($scores, $greenArea, $totalArea) {
        $greenPercentage = ($greenArea / $totalArea) * 100;

        $score = 0;
        $score += min(40, $greenPercentage * 1.5); // Green area (max 40 points)
        $score += min(30, $scores['residential'] * 0.3); // Suitability (max 30 points)
        $score += min(30, $scores['solar_energy'] * 0.3); // Energy potential (max 30 points)

        return round($score);
    }
}

// API Endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'analyze';

    $lat = floatval($_GET['lat'] ?? 0);
    $lon = floatval($_GET['lon'] ?? 0);
    $areaSize = intval($_GET['area_size'] ?? 10000); // Default 1 hectare (10,000 m²)

    if (!$lat || !$lon) {
        echo json_encode(['error' => 'Latitude and longitude required'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $response = [];

    switch ($action) {
        case 'analyze':
            $response = UrbanPlanningAI::analyzeAreaForLandUse($lat, $lon, $areaSize);
            break;

        default:
            $response = ['error' => 'Invalid action'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
