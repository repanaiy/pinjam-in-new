<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemPhoto;
use App\Models\ItemUnit;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** CRUD alat, unit fisik, foto, dan kategori. Hapus selalu soft delete dan dijaga dari data yang masih aktif. */
class InventoryService
{
    public function __construct(private AuditService $audit) {}

    // ------------------------------------------------------------------ alat

    /** @param UploadedFile[] $photos */
    public function createItem(User $admin, array $data, array $photos = [], int $initialUnits = 0): Item
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($admin, $data, $photos, $initialUnits, &$stored) {
                $item = Item::create($data);

                for ($n = 1, $made = 0; $made < $initialUnits; $n++) {
                    $code = sprintf('%s-%02d', $item->code, $n);
                    if (! ItemUnit::withTrashed()->where('unit_code', $code)->exists()) {
                        ItemUnit::create(['item_id' => $item->id, 'unit_code' => $code, 'status' => 'available']);
                        $made++;
                    }
                }

                $this->storePhotos($item, $photos, $stored);
                $this->audit->log(null, $admin, 'item_created', "Alat {$item->code} — {$item->name} ditambahkan", ['item_id' => $item->id, 'units' => $initialUnits]);

                return $item;
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($stored);
            throw $e;
        }
    }

    /** @param UploadedFile[] $photos */
    public function updateItem(User $admin, Item $item, array $data, array $photos = []): Item
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($admin, $item, $data, $photos, &$stored) {
                $item->update($data);
                $this->storePhotos($item, $photos, $stored);
                $this->audit->log(null, $admin, 'item_updated', "Alat {$item->code} diperbarui", ['item_id' => $item->id]);

                return $item;
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($stored);
            throw $e;
        }
    }

    /** Soft delete. Ditolak bila masih ada pengajuan/peminjaman aktif. Riwayat transaksi tetap utuh. */
    public function deleteItem(User $admin, Item $item): void
    {
        if (Loan::query()->where('item_id', $item->id)->reserving()->exists()) {
            throw new BusinessRuleException('Alat masih memiliki pengajuan atau peminjaman aktif sehingga tidak bisa dihapus. Selesaikan atau batalkan dulu transaksinya.');
        }

        DB::transaction(function () use ($admin, $item) {
            $item->units()->delete();
            $item->delete();
            $this->audit->log(null, $admin, 'item_deleted', "Alat {$item->code} — {$item->name} dihapus (soft delete)", ['item_id' => $item->id]);
        });
    }

    // ------------------------------------------------------------------ unit

    public function addUnit(User $admin, Item $item, array $data): ItemUnit
    {
        $unit = ItemUnit::create(array_merge($data, ['item_id' => $item->id]));
        $this->audit->log(null, $admin, 'unit_created', "Unit {$unit->unit_code} ditambahkan ke {$item->code}", ['unit_id' => $unit->id]);

        return $unit;
    }

    public function updateUnit(User $admin, ItemUnit $unit, array $data): ItemUnit
    {
        $statusChanged = isset($data['status']) && $data['status'] !== $unit->status->value;

        if ($statusChanged && $this->inUse($unit)) {
            throw new BusinessRuleException("Unit {$unit->unit_code} sedang dibawa siswa. Status baru bisa diubah setelah dikembalikan.");
        }

        $unit->update($data);
        $this->audit->log(null, $admin, 'unit_updated', "Unit {$unit->unit_code} diperbarui", ['unit_id' => $unit->id, 'status' => $unit->status->value]);

        return $unit;
    }

    public function deleteUnit(User $admin, ItemUnit $unit): void
    {
        if ($this->inUse($unit)) {
            throw new BusinessRuleException("Unit {$unit->unit_code} sedang dibawa siswa sehingga tidak bisa dihapus.");
        }

        $unit->delete();
        $this->audit->log(null, $admin, 'unit_deleted', "Unit {$unit->unit_code} dihapus (soft delete)", ['unit_id' => $unit->id]);
    }

    private function inUse(ItemUnit $unit): bool
    {
        return ItemUnit::query()->inUse()->whereKey($unit->id)->exists();
    }

    // ------------------------------------------------------------------ foto (disk public)

    /** @param UploadedFile[] $files */
    private function storePhotos(Item $item, array $files, array &$stored): void
    {
        $order = (int) $item->photos()->max('sort_order');
        $hasCover = $item->photos()->where('is_cover', true)->exists();

        foreach ($files as $file) {
            $path = $file->store("items/{$item->id}", 'public');
            $stored[] = $path;

            ItemPhoto::create([
                'item_id' => $item->id,
                'path' => $path,
                'sort_order' => ++$order,
                'is_cover' => ! $hasCover,
            ]);
            $hasCover = true;
        }
    }

    public function addPhotos(Item $item, array $files): void
    {
        $stored = [];
        try {
            DB::transaction(fn () => $this->storePhotos($item, $files, $stored));
        } catch (Throwable $e) {
            Storage::disk('public')->delete($stored);
            throw $e;
        }
    }

    public function deletePhoto(ItemPhoto $photo): void
    {
        DB::transaction(function () use ($photo) {
            $wasCover = $photo->is_cover;
            $itemId = $photo->item_id;
            $path = $photo->path;
            $photo->delete();

            if ($wasCover) {
                ItemPhoto::query()->where('item_id', $itemId)->orderBy('sort_order')->first()?->update(['is_cover' => true]);
            }

            Storage::disk('public')->delete($path);
        });
    }

    public function setCover(ItemPhoto $photo): void
    {
        DB::transaction(function () use ($photo) {
            ItemPhoto::query()->where('item_id', $photo->item_id)->update(['is_cover' => false]);
            $photo->update(['is_cover' => true]);
        });
    }

    // ------------------------------------------------------------------ kategori

    public function createCategory(User $admin, array $data): Category
    {
        $data['slug'] = $this->uniqueSlug($data['name']);
        $category = Category::create($data);
        $this->audit->log(null, $admin, 'category_created', "Kategori {$category->name} ditambahkan");

        return $category;
    }

    public function updateCategory(User $admin, Category $category, array $data): Category
    {
        $category->update($data); // slug dipertahankan agar URL/filter lama tidak rusak
        $this->audit->log(null, $admin, 'category_updated', "Kategori {$category->name} diperbarui");

        return $category;
    }

    public function deleteCategory(User $admin, Category $category): void
    {
        if ($category->items()->withTrashed()->exists()) {
            throw new BusinessRuleException("Kategori {$category->name} masih dipakai alat (termasuk yang sudah dihapus) sehingga tidak bisa dihapus.");
        }

        $category->delete();
        $this->audit->log(null, $admin, 'category_deleted', "Kategori {$category->name} dihapus");
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;

        for ($i = 2; Category::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
