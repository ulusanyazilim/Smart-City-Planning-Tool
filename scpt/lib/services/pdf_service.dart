import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';
import '../models/analysis_data.dart';

class PDFService {
  static Future<void> generateAndPrintReport(AnalysisData analysisData) async {
    final pdf = pw.Document();

    final Map<String, String> useNames = {
      'agriculture': 'Tarım',
      'residential': 'Konut/Yerleşim',
      'green_area': 'Yeşil Alan/Park',
      'solar_energy': 'Güneş Enerjisi',
      'wind_energy': 'Rüzgar Enerjisi',
      'tourism': 'Turizm',
      'geothermal': 'Jeotermal Enerji',
    };

    // Sort probabilities
    final sortedProbs = analysisData.probabilities.entries.toList()
      ..sort((a, b) => (b.value as num).compareTo(a.value as num));

    pdf.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.all(32),
        build: (context) => [
          // Header
          pw.Header(
            level: 0,
            child: pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                pw.Text(
                  'SCPT - Akıllı Şehir Planlama Raporu',
                  style: pw.TextStyle(
                    fontSize: 24,
                    fontWeight: pw.FontWeight.bold,
                    color: PdfColors.blue900,
                  ),
                ),
                pw.SizedBox(height: 8),
                pw.Text(
                  'NASA Space Apps Challenge 2025',
                  style: const pw.TextStyle(
                    fontSize: 12,
                    color: PdfColors.grey700,
                  ),
                ),
                pw.Divider(thickness: 2),
              ],
            ),
          ),

          pw.SizedBox(height: 20),

