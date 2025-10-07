<?php
require_once 'config.php';

class AstronomyCalculator {

    // Calculate Julian Date
    public static function getJulianDate($timestamp = null) {
        if ($timestamp === null) $timestamp = time();
        $unix = $timestamp / 86400.0 + 2440587.5;
        return $unix;
    }

    // Calculate Local Sidereal Time
    public static function getLocalSiderealTime($longitude, $timestamp = null) {
        $jd = self::getJulianDate($timestamp);
        $t = ($jd - 2451545.0) / 36525.0;

        // Greenwich Mean Sidereal Time
        $gmst = 280.46061837 + 360.98564736629 * ($jd - 2451545.0) +
                0.000387933 * $t * $t - ($t * $t * $t / 38710000.0);

        // Local Sidereal Time
        $lst = fmod($gmst + $longitude, 360.0);
        if ($lst < 0) $lst += 360.0;

        return $lst;
    }

    // Convert RA/Dec to Alt/Az
    public static function equatorialToHorizontal($ra, $dec, $lat, $lon, $timestamp = null) {
        $lst = self::getLocalSiderealTime($lon, $timestamp);
        $ha = $lst - $ra; // Hour Angle

        $lat_rad = deg2rad($lat);
        $ha_rad = deg2rad($ha);
        $dec_rad = deg2rad($dec);

        // Calculate Altitude
        $sin_alt = sin($dec_rad) * sin($lat_rad) +
                   cos($dec_rad) * cos($lat_rad) * cos($ha_rad);
        $alt = rad2deg(asin($sin_alt));

        // Calculate Azimuth
        $cos_az = (sin($dec_rad) - sin($lat_rad) * $sin_alt) /
                  (cos($lat_rad) * cos(deg2rad($alt)));
        $az = rad2deg(acos(max(-1, min(1, $cos_az))));

        if (sin($ha_rad) > 0) {
            $az = 360 - $az;
        }

        return [
            'altitude' => $alt,
            'azimuth' => $az,
            'visible' => $alt > 0
        ];
    }

    // Calculate Sun Position
    public static function getSunPosition($lat, $lon, $timestamp = null) {
        if ($timestamp === null) $timestamp = time();
        $jd = self::getJulianDate($timestamp);
        $n = $jd - 2451545.0;

        // Mean longitude
        $L = fmod(280.460 + 0.9856474 * $n, 360.0);

        // Mean anomaly
        $g = fmod(357.528 + 0.9856003 * $n, 360.0);
        $g_rad = deg2rad($g);

        // Ecliptic longitude
        $lambda = $L + 1.915 * sin($g_rad) + 0.020 * sin(2 * $g_rad);
        $lambda_rad = deg2rad($lambda);

        // Ecliptic latitude (0 for sun)
        $beta = 0;

        // Obliquity of ecliptic
        $epsilon = 23.439 - 0.0000004 * $n;
        $epsilon_rad = deg2rad($epsilon);

        // Right Ascension
        $ra = rad2deg(atan2(cos($epsilon_rad) * sin($lambda_rad), cos($lambda_rad)));
        if ($ra < 0) $ra += 360;

        // Declination
        $dec = rad2deg(asin(sin($epsilon_rad) * sin($lambda_rad)));

        $horizontal = self::equatorialToHorizontal($ra, $dec, $lat, $lon, $timestamp);

        return array_merge($horizontal, [
            'ra' => $ra,
            'dec' => $dec,
            'distance_au' => 1.0
        ]);
    }

