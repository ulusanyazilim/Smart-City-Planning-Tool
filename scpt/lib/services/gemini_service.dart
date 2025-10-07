import 'dart:convert';
import 'package:http/http.dart' as http;

class GeminiService {
  // TODO: Add your Gemini API key here or use environment variables
  static const String apiKey = 'YOUR_GEMINI_API_KEY_HERE';
  static const String apiUrl =
      'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

  /// Generate AI report using Gemini
  static Future<Map<String, dynamic>> generateReport({
    required double lat,
    required double lon,
    required double areaSize,
    required Map<String, dynamic> scores,
    required Map<String, dynamic> probabilities,
    required Map<String, dynamic> nasaData,
    required String primaryUse,
  }) async {
    try {
      final context = _prepareContext(
        lat: lat,
        lon: lon,
        areaSize: areaSize,
        scores: scores,
        probabilities: probabilities,
        nasaData: nasaData,
        primaryUse: primaryUse,
      );

      final response = await http.post(
        Uri.parse(apiUrl),
        headers: {
          'Content-Type': 'application/json',
          'x-goog-api-key': apiKey,
        },
        body: jsonEncode({
          'contents': [
            {
              'parts': [
                {'text': context}
              ]
            }
          ],
          'generationConfig': {
            'temperature': 0.7,
            'topK': 40,
            'topP': 0.95,
            'maxOutputTokens': 2048,
          }
        }),
      );

      if (response.statusCode != 200) {
        return {
          'success': false,
          'error': 'API HTTP ${response.statusCode} hatası',
          'fallback_report': _generateFallbackReport(
            lat: lat,
            lon: lon,
            areaSize: areaSize,
            probabilities: probabilities,
            primaryUse: primaryUse,
          ),
        };
      }

      final result = jsonDecode(response.body);

      if (result['candidates'] != null &&
          result['candidates'][0]['content'] != null &&
          result['candidates'][0]['content']['parts'] != null &&
          result['candidates'][0]['content']['parts'][0]['text'] != null) {
        return {
          'success': true,
          'ai_report': result['candidates'][0]['content']['parts'][0]['text'],
          'metadata': {
            'model': 'gemini-2.0-flash-exp',
            'timestamp': DateTime.now().toIso8601String(),
            'location': {'lat': lat, 'lon': lon},
            'primary_use': primaryUse,
          }
        };
      }

      return {
        'success': false,
        'error': 'Gemini yanıt formatı hatalı',
        'fallback_report': _generateFallbackReport(
          lat: lat,
          lon: lon,
          areaSize: areaSize,
          probabilities: probabilities,
          primaryUse: primaryUse,
        ),
      };
    } catch (e) {
      return {
        'success': false,
        'error': e.toString(),
        'fallback_report': _generateFallbackReport(
          lat: lat,
          lon: lon,
          areaSize: areaSize,
          probabilities: probabilities,
          primaryUse: primaryUse,
        ),
      };
    }
  }

  /// Prepare context for Gemini
  static String _prepareContext({
    required double lat,
    required double lon,
    required double areaSize,
    required Map<String, dynamic> scores,
    required Map<String, dynamic> probabilities,
    required Map<String, dynamic> nasaData,
    required String primaryUse,
  }) {
    const useNames = {
      'agriculture': 'Tarım',
      'residential': 'Konut/Yerleşim',
      'green_area': 'Yeşil Alan/Park',
      'solar_energy': 'Güneş Enerjisi',
      'wind_energy': 'Rüzgar Enerjisi',
      'tourism': 'Turizm',
      'geothermal': 'Jeotermal Enerji',
    };

    final ndvi = nasaData['ndvi']?['ndvi_estimate'] ?? 'N/A';
    final tempRaw = nasaData['temperature']?['air_temp'];
    final temp = (tempRaw != null && tempRaw > -999) ? tempRaw : 'N/A';
    final elevation = nasaData['elevation']?['elevation'] ?? 'N/A';
    final fireRisk = nasaData['fire_risk']?['risk_level'] ?? 'N/A';

    final probList = probabilities.entries
        .map((e) => '- ${useNames[e.key] ?? e.key}: %${e.value}')
        .join('\n');

    return '''Sen bir uzman şehir planlama danışmanısın. Aşağıdaki NASA uydu verileri ve analiz sonuçlarına göre detaylı bir şehir planlama raporu hazırla.

📍 KONUM BİLGİLERİ:
- Enlem/Boylam: $lat, $lon
- Alan Büyüklüğü: ${areaSize.toStringAsFixed(0)} m²

🛰️ NASA UYDU VERİLERİ:
- NDVI (Bitki Örtüsü): $ndvi
- Hava Sıcaklığı: $temp°C
- Yükseklik: ${elevation}m
- Yangın Riski: $fireRisk

📊 KULLANIM UYGUNLUK ORANLARI:
$probList

🎯 BİRİNCİL ÖNERİ: ${useNames[primaryUse] ?? primaryUse}

Lütfen aşağıdaki başlıklar altında detaylı bir rapor hazırla:

1. YÖNETİCİ ÖZETİ (2-3 paragraf)
2. ALAN ANALİZİ (NASA verileri yorumu)
3. ÖNERİLEN KULLANIM PLANI (Birincil ve alternatif senaryolar)
4. RİSK DEĞERLENDİRMESİ (Olası tehditler ve önlemler)
5. UYGULAMA ÖNERİLERİ (Somut adımlar)
6. SÜRDÜRÜLEBİLİRLİK (Çevre ve sosyal etki)

Raporu Türkçe, profesyonel ve belediye yöneticileri için anlaşılır şekilde yaz. Emojileri uygun şekilde kullan.''';
  }

  /// Generate fallback report
  static String _generateFallbackReport({
    required double lat,
    required double lon,
    required double areaSize,
    required Map<String, dynamic> probabilities,
    required String primaryUse,
  }) {
    const useNames = {
      'agriculture': 'Tarım',
      'residential': 'Konut/Yerleşim',
      'green_area': 'Yeşil Alan/Park',
      'solar_energy': 'Güneş Enerjisi',
      'wind_energy': 'Rüzgar Enerjisi',
      'tourism': 'Turizm',
      'geothermal': 'Jeotermal Enerji',
    };

    final probList = probabilities.entries
        .map((e) => '- ${useNames[e.key] ?? e.key}: %${e.value}')
        .join('\n');

    return '''# 📊 ŞEHİR PLANLAMA RAPORU (Yedek)

**Konum:** $lat, $lon
**Alan:** ${areaSize.toStringAsFixed(0)} m²

## 🎯 Birincil Öneri

Bu alan **${useNames[primaryUse] ?? primaryUse}** kullanımı için uygundur.

## 📈 Uygunluk Oranları

$probList

*Not: Gemini AI servisi şu anda kullanılamıyor. Standart rapor gösterilmektedir.*''';
  }
}
