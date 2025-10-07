import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'providers/analysis_provider.dart';
import 'screens/location_screen.dart';
import 'screens/area_screen.dart';
import 'screens/weight_screen.dart';
import 'screens/results_screen.dart';
import 'screens/decision_screen.dart';

void main() {
  runApp(const SCPTApp());
}

class SCPTApp extends StatelessWidget {
  const SCPTApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AnalysisProvider()),
      ],
      child: MaterialApp(
        title: 'SCPT - Smart City Planning Tool',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(
            seedColor: Colors.blue,
            brightness: Brightness.light,
          ),
          useMaterial3: true,
        ),
        home: const HomePage(title: 'SCPT - NASA Space Apps 2025'),
      ),
    );
  }
}

class HomePage extends StatefulWidget {
  const HomePage({super.key, required this.title});

  final String title;

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  int currentStep = 0;

  final List<String> stepTitles = [
    '1. Konum Seçimi',
    '2. Alan Seçimi',
    '3. Ağırlık Ayarları',
    '4. Analiz Sonuçları',
    '5. Karar Destek'
  ];

  void _goToStep(int step) {
    setState(() {
      currentStep = step;
    });
  }

  void _nextStep() {
    if (currentStep < 4) {
      setState(() {
        currentStep++;
      });
    }
  }

  void _previousStep() {
    if (currentStep > 0) {
      setState(() {
        currentStep--;
      });
    }
  }

  void _reset() {
    Provider.of<AnalysisProvider>(context, listen: false).reset();
    setState(() {
      currentStep = 0;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        backgroundColor: Theme.of(context).colorScheme.inversePrimary,
        actions: [
          if (currentStep > 0)
            IconButton(
              icon: const Icon(Icons.refresh),
              tooltip: 'Baştan Başla',
              onPressed: _reset,
            ),
        ],
      ),
      body: Column(
        children: [
          // Step Indicator
          _buildStepIndicator(),

          // Content Area
          Expanded(
            child: _buildStepContent(),
          ),
        ],
      ),
    );
  }

  Widget _buildStepIndicator() {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: List.generate(5, (index) {
          final isActive = index == currentStep;
          final isCompleted = index < currentStep;
          return Expanded(
            child: Container(
              margin: const EdgeInsets.symmetric(horizontal: 4),
              padding: const EdgeInsets.symmetric(vertical: 12),
              decoration: BoxDecoration(
                color: isActive
                    ? Colors.blue
                    : isCompleted
                        ? Colors.green
                        : Colors.grey.shade300,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                'Adım ${index + 1}',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: isActive || isCompleted ? Colors.white : Colors.black54,
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                ),
              ),
            ),
          );
        }),
      ),
    );
  }

  Widget _buildStepContent() {
    switch (currentStep) {
      case 0:
        return LocationScreen(onNext: _nextStep);
      case 1:
        return AreaScreen(onNext: _nextStep, onBack: _previousStep);
      case 2:
        return WeightScreen(onNext: _nextStep, onBack: _previousStep);
      case 3:
        return ResultsScreen(onReset: _reset, onDecisionPanel: _nextStep);
      case 4:
        return DecisionScreen(onReset: _reset);
      default:
        return const Center(child: Text('Geçersiz adım'));
    }
  }
}
