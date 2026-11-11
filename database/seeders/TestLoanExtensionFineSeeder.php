<?php

namespace Database\Seeders;

use App\Enums\ItemCondition;
use App\Enums\LoanStatus;
use App\Models\DamageReport;
use App\Models\Item;
use App\Models\Loan;
use App\Models\LoanUnit;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestLoanExtensionFineSeeder extends Seeder
{
    /**
     * Seed test data untuk fitur perpanjang dan denda.
     * - Loan borrowed (bisa diperpanjang)
     * - Loan overdue (bisa diperpanjang, ada denda telat)
     * - Loan returned dengan denda telat
     * - Loan returned dengan denda kerusakan
     */
    public function run(): void
    {
        $siswa = User::where('role', 'siswa')->first();
        $admin = User::where('role', 'admin')->first();
        $item = Item::first();

        if (! $siswa || ! $admin || ! $item || $item->units()->count() < 4) {
            $this->command->warn('Prerequisites missing. Run UserSeeder and ItemSeeder first.');

            return;
        }

        $units = $item->units()->take(4)->get();

        // 1. Loan BORROWED (bisa diperpanjang)
        $loan1 = Loan::create([
            'request_code' => 'REQ-2026-9001',
            'user_id' => $siswa->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(3),
            'purpose' => 'Test perpanjangan',
            'student_card_path' => 'student-cards/test1.jpg',
            'status' => LoanStatus::Borrowed,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now()->subDays(2),
            'approved_at' => now()->subDays(2),
            'borrowed_at' => now()->subDays(2),
            'pickup_date' => now()->subDays(2),
            'pickup_time_start' => '08:00',
            'pickup_time_end' => '10:00',
            'pickup_location' => 'Lab Komputer',
            'agreement_accepted' => true,
        ]);

        LoanUnit::create([
            'loan_id' => $loan1->id,
            'item_unit_id' => $units[0]->id,
            'condition_out' => ItemCondition::Baik->value,
        ]);

        // 2. Loan OVERDUE (bisa diperpanjang, 2 hari telat)
        $loan2 = Loan::create([
            'request_code' => 'REQ-2026-9002',
            'user_id' => $siswa->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'start_date' => now()->subDays(7),
            'end_date' => now()->subDays(2),
            'purpose' => 'Test perpanjangan overdue',
            'student_card_path' => 'student-cards/test2.jpg',
            'status' => LoanStatus::Overdue,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now()->subDays(7),
            'approved_at' => now()->subDays(7),
            'borrowed_at' => now()->subDays(7),
            'pickup_date' => now()->subDays(7),
            'pickup_time_start' => '08:00',
            'pickup_time_end' => '10:00',
            'pickup_location' => 'Lab Komputer',
            'agreement_accepted' => true,
            'is_late' => true,
            'late_days' => 2,
        ]);

        LoanUnit::create([
            'loan_id' => $loan2->id,
            'item_unit_id' => $units[1]->id,
            'condition_out' => ItemCondition::Baik->value,
        ]);

        // 3. Loan RETURNED dengan denda keterlambatan (3 hari telat = 30k)
        $loan3 = Loan::create([
            'request_code' => 'REQ-2026-9003',
            'user_id' => $siswa->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(7),
            'purpose' => 'Test denda telat',
            'student_card_path' => 'student-cards/test3.jpg',
            'status' => LoanStatus::Returned,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now()->subDays(10),
            'approved_at' => now()->subDays(10),
            'borrowed_at' => now()->subDays(10),
            'returned_at' => now()->subDays(4),
            'pickup_date' => now()->subDays(10),
            'pickup_time_start' => '08:00',
            'pickup_time_end' => '10:00',
            'pickup_location' => 'Lab Komputer',
            'agreement_accepted' => true,
            'is_late' => true,
            'late_days' => 3,
            'fine_amount' => 30000, // 3 hari x 10k
        ]);

        LoanUnit::create([
            'loan_id' => $loan3->id,
            'item_unit_id' => $units[2]->id,
            'condition_out' => ItemCondition::Baik->value,
            'condition_in' => ItemCondition::Baik->value,
            'returned_at' => now()->subDays(4),
        ]);

        // 4. Loan RETURNED_DAMAGED dengan denda kerusakan
        $loan4 = Loan::create([
            'request_code' => 'REQ-2026-9004',
            'user_id' => $siswa->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'start_date' => now()->subDays(8),
            'end_date' => now()->subDays(6),
            'purpose' => 'Test denda kerusakan',
            'student_card_path' => 'student-cards/test4.jpg',
            'status' => LoanStatus::ReturnedDamaged,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now()->subDays(8),
            'approved_at' => now()->subDays(8),
            'borrowed_at' => now()->subDays(8),
            'returned_at' => now()->subDays(5),
            'pickup_date' => now()->subDays(8),
            'pickup_time_start' => '08:00',
            'pickup_time_end' => '10:00',
            'pickup_location' => 'Lab Komputer',
            'agreement_accepted' => true,
            'is_late' => false,
            'late_days' => 0,
            'fine_amount' => 0,
        ]);

        $loanUnit4 = LoanUnit::create([
            'loan_id' => $loan4->id,
            'item_unit_id' => $units[3]->id,
            'condition_out' => ItemCondition::Baik->value,
            'condition_in' => ItemCondition::Rusak->value,
            'returned_at' => now()->subDays(5),
        ]);

        DamageReport::create([
            'loan_id' => $loan4->id,
            'item_unit_id' => $loanUnit4->item_unit_id,
            'type' => ItemCondition::Rusak,
            'description' => 'Layar retak, tombol power macet',
            'status' => 'open',
            'reported_by' => $admin->id,
            'fine_amount' => 150000, // Denda kerusakan
        ]);

        $this->command->info('Test data created:');
        $this->command->info("- {$loan1->request_code}: BORROWED (bisa diperpanjang)");
        $this->command->info("- {$loan2->request_code}: OVERDUE 2 hari (bisa diperpanjang)");
        $this->command->info("- {$loan3->request_code}: RETURNED dengan denda telat Rp 30.000");
        $this->command->info("- {$loan4->request_code}: RETURNED_DAMAGED dengan denda kerusakan Rp 150.000");
    }
}
