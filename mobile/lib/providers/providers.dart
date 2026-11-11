import 'package:flutter/material.dart';
import '../models/models.dart';
import '../services/services.dart';

class AuthProvider extends ChangeNotifier {
  final ApiService api;
  User? _user;
  bool _isLoading = false;
  String? _error;

  AuthProvider({required this.api});

  User? get user => _user;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get isAuthenticated => _user != null;

  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final res = await api.login(username, password);
      _user = res.user;
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    await api.logout();
    _user = null;
    notifyListeners();
  }

  Future<void> loadUser() async {
    await api.loadToken();
  }
}

class ItemProvider extends ChangeNotifier {
  final ApiService api;
  List<Item> _items = [];
  bool _isLoading = false;

  ItemProvider({required this.api});

  List<Item> get items => _items;
  bool get isLoading => _isLoading;

  Future<void> fetchItems({String? query}) async {
    _isLoading = true;
    notifyListeners();

    try {
      _items = await api.getItems(query: query);
    } catch (e) {
      _items = [];
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<Item?> fetchItem(int id) async {
    try {
      return await api.getItem(id);
    } catch (e) {
      return null;
    }
  }
}

class LoanProvider extends ChangeNotifier {
  final ApiService api;
  List<Loan> _loans = [];
  bool _isLoading = false;

  LoanProvider({required this.api});

  List<Loan> get loans => _loans;
  bool get isLoading => _isLoading;

  Future<void> fetchLoans() async {
    _isLoading = true;
    notifyListeners();

    try {
      _loans = await api.getLoans();
    } catch (e) {
      _loans = [];
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> submitLoan({
    required int itemId,
    required int quantity,
    required String startDate,
    required String endDate,
    required String purpose,
  }) async {
    try {
      final loan = await api.submitLoan(
        itemId: itemId,
        quantity: quantity,
        startDate: startDate,
        endDate: endDate,
        purpose: purpose,
      );
      _loans.insert(0, loan);
      notifyListeners();
      return true;
    } catch (e) {
      return false;
    }
  }
}
