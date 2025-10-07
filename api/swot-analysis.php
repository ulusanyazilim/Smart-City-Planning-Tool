<?php
/**
 * SWOT Analysis Module
 * Generates SWOT analysis based on NASA data and land use recommendations
 */

class SWOTAnalysis {

    /**
     * Generate comprehensive SWOT analysis
     */
    public static function generateSWOT($primaryUse, $scores, $nasaData, $areaSize, $location) {
        $swot = [
            'strengths' => self::calculateStrengths($primaryUse, $scores, $nasaData, $areaSize),
            'weaknesses' => self::calculateWeaknesses($primaryUse, $scores, $nasaData, $areaSize),
            'opportunities' => self::calculateOpportunities($primaryUse, $scores, $nasaData, $location),
            'threats' => self::calculateThreats($primaryUse, $scores, $nasaData, $location)
        ];

        return $swot;
    }

    /**
     * Calculate Strengths (Güçlü Yönler)
     */
    private static function calculateStrengths($primaryUse, $scores, $nasaData, $areaSize) {
        $strengths = [];

        // NASA data availability
        $strengths[] = "NASA uydu verileriyle desteklenen objektif analiz";

        // Area size advantages
        if ($areaSize > 100000) {
            $strengths[] = "Geniş alan (>10 hektar) - Büyük ölçekli proje potansiyeli";
        }

        // NDVI advantages
        if ($nasaData['ndvi']['ndvi_estimate'] > 0.5) {
            $strengths[] = "Yüksek NDVI değeri - Sağlıklı bitki örtüsü mevcut";
        }

        // Temperature advantages
        if ($nasaData['temperature']['air_temp'] >= 15 && $nasaData['temperature']['air_temp'] <= 30) {
            $strengths[] = "İdeal sıcaklık aralığı - Hem tarım hem yerleşim için uygun";
        }

        // Low fire risk
        if ($nasaData['fire_risk']['risk_level'] === 'low') {
            $strengths[] = "Düşük yangın riski - Güvenli bölge";
        }

        // Elevation advantages
        $elevation = $nasaData['elevation']['elevation'];
        if ($elevation > 100 && $elevation < 1500) {
            $strengths[] = "Uygun rakım seviyesi - Sel riski düşük, inşaat uygun";
        }

        // Solar potential
        if (isset($nasaData['ndvi']['solar_radiation']) && $nasaData['ndvi']['solar_radiation'] > 180) {
            $strengths[] = "Yüksek güneş radyasyonu - Güneş enerjisi potansiyeli";
        }

        // Primary use score
        if ($scores[$primaryUse] > 70) {
            $strengths[] = "Yüksek uygunluk skoru (%{$scores[$primaryUse]}) - Güvenilir öneri";
        }

        return $strengths;
    }

