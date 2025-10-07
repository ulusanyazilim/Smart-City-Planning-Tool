<?php
require_once 'config.php';

/**
 * NASA Advanced Data Integration
 * - FIRMS (Fire Information)
 * - MODIS (Land Surface Temperature, NDVI)
 * - Sentinel (High-res imagery)
 * - SRTM (Elevation data)
 */

class NASAAdvancedData {

    // FIRMS - Active Fire Data
    public static function getFireData($lat, $lon, $days = 1) {
        // NASA FIRMS API için Map Key gerekli
        // https://firms.modaps.eosdis.nasa.gov/api/area/ adresinden alınabilir

        $mapKey = defined('NASA_FIRMS_KEY') ? NASA_FIRMS_KEY : 'demo';
        $radius = 50; // km

        // VIIRS data (more accurate than MODIS)
        $url = "https://firms.modaps.eosdis.nasa.gov/api/area/csv/{$mapKey}/VIIRS_NOAA20_NRT/{$lat},{$lon}/{$radius}/{$days}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200 && $response) {
            $fires = [];
            $lines = explode("\n", $response);

            // Skip header
            for ($i = 1; $i < count($lines); $i++) {
                $data = str_getcsv($lines[$i]);
                if (count($data) > 10) {
                    $fires[] = [
                        'latitude' => floatval($data[0]),
                        'longitude' => floatval($data[1]),
                        'brightness' => floatval($data[2]),
                        'confidence' => $data[8],
                        'date' => $data[5],
                        'distance' => self::calculateDistance($lat, $lon, floatval($data[0]), floatval($data[1]))
                    ];
                }
            }

            return [
                'count' => count($fires),
                'fires' => $fires,
                'risk_level' => self::calculateFireRisk(count($fires), $fires)
            ];
        }

