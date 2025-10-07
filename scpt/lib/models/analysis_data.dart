class AnalysisData {
  final Map<String, dynamic> location;
  final double areaSize;
  final Map<String, dynamic> scores;
  final Map<String, dynamic> probabilities;
  final String primaryRecommendation;
  final double recommendationConfidence;
  final Map<String, dynamic>? detailedAnalysis;
  final Map<String, dynamic>? swotAnalysis;
  final String? swotMatrixHtml;
  final Map<String, dynamic>? textualReport;
  final Map<String, dynamic>? nasaData;

  AnalysisData({
    required this.location,
    required this.areaSize,
    required this.scores,
    required this.probabilities,
    required this.primaryRecommendation,
    required this.recommendationConfidence,
    this.detailedAnalysis,
    this.swotAnalysis,
    this.swotMatrixHtml,
    this.textualReport,
    this.nasaData,
  });

  factory AnalysisData.fromJson(Map<String, dynamic> json) {
    // Safe conversion helper
    Map<String, dynamic>? _toMapOrNull(dynamic value) {
      if (value == null) return null;
      if (value is Map<String, dynamic>) return value;
      if (value is Map) return Map<String, dynamic>.from(value);
      return null; // If List or other type, return null
    }

    return AnalysisData(
      location: _toMapOrNull(json['location']) ?? {},
      areaSize: (json['area_size'] ?? 0).toDouble(),
      scores: _toMapOrNull(json['scores']) ?? {},
      probabilities: _toMapOrNull(json['probabilities']) ?? {},
      primaryRecommendation: json['primary_recommendation'] ?? '',
      recommendationConfidence: (json['recommendation_confidence'] ?? 0).toDouble(),
      detailedAnalysis: _toMapOrNull(json['detailed_analysis']),
      swotAnalysis: _toMapOrNull(json['swot_analysis']),
      swotMatrixHtml: json['swot_matrix_html'],
      textualReport: _toMapOrNull(json['textual_report']),
      nasaData: _toMapOrNull(json['nasa_data']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'location': location,
      'area_size': areaSize,
      'scores': scores,
      'probabilities': probabilities,
      'primary_recommendation': primaryRecommendation,
      'recommendation_confidence': recommendationConfidence,
      'detailed_analysis': detailedAnalysis,
      'swot_analysis': swotAnalysis,
      'swot_matrix_html': swotMatrixHtml,
      'textual_report': textualReport,
      'nasa_data': nasaData,
    };
  }
}
