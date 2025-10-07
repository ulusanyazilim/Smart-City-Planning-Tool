import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/analysis_provider.dart';
import '../services/pdf_service.dart';

class DecisionScreen extends StatefulWidget {
  final VoidCallback onReset;

  const DecisionScreen({super.key, required this.onReset});

  @override
  State<DecisionScreen> createState() => _DecisionScreenState();
}

class _DecisionScreenState extends State<DecisionScreen> {
  String? _selectedUse;

  final Map<String, String> _useNames = {
    'agriculture': 'Tarım',
    'residential': 'Konut/Yerleşim',
    'green_area': 'Yeşil Alan/Park',
    'solar_energy': 'Güneş Enerjisi',
    'wind_energy': 'Rüzgar Enerjisi',
    'tourism': 'Turizm',
    'geothermal': 'Jeotermal Enerji',
  };

  final Map<String, IconData> _useIcons = {
    'agriculture': Icons.agriculture,
    'residential': Icons.home,
    'green_area': Icons.park,
    'solar_energy': Icons.wb_sunny,
    'wind_energy': Icons.wind_power,
    'tourism': Icons.landscape,
    'geothermal': Icons.hot_tub,
  };

  final Map<String, Color> _useColors = {
    'agriculture': Colors.green,
    'residential': Colors.blue,
    'green_area': Colors.teal,
    'solar_energy': Colors.orange,
    'wind_energy': Colors.lightBlue,
    'tourism': Colors.purple,
    'geothermal': Colors.red,
  };

