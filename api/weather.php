<?php
require_once 'config.php';

class WeatherService {

    // Get weather from OpenWeatherMap API
    public static function getCurrentWeather($lat, $lon) {
        $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid=" . OPENWEATHER_API_KEY . "&units=metric&lang=tr";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            $data = json_decode($response, true);
            return [
                'temperature' => $data['main']['temp'],
                'feels_like' => $data['main']['feels_like'],
                'humidity' => $data['main']['humidity'],
                'pressure' => $data['main']['pressure'],
                'clouds' => $data['clouds']['all'],
                'visibility' => $data['visibility'] / 1000, // km
                'wind_speed' => $data['wind']['speed'],
                'description' => $data['weather'][0]['description'],
                'icon' => $data['weather'][0]['icon']
            ];
        }

        return null;
    }

    // Get forecast from OpenWeatherMap
    public static function getForecast($lat, $lon, $date) {
        $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid=" . OPENWEATHER_API_KEY . "&units=metric&lang=tr";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            $data = json_decode($response, true);
            $target_date = date('Y-m-d', strtotime($date));

            // Find forecast for the target date
            foreach ($data['list'] as $item) {
                $forecast_date = date('Y-m-d', $item['dt']);
                if ($forecast_date == $target_date) {
                    return [
                        'datetime' => date('Y-m-d H:i:s', $item['dt']),
                        'temperature' => $item['main']['temp'],
                        'clouds' => $item['clouds']['all'],
                        'visibility' => isset($item['visibility']) ? $item['visibility'] / 1000 : 10,
                        'humidity' => $item['main']['humidity'],
                        'precipitation_prob' => isset($item['pop']) ? $item['pop'] * 100 : 0,
                        'description' => $item['weather'][0]['description']
                    ];
                }
            }
        }

        return null;
    }

    // Get NASA POWER data for solar radiation
    public static function getNASAPowerData($lat, $lon, $date) {
        $date_formatted = date('Ymd', strtotime($date));
        $url = "https://power.larc.nasa.gov/api/temporal/daily/point?parameters=ALLSKY_SFC_SW_DWN,CLOUD_AMT&community=AG&longitude={$lon}&latitude={$lat}&start={$date_formatted}&end={$date_formatted}&format=JSON";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            $data = json_decode($response, true);
            if (isset($data['properties']['parameter'])) {
                $params = $data['properties']['parameter'];
                return [
                    'solar_radiation' => isset($params['ALLSKY_SFC_SW_DWN'][$date_formatted]) ? $params['ALLSKY_SFC_SW_DWN'][$date_formatted] : null,
                    'cloud_amount' => isset($params['CLOUD_AMT'][$date_formatted]) ? $params['CLOUD_AMT'][$date_formatted] : null
                ];
            }
        }

        return null;
    }

    // Calculate observation quality score
    public static function getObservationQuality($weather_data, $moon_phase = 0) {
        $score = 100;

        // Cloud coverage penalty
        if (isset($weather_data['clouds'])) {
            $score -= ($weather_data['clouds'] * 0.8);
        }

        // Humidity penalty (high humidity = haze)
        if (isset($weather_data['humidity'])) {
            if ($weather_data['humidity'] > 80) {
                $score -= 20;
            } elseif ($weather_data['humidity'] > 60) {
                $score -= 10;
            }
        }

        // Visibility bonus
        if (isset($weather_data['visibility'])) {
            if ($weather_data['visibility'] < 5) {
                $score -= 30;
            } elseif ($weather_data['visibility'] < 8) {
                $score -= 15;
            }
        }

        // Moon phase penalty (bright moon)
        $moon_penalty = $moon_phase * 20;
        $score -= $moon_penalty;

        $score = max(0, min(100, $score));

        return [
            'score' => round($score),
            'quality' => self::getQualityLabel($score),
            'recommendations' => self::getRecommendations($score, $weather_data)
        ];
    }

    private static function getQualityLabel($score) {
        if ($score >= 80) return 'Mükemmel';
        if ($score >= 60) return 'İyi';
        if ($score >= 40) return 'Orta';
        if ($score >= 20) return 'Zayıf';
        return 'Çok Kötü';
    }

    private static function getRecommendations($score, $weather_data) {
        $recommendations = [];

        if ($score >= 70) {
            $recommendations[] = 'Astronomi gözlemi için harika bir gece!';
            $recommendations[] = 'Teleskop kullanımı için ideal koşullar.';
        } elseif ($score >= 40) {
            $recommendations[] = 'Gözlem yapılabilir ancak bazı kısıtlamalar var.';
            if (isset($weather_data['clouds']) && $weather_data['clouds'] > 40) {
                $recommendations[] = 'Bulutlar arasında gözlem yapabilirsiniz.';
            }
        } else {
            $recommendations[] = 'Gözlem için uygun koşullar değil.';
            if (isset($weather_data['clouds']) && $weather_data['clouds'] > 70) {
                $recommendations[] = 'Yüksek bulut örtüsü nedeniyle başka bir gün deneyin.';
            }
        }

        return $recommendations;
    }

    // Agricultural recommendations
    public static function getAgriculturalTips($weather_data, $sun_data, $moon_phase) {
        $tips = [];

        // Irrigation tips
        if (isset($weather_data['clouds']) && $weather_data['clouds'] < 30) {
            if (isset($sun_data['altitude']) && $sun_data['altitude'] > 45) {
                $tips[] = [
                    'type' => 'Sulama',
                    'tip' => 'Güçlü güneş ışığı var. Sabah erken veya akşam geç saatlerde sulama yapın.'
                ];
            }
        }

        if (isset($weather_data['precipitation_prob']) && $weather_data['precipitation_prob'] > 60) {
            $tips[] = [
                'type' => 'Sulama',
                'tip' => 'Yağmur ihtimali yüksek. Sulama gerekli olmayabilir.'
            ];
        }

        // Planting tips based on moon phase
        if ($moon_phase < 0.25) {
            $tips[] = [
                'type' => 'Ekim',
                'tip' => 'Yeni ay döneminde kök bitkileri ekmek için uygun zamandır.'
            ];
        } elseif ($moon_phase > 0.75) {
            $tips[] = [
                'type' => 'Hasat',
                'tip' => 'Dolunay döneminde üst kısmı hasat etmek için iyi bir zamandır.'
            ];
        }

        // Frost warning
        if (isset($weather_data['temperature']) && $weather_data['temperature'] < 5) {
            $tips[] = [
                'type' => 'Uyarı',
                'tip' => 'Düşük sıcaklık! Bitkileri dondan koruyun.'
            ];
        }

        return $tips;
    }
}

