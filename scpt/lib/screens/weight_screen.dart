import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/analysis_provider.dart';
import '../services/api_service.dart';
import '../models/analysis_data.dart';

class WeightScreen extends StatefulWidget {
  final VoidCallback onNext;
  final VoidCallback onBack;

  const WeightScreen({super.key, required this.onNext, required this.onBack});

  @override
  State<WeightScreen> createState() => _WeightScreenState();
}

class _WeightScreenState extends State<WeightScreen> {
  // Default weights (not used in current API but shown for future enhancement)
  final Map<String, double> _weights = {
    'NDVI (Bitki Örtüsü)': 1.0,
    'Sıcaklık': 1.0,
    'Yükseklik': 1.0,
    'Yangın Riski': 1.0,
    'Yağış': 1.0,
    'Güneş Radyasyonu': 1.0,
    'Deprem': 1.0,
    'CO2 Emisyonu': 1.0,
  };

  bool _isAnalyzing = false;

  Future<void> _startAnalysis() async {
    final provider = Provider.of<AnalysisProvider>(context, listen: false);

    if (provider.selectedLat == null || provider.selectedLon == null) {
      _showError('Konum seçilmedi');
      return;
    }

    setState(() {
      _isAnalyzing = true;
    });

    provider.setLoading(true);
    provider.setError(null);

    try {
      final result = await ApiService.analyzeArea(
        lat: provider.selectedLat!,
        lon: provider.selectedLon!,
        areaSize: provider.areaSize ?? 10000,
      );

      if (result['success'] == true && result['data'] != null) {
        // Safe type conversion - ensure it's a Map
        final data = result['data'];
        Map<String, dynamic> jsonData;

        if (data is Map<String, dynamic>) {
          jsonData = data;
        } else if (data is Map) {
          jsonData = Map<String, dynamic>.from(data);
        } else {
          throw Exception('Invalid data format: expected Map, got ${data.runtimeType}');
        }

        final analysisData = AnalysisData.fromJson(jsonData);
        provider.setAnalysisData(analysisData);
        provider.setLoading(false);

        if (mounted) {
          widget.onNext();
        }
      } else {
        provider.setLoading(false);
        provider.setError(result['error'] ?? 'Analiz başarısız oldu');
        _showError(result['error'] ?? 'Analiz başarısız oldu');
      }
    } catch (e) {
      provider.setLoading(false);
      provider.setError(e.toString());
      _showError('Hata: ${e.toString()}');
    } finally {
      if (mounted) {
        setState(() {
          _isAnalyzing = false;
        });
      }
    }
  }

  void _showError(String message) {
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        duration: const Duration(seconds: 5),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = Provider.of<AnalysisProvider>(context);

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Info card
          Card(
            color: Colors.blue.shade50,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.tune, color: Colors.blue),
                      SizedBox(width: 8),
                      Text(
                        'Ağırlık Ayarları',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'NASA verilerinin analiz üzerindeki etkisini ayarlayın.',
                    style: TextStyle(color: Colors.black87),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Seçili Konum: ${provider.selectedLat?.toStringAsFixed(6)}, ${provider.selectedLon?.toStringAsFixed(6)}',
                    style: const TextStyle(fontSize: 12, color: Colors.black54),
                  ),
                  if (provider.areaSize != null)
                    Text(
                      'Alan: ${provider.areaSize!.toStringAsFixed(0)} m²',
                      style: const TextStyle(fontSize: 12, color: Colors.black54),
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Weight sliders
          Expanded(
            child: Card(
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: _weights.entries.map((entry) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              entry.key,
                              style: const TextStyle(
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            Text(
                              '×${entry.value.toStringAsFixed(1)}',
                              style: TextStyle(
                                color: Colors.blue.shade700,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                        Slider(
                          value: entry.value,
                          min: 0.0,
                          max: 2.0,
                          divisions: 20,
                          label: entry.value.toStringAsFixed(1),
                          onChanged: (value) {
                            setState(() {
                              _weights[entry.key] = value;
                            });
                          },
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Info note
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.blue.shade50,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.blue.shade200),
            ),
            child: Row(
              children: [
                Icon(Icons.info_outline, color: Colors.blue.shade700, size: 20),
                const SizedBox(width: 8),
                const Expanded(
                  child: Text(
                    'ℹ️ Bilgi: Ağırlıkları bulunduğunuz bölgenin özelliklerine göre ayarlayabilirsiniz.',
                    style: TextStyle(fontSize: 12, color: Colors.black87),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Action buttons
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: _isAnalyzing ? null : widget.onBack,
                  icon: const Icon(Icons.arrow_back),
                  label: const Text('Geri'),
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.all(16),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: ElevatedButton.icon(
                  onPressed: _isAnalyzing ? null : _startAnalysis,
                  icon: _isAnalyzing
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.analytics),
                  label: Text(_isAnalyzing ? 'Analiz Ediliyor...' : 'Analizi Başlat'),
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.all(16),
                    backgroundColor: Colors.green,
                    foregroundColor: Colors.white,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