    // Calculate Moon Position (simplified)
    public static function getMoonPosition($lat, $lon, $timestamp = null) {
        if ($timestamp === null) $timestamp = time();
        $jd = self::getJulianDate($timestamp);
        $t = ($jd - 2451545.0) / 36525.0;

        // Moon's mean longitude
        $L0 = fmod(218.316 + 13.176396 * ($jd - 2451545.0), 360.0);

        // Moon's mean anomaly
        $M = fmod(134.963 + 13.064993 * ($jd - 2451545.0), 360.0);
        $M_rad = deg2rad($M);

        // Moon's longitude
        $lambda = $L0 + 6.289 * sin($M_rad);
        $lambda_rad = deg2rad($lambda);

        // Moon's latitude
        $beta = 5.128 * sin(deg2rad(93.272 + 13.229350 * ($jd - 2451545.0)));
        $beta_rad = deg2rad($beta);

        // Obliquity
        $epsilon = 23.439 - 0.0000004 * ($jd - 2451545.0);
        $epsilon_rad = deg2rad($epsilon);

        // Convert to equatorial coordinates
        $ra = rad2deg(atan2(
            sin($lambda_rad) * cos($epsilon_rad) - tan($beta_rad) * sin($epsilon_rad),
            cos($lambda_rad)
        ));
        if ($ra < 0) $ra += 360;

        $dec = rad2deg(asin(
            sin($beta_rad) * cos($epsilon_rad) +
            cos($beta_rad) * sin($epsilon_rad) * sin($lambda_rad)
        ));

        // Moon phase
        $sun_lambda = fmod(280.460 + 0.9856474 * ($jd - 2451545.0), 360.0);
        $phase_angle = $lambda - $sun_lambda;
        $illumination = (1 + cos(deg2rad($phase_angle))) / 2;

        $horizontal = self::equatorialToHorizontal($ra, $dec, $lat, $lon, $timestamp);

        return array_merge($horizontal, [
            'ra' => $ra,
            'dec' => $dec,
            'phase' => $illumination,
            'phase_name' => self::getMoonPhaseName($illumination)
        ]);
    }

    // Get Moon Phase Name
    public static function getMoonPhaseName($illumination) {
        if ($illumination < 0.1) return 'Yeni Ay';
        if ($illumination < 0.4) return 'Hilal';
        if ($illumination < 0.6) return 'İlk Dördün';
        if ($illumination < 0.9) return 'Şişkin Ay';
        if ($illumination <= 1.0) return 'Dolunay';
        return 'Bilinmiyor';
    }

    // Calculate Planet Positions (simplified for 5 visible planets)
    public static function getPlanetPosition($planet, $lat, $lon, $timestamp = null) {
        if ($timestamp === null) $timestamp = time();
        $jd = self::getJulianDate($timestamp);
        $t = ($jd - 2451545.0) / 36525.0;

        $elements = self::getPlanetOrbitalElements($planet, $t);
        if (!$elements) return null;

        // Calculate heliocentric position (simplified)
        $M = fmod($elements['M0'] + $elements['M1'] * $t, 360.0);
        $M_rad = deg2rad($M);

        // Eccentric anomaly (simplified)
        $E = $M + $elements['e'] * sin($M_rad) * (1.0 + $elements['e'] * cos($M_rad));
        $E_rad = deg2rad($E);

        // True anomaly
        $v = rad2deg(2 * atan(sqrt((1 + $elements['e']) / (1 - $elements['e'])) * tan($E_rad / 2)));

        // Distance to sun
        $r = $elements['a'] * (1 - $elements['e'] * cos($E_rad));

        // Heliocentric longitude
        $lambda = fmod($v + $elements['w'], 360.0);

        // Simplified ecliptic coordinates
        $lambda_rad = deg2rad($lambda);
        $i_rad = deg2rad($elements['i']);

        // Convert to RA/Dec (very simplified)
        $epsilon = 23.439;
        $epsilon_rad = deg2rad($epsilon);

        $ra = rad2deg(atan2(sin($lambda_rad) * cos($epsilon_rad), cos($lambda_rad)));
        if ($ra < 0) $ra += 360;

        $dec = rad2deg(asin(sin($epsilon_rad) * sin($lambda_rad)));

        $horizontal = self::equatorialToHorizontal($ra, $dec, $lat, $lon, $timestamp);

        return array_merge($horizontal, [
            'ra' => $ra,
            'dec' => $dec,
            'distance_au' => $r
        ]);
    }

