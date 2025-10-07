import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:provider/provider.dart';
import 'package:geolocator/geolocator.dart';
import '../providers/analysis_provider.dart';

class LocationScreen extends StatefulWidget {
  final VoidCallback onNext;

  const LocationScreen({super.key, required this.onNext});

  @override
  State<LocationScreen> createState() => _LocationScreenState();
}

class _LocationScreenState extends State<LocationScreen> {
  final MapController _mapController = MapController();
  final TextEditingController _latController = TextEditingController();
  final TextEditingController _lonController = TextEditingController();

  LatLng _center = const LatLng(38.3575, 31.4164); // Konya Akşehir
  LatLng? _selectedLocation;

  Map<String, dynamic> _cityData = {};
  String? _selectedProvince = 'Konya';
  String? _selectedDistrict = 'Akşehir';
  List<String> _provinces = [];
  List<String> _districts = [];

  @override
  void initState() {
    super.initState();
    _loadCityData();
    _setDefaultLocation();
  }

  @override
  void dispose() {
    _latController.dispose();
    _lonController.dispose();
    super.dispose();
  }

  void _setDefaultLocation() {
    _selectedLocation = _center;
    _latController.text = _center.latitude.toStringAsFixed(6);
    _lonController.text = _center.longitude.toStringAsFixed(6);
  }

  Future<void> _loadCityData() async {
    try {
      final String jsonString = await rootBundle.loadString('assets/il-ilce-coordinates.json');
      final data = json.decode(jsonString) as Map<String, dynamic>;
      setState(() {
        _cityData = data;
        _provinces = data.keys.toList()..sort();
        if (_selectedProvince != null && data.containsKey(_selectedProvince)) {
          _districts = (data[_selectedProvince]['districts'] as Map<String, dynamic>).keys.toList()..sort();
        }
      });
    } catch (e) {
      // Continue with empty data if loading fails
    }
  }

  void _onProvinceChanged(String? province) {
    if (province == null) return;
    setState(() {
      _selectedProvince = province;
      final provinceData = _cityData[province];
      if (provinceData != null) {
        _districts = (provinceData['districts'] as Map<String, dynamic>).keys.toList()..sort();
        _selectedDistrict = _districts.isNotEmpty ? _districts[0] : null;
        _onDistrictChanged(_selectedDistrict);
      }
    });
  }

  void _onDistrictChanged(String? district) {
    if (district == null || _selectedProvince == null) return;
    setState(() {
      _selectedDistrict = district;
      final districtData = _cityData[_selectedProvince]['districts'][district];
      if (districtData != null) {
        final lat = (districtData['lat'] as num).toDouble();
        final lon = (districtData['lon'] as num).toDouble();
        final location = LatLng(lat, lon);
        _selectedLocation = location;
        _center = location;
        _latController.text = lat.toStringAsFixed(6);
        _lonController.text = lon.toStringAsFixed(6);
        _mapController.move(location, 13.0);
      }
    });
  }

  Future<void> _getCurrentLocation() async {
    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.whileInUse ||
          permission == LocationPermission.always) {
        final position = await Geolocator.getCurrentPosition();
        setState(() {
          _center = LatLng(position.latitude, position.longitude);
          _selectedLocation = _center;
          _latController.text = position.latitude.toStringAsFixed(6);
          _lonController.text = position.longitude.toStringAsFixed(6);
        });
        _mapController.move(_center, 13.0);
      }
    } catch (e) {
      // Use default location
    }
  }

  void _onMapTap(LatLng location) {
    setState(() {
      _selectedLocation = location;
      _latController.text = location.latitude.toStringAsFixed(6);
      _lonController.text = location.longitude.toStringAsFixed(6);
    });
  }

  void _selectLocation() {
    if (_selectedLocation != null) {
      Provider.of<AnalysisProvider>(context, listen: false)
          .setLocation(_selectedLocation!.latitude, _selectedLocation!.longitude);
      widget.onNext();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Lütfen bir konum seçin')),
      );
    }
  }

  void _useManualCoordinates() {
    final lat = double.tryParse(_latController.text);
    final lon = double.tryParse(_lonController.text);

    if (lat != null && lon != null) {
      final location = LatLng(lat, lon);
      setState(() {
        _selectedLocation = location;
        _center = location;
      });
      _mapController.move(location, 13.0);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Geçersiz koordinatlar')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Province/District Dropdown
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'İl/İlçe Seçimi',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          value: _selectedProvince,
                          decoration: const InputDecoration(
                            labelText: 'İl',
                            border: OutlineInputBorder(),
                          ),
                          items: _provinces.map((province) {
                            return DropdownMenuItem(
                              value: province,
                              child: Text(province),
                            );
                          }).toList(),
                          onChanged: _onProvinceChanged,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          value: _selectedDistrict,
                          decoration: const InputDecoration(
                            labelText: 'İlçe',
                            border: OutlineInputBorder(),
                          ),
                          items: _districts.map((district) {
                            return DropdownMenuItem(
                              value: district,
                              child: Text(district),
                            );
                          }).toList(),
                          onChanged: _onDistrictChanged,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Manual coordinate input
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Koordinat Girin',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _latController,
                          decoration: const InputDecoration(
                            labelText: 'Enlem',
                            border: OutlineInputBorder(),
                            hintText: '38.357500',
                          ),
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextField(
                          controller: _lonController,
                          decoration: const InputDecoration(
                            labelText: 'Boylam',
                            border: OutlineInputBorder(),
                            hintText: '31.416400',
                          ),
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: _useManualCoordinates,
                          icon: const Icon(Icons.search),
                          label: const Text('Konumu Göster'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: _getCurrentLocation,
                          icon: const Icon(Icons.my_location),
                          label: const Text('Konumum'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green,
                            foregroundColor: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Map
          Expanded(
            child: Card(
              clipBehavior: Clip.antiAlias,
              child: Stack(
                children: [
                  FlutterMap(
                    mapController: _mapController,
                    options: MapOptions(
                      initialCenter: _center,
                      initialZoom: 13.0,
                      onTap: (_, location) => _onMapTap(location),
                    ),
                    children: [
                      TileLayer(
                        urlTemplate: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                        subdomains: const ['a', 'b', 'c'],
                      ),
                      if (_selectedLocation != null)
                        MarkerLayer(
                          markers: [
                            Marker(
                              point: _selectedLocation!,
                              width: 80,
                              height: 80,
                              child: const Icon(
                                Icons.location_pin,
                                size: 50,
                                color: Colors.red,
                              ),
                            ),
                          ],
                        ),
                    ],
                  ),
                  Positioned(
                    top: 16,
                    left: 16,
                    right: 16,
                    child: Card(
                      color: Colors.white.withOpacity(0.9),
                      child: const Padding(
                        padding: EdgeInsets.all(12),
                        child: Text(
                          'Haritaya tıklayarak konum seçin',
                          style: TextStyle(fontWeight: FontWeight.bold),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Next button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _selectedLocation != null ? _selectLocation : null,
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
    );
  }
}