          // Location Info
          pw.Container(
            padding: const pw.EdgeInsets.all(16),
            decoration: pw.BoxDecoration(
              color: PdfColors.green50,
              border: pw.Border.all(color: PdfColors.green, width: 2),
              borderRadius: const pw.BorderRadius.all(pw.Radius.circular(8)),
            ),
            child: pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                pw.Text(
                  'Analiz Tamamlandı',
                  style: pw.TextStyle(
                    fontSize: 18,
                    fontWeight: pw.FontWeight.bold,
                    color: PdfColors.green900,
                  ),
                ),
                pw.SizedBox(height: 12),
                pw.Text(
                  'Birincil Öneri: ${useNames[analysisData.primaryRecommendation] ?? analysisData.primaryRecommendation}',
                  style: pw.TextStyle(fontSize: 14, fontWeight: pw.FontWeight.bold),
                ),
                pw.SizedBox(height: 4),
                pw.Text(
                  'Güven Skoru: ${analysisData.recommendationConfidence.toStringAsFixed(1)}',
                  style: const pw.TextStyle(fontSize: 12),
                ),
                pw.SizedBox(height: 4),
                pw.Text(
                  'Konum: ${analysisData.location['latitude']?.toStringAsFixed(6)}, ${analysisData.location['longitude']?.toStringAsFixed(6)}',
                  style: const pw.TextStyle(fontSize: 10, color: PdfColors.grey700),
                ),
                pw.Text(
                  'Alan: ${analysisData.areaSize.toStringAsFixed(0)} m²',
                  style: const pw.TextStyle(fontSize: 10, color: PdfColors.grey700),
                ),
              ],
            ),
          ),

          pw.SizedBox(height: 20),

          // Probabilities Table
          pw.Text(
            'KULLANIM UYGUNLUK ORANLARI',
            style: pw.TextStyle(fontSize: 16, fontWeight: pw.FontWeight.bold),
          ),
          pw.SizedBox(height: 12),
          pw.Table(
            border: pw.TableBorder.all(color: PdfColors.grey400),
            children: [
              // Header
              pw.TableRow(
                decoration: const pw.BoxDecoration(color: PdfColors.blue100),
                children: [
                  pw.Padding(
                    padding: const pw.EdgeInsets.all(8),
                    child: pw.Text(
                      'Kullanım Türü',
                      style: pw.TextStyle(fontWeight: pw.FontWeight.bold),
                    ),
                  ),
                  pw.Padding(
                    padding: const pw.EdgeInsets.all(8),
                    child: pw.Text(
                      'Uygunluk Oranı',
                      style: pw.TextStyle(fontWeight: pw.FontWeight.bold),
                      textAlign: pw.TextAlign.right,
                    ),
                  ),
                ],
              ),
              // Data rows
              ...sortedProbs.map((entry) {
                final isPrimary = entry.key == analysisData.primaryRecommendation;
                return pw.TableRow(
                  decoration: isPrimary
                      ? const pw.BoxDecoration(color: PdfColors.blue50)
                      : null,
                  children: [
                    pw.Padding(
                      padding: const pw.EdgeInsets.all(8),
                      child: pw.Text(
                        useNames[entry.key] ?? entry.key,
                        style: isPrimary ? pw.TextStyle(fontWeight: pw.FontWeight.bold) : null,
                      ),
                    ),
                    pw.Padding(
                      padding: const pw.EdgeInsets.all(8),
                      child: pw.Text(
                        '%${entry.value}',
                        textAlign: pw.TextAlign.right,
                        style: isPrimary ? pw.TextStyle(fontWeight: pw.FontWeight.bold) : null,
                      ),
                    ),
                  ],
                );
              }),
            ],
          ),

          pw.SizedBox(height: 20),

          // SWOT Analysis
          if (analysisData.swotAnalysis != null) ...[
            pw.Text(
              'SWOT ANALİZİ',
              style: pw.TextStyle(fontSize: 16, fontWeight: pw.FontWeight.bold),
            ),
            pw.SizedBox(height: 12),
            _buildSWOTBox('Güçlü Yönler', analysisData.swotAnalysis!['strengths'], PdfColors.green),
            pw.SizedBox(height: 12),
            _buildSWOTBox('Zayıf Yönler', analysisData.swotAnalysis!['weaknesses'], PdfColors.red),
            pw.SizedBox(height: 12),
            _buildSWOTBox('Fırsatlar', analysisData.swotAnalysis!['opportunities'], PdfColors.blue),
            pw.SizedBox(height: 12),
            _buildSWOTBox('Tehditler', analysisData.swotAnalysis!['threats'], PdfColors.orange),
          ],

          pw.SizedBox(height: 20),

          // Footer
          pw.Container(
            padding: const pw.EdgeInsets.all(12),
            decoration: const pw.BoxDecoration(
              border: pw.Border(top: pw.BorderSide(color: PdfColors.grey400)),
            ),
            child: pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                pw.Text(
                  'NASA Veri Kaynakları',
                  style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold),
                ),
                pw.SizedBox(height: 4),
                pw.Text(
                  'FIRMS, MODIS, NDVI, NASA POWER, OpenElevation',
                  style: const pw.TextStyle(fontSize: 9, color: PdfColors.grey700),
                ),
                pw.SizedBox(height: 8),
                pw.Text(
                  'Rapor Tarihi: ${DateTime.now().toString().substring(0, 19)}',
                  style: const pw.TextStyle(fontSize: 9, color: PdfColors.grey700),
                ),
                pw.Text(
                  'Oluşturan: SCPT Mobile App',
                  style: const pw.TextStyle(fontSize: 9, color: PdfColors.grey700),
                ),
              ],
            ),
          ),
        ],
      ),
    );

    // Print or save PDF
    await Printing.layoutPdf(
      onLayout: (format) async => pdf.save(),
    );
  }

  static pw.Widget _buildSWOTBox(String title, dynamic items, PdfColor color) {
    final itemList = items is List ? items : [];
    return pw.Container(
      padding: const pw.EdgeInsets.all(12),
      decoration: pw.BoxDecoration(
        color: color.shade(0.1),
        border: pw.Border.all(color: color, width: 1),
        borderRadius: const pw.BorderRadius.all(pw.Radius.circular(4)),
      ),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Text(
            title,
            style: pw.TextStyle(
              fontSize: 12,
              fontWeight: pw.FontWeight.bold,
              color: color,
            ),
          ),
          pw.SizedBox(height: 8),
          ...itemList.map((item) => pw.Padding(
                padding: const pw.EdgeInsets.only(bottom: 4),
                child: pw.Row(
                  crossAxisAlignment: pw.CrossAxisAlignment.start,
                  children: [
                    pw.Text('• ', style: pw.TextStyle(color: color)),
                    pw.Expanded(
                      child: pw.Text(
                        item.toString(),
                        style: const pw.TextStyle(fontSize: 10),
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