  @override
  Widget build(BuildContext context) {
    final provider = Provider.of<AnalysisProvider>(context);
    final analysisData = provider.analysisData;

    if (analysisData == null) {
      return const Center(child: Text('Analiz verisi bulunamadı'));
    }

    // Sort uses by probability
    final sortedUses = analysisData.probabilities.entries.toList()
      ..sort((a, b) => (b.value as num).compareTo(a.value as num));

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Card(
              color: Colors.purple.shade50,
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(Icons.analytics, color: Colors.purple.shade700, size: 32),
                        const SizedBox(width: 12),
                        const Expanded(
                          child: Text(
                            'Karar Destek Paneli',
                            style: TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                              color: Colors.purple,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'Alanınızı nasıl kullanmak istediğinizi seçin. Size özel detaylı öneriler sunulacak.',
                      style: TextStyle(fontSize: 14),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),

            // Use type selector
            const Text(
              'Kullanım Türü Seçin:',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),

            ...sortedUses.map((entry) {
              final useKey = entry.key;
              final probability = entry.value;
              final isRecommended = useKey == analysisData.primaryRecommendation;

              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                color: _selectedUse == useKey
                    ? _useColors[useKey]!.withOpacity(0.2)
                    : null,
                child: InkWell(
                  onTap: () {
                    setState(() {
                      _selectedUse = useKey;
                    });
                  },
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Icon(
                          _useIcons[useKey],
                          size: 32,
                          color: _useColors[useKey],
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Text(
                                    _useNames[useKey] ?? useKey,
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  if (isRecommended) ...[
                                    const SizedBox(width: 8),
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 8,
                                        vertical: 2,
                                      ),
                                      decoration: BoxDecoration(
                                        color: Colors.green,
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: const Text(
                                        'ÖNERİLEN',
                                        style: TextStyle(
                                          color: Colors.white,
                                          fontSize: 10,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'Uygunluk: %$probability',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Radio<String>(
                          value: useKey,
                          groupValue: _selectedUse,
                          onChanged: (value) {
                            setState(() {
                              _selectedUse = value;
                            });
                          },
                        ),
                      ],
                    ),
                  ),
                ),
              );
            }),

            if (_selectedUse != null) ...[
              const SizedBox(height: 20),
              _buildDetailedRecommendations(_selectedUse!, analysisData),
            ],

            const SizedBox(height: 20),

            // Action buttons
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: widget.onReset,
                icon: const Icon(Icons.refresh),
                label: const Text('Yeni Analiz'),
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.all(16),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailedRecommendations(String useType, dynamic analysisData) {
    return Card(
      color: _useColors[useType]!.withOpacity(0.1),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(_useIcons[useType], color: _useColors[useType], size: 28),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    '${_useNames[useType]} - Detaylı Öneriler',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: _useColors[useType],
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            ..._getRecommendationContent(useType, analysisData),
          ],
        ),
      ),
    );
  }

  List<Widget> _getRecommendationContent(String useType, dynamic analysisData) {
    final areaSize = analysisData.areaSize;
    final nasaData = analysisData.nasaData ?? {};

    switch (useType) {
      case 'agriculture':
        return _getAgricultureRecommendations(areaSize, nasaData);
      case 'residential':
        return _getResidentialRecommendations(areaSize, nasaData);
      case 'green_area':
        return _getGreenAreaRecommendations(areaSize, nasaData);
      case 'solar_energy':
        return _getSolarRecommendations(areaSize, nasaData);
      case 'wind_energy':
        return _getWindRecommendations(areaSize, nasaData);
      case 'tourism':
        return _getTourismRecommendations(areaSize, nasaData);
      case 'geothermal':
        return _getGeothermalRecommendations(areaSize, nasaData);
      default:
        return [const Text('Detaylı bilgi mevcut değil')];
    }
  }

  List<Widget> _getAgricultureRecommendations(double areaSize, Map<String, dynamic> nasaData) {
    final tempRaw = nasaData['temperature']?['air_temp'];
    final temp = (tempRaw != null && tempRaw > -999) ? tempRaw.toDouble() : 20.0;
    final precipitation = nasaData['ndvi']?['precipitation'] ?? 2.0;
    final soilMoisture = nasaData['soil_moisture'];

    final dekar = areaSize / 1000; // m² to dekar
    final estimatedYield = dekar * 400; // kg (örnek: buğday)

    return [
      _buildInfoRow('Alan', '${areaSize.toStringAsFixed(0)} m² (${dekar.toStringAsFixed(1)} dekar)'),
      _buildInfoRow('Ortalama Sıcaklık', '${temp.toStringAsFixed(1)}°C'),
      _buildInfoRow('Yağış', '${precipitation.toStringAsFixed(1)} mm/gün'),
      if (soilMoisture != null && soilMoisture['root_zone_moisture'] != null) ...[
        const Divider(height: 24),
        const Text('🌍 NASA SMAP Toprak Nemi:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.brown)),
        const SizedBox(height: 8),
        _buildInfoRow('Kök Bölgesi Nem', '${soilMoisture['root_zone_moisture'].toStringAsFixed(1)}%', isBold: true),
        _buildInfoRow('Yüzey Nem', '${soilMoisture['surface_moisture'].toStringAsFixed(1)}%'),
        _buildInfoRow('Nem Durumu', soilMoisture['moisture_status'] ?? 'N/A'),
        _buildInfoRow('Sulama İhtiyacı', soilMoisture['irrigation_need'] ?? 'N/A'),
        _buildInfoRow('Ürün Uygunluğu', soilMoisture['crop_suitability'] ?? 'N/A'),
      ],
      const Divider(height: 24),
      const Text('Önerilen Ürünler:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      if (temp >= 25) ...[
        _buildCropCard('Mısır', '800-1000 kg/dekar', 'Nisan-Eylül', 'Yüksek'),
        _buildCropCard('Pamuk', '400-500 kg/dekar', 'Nisan-Ekim', 'Orta-Yüksek'),
        _buildCropCard('Karpuz', '4000-6000 kg/dekar', 'Mayıs-Ağustos', 'Yüksek'),
      ] else if (temp >= 20) ...[
        _buildCropCard('Buğday', '400-600 kg/dekar', 'Ekim-Temmuz', 'Orta'),
        _buildCropCard('Domates', '5000-7000 kg/dekar', 'Mayıs-Eylül', 'Yüksek'),
        _buildCropCard('Biber', '3000-4000 kg/dekar', 'Mayıs-Ekim', 'Orta-Yüksek'),
      ] else if (temp >= 15) ...[
        _buildCropCard('Patates', '2500-4000 kg/dekar', 'Mart-Eylül', 'Orta-Yüksek'),
        _buildCropCard('Soğan', '3000-5000 kg/dekar', 'Şubat-Ağustos', 'Orta'),
        _buildCropCard('Havuç', '2500-3500 kg/dekar', 'Mart-Ekim', 'Orta'),
      ] else ...[
        _buildCropCard('Arpa', '250-400 kg/dekar', 'Ekim-Haziran', 'Düşük-Orta'),
        _buildCropCard('Lahana', '3000-5000 kg/dekar', 'Temmuz-Kasım', 'Orta'),
      ],
      const Divider(height: 24),
      _buildInfoRow('Tahmini Verim', '~${estimatedYield.toStringAsFixed(0)} kg/yıl'),
      _buildInfoRow('Sulama İhtiyacı', precipitation < 1 ? 'Yüksek - Damla sulama' : 'Orta - Mevsimsel'),
      const SizedBox(height: 12),
      const Text('Öneriler:', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      _buildBulletPoint('Toprak analizi yaptırın (NPK değerleri)'),
      _buildBulletPoint('Modern sulama sistemleri kurun'),
      _buildBulletPoint('Organik gübre kullanın'),
      _buildBulletPoint('Ürün rotasyonu uygulayın'),
      _buildSWOTAnalysis(
        'Tarım Kullanımı',
        ['NASA NDVI verileri mevcut', 'Toprak verimliliği ölçülebilir', 'Düşük başlangıç maliyeti', 'Sürdürülebilir gelir'],
        ['İklim değişikliğine hassas', 'Su kaynağı gereksinimi', 'Uzman işgücü ihtiyacı', 'Hasat döneminde yoğun emek'],
        ['Organik tarım sertifikası', 'Tarım destekleri', 'Teknolojik tarım (akıllı sera)', 'Kooperatif kurma imkanı'],
        ['Kuraklık riski', 'Pazar fiyat dalgalanmaları', 'Zararlı böcekler/hastalıklar', 'Aşırı hava olayları'],
      ),
    ];
  }

  List<Widget> _getResidentialRecommendations(double areaSize, Map<String, dynamic> nasaData) {
    final usableArea = areaSize * 0.7; // 70% usable
    final avgHouseSize = 120.0; // m²
    final maxHouses = (usableArea / avgHouseSize).floor();
    final estimatedPopulation = (maxHouses * 3.5).round(); // 3.5 kişi/hane
    final idealGreenArea = estimatedPopulation * 50; // WHO ideal: 50 m²/kişi
    final minGreenArea = estimatedPopulation * 9; // WHO minimum: 9 m²/kişi
    final treesNeeded = (idealGreenArea / 25).ceil(); // 25 m²/ağaç

    return [
      _buildInfoRow('Alan', '${areaSize.toStringAsFixed(0)} m²'),
      _buildInfoRow('Kullanılabilir Alan', '${usableArea.toStringAsFixed(0)} m² (%70)'),
      const Divider(height: 24),
      const Text('Kapasite Analizi:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      _buildInfoRow('Maksimum Konut', '$maxHouses adet'),
      _buildInfoRow('Tahmini Nüfus', '$estimatedPopulation kişi'),
      _buildInfoRow('Nüfus Yoğunluğu', '${(estimatedPopulation / (areaSize / 10000)).toStringAsFixed(0)} kişi/hektar'),
      const Divider(height: 24),
      const Text('WHO Yeşil Alan Standartları:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      _buildInfoRow('İdeal Standard', '50 m²/kişi (WHO İdeal)', isBold: true),
      _buildInfoRow('Minimum Standard', '9 m²/kişi (WHO Minimum)', isBold: true),
      _buildInfoRow('İdeal için Gerekli Alan', '${idealGreenArea.toStringAsFixed(0)} m²'),
      _buildInfoRow('Minimum için Gerekli Alan', '${minGreenArea.toStringAsFixed(0)} m²'),
      const Divider(height: 24),
      const Text('Ağaçlandırma Planı:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      _buildInfoRow('Dikilmesi Gereken Ağaç', '$treesNeeded adet'),
      _buildInfoRow('Ev Başına Ağaç', '${(treesNeeded / maxHouses).toStringAsFixed(1)} adet'),
      _buildInfoRow('CO₂ Emilimi', '${(treesNeeded * 22 / 1000).toStringAsFixed(1)} ton/yıl'),
      const SizedBox(height: 12),
      const Text('Önerilen Ağaç Türleri:', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      _buildTreeCard('Çınar', '30 kg CO₂/yıl', 'Hızlı büyüme'),
      _buildTreeCard('Meşe', '25 kg CO₂/yıl', 'Uzun ömür'),
      _buildTreeCard('Ihlamur', '22 kg CO₂/yıl', 'Kokulu'),
      const Divider(height: 24),
      const Text('Altyapı İhtiyaçları:', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      _buildBulletPoint('Su: ${(estimatedPopulation * 0.15).toStringAsFixed(0)} m³/gün'),
      _buildBulletPoint('Elektrik: ${(maxHouses * 5).toStringAsFixed(0)} kVA'),
      _buildBulletPoint('Yol: %20 (${(areaSize * 0.20).toStringAsFixed(0)} m²)'),
      _buildBulletPoint('Yeşil alan (ideal): ${idealGreenArea.toStringAsFixed(0)} m²'),
      _buildBulletPoint('Yeşil alan (minimum): ${minGreenArea.toStringAsFixed(0)} m²'),
      _buildSWOTAnalysis(
        'Konut Yerleşimi',
        ['Planlı yerleşim imkanı', 'WHO standartlarına uygunluk', 'Yeşil alan entegrasyonu', 'Modern altyapı kurulumu'],
        ['Yüksek altyapı maliyeti', 'Su/elektrik bağlantısı gerekli', 'İnşaat süreci uzun', 'Çevresel etki yönetimi'],
        ['Kentsel dönüşüm teşvikleri', 'Sosyal konut projeleri', 'Akıllı şehir teknolojileri', 'Artan konut talebi'],
        ['Deprem riski (zemin etüdü)', 'İmar planı değişiklikleri', 'Aşırı nüfus yoğunluğu', 'Altyapı yetersizliği'],
      ),
    ];
  }

  List<Widget> _getGreenAreaRecommendations(double areaSize, Map<String, dynamic> nasaData) {
    final trees = (areaSize / 25).ceil();
    final walkingPaths = areaSize * 0.15;
    final playgrounds = (areaSize / 1000).floor();
    final soilMoisture = nasaData['soil_moisture'];

    return [
      _buildInfoRow('Toplam Alan', '${areaSize.toStringAsFixed(0)} m²'),
      if (soilMoisture != null && soilMoisture['root_zone_moisture'] != null) ...[
        const Divider(height: 24),
        const Text('🌍 NASA SMAP Toprak Nemi (Ağaçlandırma):', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.green)),
        const SizedBox(height: 6),
        _buildInfoRow('Nem Seviyesi', '${soilMoisture['root_zone_moisture'].toStringAsFixed(1)}%'),
        _buildInfoRow('Durum', soilMoisture['moisture_status'] ?? 'N/A'),
      ],
      const Divider(height: 24),
      const Text('Park Tasarımı:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      _buildInfoRow('Toplam Ağaç', '$trees adet'),
      _buildInfoRow('Yürüyüş Yolları', '${walkingPaths.toStringAsFixed(0)} m'),
      _buildInfoRow('Çocuk Parkı', '$playgrounds adet'),
      _buildInfoRow('Spor Alanı', '${(areaSize * 0.10).toStringAsFixed(0)} m²'),
      _buildInfoRow('Piknik Alanları', '${(areaSize / 1000).floor()} adet'),
      const Divider(height: 24),
      const Text('Çevresel Etki:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      _buildInfoRow('CO₂ Emilimi', '${(trees * 22 / 1000).toStringAsFixed(1)} ton/yıl'),
      _buildInfoRow('Oksijen Üretimi', '${(trees * 120).toStringAsFixed(0)} kg/yıl'),
      _buildInfoRow('Sıcaklık Azalması', '2-3°C (şehir ısı adası etkisi)'),
      const Divider(height: 24),
      const Text('Ağaç Dağılımı:', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      _buildBulletPoint('Çınar (gölge): ${(trees * 0.30).floor()} adet'),
      _buildBulletPoint('Meşe (uzun ömür): ${(trees * 0.25).floor()} adet'),
      _buildBulletPoint('Ihlamur (koku): ${(trees * 0.20).floor()} adet'),
      _buildBulletPoint('Çam (her mevsim): ${(trees * 0.15).floor()} adet'),
      _buildBulletPoint('Süs ağaçları: ${(trees * 0.10).floor()} adet'),
      _buildSWOTAnalysis(
        'Yeşil Alan/Park',
        ['Halk sağlığına katkı', 'CO₂ emilimi yüksek', 'Kentsel ısı azaltma', 'Rekreasyon alanı'],
        ['Sürekli bakım gereksinimi', 'Sulama maliyeti', 'Personel ihtiyacı', 'Direkt gelir getirmez'],
        ['Çevre sertifikaları', 'Yerel bitki türleri', 'Doğal sulama sistemleri', 'Gönüllü bakım programları'],
        ['Vandalizm riski', 'Kuraklık dönemleri', 'İmar baskısı', 'Bütçe kısıtlamaları'],
      ),
    ];
  }

  List<Widget> _getSolarRecommendations(double areaSize, Map<String, dynamic> nasaData) {
    final panelArea = areaSize * 0.7;
    final capacity = panelArea * 0.15 / 1000; // MW
    final annualProduction = capacity * 1500; // MWh
    final homesPowered = (annualProduction * 1000 / 3600).floor();

    return [
      _buildInfoRow('Toplam Alan', '${areaSize.toStringAsFixed(0)} m²'),
      _buildInfoRow('Panel Alanı', '${panelArea.toStringAsFixed(0)} m² (%70)'),
      const Divider(height: 24),
      const Text('Enerji Üretimi:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      _buildInfoRow('Kurulu Güç', '${capacity.toStringAsFixed(2)} MW'),
      _buildInfoRow('Yıllık Üretim', '${annualProduction.toStringAsFixed(0)} MWh'),
      _buildInfoRow('Günlük Ortalama', '${(annualProduction * 1000 / 365).toStringAsFixed(0)} kWh'),
      _buildInfoRow('Karşılayabilir Ev', '$homesPowered adet'),
      const Divider(height: 24),
      const Text('Çevresel Etki:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      _buildInfoRow('CO₂ Tasarrufu', '${(annualProduction * 0.5).toStringAsFixed(0)} ton/yıl'),
      _buildInfoRow('Ağaç Eşdeğeri', '${((annualProduction * 0.5 * 1000) / 22).toStringAsFixed(0)} ağaç'),
      const Divider(height: 24),
      const Text('Ekonomik Analiz:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      _buildInfoRow('Yatırım Tahmini', '${(panelArea * 1000).toStringAsFixed(0)} TL'),
      _buildInfoRow('Geri Ödeme Süresi', '6-8 yıl'),
      _buildInfoRow('Yıllık Gelir', '${(annualProduction * 500).toStringAsFixed(0)} TL'),
      const SizedBox(height: 12),
      const Text('Öneriler:', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      _buildBulletPoint('Monokristal paneller kullanın (%18-22 verim)'),
      _buildBulletPoint('Güneye 30-35° açılı yerleştirin'),
      _buildBulletPoint('Düzenli temizlik yapın (verim kaybı önleme)'),
      _buildBulletPoint('İzleme sistemi kurun'),
      _buildSWOTAnalysis(
        'Güneş Enerjisi',
        ['Sınırsız enerji kaynağı', 'Düşük işletme maliyeti', 'NASA POWER verileri mevcut', '20-25 yıl ömür'],
        ['Yüksek başlangıç yatırımı', 'Hava durumuna bağımlılık', 'Gece üretim yok', 'Batarya depolama gerekebilir'],
        ['Devlet teşvikleri (YEKDEM)', 'Şebekeye satış imkanı', 'Karbon kredisi', 'Panel teknolojisi gelişiyor'],
        ['Dolu/fırtına hasarı', 'Panel verimliliği düşebilir', 'Elektrik alım fiyatları', 'Lisans gereklilikleri'],
      ),
    ];
  }

  List<Widget> _getWindRecommendations(double areaSize, Map<String, dynamic> nasaData) {
    final elevation = nasaData['elevation']?['elevation'] ?? 500.0;
    final turbineCount = (areaSize / 50000).floor().clamp(1, 10);
    final capacity = turbineCount * 2.5;

    return [
      _buildInfoRow('Alan', '${areaSize.toStringAsFixed(0)} m²'),
      _buildInfoRow('Rakım', '${elevation.toStringAsFixed(0)} m'),
      const Divider(height: 24),
      const Text('Türbin Kapasitesi:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      _buildInfoRow('Türbin Sayısı', '$turbineCount adet'),
      _buildInfoRow('Kurulu Güç', '${capacity.toStringAsFixed(1)} MW'),
      _buildInfoRow('Türbin Başına Alan', '5 hektar (50.000 m²)'),
      const Divider(height: 24),
      if (elevation < 500) ...[
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.red.shade50,
            border: Border.all(color: Colors.red.shade200),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Row(
            children: [
              Icon(Icons.warning, color: Colors.red),
              SizedBox(width: 12),
              Expanded(
                child: Text(
                  'UYARI: Düşük rakım rüzgar enerjisi için ideal değil. 1 yıllık rüzgar ölçümü ZORUNLU!',
                  style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
      ],
      const Text('Gereksinimler:', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      _buildBulletPoint('1 yıllık rüzgar hızı ölçümü (ZORUNLU)'),
      _buildBulletPoint('Minimum rüzgar hızı: 6 m/s (ekonomik)'),
      _buildBulletPoint('İdeal rüzgar hızı: 8-12 m/s'),
      _buildBulletPoint('Yatırım: ~${(capacity * 1000000).toStringAsFixed(0)} TL'),
      _buildBulletPoint('Çevresel etki değerlendirmesi'),
      const SizedBox(height: 12),
      if (elevation >= 1000)
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.green.shade50,
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Text(
            '✓ Yüksek rakım rüzgar enerjisi için avantaj sağlıyor. Detaylı fizibilite çalışması önerilir.',
            style: TextStyle(color: Colors.green),
          ),
        ),
      _buildSWOTAnalysis(
        'Rüzgar Enerjisi',
        ['Yerli ve yenilenebilir', '24 saat üretim (rüzgar varsa)', 'Yüksek enerji yoğunluğu', 'Uzun ömür (20-25 yıl)'],
        ['Çok yüksek yatırım maliyeti', 'Rüzgar düzensizliği', 'Görsel/gürültü kirliliği', 'Kuş göçü etkisi'],
        ['YEKDEM garantili alım', 'Yüksek rakım avantajı', 'Teknoloji maliyetleri düşüyor', 'Hibrit sistemler (güneş+rüzgar)'],
        ['Fırtına/buz hasarı', 'Bakım maliyeti yüksek', 'Yerel halk direnci olabilir', 'Elektrik alım fiyatları'],
      ),
    ];
  }

  List<Widget> _getTourismRecommendations(double areaSize, Map<String, dynamic> nasaData) {
    return [
      _buildInfoRow('Alan', '${areaSize.toStringAsFixed(0)} m²'),
      const Divider(height: 24),
      const Text('Turizm Potansiyeli:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      _buildBulletPoint('Doğa turizmi tesisleri'),
      _buildBulletPoint('Yürüyüş parkurları'),
      _buildBulletPoint('Kamp alanları'),
      _buildBulletPoint('Mesire yerleri'),
      _buildBulletPoint('Fotoğraf noktaları'),
      const Divider(height: 24),
      const Text('Öneriler:', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      _buildBulletPoint('Doğal yapıyı koruyun'),
      _buildBulletPoint('Sürdürülebilir turizm uygulayın'),
      _buildBulletPoint('Yerel halkı sürece dahil edin'),
      _buildBulletPoint('Altyapıyı minimalde tutun'),
      _buildSWOTAnalysis(
        'Turizm',
        ['Doğal güzellikler', 'Düşük altyapı ihtiyacı', 'Yerel ekonomiye katkı', '4 mevsim potansiyeli'],
        ['Mevsimsel dalgalanmalar', 'Tanıtım gereksinimi', 'Ulaşım altyapısı', 'Hizmet kalitesi standardı'],
        ['Ekoturizm trendi', 'Yerel ürün pazarlama', 'Festival/etkinlik organizasyonu', 'Kültür Bakanlığı destekleri'],
        ['Çevre kirliliği riski', 'Aşırı ziyaretçi yükü', 'İklim değişikliği', 'Rekabet bölgeleri'],
      ),
    ];
  }

  List<Widget> _getGeothermalRecommendations(double areaSize, Map<String, dynamic> nasaData) {
    return [
      _buildInfoRow('Alan', '${areaSize.toStringAsFixed(0)} m²'),
      const Divider(height: 24),
      const Text('Jeotermal Enerji:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.orange.shade50,
          border: Border.all(color: Colors.orange.shade200),
          borderRadius: BorderRadius.circular(8),
        ),
        child: const Row(
          children: [
            Icon(Icons.info, color: Colors.orange),
            SizedBox(width: 12),
            Expanded(
              child: Text(
                'Jeotermal enerji için detaylı jeolojik etüt gereklidir. MTA ile iletişime geçin.',
                style: TextStyle(color: Colors.orange),
              ),
            ),
          ],
        ),
      ),
      const SizedBox(height: 16),
      const Text('Gereksinimler:', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      _buildBulletPoint('Jeolojik etüt (MTA)'),
      _buildBulletPoint('Sondaj çalışmaları'),
      _buildBulletPoint('Sıcaklık ve debi ölçümü'),
      _buildBulletPoint('Kimyasal analiz'),
      _buildBulletPoint('Fizibilite raporu'),
      const Divider(height: 24),
      const Text('Kullanım Alanları:', style: TextStyle(fontWeight: FontWeight.bold)),
      const SizedBox(height: 8),
      _buildBulletPoint('Elektrik üretimi (>150°C)'),
      _buildBulletPoint('Isıtma (40-100°C)'),
      _buildBulletPoint('Sera ısıtması'),
      _buildBulletPoint('Termal turizm'),
      _buildSWOTAnalysis(
        'Jeotermal Enerji',
        ['7/24 kesintisiz üretim', 'Yerli kaynak', 'Düşük işletme maliyeti', 'Çok amaçlı kullanım'],
        ['Çok yüksek arama maliyeti', 'Jeolojik belirsizlik', 'Sondaj riski', 'Uzun fizibilite süreci'],
        ['Türkiye jeotermal potansiyeli', 'MTA destek ve haritalar', 'Kombine kullanım (enerji+tesis)', 'Sera tarımı entegrasyonu'],
        ['Kuyu verimi düşebilir', 'Kimyasal tıkanma', 'Deprem/tektonik hareketler', 'Yüksek başlangıç riski'],
      ),
    ];
  }

  Widget _buildInfoRow(String label, String value, {bool isBold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 140,
            child: Text(
              '$label:',
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                color: Colors.black87,
                fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBulletPoint(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('• ', style: TextStyle(fontSize: 16)),
          Expanded(child: Text(text)),
        ],
      ),
    );
  }

  Widget _buildCropCard(String name, String yield, String season, String water) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
            const SizedBox(height: 4),
            Text('Verim: $yield', style: const TextStyle(fontSize: 13)),
            Text('Sezon: $season', style: const TextStyle(fontSize: 13)),
            Text('Su ihtiyacı: $water', style: const TextStyle(fontSize: 13)),
          ],
        ),
      ),
    );
  }

  Widget _buildSWOTAnalysis(String title, List<String> strengths, List<String> weaknesses, List<String> opportunities, List<String> threats) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Divider(height: 24),
        Text('📊 SWOT Analizi - $title:', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        const SizedBox(height: 12),
        _buildSWOTBox('💪 Güçlü Yönler', Colors.green.shade50, Colors.green.shade700, strengths),
        const SizedBox(height: 8),
        _buildSWOTBox('⚠️ Zayıf Yönler', Colors.orange.shade50, Colors.orange.shade700, weaknesses),
        const SizedBox(height: 8),
        _buildSWOTBox('🎯 Fırsatlar', Colors.blue.shade50, Colors.blue.shade700, opportunities),
        const SizedBox(height: 8),
        _buildSWOTBox('⚡ Tehditler', Colors.red.shade50, Colors.red.shade700, threats),
      ],
    );
  }

  Widget _buildSWOTBox(String title, Color bgColor, Color borderColor, List<String> items) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(8),
        border: Border(left: BorderSide(color: borderColor, width: 4)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: borderColor, fontSize: 14)),
          const SizedBox(height: 6),
          ...items.map((item) => Padding(
            padding: const EdgeInsets.only(bottom: 2),
            child: Text('• $item', style: const TextStyle(fontSize: 13)),
          )),
        ],
      ),
    );
  }

  Widget _buildTreeCard(String name, String co2, String growth) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      color: Colors.green.shade50,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            const Icon(Icons.park, color: Colors.green),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(name, style: const TextStyle(fontWeight: FontWeight.bold)),
                  Text('$co2 • $growth', style: const TextStyle(fontSize: 12)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