        return ['count' => 0, 'fires' => [], 'risk_level' => 'low'];
    }

    // Calculate fire risk based on fire count and proximity
    private static function calculateFireRisk($count, $fires) {
        if ($count == 0) return 'low';

        // Check if any fire is very close (< 10km)
        foreach ($fires as $fire) {
            if ($fire['distance'] < 10 && $fire['confidence'] === 'high') {
                return 'critical';
            }
        }

        if ($count > 5) return 'high';
        if ($count > 2) return 'medium';
        return 'low';
    }

    // MODIS NDVI (Normalized Difference Vegetation Index)
    // Simplified calculation using NASA POWER API
    public static function getNDVI($lat, $lon, $startDate, $endDate) {
        // Use NASA POWER for vegetation parameters
        $start = date('Ymd', strtotime($startDate));
        $end = date('Ymd', strtotime($endDate));

        $url = "https://power.larc.nasa.gov/api/temporal/daily/point?";
        $params = [
            'parameters' => 'ALLSKY_SFC_SW_DWN,PRECTOTCORR,T2M',
            'community' => 'AG',
            'longitude' => $lon,
            'latitude' => $lat,
            'start' => $start,
            'end' => $end,
            'format' => 'JSON'
        ];

        $url .= http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);

            if (isset($data['properties']['parameter'])) {
                $params = $data['properties']['parameter'];

                // Calculate vegetation health score based on solar radiation and precipitation
                $avgSolar = self::getAverage($params['ALLSKY_SFC_SW_DWN'] ?? []);
                $avgPrecip = self::getAverage($params['PRECTOTCORR'] ?? []);
                $avgTemp = self::getAverage($params['T2M'] ?? []);

                // Simplified NDVI estimation (0-1 scale)
                $ndviEstimate = self::estimateNDVI($avgSolar, $avgPrecip, $avgTemp);

                return [
                    'ndvi_estimate' => $ndviEstimate,
                    'vegetation_health' => self::getVegetationHealth($ndviEstimate),
                    'solar_radiation' => $avgSolar,
                    'precipitation' => $avgPrecip,
                    'temperature' => $avgTemp,
                    'recommendation' => self::getVegetationRecommendation($ndviEstimate, $avgPrecip)
                ];
            }
        }

        return null;
    }

    private static function estimateNDVI($solar, $precip, $temp) {
        // Simplified NDVI estimation based on environmental factors
        $score = 0;

        // Solar radiation factor (higher = better for plants)
        if ($solar > 200) $score += 0.4;
        elseif ($solar > 150) $score += 0.3;
        else $score += 0.2;

        // Precipitation factor
        if ($precip > 3) $score += 0.3;
        elseif ($precip > 1) $score += 0.2;
        else $score += 0.1;

        // Temperature factor (optimal 15-25°C)
        if ($temp >= 15 && $temp <= 25) $score += 0.3;
        elseif ($temp >= 10 && $temp <= 30) $score += 0.2;
        else $score += 0.1;

        return round($score, 2);
    }

    private static function getVegetationHealth($ndvi) {
        if ($ndvi > 0.7) return 'Mükemmel';
        if ($ndvi > 0.5) return 'İyi';
        if ($ndvi > 0.3) return 'Orta';
        if ($ndvi > 0.2) return 'Zayıf';
        return 'Kötü';
    }

    private static function getVegetationRecommendation($ndvi, $precip) {
        if ($ndvi < 0.3) {
            if ($precip < 1) {
                return 'Düşük bitki örtüsü ve yetersiz yağış. Acil sulama ve yeşillendirme gerekli.';
            }
            return 'Düşük bitki örtüsü. Ağaç dikimi ve yeşillendirme önerilir.';
        } elseif ($ndvi < 0.5) {
            return 'Orta seviye bitki örtüsü. Yeşil alanlar artırılabilir.';
        }
        return 'Sağlıklı bitki örtüsü. Mevcut durumu koruyun.';
    }

    // Land Surface Temperature (LST) from NASA POWER
    public static function getLandTemperature($lat, $lon, $date) {
        $dateFormatted = date('Ymd', strtotime($date));

        $url = "https://power.larc.nasa.gov/api/temporal/daily/point?";
        $params = [
            'parameters' => 'T2M,T2M_MAX,T2M_MIN,TS',
            'community' => 'AG',
            'longitude' => $lon,
            'latitude' => $lat,
            'start' => $dateFormatted,
            'end' => $dateFormatted,
            'format' => 'JSON'
        ];

        $url .= http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);

            if (isset($data['properties']['parameter'])) {
                $params = $data['properties']['parameter'];

                return [
                    'air_temp' => $params['T2M'][$dateFormatted] ?? null,
                    'max_temp' => $params['T2M_MAX'][$dateFormatted] ?? null,
                    'min_temp' => $params['T2M_MIN'][$dateFormatted] ?? null,
                    'surface_temp' => $params['TS'][$dateFormatted] ?? null,
                    'heat_island_risk' => self::assessHeatIsland($params['T2M'][$dateFormatted] ?? 0)
                ];
            }
        }

        return null;
    }

    private static function assessHeatIsland($temp) {
        if ($temp > 35) return 'Yüksek - Acil yeşil alan artışı gerekli';
        if ($temp > 30) return 'Orta - Yeşil alan ve gölgelendirme önerilir';
        if ($temp > 25) return 'Düşük - Mevcut durumu koruyun';
        return 'Risk yok';
    }

    // Elevation data (simplified - using external API)
    public static function getElevation($lat, $lon) {
        // Using Open Elevation API (free alternative to SRTM)
        $url = "https://api.open-elevation.com/api/v1/lookup?locations={$lat},{$lon}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);

            if (isset($data['results'][0]['elevation'])) {
                $elevation = $data['results'][0]['elevation'];

                return [
                    'elevation' => $elevation,
                    'flood_risk' => self::assessFloodRisk($elevation)
                ];
            }
        }

        return ['elevation' => null, 'flood_risk' => 'unknown'];
    }

    private static function assessFloodRisk($elevation) {
        if ($elevation < 50) return 'Yüksek - Taşkın riski mevcut';
        if ($elevation < 200) return 'Orta - Yağış sonrası dikkat';
        return 'Düşük';
    }

    // NASA SMAP - Soil Moisture Active Passive
    public static function getSoilMoisture($lat, $lon) {
        // SMAP L3 Radiometer Global Daily 36 km EASE-Grid Soil Moisture
        // Using NASA Earthdata API

        $apiKey = NASA_API_KEY;

        // Calculate date range (last 7 days average)
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-7 days'));

        // NASA POWER API also provides soil moisture estimate
        $url = "https://power.larc.nasa.gov/api/temporal/daily/point";
        $url .= "?start={$startDate}&end={$endDate}";
        $url .= "&latitude={$lat}&longitude={$lon}";
        $url .= "&community=ag";
        $url .= "&parameters=GWETROOT,GWETTOP,PRECTOTCORR";
        $url .= "&format=json";
        $url .= "&user=SCPT";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);

            if (isset($data['properties']['parameter'])) {
                $params = $data['properties']['parameter'];

                // Get average values
                $rootMoisture = isset($params['GWETROOT']) ? array_values($params['GWETROOT']) : [];
                $topMoisture = isset($params['GWETTOP']) ? array_values($params['GWETTOP']) : [];
                $precipitation = isset($params['PRECTOTCORR']) ? array_values($params['PRECTOTCORR']) : [];

                $avgRootMoisture = !empty($rootMoisture) ? array_sum($rootMoisture) / count($rootMoisture) : 0;
                $avgTopMoisture = !empty($topMoisture) ? array_sum($topMoisture) / count($topMoisture) : 0;
                $avgPrecipitation = !empty($precipitation) ? array_sum($precipitation) / count($precipitation) : 0;

                // Soil moisture is in fraction (0-1), convert to percentage
                $rootMoisturePercent = round($avgRootMoisture * 100, 2);
                $topMoisturePercent = round($avgTopMoisture * 100, 2);

                return [
                    'root_zone_moisture' => $rootMoisturePercent, // Kök bölgesi nem (%)
                    'surface_moisture' => $topMoisturePercent, // Yüzey nem (%)
                    'recent_precipitation' => round($avgPrecipitation, 2), // mm/gün
                    'moisture_status' => self::assessSoilMoistureStatus($rootMoisturePercent),
                    'irrigation_need' => self::assessIrrigationNeed($rootMoisturePercent, $avgPrecipitation),
                    'crop_suitability' => self::assessCropSuitability($rootMoisturePercent, $topMoisturePercent)
                ];
            }
        }

        return [
            'root_zone_moisture' => null,
            'surface_moisture' => null,
            'recent_precipitation' => null,
            'moisture_status' => 'Veri alınamadı',
            'irrigation_need' => 'Bilinmiyor',
            'crop_suitability' => 'Değerlendirilemedi'
        ];
    }

    private static function assessSoilMoistureStatus($moisture) {
        if ($moisture > 80) return 'Çok Yüksek - Aşırı sulak';
        if ($moisture > 60) return 'Yüksek - İdeal koşullar';
        if ($moisture > 40) return 'Orta - Sulama gerekebilir';
        if ($moisture > 20) return 'Düşük - Sulama gerekli';
        return 'Çok Düşük - Kuraklık riski';
    }

    private static function assessIrrigationNeed($moisture, $precipitation) {
        if ($precipitation > 5) return 'Yok - Yeterli yağış var';
        if ($moisture > 60) return 'Düşük - Haftalık kontrol';
        if ($moisture > 40) return 'Orta - 2-3 günde bir';
        if ($moisture > 20) return 'Yüksek - Günlük sulama';
        return 'Acil - Hemen sulama gerekli';
    }

    private static function assessCropSuitability($rootMoisture, $surfaceMoisture) {
        $avgMoisture = ($rootMoisture + $surfaceMoisture) / 2;

        if ($avgMoisture > 70) return 'Pirinç, su ürünleri ideal';
        if ($avgMoisture > 50) return 'Sebze, meyve ideal';
        if ($avgMoisture > 30) return 'Tahıl, baklagil uygun';
        if ($avgMoisture > 15) return 'Kuraklığa dayanıklı ürünler';
        return 'Tarım zorlu, sulama sistemi şart';
    }

    // Tree Planting Suitability Analysis
    public static function getTreePlantingSuitability($lat, $lon, $date) {
        $ndvi = self::getNDVI($lat, $lon, date('Y-m-d', strtotime('-30 days', strtotime($date))), $date);
        $temp = self::getLandTemperature($lat, $lon, $date);
        $elevation = self::getElevation($lat, $lon);

        $score = 0;
        $factors = [];

        // NDVI factor (lower is better for new planting)
        if ($ndvi && $ndvi['ndvi_estimate'] < 0.4) {
            $score += 40;
            $factors[] = 'Düşük bitki örtüsü - ağaç dikimi için ideal';
        } elseif ($ndvi && $ndvi['ndvi_estimate'] < 0.6) {
            $score += 20;
            $factors[] = 'Orta bitki örtüsü - ağaç dikimi uygun';
        }

        // Temperature factor
        if ($temp && $temp['air_temp'] >= 15 && $temp['air_temp'] <= 25) {
            $score += 30;
            $factors[] = 'Optimal sıcaklık - fidan gelişimi için uygun';
        } elseif ($temp && $temp['air_temp'] >= 10 && $temp['air_temp'] <= 30) {
            $score += 15;
            $factors[] = 'Kabul edilebilir sıcaklık';
        }

        // Elevation factor
        if ($elevation && $elevation['elevation'] > 100 && $elevation['elevation'] < 2000) {
            $score += 30;
            $factors[] = 'Uygun rakım seviyesi';
        }

        return [
            'suitability_score' => min(100, $score),
            'suitability_level' => self::getSuitabilityLevel($score),
            'factors' => $factors,
            'recommended_species' => self::getRecommendedSpecies($temp, $elevation, $ndvi),
            'best_planting_time' => self::getBestPlantingTime($temp)
        ];
    }

    private static function getSuitabilityLevel($score) {
        if ($score >= 80) return 'Çok Uygun';
        if ($score >= 60) return 'Uygun';
        if ($score >= 40) return 'Orta';
        return 'Az Uygun';
    }

    private static function getRecommendedSpecies($temp, $elevation, $ndvi) {
        $species = [];

        if ($temp && $temp['air_temp']) {
            $avgTemp = $temp['air_temp'];

            if ($avgTemp > 25) {
                $species = ['Zeytin', 'Badem', 'Çam', 'Akasya'];
            } elseif ($avgTemp > 15) {
                $species = ['Meşe', 'Çınar', 'Ihlamur', 'Kavak'];
            } else {
                $species = ['Çam', 'Ladin', 'Göknar', 'Servi'];
            }
        } else {
            $species = ['Meşe', 'Çınar', 'Çam', 'Kavak'];
        }

        return $species;
    }

    private static function getBestPlantingTime($temp) {
        $month = date('n');

        // Spring (March-May) or Autumn (October-November)
        if ($month >= 3 && $month <= 5) {
            return 'İlkbahar - şu an dikim için ideal';
        } elseif ($month >= 10 && $month <= 11) {
            return 'Sonbahar - şu an dikim için ideal';
        } elseif ($month >= 6 && $month <= 9) {
            return 'Yaz - sonbaharı bekleyin';
        } else {
            return 'Kış - ilkbaharı bekleyin';
        }
    }

    // Helper functions
    private static function getAverage($array) {
        if (empty($array)) return 0;
        $values = array_filter($array, function($v) { return $v !== null && $v !== -999; });
        return count($values) > 0 ? array_sum($values) / count($values) : 0;
    }

    private static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return round($earthRadius * $c, 2);
    }
}