    /**
     * Calculate Weaknesses (Zayıf Yönler)
     */
    private static function calculateWeaknesses($primaryUse, $scores, $nasaData, $areaSize) {
        $weaknesses = [];

        // Low NDVI
        if ($nasaData['ndvi']['ndvi_estimate'] < 0.3) {
            $weaknesses[] = "Düşük NDVI - Zayıf bitki örtüsü, ağaçlandırma gerekli";
        }

        // Low precipitation
        if (isset($nasaData['ndvi']['precipitation']) && $nasaData['ndvi']['precipitation'] < 1.5) {
            $weaknesses[] = "Düşük yağış - Sulama altyapısı zorunlu";
        }

        // High/Low temperature extremes
        $temp = $nasaData['temperature']['air_temp'];
        if ($temp > 35) {
            $weaknesses[] = "Yüksek sıcaklık - Enerji tüketimi ve su ihtiyacı artacak";
        } elseif ($temp < 5) {
            $weaknesses[] = "Düşük sıcaklık - Isınma maliyetleri yüksek";
        }

        // Fire risk
        if ($nasaData['fire_risk']['risk_level'] === 'high') {
            $weaknesses[] = "Yüksek yangın riski - Güvenlik önlemleri kritik";
        } elseif ($nasaData['fire_risk']['risk_level'] === 'medium') {
            $weaknesses[] = "Orta yangın riski - İzleme ve önlem sistemi gerekli";
        }

        // Elevation issues
        if ($nasaData['elevation']['elevation'] < 50) {
            $weaknesses[] = "Çok düşük rakım - Taşkın riski mevcut";
        } elseif ($nasaData['elevation']['elevation'] > 2000) {
            $weaknesses[] = "Yüksek rakım - İnşaat ve tarım maliyetleri artacak";
        }

        // Earthquake risk
        if (isset($nasaData['elevation']['earthquake_zone']) && $nasaData['elevation']['earthquake_zone'] <= 2) {
            $weaknesses[] = "Yüksek deprem riski - Yapı güvenliği kritik";
        }

        // Small area
        if ($areaSize < 5000) {
            $weaknesses[] = "Küçük alan (<0.5 hektar) - Sınırlı kullanım esnekliği";
        }

        // Low score for primary use
        if ($scores[$primaryUse] < 50) {
            $weaknesses[] = "Orta-düşük uygunluk skoru - Ek önlemler gerektirebilir";
        }

        // Data resolution limits
        $weaknesses[] = "NASA veri çözünürlüğü limitleri - Mikro ölçek analiz sınırlı";

        return $weaknesses;
    }

    /**
     * Calculate Opportunities (Fırsatlar)
     */
    private static function calculateOpportunities($primaryUse, $scores, $nasaData, $location) {
        $opportunities = [];

        // Solar energy potential
        if (isset($nasaData['ndvi']['solar_radiation']) && $nasaData['ndvi']['solar_radiation'] > 180) {
            $opportunities[] = "Güneş enerjisi entegrasyonu ile enerji maliyeti sıfırlanabilir";
            $opportunities[] = "Çatı üstü GES kurulumu - Elektrik üretimi + satış";
        }

        // Green area creation
        if ($nasaData['ndvi']['ndvi_estimate'] < 0.4) {
            $opportunities[] = "Yeşil dönüşüm projesi - WHO standartlarına uyum fırsatı";
            $opportunities[] = "Karbon kredisi programları - Ağaçlandırma desteği";
        }

        // Tourism potential (if applicable)
        $elevation = $nasaData['elevation']['elevation'];
        if ($elevation > 1500) {
            $opportunities[] = "Yüksek rakım - Doğa turizmi ve yayla turizmi potansiyeli";
        }

        // Agriculture diversity
        if ($scores['agriculture'] > 40) {
            $opportunities[] = "Organik tarım sertifikasyonu - Katma değer artışı";
            $opportunities[] = "Tarımsal işletme kurulumu - İstihdam yaratma";
        }

        // Residential development
        if ($scores['residential'] > 40) {
            $opportunities[] = "Akıllı şehir teknolojileri - Sürdürülebilir yerleşim";
            $opportunities[] = "Yeşil bina sertifikasyonu (LEED/BREEAM)";
        }

        // Regional location advantages
        $opportunities[] = "İmar planı güncellemeleri ile değer artışı potansiyeli";
        $opportunities[] = "Kamu-özel işbirliği (PPP) modelleri - Yatırım çekme";

        // Technology integration
        $opportunities[] = "IoT sensör ağı kurulumu - Gerçek zamanlı izleme";
        $opportunities[] = "Dijital ikiz (Digital Twin) oluşturma - Simülasyon";

        return $opportunities;
    }

