import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import './providers/providers.dart';
import './screens/screens.dart';
import './services/services.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    const apiBase = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.1.100:8000/api');
    final apiService = ApiService(baseUrl: apiBase);

    return MultiProvider(
      providers: [
        Provider<ApiService>(create: (_) => apiService),
        ChangeNotifierProvider(create: (_) => AuthProvider(api: apiService)),
        ChangeNotifierProvider(create: (_) => ItemProvider(api: apiService)),
        ChangeNotifierProvider(create: (_) => LoanProvider(api: apiService)),
      ],
      child: MaterialApp(
        title: 'Pinjam Alat',
        theme: ThemeData(primarySwatch: Colors.blue),
        home: Consumer<AuthProvider>(
          builder: (ctx, auth, _) {
            return FutureBuilder(
              future: auth.loadUser(),
              builder: (ctx, snap) {
                if (snap.connectionState == ConnectionState.waiting) {
                  return const Scaffold(body: Center(child: CircularProgressIndicator()));
                }
                return auth.isAuthenticated ? const HomeScreen() : const LoginScreen();
              },
            );
          },
        ),
        onGenerateRoute: (settings) {
          if (settings.name == '/login') {
            return MaterialPageRoute(builder: (ctx) => const LoginScreen());
          } else if (settings.name == '/home') {
            return MaterialPageRoute(builder: (ctx) => const HomeScreen());
          } else if (settings.name == '/items') {
            return MaterialPageRoute(builder: (ctx) => const ItemListScreen());
          } else if (settings.name?.startsWith('/items/') == true) {
            final id = int.parse(settings.name!.substring(settings.name!.lastIndexOf('/') + 1));
            return MaterialPageRoute(builder: (ctx) => ItemDetailScreen(itemId: id));
          } else if (settings.name == '/loans') {
            return MaterialPageRoute(builder: (ctx) => const LoanListScreen());
          } else if (settings.name?.startsWith('/loans/form/') == true) {
            final id = int.parse(settings.name!.substring(settings.name!.lastIndexOf('/') + 1));
            return MaterialPageRoute(builder: (ctx) => LoanFormScreen(itemId: id));
          } else if (settings.name?.startsWith('/loans/') == true) {
            final id = int.parse(settings.name!.substring(settings.name!.lastIndexOf('/') + 1));
            return MaterialPageRoute(builder: (ctx) => LoanDetailScreen(loanId: id));
          }
          return null;
        },
      ),
    );
  }
}
