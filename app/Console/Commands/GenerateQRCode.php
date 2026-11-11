<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Loan;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Console\Command;

class GenerateQRCode extends Command
{
    protected $signature = 'qr:generate
                            {type : Tipe QR (item/loan)}
                            {code : Kode barang (Item code) atau kode pengajuan (request_code)}
                            {--size=200 : Ukuran gambar SVG}';

    protected $description = 'Generate QR Code SVG untuk barang atau pengajuan pinjaman';

    public function handle(): int
    {
        $type = strtolower($this->argument('type'));
        $code = $this->argument('code');
        $size = (int) $this->option('size');

        if ($type === 'item') {
            $item = Item::where('code', $code)->first();
            if (! $item) {
                $this->error("Barang dengan kode {$code} tidak ditemukan.");

                return self::FAILURE;
            }
            $payload = url("/katalog/{$item->id}");
        } elseif ($type === 'loan') {
            $loan = Loan::where('request_code', $code)->first();
            if (! $loan) {
                $this->error("Pengajuan dengan kode {$code} tidak ditemukan.");

                return self::FAILURE;
            }
            $payload = route('admin.verifikasi.show', $loan->id);
        } else {
            $this->error("Tipe {$type} tidak valid. Gunakan 'item' atau 'loan'.");

            return self::FAILURE;
        }

        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($payload);

        $this->output->write($svg);

        return self::SUCCESS;
    }
}
