<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Menangkap kesalahan sintaks Blade (mis. @php inline yang berpasangan dengan @endphp blok, @endif yang hilang)
 * pada SEMUA view, termasuk halaman yang jarang dibuka.
 */
class ViewsCompileTest extends TestCase
{
    private function views(): array
    {
        return collect(File::allFiles(resource_path('views')))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.blade.php'))
            ->values()->all();
    }

    public function test_semua_view_terkompilasi_menjadi_php_yang_valid(): void
    {
        $views = $this->views();
        $this->assertGreaterThan(40, count($views), 'folder view terbaca');

        foreach ($views as $file) {
            $php = Blade::compileString(File::get($file->getPathname()));

            try {
                token_get_all($php, TOKEN_PARSE);
            } catch (\ParseError $e) {
                $this->fail("{$file->getRelativePathname()}: {$e->getMessage()} (baris {$e->getLine()} hasil kompilasi)");
            }
        }

        $this->assertTrue(true);
    }

    public function test_tidak_ada_direktif_blade_yang_tidak_terkompilasi(): void
    {
        foreach ($this->views() as $file) {
            $php = Blade::compileString(File::get($file->getPathname()));

            $this->assertDoesNotMatchRegularExpression(
                '/@(foreach|forelse|endforeach|endforelse|endif|elseif|endphp|endunless|endisset)\b/',
                $php,
                "Direktif Blade tidak terkompilasi di {$file->getRelativePathname()}"
            );
        }
    }
}
