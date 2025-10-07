import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:flutter_markdown/flutter_markdown.dart';
import '../providers/analysis_provider.dart';
import '../services/gemini_service.dart';
import '../services/pdf_service.dart';
import 'package:flutter/services.dart';

class ResultsScreen extends StatefulWidget {
  final VoidCallback onReset;
  final VoidCallback onDecisionPanel;

  const ResultsScreen({super.key, required this.onReset, required this.onDecisionPanel});

  @override
  State<ResultsScreen> createState() => _ResultsScreenState();
}

class _ResultsScreenState extends State<ResultsScreen> {
  bool _isGeneratingAI = false;

  final Map<String, String> _useNames = {
    'agriculture': 'Tarım',
    'residential': 'Konut/Yerleşim',
    'green_area': 'Yeşil Alan/Park',
    'solar_energy': 'Güneş Enerjisi',
    'wind_energy': 'Rüzgar Enerjisi',
    'tourism': 'Turizm',
    'geothermal': 'Jeotermal Enerji',
  };

  Future<void> _generateAIReport() async {
    final provider = Provider.of<AnalysisProvider>(context, listen: false);
    final analysisData = provider.analysisData;

    if (analysisData == null) return;

    setState(() {
      _isGeneratingAI = true;
    });

    try {
      final result = await GeminiService.generateReport(
        lat: analysisData.location['latitude'] ?? 0.0,
        lon: analysisData.location['longitude'] ?? 0.0,
        areaSize: analysisData.areaSize,
        scores: analysisData.scores,
        probabilities: analysisData.probabilities,
        nasaData: analysisData.nasaData ?? {},
        primaryUse: analysisData.primaryRecommendation,
      );

      if (mounted) {
        if (result['success'] == true && result['ai_report'] != null) {
          _showAIReportDialog(result['ai_report']);
        } else if (result['fallback_report'] != null) {
          _showAIReportDialog(result['fallback_report']);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('AI raporu oluşturulamadı: ${result['error'] ?? 'Bilinmeyen hata'}'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Hata: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isGeneratingAI = false;
        });
      }
    }
  }

