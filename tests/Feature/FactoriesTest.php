<?php

namespace Tests\Feature;

use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Menjaga agar setiap factory punya model ber-trait HasFactory dan benar-benar bisa membuat data. */
class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    public static function factories(): array
    {
        $out = [];
        foreach (glob(__DIR__.'/../../database/factories/*Factory.php') as $file) {
            $name = basename($file, 'Factory.php');
            $out[$name] = [$name];
        }

        return $out;
    }

    #[DataProvider('factories')]
    public function test_model_memakai_has_factory_dan_factory_dapat_membuat_data(string $name): void
    {
        $model = "App\\Models\\{$name}";

        $this->assertTrue(class_exists($model), "Model {$model} tidak ada");
        $this->assertContains(HasFactory::class, class_uses_recursive($model), "{$model} belum memakai trait HasFactory");
        $this->assertTrue($model::factory()->create()->exists);
    }

    public function test_state_factory_utama(): void
    {
        $item = Item::factory()->withUnits(3)->create();
        $this->assertSame(3, ItemUnit::where('item_id', $item->id)->where('status', 'available')->count());

        $this->assertTrue(User::factory()->admin()->create()->isAdmin());
        $this->assertFalse(User::factory()->inactive()->create()->is_active);
        $this->assertSame('inactive', Item::factory()->inactive()->create()->status->value);

        $loan = Loan::factory()->status(LoanStatus::Approved)->between('2026-10-21', '2026-10-23')->create();
        $this->assertSame(LoanStatus::Approved, $loan->status);
        $this->assertSame('2026-10-23', $loan->end_date->toDateString());
    }
}
