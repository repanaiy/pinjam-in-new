<?php

namespace Database\Factories;

use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Loan> */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $start = today()->addDays(2)->toDateString();

        return [
            'request_code' => 'REQ-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'user_id' => User::factory(),
            'item_id' => Item::factory(),
            'quantity' => 1,
            'start_date' => $start,
            'end_date' => $start,
            'purpose' => 'Dokumentasi kegiatan sekolah',
            'student_card_path' => 'student-cards/contoh.png',
            'agreement_accepted' => true,
            'status' => LoanStatus::Pending,
        ];
    }

    public function status(LoanStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function between(string $start, string $end): static
    {
        return $this->state(fn () => ['start_date' => $start, 'end_date' => $end]);
    }
}
