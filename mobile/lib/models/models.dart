class AuthResponse {
  final String token;
  final User user;

  AuthResponse({required this.token, required this.user});

  factory AuthResponse.fromJson(Map<String, dynamic> json) {
    return AuthResponse(
      token: json['token'] ?? '',
      user: User.fromJson(json['user']),
    );
  }
}

class User {
  final int id;
  final String name;
  final String username;
  final String role;
  final String? nis;
  final String? kelas;
  final int? lateCount;

  User({
    required this.id,
    required this.name,
    required this.username,
    required this.role,
    this.nis,
    this.kelas,
    this.lateCount,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'] ?? '',
      username: json['username'] ?? '',
      role: json['role'] ?? '',
      nis: json['nis'],
      kelas: json['kelas'],
      lateCount: json['late_count'],
    );
  }
}

class Item {
  final int id;
  final String code;
  final String name;
  final String? summary;
  final String? description;
  final String? cover;
  final String category;
  final int maxLoanDays;
  final int maxQtyPerLoan;
  final List<String>? specs;
  final String? packageContents;

  Item({
    required this.id,
    required this.code,
    required this.name,
    this.summary,
    this.description,
    this.cover,
    required this.category,
    required this.maxLoanDays,
    required this.maxQtyPerLoan,
    this.specs,
    this.packageContents,
  });

  factory Item.fromJson(Map<String, dynamic> json) {
    return Item(
      id: json['id'],
      code: json['code'] ?? '',
      name: json['name'] ?? '',
      summary: json['summary'],
      description: json['description'],
      cover: json['cover'],
      category: json['category'] ?? '',
      maxLoanDays: json['max_loan_days'] ?? 7,
      maxQtyPerLoan: json['max_qty_per_loan'] ?? 1,
      specs: (json['specs'] as List?)?.cast<String>(),
      packageContents: json['package_contents'],
    );
  }
}

class Loan {
  final int id;
  final String requestCode;
  final String itemName;
  final String? itemCover;
  final int quantity;
  final String startDate;
  final String endDate;
  final String status;
  final String statusLabel;
  final String purpose;
  final String? rejectReason;
  final String? studentCard;
  final String? attachment;
  final bool isLate;
  final List<TrackerStep>? tracker;

  Loan({
    required this.id,
    required this.requestCode,
    required this.itemName,
    this.itemCover,
    required this.quantity,
    required this.startDate,
    required this.endDate,
    required this.status,
    required this.statusLabel,
    required this.purpose,
    this.rejectReason,
    this.studentCard,
    this.attachment,
    required this.isLate,
    this.tracker,
  });

  factory Loan.fromJson(Map<String, dynamic> json) {
    return Loan(
      id: json['id'],
      requestCode: json['request_code'] ?? '',
      itemName: json['item_name'] ?? '',
      itemCover: json['item_cover'],
      quantity: json['quantity'] ?? 1,
      startDate: json['start_date'] ?? '',
      endDate: json['end_date'] ?? '',
      status: json['status'] ?? '',
      statusLabel: json['status_label'] ?? '',
      purpose: json['purpose'] ?? '',
      rejectReason: json['reject_reason'],
      studentCard: json['student_card'],
      attachment: json['attachment'],
      isLate: json['is_late'] ?? false,
      tracker: (json['tracker'] as List?)?.map((s) => TrackerStep.fromJson(s)).toList(),
    );
  }
}

class TrackerStep {
  final String label;
  final String state;
  final String? at;

  TrackerStep({
    required this.label,
    required this.state,
    this.at,
  });

  factory TrackerStep.fromJson(Map<String, dynamic> json) {
    return TrackerStep(
      label: json['label'] ?? '',
      state: json['state'] ?? 'todo',
      at: json['at'],
    );
  }
}
