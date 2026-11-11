import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/models.dart';

class ApiService {
  final String baseUrl;
  String? _token;

  ApiService({required this.baseUrl});

  Future<void> loadToken() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
  }

  Future<AuthResponse> login(String username, String password) async {
    final res = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({'username': username, 'password': password}),
    );

    if (res.statusCode == 200) {
      final data = AuthResponse.fromJson(jsonDecode(res.body));
      _token = data.token;
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('auth_token', data.token);
      return data;
    } else {
      throw Exception('Login gagal');
    }
  }

  Future<void> logout() async {
    if (_token == null) return;
    await http.post(
      Uri.parse('$baseUrl/logout'),
      headers: _authHeaders(),
    );
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }

  Future<List<Item>> getItems({String? query}) async {
    final url = Uri.parse('$baseUrl/items${query != null ? '?q=$query' : ''}');
    final res = await http.get(url, headers: _authHeaders());

    if (res.statusCode == 200) {
      final data = jsonDecode(res.body);
      final items = (data['data'] as List).map((i) => Item.fromJson(i)).toList();
      return items;
    } else {
      throw Exception('Gagal memuat katalog');
    }
  }

  Future<Item> getItem(int id) async {
    final res = await http.get(
      Uri.parse('$baseUrl/items/$id'),
      headers: _authHeaders(),
    );

    if (res.statusCode == 200) {
      return Item.fromJson(jsonDecode(res.body)['data']);
    } else {
      throw Exception('Gagal memuat detail alat');
    }
  }

  Future<List<Loan>> getLoans() async {
    final res = await http.get(
      Uri.parse('$baseUrl/loans'),
      headers: _authHeaders(),
    );

    if (res.statusCode == 200) {
      final data = jsonDecode(res.body);
      final loans = (data['data'] as List).map((l) => Loan.fromJson(l)).toList();
      return loans;
    } else {
      throw Exception('Gagal memuat pengajuan');
    }
  }

  Future<Loan> submitLoan({
    required int itemId,
    required int quantity,
    required String startDate,
    required String endDate,
    required String purpose,
  }) async {
    final res = await http.post(
      Uri.parse('$baseUrl/loans'),
      headers: {..._authHeaders(), 'Accept': 'application/json'},
      body: jsonEncode({
        'item_id': itemId,
        'quantity': quantity,
        'start_date': startDate,
        'end_date': endDate,
        'purpose': purpose,
      }),
    );

    if (res.statusCode == 201) {
      return Loan.fromJson(jsonDecode(res.body)['data']);
    } else {
      throw Exception('Gagal mengajukan peminjaman');
    }
  }

  Map<String, String> _authHeaders() {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };
  }
}
