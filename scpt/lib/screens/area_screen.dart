import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';
import '../providers/analysis_provider.dart';

class AreaScreen extends StatefulWidget {
  final VoidCallback onNext;
  final VoidCallback onBack;

  const AreaScreen({super.key, required this.onNext, required this.onBack});

  @override
  State<AreaScreen> createState() => _AreaScreenState();
}

class _AreaScreenState extends State<AreaScreen> {
  final MapController _mapController = MapController();
  final List<LatLng> _polygonPoints = [];
  bool _isPolygonComplete = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = Provider.of<AnalysisProvider>(context, listen: false);
      if (provider.selectedLat != null && provider.selectedLon != null) {
        _mapController.move(
          LatLng(provider.selectedLat!, provider.selectedLon!),
          14.0,
        );
      }
    });
  }

  void _onMapTap(LatLng location) {
    if (_polygonPoints.length < 4) {
      setState(() {
        _polygonPoints.add(location);
        if (_polygonPoints.length == 4) {
          _isPolygonComplete = true;
          _calculateArea();
        }
      });
    }
  }

  void _calculateArea() {
    // Simple area calculation using shoelace formula
    double area = 0.0;
    const Distance distance = Distance();

    for (int i = 0; i < _polygonPoints.length; i++) {
      int j = (i + 1) % _polygonPoints.length;
      double xi = _polygonPoints[i].latitude;
      double yi = _polygonPoints[i].longitude;
      double xj = _polygonPoints[j].latitude;
      double yj = _polygonPoints[j].longitude;
      area += (xi * yj) - (xj * yi);
    }
    area = area.abs() / 2.0;

    // Convert to square meters (approximate)
    // 1 degree latitude ≈ 111,000 meters
    // 1 degree longitude ≈ 111,000 * cos(latitude) meters
    double avgLat = _polygonPoints.map((p) => p.latitude).reduce((a, b) => a + b) / _polygonPoints.length;
    double metersPerDegreeLat = 111000.0;
    double metersPerDegreeLon = 111000.0 * (avgLat * 3.14159 / 180.0).abs();
    double areaInMeters = area * metersPerDegreeLat * metersPerDegreeLon;

    final provider = Provider.of<AnalysisProvider>(context, listen: false);
    provider.setPolygon(
      _polygonPoints.map((p) => {'lat': p.latitude, 'lon': p.longitude}).toList(),
      areaInMeters,
    );
  }

  void _clearPolygon() {
    setState(() {
      _polygonPoints.clear();
      _isPolygonComplete = false;
    });
    Provider.of<AnalysisProvider>(context, listen: false).clearPolygon();
  }

  void _confirmArea() {
    if (_isPolygonComplete) {
      widget.onNext();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Lütfen 4 nokta seçerek alanı belirleyin')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = Provider.of<AnalysisProvider>(context);

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Instructions
          Card(
            color: Colors.blue.shade50,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Row(
                    children: [
                      const Icon(Icons.info_outline, color: Colors.blue),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Haritaya 4 nokta tıklayarak alanı belirleyin (${_polygonPoints.length}/4)',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  if (provider.areaSize != null) ...[
                    const SizedBox(height: 8),
                    Text(
                      'Alan Büyüklüğü: ${provider.areaSize!.toStringAsFixed(0)} m²',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.green,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Map
          Expanded(
            child: Card(
              clipBehavior: Clip.antiAlias,
              child: FlutterMap(
                mapController: _mapController,
                options: MapOptions(
                  initialCenter: provider.selectedLat != null && provider.selectedLon != null
                      ? LatLng(provider.selectedLat!, provider.selectedLon!)
                      : const LatLng(39.9334, 32.8597),
                  initialZoom: 14.0,
                  onTap: (_, location) => _onMapTap(location),
                ),
                children: [
                  TileLayer(
                    urlTemplate: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    subdomains: const ['a', 'b', 'c'],
                  ),
                  // Selected location marker
                  if (provider.selectedLat != null && provider.selectedLon != null)
                    MarkerLayer(
                      markers: [
                        Marker(
                          point: LatLng(provider.selectedLat!, provider.selectedLon!),
                          width: 40,
                          height: 40,
                          child: const Icon(
                            Icons.location_pin,
                            size: 30,
                            color: Colors.blue,
                          ),
                        ),
                      ],
                    ),
                  // Polygon points
                  if (_polygonPoints.isNotEmpty)
                    MarkerLayer(
                      markers: _polygonPoints.asMap().entries.map((entry) {
                        return Marker(
                          point: entry.value,
                          width: 30,
                          height: 30,
                          child: Container(
                            decoration: const BoxDecoration(
                              color: Colors.red,
                              shape: BoxShape.circle,
                            ),
                            child: Center(
                              child: Text(
                                '${entry.key + 1}',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                  // Polygon
                  if (_polygonPoints.length >= 3)
                    PolygonLayer(
                      polygons: [
                        Polygon(
                          points: _polygonPoints,
                          color: Colors.blue.withOpacity(0.3),
                          borderColor: Colors.blue,
                          borderStrokeWidth: 3,
                        ),
                      ],
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Action buttons
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: widget.onBack,
                  icon: const Icon(Icons.arrow_back),
                  label: const Text('Geri'),
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.all(16),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              if (_polygonPoints.isNotEmpty)
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _clearPolygon,
                    icon: const Icon(Icons.clear),
                    label: const Text('Temizle'),
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.all(16),
                      backgroundColor: Colors.orange,
                      foregroundColor: Colors.white,
                    ),
                  ),
                ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: _isPolygonComplete ? _confirmArea : null,
                  icon: const Icon(Icons.arrow_forward),
                  label: const Text('İleri'),
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.all(16),
                    backgroundColor: Colors.blue,
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