    /**
     * Calculate Threats (Tehditler)
     */
    private static function calculateThreats($primaryUse, $scores, $nasaData, $location) {
        $threats = [];

        // Climate change
        $threats[] = "İklim değişikliği - Sıcaklık ve yağış modellerinde sapma riski";

        // Fire risk
        if ($nasaData['fire_risk']['risk_level'] !== 'low') {
            $threats[] = "Yangın tehdidi - Orman yangını riski mevcut";
        }

        // Earthquake
        if (isset($nasaData['elevation']['earthquake_zone']) && $nasaData['elevation']['earthquake_zone'] <= 3) {
            $threats[] = "Deprem riski - Yapısal hasar potansiyeli";
        }

        // Flood risk
        if ($nasaData['elevation']['flood_risk'] !== 'Düşük') {
            $threats[] = "Taşkın riski - Altyapı ve bina hasarı tehlikesi";
        }

        // Extreme weather
        if ($nasaData['temperature']['air_temp'] > 30 || $nasaData['temperature']['air_temp'] < 10) {
            $threats[] = "Ekstrem hava koşulları - Enerji ve su maliyeti artışı";
        }

        // Regulatory changes
        $threats[] = "İmar planı değişiklikleri - Kullanım kısıtlamaları gelebilir";

        // Unplanned urbanization
        $threats[] = "Plansız kentleşme baskısı - Çevre tahribatı riski";

        // Water scarcity
        if (isset($nasaData['ndvi']['precipitation']) && $nasaData['ndvi']['precipitation'] < 2) {
            $threats[] = "Su kıtlığı - Kuraklık dönemlerinde su temini zorlaşabilir";
        }

        // Economic factors
        $threats[] = "Ekonomik dalgalanmalar - Yatırım maliyetlerinde artış";

        // Data uncertainty
        $threats[] = "Veri belirsizliği - Yerel mikro iklim sapmaları olabilir";

        return $threats;
    }

    /**
     * Generate SWOT matrix HTML for report
     */
    public static function generateSWOTMatrix($swot) {
        $html = "<div class='swot-matrix' style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0;'>";

        // Strengths (Top Left)
        $html .= "<div class='swot-box strengths' style='background: #e8f5e9; padding: 20px; border-radius: 8px; border-left: 4px solid #4CAF50;'>";
        $html .= "<h4 style='color: #2e7d32; margin: 0 0 15px 0;'>💪 GÜÇLÜ YÖNLER (Strengths)</h4>";
        $html .= "<ul style='margin: 0; padding-left: 20px;'>";
        foreach ($swot['strengths'] as $item) {
            $html .= "<li style='margin-bottom: 8px;'>{$item}</li>";
        }
        $html .= "</ul></div>";

        // Weaknesses (Top Right)
        $html .= "<div class='swot-box weaknesses' style='background: #ffebee; padding: 20px; border-radius: 8px; border-left: 4px solid #f44336;'>";
        $html .= "<h4 style='color: #c62828; margin: 0 0 15px 0;'>⚠️ ZAYIF YÖNLER (Weaknesses)</h4>";
        $html .= "<ul style='margin: 0; padding-left: 20px;'>";
        foreach ($swot['weaknesses'] as $item) {
            $html .= "<li style='margin-bottom: 8px;'>{$item}</li>";
        }
        $html .= "</ul></div>";

        // Opportunities (Bottom Left)
        $html .= "<div class='swot-box opportunities' style='background: #e3f2fd; padding: 20px; border-radius: 8px; border-left: 4px solid #2196F3;'>";
        $html .= "<h4 style='color: #1565c0; margin: 0 0 15px 0;'>🚀 FIRSATLAR (Opportunities)</h4>";
        $html .= "<ul style='margin: 0; padding-left: 20px;'>";
        foreach ($swot['opportunities'] as $item) {
            $html .= "<li style='margin-bottom: 8px;'>{$item}</li>";
        }
        $html .= "</ul></div>";

        // Threats (Bottom Right)
        $html .= "<div class='swot-box threats' style='background: #fff3e0; padding: 20px; border-radius: 8px; border-left: 4px solid #ff9800;'>";
        $html .= "<h4 style='color: #e65100; margin: 0 0 15px 0;'>⚡ TEHDİTLER (Threats)</h4>";
        $html .= "<ul style='margin: 0; padding-left: 20px;'>";
        foreach ($swot['threats'] as $item) {
            $html .= "<li style='margin-bottom: 8px;'>{$item}</li>";
        }
        $html .= "</ul></div>";

        $html .= "</div>";

        return $html;
    }
}
?>
