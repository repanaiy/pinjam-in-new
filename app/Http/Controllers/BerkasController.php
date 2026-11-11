<?php

namespace App\Http\Controllers;

use App\Models\InspectionMedia;
use App\Models\Loan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Berkas privat dilayani lewat controller + Policy (siswa hanya miliknya, admin semua). */
class BerkasController extends Controller
{
    public function kartu(Loan $loan): StreamedResponse
    {
        Gate::authorize('viewFile', $loan);

        return $this->stream($loan->student_card_path);
    }

    public function lampiran(Loan $loan): StreamedResponse
    {
        Gate::authorize('viewFile', $loan);
        abort_unless($loan->attachment_path, 404);

        return $this->stream($loan->attachment_path);
    }

    /** Foto/video inspeksi: mengikuti hak akses pengajuan induknya. */
    public function media(InspectionMedia $media): StreamedResponse
    {
        Gate::authorize('viewFile', $media->inspection->loan);

        return $this->stream($media->path);
    }

    private function stream(string $path): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=0, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
