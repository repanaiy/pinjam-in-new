<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Enums\UnitStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'code', 'name', 'summary', 'description', 'specs', 'package_contents',
        'checklist_template', 'location', 'max_loan_days', 'max_qty_per_loan', 'min_stock',
        'status', 'video_url', 'video_title', 'video_duration',
    ];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'checklist_template' => 'array',
            'status' => ItemStatus::class,
            'max_loan_days' => 'integer',
            'max_qty_per_loan' => 'integer',
            'min_stock' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ItemPhoto::class)->orderBy('sort_order');
    }

    public function cover(): HasOne
    {
        return $this->hasOne(ItemPhoto::class)->where('is_cover', true);
    }

    public function units(): HasMany
    {
        return $this->hasMany(ItemUnit::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function availableUnits(): HasMany
    {
        return $this->units()->where('status', UnitStatus::Available->value);
    }

    public function scopeActive($q)
    {
        return $q->where('status', ItemStatus::Active->value);
    }

    public function scopeSearch($q, ?string $term)
    {
        return $term
            ? $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"))
            : $q;
    }

    public function coverUrl(): ?string
    {
        $path = $this->relationLoaded('cover') ? $this->cover?->path : $this->cover()->value('path');

        return $path ? asset('storage/'.$path) : null;
    }

    /** Butir checklist inspeksi: template alat, atau butir umum bila belum diisi. */
    public function checklistItems(): array
    {
        $template = array_values(array_filter((array) $this->checklist_template, fn ($v) => is_string($v) && $v !== ''));

        return $template ?: ['Unit sesuai label aset', 'Kondisi fisik baik tanpa kerusakan', 'Kelengkapan sesuai isi paket'];
    }

    /** Tautan video yang aman (hanya http/https) atau null. */
    public function videoLink(): ?string
    {
        $url = trim((string) $this->video_url);

        return preg_match('~^https?://~i', $url) ? $url : null;
    }

    /** youtube | vimeo | file | link — menentukan cara menampilkan video panduan. */
    public function videoType(): ?string
    {
        $url = $this->videoLink();

        return match (true) {
            $url === null => null,
            (bool) preg_match('~(?:youtube\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/)|youtu\.be/)[A-Za-z0-9_-]{11}~i', $url) => 'youtube',
            (bool) preg_match('~vimeo\.com/(?:video/)?\d+~i', $url) => 'vimeo',
            (bool) preg_match('~\.(mp4|webm|ogg)(?:[?#].*)?$~i', $url) => 'file',
            default => 'link',
        };
    }

    /** URL pemutar tertanam (YouTube dalam mode privasi), atau file langsung; null untuk tautan biasa. */
    public function videoEmbedUrl(): ?string
    {
        $url = $this->videoLink();

        return match ($this->videoType()) {
            'youtube' => preg_match('~(?:v=|embed/|shorts/|live/|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)
                ? "https://www.youtube-nocookie.com/embed/{$m[1]}?rel=0&modestbranding=1" : null,
            'vimeo' => preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m) ? "https://player.vimeo.com/video/{$m[1]}" : null,
            'file' => $url,
            default => null,
        };
    }

    /** Daftar foto untuk galeri/penampil: [['src','alt','caption','type']]. */
    public function galleryItems(): array
    {
        $photos = $this->relationLoaded('photos') ? $this->photos : $this->photos()->get();
        $n = $photos->count();

        return $photos->values()->map(fn ($p, $i) => [
            'src' => $p->url(),
            'alt' => $this->name,
            'caption' => $n > 1 ? "{$this->name} — foto ".($i + 1)." dari {$n}" : $this->name,
            'type' => 'image',
        ])->all();
    }
}
