<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Loan;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Response;

class QRCodeController extends Controller
{
    public function item(Item $item): Response
    {
        $payload = url("/katalog/{$item->id}");
        $svg = $this->renderSvg($payload);

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    public function loan(Loan $loan): Response
    {
        $payload = route('admin.verifikasi.show', $loan->id);
        $svg = $this->renderSvg($payload);

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    private function renderSvg(string $payload): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(250),
            new SvgImageBackEnd
        );
        $writer = new Writer($renderer);

        return $writer->writeString($payload);
    }
}
