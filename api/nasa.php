<?php
require_once 'config.php';

class NASAService {

    // Get Astronomy Picture of the Day
    public static function getAPOD($date = null) {
        $url = "https://api.nasa.gov/planetary/apod?api_key=" . NASA_API_KEY;
        if ($date) {
            $url .= "&date=" . date('Y-m-d', strtotime($date));
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            return json_decode($response, true);
        }

        return null;
    }

    // Get Near Earth Objects
    public static function getNEO($start_date, $end_date = null) {
        if (!$end_date) $end_date = $start_date;

        $url = "https://api.nasa.gov/neo/rest/v1/feed?start_date={$start_date}&end_date={$end_date}&api_key=" . NASA_API_KEY;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            $data = json_decode($response, true);
            return $data;
        }

        return null;
    }

    // Get Mars Rover Photos
    public static function getMarsPhotos($date = null, $rover = 'curiosity') {
        $earth_date = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        $url = "https://api.nasa.gov/mars-photos/api/v1/rovers/{$rover}/photos?earth_date={$earth_date}&api_key=" . NASA_API_KEY;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            return json_decode($response, true);
        }

        return null;
    }

    // Get EPIC Earth images
    public static function getEPIC($date = null) {
        $date_str = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        $url = "https://api.nasa.gov/EPIC/api/natural/date/{$date_str}?api_key=" . NASA_API_KEY;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            return json_decode($response, true);
        }

        return null;
    }

    // Get satellite imagery from NASA Worldview (GIBS)
    public static function getWorldviewImagery($lat, $lon, $date) {
        // NASA GIBS requires specific layer and date format
        $date_formatted = date('Y-m-d', strtotime($date));

        // Return metadata for client-side rendering
        return [
            'service' => 'NASA GIBS/Worldview',
            'date' => $date_formatted,
            'location' => ['lat' => $lat, 'lon' => $lon],
            'layers' => [
                [
                    'name' => 'MODIS_Terra_CorrectedReflectance_TrueColor',
                    'url' => "https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/MODIS_Terra_CorrectedReflectance_TrueColor/default/{$date_formatted}/250m/{z}/{y}/{x}.jpg",
                    'description' => 'MODIS True Color görüntüsü'
                ],
                [
                    'name' => 'VIIRS_SNPP_CorrectedReflectance_TrueColor',
                    'url' => "https://gibs.earthdata.nasa.gov/wmts/epsg4326/best/VIIRS_SNPP_CorrectedReflectance_TrueColor/default/{$date_formatted}/250m/{z}/{y}/{x}.jpg",
                    'description' => 'VIIRS True Color görüntüsü'
                ]
            ],
            'worldview_url' => "https://worldview.earthdata.nasa.gov/?v=" . ($lon - 5) . "," . ($lat - 5) . "," . ($lon + 5) . "," . ($lat + 5) . "&t={$date_formatted}"
        ];
    }

    // Solar System Bodies positions from JPL Horizons (simplified)
    public static function getHorizonsData($body, $date, $lat, $lon) {
        // This is a simplified version. Full JPL Horizons API requires more complex queries
        // For production, consider using the full Horizons API or SPICE toolkit

        return [
            'body' => $body,
            'date' => $date,
            'note' => 'JPL Horizons tam entegrasyonu için SPICE toolkit kullanılabilir',
            'horizons_url' => "https://ssd.jpl.nasa.gov/horizons.cgi#top"
        ];
    }

    // Get space events and astronomical phenomena
    public static function getSpaceEvents($date) {
        // Combine multiple NASA APIs for comprehensive space events
        $events = [];

        // Check for Near Earth Objects
        $neo = self::getNEO($date);
        if ($neo && isset($neo['near_earth_objects'][$date])) {
            foreach ($neo['near_earth_objects'][$date] as $object) {
                $events[] = [
                    'type' => 'NEO',
                    'name' => $object['name'],
                    'description' => 'Yakın Dünya Nesnesi geçişi',
                    'date' => $date,
                    'distance_km' => $object['close_approach_data'][0]['miss_distance']['kilometers'],
                    'potentially_hazardous' => $object['is_potentially_hazardous_asteroid']
                ];
            }
        }

        // Get APOD
        $apod = self::getAPOD($date);
        if ($apod) {
            $events[] = [
                'type' => 'APOD',
                'title' => $apod['title'],
                'description' => isset($apod['explanation']) ? substr($apod['explanation'], 0, 200) . '...' : '',
                'image_url' => $apod['url'] ?? null,
                'date' => $date
            ];
        }

        return $events;
    }
}

// API Endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'apod';
    $date = $_GET['date'] ?? date('Y-m-d');

    $response = [];

    switch ($action) {
        case 'apod':
            $apod = NASAService::getAPOD($date);
            if ($apod) {
                $response = $apod;
            } else {
                $response = ['error' => 'APOD data unavailable'];
            }
            break;

        case 'neo':
            $neo = NASAService::getNEO($date);
            if ($neo) {
                $response = $neo;
            } else {
                $response = ['error' => 'NEO data unavailable'];
            }
            break;

        case 'mars':
            $rover = $_GET['rover'] ?? 'curiosity';
            $photos = NASAService::getMarsPhotos($date, $rover);
            if ($photos) {
                $response = $photos;
            } else {
                $response = ['error' => 'Mars photos unavailable'];
            }
            break;

        case 'epic':
            $epic = NASAService::getEPIC($date);
            if ($epic) {
                $response = $epic;
            } else {
                $response = ['error' => 'EPIC data unavailable'];
            }
            break;

        case 'worldview':
            $city_id = $_GET['city_id'] ?? 1;
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
            $stmt->execute([$city_id]);
            $city = $stmt->fetch();

            if ($city) {
                $response = NASAService::getWorldviewImagery($city['latitude'], $city['longitude'], $date);
            } else {
                $response = ['error' => 'City not found'];
            }
            break;

        case 'events':
            $events = NASAService::getSpaceEvents($date);
            $response = [
                'date' => $date,
                'events' => $events,
                'count' => count($events)
            ];
            break;

        case 'horizons':
            $body = $_GET['body'] ?? 'Moon';
            $city_id = $_GET['city_id'] ?? 1;
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
            $stmt->execute([$city_id]);
            $city = $stmt->fetch();

            if ($city) {
                $response = NASAService::getHorizonsData($body, $date, $city['latitude'], $city['longitude']);
            } else {
                $response = ['error' => 'City not found'];
            }
            break;

        default:
            $response = ['error' => 'Invalid action'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