  void _showAIReportDialog(String report) {
    showDialog(
      context: context,
      builder: (context) => Dialog(
        child: Container(
          constraints: const BoxConstraints(maxWidth: 700),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Header
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Colors.blue.shade700, Colors.blue.shade500],
                  ),
                  borderRadius: const BorderRadius.only(
                    topLeft: Radius.circular(4),
                    topRight: Radius.circular(4),
                  ),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.smart_toy, color: Colors.white, size: 28),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Text(
                        '🤖 Gemini AI Şehir Planlama Raporu',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close, color: Colors.white),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
              ),
              // Content with Markdown rendering
              Flexible(
                child: Container(
                  color: Colors.grey.shade50,
                  child: Markdown(
                    data: report,
                    padding: const EdgeInsets.all(20),
                    styleSheet: MarkdownStyleSheet(
                      h1: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: Colors.blue.shade700,
                        height: 1.4,
                      ),
                      h2: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Colors.grey.shade800,
                        height: 1.4,
                      ),
                      h3: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w600,
                        color: Colors.grey.shade700,
                        height: 1.4,
                      ),
                      p: const TextStyle(
                        fontSize: 15,
                        height: 1.8,
                        color: Colors.black87,
                      ),
                      listBullet: TextStyle(
                        fontSize: 15,
                        color: Colors.blue.shade700,
                      ),
                      strong: const TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Colors.black,
                      ),
                      em: TextStyle(
                        fontStyle: FontStyle.italic,
                        color: Colors.grey.shade700,
                      ),
                      blockquote: TextStyle(
                        backgroundColor: Colors.blue.shade50,
                        color: Colors.black87,
                      ),
                      code: TextStyle(
                        backgroundColor: Colors.grey.shade200,
                        fontFamily: 'monospace',
                      ),
                    ),
                    selectable: true,
                  ),
                ),
              ),
              // Actions
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.grey.shade300,
                      blurRadius: 4,
                      offset: const Offset(0, -2),
                    ),
                  ],
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    OutlinedButton.icon(
                      onPressed: () {
                        Clipboard.setData(ClipboardData(text: report));
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('📋 Rapor panoya kopyalandı'),
                            duration: Duration(seconds: 2),
                          ),
                        );
                      },
                      icon: const Icon(Icons.copy),
                      label: const Text('Kopyala'),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                      ),
                    ),
                    const SizedBox(width: 12),
                    ElevatedButton.icon(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(Icons.close),
                      label: const Text('Kapat'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.blue.shade700,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = Provider.of<AnalysisProvider>(context);
    final analysisData = provider.analysisData;

    if (analysisData == null) {
      return const Center(
        child: Text('Analiz verisi bulunamadı'),
      );
    }

    // Sort probabilities
    final sortedProbs = analysisData.probabilities.entries.toList()
      ..sort((a, b) => (b.value as num).compareTo(a.value as num));

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Card(
            color: Colors.green.shade50,
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.check_circle, color: Colors.green, size: 32),
                      SizedBox(width: 12),
                      Text(
                        'Analiz Tamamlandı',
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: Colors.green,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Birincil Öneri: ${_useNames[analysisData.primaryRecommendation] ?? analysisData.primaryRecommendation}',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w500),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Güven Skoru: ${analysisData.recommendationConfidence.toStringAsFixed(1)}',
                    style: TextStyle(fontSize: 14, color: Colors.grey.shade700),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Konum: ${analysisData.location['latitude']?.toStringAsFixed(6)}, ${analysisData.location['longitude']?.toStringAsFixed(6)}',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  ),
                  Text(
                    'Alan: ${analysisData.areaSize.toStringAsFixed(0)} m²',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Chart
          Card(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Kullanım Uygunluk Oranları',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    height: 300,
                    child: BarChart(
                      BarChartData(
                        alignment: BarChartAlignment.spaceAround,
                        maxY: 100,
                        barTouchData: BarTouchData(
                          touchTooltipData: BarTouchTooltipData(
                            getTooltipItem: (group, groupIndex, rod, rodIndex) {
                              return BarTooltipItem(
                                '%${rod.toY.toStringAsFixed(1)}',
                                const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                              );
                            },
                          ),
                        ),
                        titlesData: FlTitlesData(
                          show: true,
                          bottomTitles: AxisTitles(
                            sideTitles: SideTitles(
                              showTitles: true,
                              getTitlesWidget: (value, meta) {
                                if (value.toInt() >= 0 && value.toInt() < sortedProbs.length) {
                                  final entry = sortedProbs[value.toInt()];
                                  return Padding(
                                    padding: const EdgeInsets.only(top: 8),
                                    child: Text(
                                      _useNames[entry.key] ?? entry.key,
                                      style: const TextStyle(fontSize: 10),
                                      textAlign: TextAlign.center,
                                    ),
                                  );
                                }
                                return const Text('');
                              },
                              reservedSize: 50,
                            ),
                          ),
                          leftTitles: AxisTitles(
                            sideTitles: SideTitles(
                              showTitles: true,
                              getTitlesWidget: (value, meta) {
                                return Text('%${value.toInt()}', style: const TextStyle(fontSize: 12));
                              },
                              reservedSize: 40,
                            ),
                          ),
                          topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                          rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                        ),
                        gridData: FlGridData(
                          show: true,
                          drawVerticalLine: false,
                          horizontalInterval: 20,
                        ),
                        borderData: FlBorderData(show: false),
                        barGroups: sortedProbs.asMap().entries.map((entry) {
                          final isPrimary = entry.value.key == analysisData.primaryRecommendation;
                          return BarChartGroupData(
                            x: entry.key,
                            barRods: [
                              BarChartRodData(
                                toY: (entry.value.value as num).toDouble(),
                                color: isPrimary ? Colors.blue : Colors.green,
                                width: 30,
                                borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
                              ),
                            ],
                          );
                        }).toList(),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),

          // SWOT Analysis
          if (analysisData.swotAnalysis != null) ...[
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'SWOT Analizi',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 16),
                    _buildSWOTGrid(analysisData.swotAnalysis!),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),
          ],

          // Action buttons
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () async {
                    try {
                      await PDFService.generateAndPrintReport(analysisData);
                    } catch (e) {
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text('PDF oluşturma hatası: ${e.toString()}')),
                        );
                      }
                    }
                  },
                  icon: const Icon(Icons.download),
                  label: const Text('Final Rapor (PDF)'),
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.all(16),
                    backgroundColor: Colors.blue,
                    foregroundColor: Colors.white,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: _isGeneratingAI ? null : _generateAIReport,
                  icon: _isGeneratingAI
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Icon(Icons.smart_toy),
                  label: Text(_isGeneratingAI ? 'Oluşturuluyor...' : 'AI Raporu (Gemini)'),
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.all(16),
                    backgroundColor: Colors.green,
                    foregroundColor: Colors.white,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: widget.onDecisionPanel,
              icon: const Icon(Icons.analytics),
              label: const Text('Detaylı Karar Destek Paneli'),
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.all(16),
                backgroundColor: Colors.purple,
                foregroundColor: Colors.white,
              ),
            ),
          ),
          const SizedBox(height: 12),
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

  Widget _buildSWOTGrid(Map<String, dynamic> swot) {
    return Column(
      children: [
        _buildSWOTBox('Güçlü Yönler', swot['strengths'] ?? [], Colors.green),
        const SizedBox(height: 12),
        _buildSWOTBox('Zayıf Yönler', swot['weaknesses'] ?? [], Colors.red),
        const SizedBox(height: 12),
        _buildSWOTBox('Fırsatlar', swot['opportunities'] ?? [], Colors.blue),
        const SizedBox(height: 12),
        _buildSWOTBox('Tehditler', swot['threats'] ?? [], Colors.orange),
      ],
    );
  }

  Widget _buildSWOTBox(String title, List<dynamic> items, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              color: color,
              fontSize: 16,
            ),
          ),
          const SizedBox(height: 8),
          ...items.map((item) => Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('• ', style: TextStyle(color: color)),
                    Expanded(
                      child: Text(
                        item.toString(),
                        style: const TextStyle(fontSize: 13),
                      ),
                    ),
                  ],
                ),
              )),
        ],
      ),
    );
  }
}
