import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/providers.dart';
import '../models/models.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final usernameCtrl = TextEditingController();
  final passwordCtrl = TextEditingController();

  void _login() async {
    final auth = context.read<AuthProvider>();
    final ok = await auth.login(usernameCtrl.text, passwordCtrl.text);

    if (ok) {
      if (mounted) Navigator.of(context).pushReplacementNamed('/home');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(auth.error ?? 'Login gagal')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Masuk')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: usernameCtrl,
              decoration: const InputDecoration(labelText: 'Username'),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: passwordCtrl,
              decoration: const InputDecoration(labelText: 'Password'),
              obscureText: true,
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: _login,
              child: const Text('Masuk'),
            ),
          ],
        ),
      ),
    );
  }
}

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Beranda'),
        actions: [
          PopupMenuButton(
            itemBuilder: (ctx) => [
              PopupMenuItem(
                child: const Text('Keluar'),
                onTap: () {
                  context.read<AuthProvider>().logout();
                  Navigator.of(context).pushReplacementNamed('/login');
                },
              ),
            ],
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Text('Halo, ${user?.name}'),
          ),
          Expanded(
            child: ListView(
              children: [
                ListTile(
                  title: const Text('Katalog'),
                  onTap: () => Navigator.of(context).pushNamed('/items'),
                ),
                ListTile(
                  title: const Text('Pengajuan Saya'),
                  onTap: () => Navigator.of(context).pushNamed('/loans'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class ItemListScreen extends StatefulWidget {
  const ItemListScreen({super.key});

  @override
  _ItemListScreenState createState() => _ItemListScreenState();
}

class _ItemListScreenState extends State<ItemListScreen> {
  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    context.read<ItemProvider>().fetchItems();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Katalog')),
      body: Consumer<ItemProvider>(
        builder: (ctx, provider, _) {
          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }
          if (provider.items.isEmpty) {
            return const Center(child: Text('Tidak ada alat'));
          }
          return ListView.builder(
            itemCount: provider.items.length,
            itemBuilder: (ctx, i) {
              final item = provider.items[i];
              return ListTile(
                title: Text(item.name),
                subtitle: Text(item.category),
                onTap: () => Navigator.of(context).pushNamed(
                  '/items/${item.id}',
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class LoanListScreen extends StatefulWidget {
  const LoanListScreen({super.key});

  @override
  _LoanListScreenState createState() => _LoanListScreenState();
}

class _LoanListScreenState extends State<LoanListScreen> {
  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    context.read<LoanProvider>().fetchLoans();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Pengajuan Saya')),
      body: Consumer<LoanProvider>(
        builder: (ctx, provider, _) {
          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }
          if (provider.loans.isEmpty) {
            return const Center(child: Text('Belum ada pengajuan'));
          }
          return ListView.builder(
            itemCount: provider.loans.length,
            itemBuilder: (ctx, i) {
              final loan = provider.loans[i];
              return ListTile(
                title: Text(loan.requestCode),
                subtitle: Text('${loan.itemName} — ${loan.statusLabel}'),
                onTap: () => Navigator.of(context).pushNamed(
                  '/loans/${loan.id}',
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class ItemDetailScreen extends StatelessWidget {
  const ItemDetailScreen({super.key, required this.itemId});
  final int itemId;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Detail Alat')),
      body: Consumer<ItemProvider>(
        builder: (ctx, provider, _) {
          final item = provider.items.firstWhere(
            (i) => i.id == itemId,
            orElse: () => Item(id: 0, code: '', name: '', category: '', maxLoanDays: 7, maxQtyPerLoan: 1),
          );
          if (item.id == 0) {
            return const Center(child: Text('Alat tidak ditemukan'));
          }
          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (item.cover != null)
                  Image.network(
                    item.cover!,
                    height: 200,
                    width: double.infinity,
                    fit: BoxFit.cover,
                  ),
                const SizedBox(height: 12),
                Text('Kategori: ${item.category}', style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 8),
                Text('Kode: ${item.code}', style: Theme.of(context).textTheme.bodySmall),
                const SizedBox(height: 12),
                Text('Deskripsi', style: Theme.of(context).textTheme.titleMedium),
                Text(item.description ?? '—'),
                if (item.specs != null && item.specs!.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Text('Spesifikasi', style: Theme.of(context).textTheme.titleMedium),
                  ...item.specs!.map((s) => Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text('• $s'),
                  )),
                ],
                const SizedBox(height: 12),
                Text('Isi Paket', style: Theme.of(context).textTheme.titleMedium),
                Text(item.packageContents ?? '—'),
                const SizedBox(height: 12),
                Text('Maks. Pinjam: ${item.maxLoanDays} hari | Per unit: ${item.maxQtyPerLoan}'),
                const SizedBox(height: 16),
                ElevatedButton.icon(
                  onPressed: () => Navigator.of(context).pushNamed('/loans/form/${item.id}'),
                  icon: const Icon(Icons.add),
                  label: const Text('Ajukan Peminjaman'),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class LoanFormScreen extends StatefulWidget {
  const LoanFormScreen({super.key, required this.itemId});
  final int itemId;

  @override
  _LoanFormScreenState createState() => _LoanFormScreenState();
}

class _LoanFormScreenState extends State<LoanFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _qtyCtrl = TextEditingController(text: '1');
  final _purposeCtrl = TextEditingController();
  DateTime? _start;
  DateTime? _end;
  bool _loading = false;

  Future<void> _pickDate({bool isStart = true}) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now().add(const Duration(days: 1)),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) {
      setState(() {
        if (isStart) {
          _start = picked;
        } else {
          _end = picked;
        }
      });
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_start == null || _end == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pilih mulai & selesai pinjam')));
      return;
    }

    setState(() => _loading = true);
    final ok = await context.read<LoanProvider>().submitLoan(
          itemId: widget.itemId,
          quantity: int.parse(_qtyCtrl.text),
          startDate: _start!.toIso8601String().split('T').first,
          endDate: _end!.toIso8601String().split('T').first,
          purpose: _purposeCtrl.text,
        );

    setState(() => _loading = false);
    if (ok) {
      if (mounted) Navigator.of(context).pushReplacementNamed('/loans');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Gagal mengajukan peminjaman')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Ajukan Peminjaman')),
      body: Form(
        key: _formKey,
        child: Consumer<ItemProvider>(
          builder: (ctx, provider, _) {
            final item = provider.items.firstWhere(
              (i) => i.id == widget.itemId,
              orElse: () => Item(id: 0, code: '', name: '', category: '', maxLoanDays: 7, maxQtyPerLoan: 1),
            );
            return SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Alat: ${item.name} (${item.code})', style: Theme.of(context).textTheme.titleLarge),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _qtyCtrl,
                    decoration: const InputDecoration(labelText: 'Jumlah'),
                    keyboardType: TextInputType.number,
                    validator: (v) => v == null || int.parse(v) < 1 ? 'Minimal 1' : null,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _purposeCtrl,
                          decoration: const InputDecoration(labelText: 'Tujuan Peminjaman'),
                          maxLines: 3,
                          validator: (v) => v == null || v.trim().isEmpty ? 'Isi tujuan' : null,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Column(
                    children: [
                      ListTile(
                        title: const Text('Mulai pinjam'),
                        trailing: Text(_start == null ? 'Pilih' : _start!.toLocal().toString().split(' ')[0]),
                        onTap: () => _pickDate(isStart: true),
                      ),
                      ListTile(
                        title: const Text('Selesai pinjam'),
                        trailing: Text(_end == null ? 'Pilih' : _end!.toLocal().toString().split(' ')[0]),
                        onTap: () => _pickDate(isStart: false),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: _loading ? null : _submit,
                    icon: _loading ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.send),
                    label: _loading ? const Text('Mengirim…') : const Text('Ajukan'),
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}

class LoanDetailScreen extends StatelessWidget {
  const LoanDetailScreen({super.key, required this.loanId});
  final int loanId;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Detail Pengajuan')),
      body: Consumer<LoanProvider>(
        builder: (ctx, provider, _) {
          final loan = provider.loans.firstWhere(
            (l) => l.id == loanId,
            orElse: () => Loan(id: 0, requestCode: '', itemName: '', startDate: '', endDate: '', status: '', statusLabel: '', purpose: '', isLate: false, quantity: 1),
          );
          if (loan.id == 0) {
            return const Center(child: Text('Pengajuan tidak ditemukan'));
          }
          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: loan.status == 'rejected' ? Colors.red : loan.status == 'approved' ? Colors.amber : Colors.green,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        loan.statusLabel,
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text('Alat: ${loan.itemName}', style: Theme.of(context).textTheme.titleMedium),
                Text('Kode: $loan.requestCode', style: Theme.of(context).textTheme.bodySmall),
                Text('Jumlah: ${loan.quantity}', style: Theme.of(context).textTheme.bodyMedium),
                Text('Mulai: ${loan.startDate}', style: Theme.of(context).textTheme.bodyMedium),
                Text('Selesai: ${loan.endDate}', style: Theme.of(context).textTheme.bodyMedium),
                if (loan.purpose.isNotEmpty) const SizedBox(height: 8),
                Text('Tujuan: ${loan.purpose}'),
                if (loan.rejectReason != null && loan.rejectReason!.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text('Alasan: ${loan.rejectReason}', style: const TextStyle(color: Colors.red)),
                ],
                if (loan.isLate) const SizedBox(height: 8),
                if (loan.isLate) const Text('Terlambat', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
                if (loan.tracker != null && loan.tracker!.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Text('Proses', style: Theme.of(context).textTheme.titleMedium),
                  ...loan.tracker!.map((s) => Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(s.label),
                        Text(s.at ?? '', style: TextStyle(color: s.state == 'done' ? Colors.green : s.state == 'todo' ? Colors.grey : Colors.orange)),
                      ],
                    ),
                  )),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