    // Orbital elements for planets
    private static function getPlanetOrbitalElements($planet, $t) {
        $elements = [
            'Mercury' => ['a' => 0.387098, 'e' => 0.205635, 'i' => 7.005, 'w' => 29.125, 'M0' => 174.795, 'M1' => 4.092339],
            'Venus' => ['a' => 0.723332, 'e' => 0.006772, 'i' => 3.395, 'w' => 54.884, 'M0' => 50.416, 'M1' => 1.602136],
            'Mars' => ['a' => 1.523688, 'e' => 0.093405, 'i' => 1.850, 'w' => 286.502, 'M0' => 19.373, 'M1' => 0.524071],
            'Jupiter' => ['a' => 5.202887, 'e' => 0.048498, 'i' => 1.303, 'w' => 273.867, 'M0' => 20.020, 'M1' => 0.083056],
            'Saturn' => ['a' => 9.536676, 'e' => 0.055546, 'i' => 2.485, 'w' => 339.392, 'M0' => 317.020, 'M1' => 0.033371]
        ];

        return $elements[$planet] ?? null;
    }

    // Get visible stars from database
    public static function getVisibleStars($lat, $lon, $timestamp = null) {
        $db = getDB();
        $stmt = $db->query("
            SELECT s.*, c.name_tr as constellation_name
            FROM stars s
            LEFT JOIN constellations c ON s.constellation_id = c.id
            WHERE s.magnitude < 3.0
            ORDER BY s.magnitude ASC
        ");

        $stars = [];
        while ($row = $stmt->fetch()) {
            $pos = self::equatorialToHorizontal(
                $row['right_ascension'],
                $row['declination'],
                $lat,
                $lon,
                $timestamp
            );

            if ($pos['visible']) {
                $stars[] = array_merge($row, $pos);
            }
        }

        return $stars;
    }

    // Get constellations
    public static function getConstellations() {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM constellations ORDER BY name_tr");
        return $stmt->fetchAll();
    }
}

// API Endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'sky';

    $city_id = $_GET['city_id'] ?? null;
    $date = $_GET['date'] ?? date('Y-m-d');
    $time = $_GET['time'] ?? date('H:i:s');

    // Get coordinates (from city or direct lat/lon)
    if (isset($_GET['lat']) && isset($_GET['lon'])) {
        // Direct coordinates
        $city = [
            'id' => 0,
            'name' => 'Özel Konum',
            'latitude' => floatval($_GET['lat']),
            'longitude' => floatval($_GET['lon']),
            'elevation' => 0
        ];
    } elseif ($city_id) {
        // Get city coordinates
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
        $stmt->execute([$city_id]);
        $city = $stmt->fetch();

        if (!$city) {
            echo json_encode(['error' => 'City not found']);
            exit;
        }
    } else {
        echo json_encode(['error' => 'Location required (city_id or lat/lon)']);
        exit;
    }

    $timestamp = strtotime("$date $time");

    $response = [];

    switch ($action) {
        case 'sky':
            $response = [
                'city' => $city,
                'datetime' => date('Y-m-d H:i:s', $timestamp),
                'sun' => AstronomyCalculator::getSunPosition($city['latitude'], $city['longitude'], $timestamp),
                'moon' => AstronomyCalculator::getMoonPosition($city['latitude'], $city['longitude'], $timestamp),
                'planets' => [
                    'Mercury' => AstronomyCalculator::getPlanetPosition('Mercury', $city['latitude'], $city['longitude'], $timestamp),
                    'Venus' => AstronomyCalculator::getPlanetPosition('Venus', $city['latitude'], $city['longitude'], $timestamp),
                    'Mars' => AstronomyCalculator::getPlanetPosition('Mars', $city['latitude'], $city['longitude'], $timestamp),
                    'Jupiter' => AstronomyCalculator::getPlanetPosition('Jupiter', $city['latitude'], $city['longitude'], $timestamp),
                    'Saturn' => AstronomyCalculator::getPlanetPosition('Saturn', $city['latitude'], $city['longitude'], $timestamp),
                ],
                'stars' => AstronomyCalculator::getVisibleStars($city['latitude'], $city['longitude'], $timestamp)
            ];
            break;

        case 'constellations':
            $response = AstronomyCalculator::getConstellations();
            break;

        case 'sun':
            $response = AstronomyCalculator::getSunPosition($city['latitude'], $city['longitude'], $timestamp);
            break;

        case 'moon':
            $response = AstronomyCalculator::getMoonPosition($city['latitude'], $city['longitude'], $timestamp);
            break;

        default:
            $response = ['error' => 'Invalid action'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