// API Endpoints
// Only execute API endpoints if this file is accessed directly (not included)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename($_SERVER['PHP_SELF']) === 'nasa-advanced.php') {
    $action = $_GET['action'] ?? '';

    $lat = floatval($_GET['lat'] ?? 0);
    $lon = floatval($_GET['lon'] ?? 0);
    $date = $_GET['date'] ?? date('Y-m-d');

    $response = [];

    switch ($action) {
        case 'fire':
            $days = intval($_GET['days'] ?? 1);
            $response = NASAAdvancedData::getFireData($lat, $lon, $days);
            break;

        case 'ndvi':
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $response = NASAAdvancedData::getNDVI($lat, $lon, $startDate, $endDate);
            break;

        case 'temperature':
            $response = NASAAdvancedData::getLandTemperature($lat, $lon, $date);
            break;

        case 'elevation':
            $response = NASAAdvancedData::getElevation($lat, $lon);
            break;

        case 'tree-planting':
            $response = NASAAdvancedData::getTreePlantingSuitability($lat, $lon, $date);
            break;

        case 'complete-analysis':
            // Comprehensive analysis
            $response = [
                'location' => ['lat' => $lat, 'lon' => $lon],
                'date' => $date,
                'fire_data' => NASAAdvancedData::getFireData($lat, $lon, 7),
                'vegetation' => NASAAdvancedData::getNDVI($lat, $lon, date('Y-m-d', strtotime('-30 days')), $date),
                'temperature' => NASAAdvancedData::getLandTemperature($lat, $lon, $date),
                'elevation' => NASAAdvancedData::getElevation($lat, $lon),
                'tree_planting' => NASAAdvancedData::getTreePlantingSuitability($lat, $lon, $date)
            ];
            break;

        default:
            $response = ['error' => 'Invalid action. Available: fire, ndvi, temperature, elevation, tree-planting, complete-analysis'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
