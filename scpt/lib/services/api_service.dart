import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  static const String baseUrl = 'https://saah.ai/api';

  /// Analyze area for urban planning
  static Future<Map<String, dynamic>> analyzeArea({
    required double lat,
    required double lon,
    required double areaSize,
  }) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/urban-planning.php?action=analyze&lat=$lat&lon=$lon&area_size=$areaSize'),
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {'success': true, 'data': data};
      } else {
        return {
          'success': false,
          'error': 'HTTP ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'error': e.toString()
      };
    }
  }

  /// Get NDVI data
  static Future<Map<String, dynamic>> getNDVI({
    required double lat,
    required double lon,
    required String startDate,
    required String endDate,
  }) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/nasa-advanced.php?type=ndvi&lat=$lat&lon=$lon&start_date=$startDate&end_date=$endDate'),
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {'success': true, 'data': data};
      } else {
        return {
          'success': false,
          'error': 'HTTP ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'error': e.toString()
      };
    }
  }

  /// Get temperature data
  static Future<Map<String, dynamic>> getTemperature({
    required double lat,
    required double lon,
    required String date,
  }) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/nasa-advanced.php?type=temperature&lat=$lat&lon=$lon&date=$date'),
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {'success': true, 'data': data};
      } else {
        return {
          'success': false,
          'error': 'HTTP ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'error': e.toString()
      };
    }
  }

  /// Get elevation data
  static Future<Map<String, dynamic>> getElevation({
    required double lat,
    required double lon,
  }) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/nasa-advanced.php?type=elevation&lat=$lat&lon=$lon'),
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {'success': true, 'data': data};
      } else {
        return {
          'success': false,
          'error': 'HTTP ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'error': e.toString()
      };
    }
  }

  /// Get fire risk data
  static Future<Map<String, dynamic>> getFireRisk({
    required double lat,
    required double lon,
    required int days,
  }) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/nasa-advanced.php?type=fire&lat=$lat&lon=$lon&days=$days'),
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {'success': true, 'data': data};
      } else {
        return {
          'success': false,
          'error': 'HTTP ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'error': e.toString()
      };
    }
  }
}
