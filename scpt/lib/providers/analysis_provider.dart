import 'package:flutter/foundation.dart';
import '../models/analysis_data.dart';

class AnalysisProvider with ChangeNotifier {
  AnalysisData? _analysisData;
  bool _isLoading = false;
  String? _error;

  // Location data
  double? _selectedLat;
  double? _selectedLon;

  // Area data
  List<Map<String, double>>? _polygonPoints;
  double? _areaSize;

  AnalysisData? get analysisData => _analysisData;
  bool get isLoading => _isLoading;
  String? get error => _error;
  double? get selectedLat => _selectedLat;
  double? get selectedLon => _selectedLon;
  List<Map<String, double>>? get polygonPoints => _polygonPoints;
  double? get areaSize => _areaSize;

  void setLocation(double lat, double lon) {
    _selectedLat = lat;
    _selectedLon = lon;
    notifyListeners();
  }

  void setPolygon(List<Map<String, double>> points, double area) {
    _polygonPoints = points;
    _areaSize = area;
    notifyListeners();
  }

  void clearPolygon() {
    _polygonPoints = null;
    _areaSize = null;
    notifyListeners();
  }

  void setAnalysisData(AnalysisData data) {
    _analysisData = data;
    notifyListeners();
  }

  void setLoading(bool loading) {
    _isLoading = loading;
    notifyListeners();
  }

  void setError(String? error) {
    _error = error;
    notifyListeners();
  }

  void reset() {
    _analysisData = null;
    _selectedLat = null;
    _selectedLon = null;
    _polygonPoints = null;
    _areaSize = null;
    _error = null;
    _isLoading = false;
    notifyListeners();
  }
}
