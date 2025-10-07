<?php
// Start output buffering to catch any stray output
ob_start();

error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';
require_once 'nasa-advanced.php';

// Clear any output that happened before
ob_clean();

header('Content-Type: application/json; charset=utf-8');

/**
 * Gemini AI Report Generator
 * Uses Google Gemini API to generate intelligent urban planning reports
 */

class GeminiReportGenerator {

    const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

    /**
     * Generate AI-powered urban planning report
     */
    public static function generateReport($lat, $lon, $areaSize, $scores, $probabilities, $nasaData, $primaryUse) {
        // Prepare context for Gemini
        $context = self::prepareContext($lat, $lon, $areaSize, $scores, $probabilities, $nasaData, $primaryUse);

        // Call Gemini API
        $geminiResponse = self::callGeminiAPI($context);

        if (!$geminiResponse || isset($geminiResponse['error'])) {
            return [
                'success' => false,
                'error' => $geminiResponse['error'] ?? 'Gemini API çağrısı başarısız oldu',
                'fallback_report' => self::generateFallbackReport($lat, $lon, $areaSize, $scores, $probabilities, $primaryUse)
            ];
        }

        return [
            'success' => true,
            'ai_report' => $geminiResponse['text'] ?? '',
            'metadata' => [
                'model' => 'gemini-pro',
                'timestamp' => date('Y-m-d H:i:s'),
                'location' => ['lat' => $lat, 'lon' => $lon],
                'primary_use' => $primaryUse
            ]
        ];
    }

    /**
     * Prepare context for Gemini AI
     */
    private static function prepareContext($lat, $lon, $areaSize, $scores, $probabilities, $nasaData, $primaryUse) {
        $useNames = [
            'agriculture' => 'Tarım',
            'residential' => 'Konut/Yerleşim',
            'green_area' => 'Yeşil Alan/Park',
            'solar_energy' => 'Güneş Enerjisi',
            'wind_energy' => 'Rüzgar Enerjisi',
            'tourism' => 'Turizm',
            'geothermal' => 'Jeotermal Enerji'
        ];

        $ndvi = $nasaData['ndvi']['ndvi_estimate'] ?? 'N/A';
        $temp = $nasaData['temperature']['air_temp'] ?? 'N/A';
        $elevation = $nasaData['elevation']['elevation'] ?? 'N/A';
        $fireRisk = $nasaData['fire_risk']['risk_level'] ?? 'N/A';

        $probList = [];
        foreach ($probabilities as $use => $prob) {
            $probList[] = "- " . $useNames[$use] . ": %" . $prob;
        }
        $probText = implode("\n", $probList);

        $prompt = "Sen bir uzman şehir planlama danışmanısın. Aşağıdaki NASA uydu verileri ve analiz sonuçlarına göre detaylı bir şehir planlama raporu hazırla.

📍 KONUM BİLGİLERİ:
- Enlem/Boylam: {$lat}, {$lon}
- Alan Büyüklüğü: " . number_format($areaSize, 0, ',', '.') . " m²

🛰️ NASA UYDU VERİLERİ:
- NDVI (Bitki Örtüsü): {$ndvi}
- Hava Sıcaklığı: {$temp}°C
- Yükseklik: {$elevation}m
- Yangın Riski: {$fireRisk}

📊 KULLANIM UYGUNLUK ORANLARI:
{$probText}

🎯 BİRİNCİL ÖNERİ: {$useNames[$primaryUse]}

Lütfen aşağıdaki başlıklar altında detaylı bir rapor hazırla:

1. YÖNETİCİ ÖZETİ (2-3 paragraf)
2. ALAN ANALİZİ (NASA verileri yorumu)
3. ÖNERİLEN KULLANIM PLANI (Birincil ve alternatif senaryolar)
4. RİSK DEĞERLENDİRMESİ (Olası tehditler ve önlemler)
5. UYGULAMA ÖNERİLERİ (Somut adımlar)
6. SÜRDÜRÜLEBİLİRLİK (Çevre ve sosyal etki)

Raporu Türkçe, profesyonel ve belediye yöneticileri için anlaşılır şekilde yaz. Emojileri uygun şekilde kullan.";

        return $prompt;
    }

    /**
     * Call Gemini API
     */
    private static function callGeminiAPI($prompt) {
        $url = self::GEMINI_API_URL;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . GEMINI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['error' => 'API HTTP ' . $httpCode . ' hatası'];
        }

        $result = json_decode($response, true);

        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return ['text' => $result['candidates'][0]['content']['parts'][0]['text']];
        }

        return ['error' => 'Gemini yanıt formatı hatalı'];
    }

    /**
     * Generate fallback report if Gemini fails
     */
    private static function generateFallbackReport($lat, $lon, $areaSize, $scores, $probabilities, $primaryUse) {
        $useNames = [
            'agriculture' => 'Tarım',
            'residential' => 'Konut/Yerleşim',
            'green_area' => 'Yeşil Alan/Park',
            'solar_energy' => 'Güneş Enerjisi',
            'wind_energy' => 'Rüzgar Enerjisi',
            'tourism' => 'Turizm',
            'geothermal' => 'Jeotermal Enerji'
        ];

        $report = "# 📊 ŞEHİR PLANLAMA RAPORU (Yedek)\n\n";
        $report .= "**Konum:** {$lat}, {$lon}\n";
        $report .= "**Alan:** " . number_format($areaSize, 0, ',', '.') . " m²\n\n";
        $report .= "## 🎯 Birincil Öneri\n\n";
        $report .= "Bu alan **{$useNames[$primaryUse]}** kullanımı için uygundur.\n\n";
        $report .= "## 📈 Uygunluk Oranları\n\n";

        foreach ($probabilities as $use => $prob) {
            $report .= "- {$useNames[$use]}: %{$prob}\n";
        }

        $report .= "\n*Not: Gemini AI servisi şu anda kullanılamıyor. Standart rapor gösterilmektedir.*";

        return $report;
    }
}

// API Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $lat = floatval($input['lat'] ?? 0);
    $lon = floatval($input['lon'] ?? 0);
    $areaSize = floatval($input['area_size'] ?? 10000);
    $scores = $input['scores'] ?? [];
    $probabilities = $input['probabilities'] ?? [];
    $nasaData = $input['nasa_data'] ?? [];
    $primaryUse = $input['primary_use'] ?? 'agriculture';

    if ($lat === 0.0 || $lon === 0.0) {
        echo json_encode([
            'success' => false,
            'error' => 'Geçersiz konum bilgisi'
        ]);
        exit;
    }

    $result = GeminiReportGenerator::generateReport($lat, $lon, $areaSize, $scores, $probabilities, $nasaData, $primaryUse);

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Invalid request
echo json_encode([
    'success' => false,
    'error' => 'Geçersiz istek metodu'
]);