// API Endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'current';

    $city_id = $_GET['city_id'] ?? 1;
    $date = $_GET['date'] ?? date('Y-m-d');

    // Get city coordinates
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
    $stmt->execute([$city_id]);
    $city = $stmt->fetch();

    if (!$city) {
        echo json_encode(['error' => 'City not found']);
        exit;
    }

    $response = [];

    switch ($action) {
        case 'current':
            $weather = WeatherService::getCurrentWeather($city['latitude'], $city['longitude']);
            if ($weather) {
                $response = array_merge(['city' => $city], $weather);
            } else {
                $response = ['error' => 'Weather data unavailable'];
            }
            break;

        case 'forecast':
            $forecast = WeatherService::getForecast($city['latitude'], $city['longitude'], $date);
            if ($forecast) {
                $response = array_merge(['city' => $city], $forecast);
            } else {
                $response = ['error' => 'Forecast data unavailable'];
            }
            break;

        case 'nasa':
            $nasa_data = WeatherService::getNASAPowerData($city['latitude'], $city['longitude'], $date);
            if ($nasa_data) {
                $response = array_merge(['city' => $city, 'date' => $date], $nasa_data);
            } else {
                $response = ['error' => 'NASA POWER data unavailable'];
            }
            break;

        case 'observation':
            $weather = WeatherService::getCurrentWeather($city['latitude'], $city['longitude']);
            $moon_phase = $_GET['moon_phase'] ?? 0;
            if ($weather) {
                $quality = WeatherService::getObservationQuality($weather, $moon_phase);
                $response = [
                    'city' => $city,
                    'weather' => $weather,
                    'observation_quality' => $quality
                ];
            } else {
                $response = ['error' => 'Weather data unavailable'];
            }
            break;

        case 'agricultural':
            $weather = WeatherService::getCurrentWeather($city['latitude'], $city['longitude']);
            $sun_data = $_GET['sun_altitude'] ?? 0;
            $moon_phase = $_GET['moon_phase'] ?? 0;
            if ($weather) {
                $tips = WeatherService::getAgriculturalTips(
                    $weather,
                    ['altitude' => $sun_data],
                    $moon_phase
                );
                $response = [
                    'city' => $city,
                    'weather' => $weather,
                    'tips' => $tips
                ];
            } else {
                $response = ['error' => 'Weather data unavailable'];
            }
            break;

        default:
            $response = ['error' => 'Invalid action'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
