import 'package:flutter_test/flutter_test.dart';
import 'package:pinjam_alat_mobile/main.dart';

void main() {
  testWidgets('App renders login screen', (WidgetTester tester) async {
    await tester.pumpWidget(const MyApp());
    expect(find.text('Masuk'), findsWidgets);
    await tester.pumpAndSettle();
    expect(find.text('Masuk'), findsWidgets);
  });
}
