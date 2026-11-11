<?php

namespace Database\Seeders;

use App\Enums\UnitStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $cat = Category::pluck('id', 'slug');

        $items = [
            [
                'category' => 'elektronik', 'code' => 'ELK-012', 'name' => 'Kamera DSLR Canon 80D',
                'summary' => '24,2 MP • Lensa 18-135 mm', 'units' => 8, 'max_qty' => 2, 'min_stock' => 2,
                'location' => 'Ruang Sarpras, Gedung B',
                'description' => 'Kamera DSLR untuk dokumentasi kegiatan, jurnalistik sekolah, dan praktik fotografi.',
                'specs' => ['Sensor' => 'APS-C 24,2 MP', 'Lensa' => '18-135 mm f/3.5-5.6', 'Video' => 'Full HD 60 fps', 'Penyimpanan' => 'SD Card'],
                'package' => 'Body, lensa 18-135 mm, baterai LP-E6N + charger, strap, tas kamera, SD card 32 GB.',
                'checklist' => ['Body & label aset sesuai', 'Lensa lengkap & bersih', 'Baterai + charger', 'Strap & tas kamera', 'SD card terpasang', 'Layar dan tombol berfungsi'],
                'video' => null, // isi tautan YouTube/Vimeo/MP4 lewat Inventaris → Ubah untuk menampilkan pemutar
            ],
            [
                'category' => 'audio-visual', 'code' => 'AVS-004', 'name' => 'Proyektor Epson EB-X500',
                'summary' => '3.600 lumen • XGA • HDMI/VGA', 'units' => 8, 'max_qty' => 1, 'min_stock' => 2,
                'location' => 'Ruang Sarpras, Gedung B',
                'description' => 'Proyektor untuk presentasi kelas, seminar, dan kegiatan ekstrakurikuler.',
                'specs' => ['Kecerahan' => '3.600 lumen', 'Resolusi' => 'XGA (1024×768)', 'Input' => 'HDMI, VGA, USB'],
                'package' => 'Unit proyektor, kabel power, kabel HDMI, kabel VGA, remote, tas.',
                'checklist' => ['Unit & label aset sesuai', 'Lensa bersih tanpa goresan', 'Kabel power', 'Kabel HDMI/VGA', 'Remote + baterai', 'Menyala & menampilkan gambar'],
                'video' => null,
            ],
            [
                'category' => 'laboratorium', 'code' => 'LAB-007', 'name' => 'Mikroskop Binokuler',
                'summary' => 'Perbesaran 40x–1000x • Lampu LED', 'units' => 6, 'max_qty' => 2, 'min_stock' => 1,
                'location' => 'Laboratorium Biologi',
                'description' => 'Mikroskop binokuler untuk praktikum biologi dan pengamatan preparat.',
                'specs' => ['Perbesaran' => '40x–1000x', 'Pencahayaan' => 'LED', 'Okuler' => 'WF10x'],
                'package' => 'Unit mikroskop, 4 lensa objektif, kabel power, penutup debu.',
                'checklist' => ['Unit & label aset sesuai', 'Lensa objektif lengkap', 'Okuler bersih', 'Lampu LED menyala', 'Penutup debu'],
                'video' => null,
            ],
            [
                'category' => 'audio-visual', 'code' => 'AVS-009', 'name' => 'Tripod Video 170 cm',
                'summary' => 'Kepala fluid • Beban maks. 5 kg', 'units' => 10, 'max_qty' => 3, 'min_stock' => 2,
                'location' => 'Ruang Sarpras, Gedung B',
                'description' => 'Tripod video dengan kepala fluid untuk pengambilan gambar yang stabil.',
                'specs' => ['Tinggi maks.' => '170 cm', 'Beban maks.' => '5 kg', 'Kepala' => 'Fluid pan & tilt'],
                'package' => 'Tripod, kepala fluid, quick-release plate, tas.',
                'checklist' => ['Kaki tripod mengunci', 'Kepala fluid halus', 'Quick-release plate', 'Tas tripod'],
                'video' => null,
            ],
            [
                'category' => 'audio-visual', 'code' => 'AVS-015', 'name' => 'Speaker Portabel 60W',
                'summary' => 'Bluetooth • Baterai 8 jam • 2 mic input', 'units' => 5, 'max_qty' => 1, 'min_stock' => 1,
                'location' => 'Ruang Sarpras, Gedung B',
                'description' => 'Speaker portabel untuk kegiatan upacara, lomba, dan acara kelas.',
                'specs' => ['Daya' => '60 W', 'Konektivitas' => 'Bluetooth, AUX', 'Baterai' => '± 8 jam'],
                'package' => 'Speaker, charger, 1 mikrofon nirkabel, kabel AUX.',
                'checklist' => ['Unit & label aset sesuai', 'Charger', 'Mikrofon + baterai', 'Suara jernih tanpa pecah'],
                'video' => null,
            ],
            [
                'category' => 'praktikum', 'code' => 'PRK-003', 'name' => 'Kit Robotika Arduino',
                'summary' => 'Arduino Uno • Sensor & motor lengkap', 'units' => 8, 'max_qty' => 2, 'min_stock' => 2,
                'location' => 'Laboratorium Komputer',
                'description' => 'Paket Arduino untuk praktikum elektronika dan proyek robotika.',
                'specs' => ['Board' => 'Arduino Uno R3', 'Sensor' => 'Ultrasonik, IR, suhu', 'Aktuator' => 'Servo & motor DC'],
                'package' => 'Arduino Uno, breadboard, kabel jumper, sensor ultrasonik, servo, motor DC + driver, box penyimpanan.',
                'checklist' => ['Board Arduino', 'Breadboard & kabel jumper', 'Sensor ultrasonik', 'Servo & motor DC', 'Kabel USB', 'Box lengkap'],
                'video' => null,
            ],
            [
                'category' => 'olahraga', 'code' => 'OLR-001', 'name' => 'Bola Basket Molten',
                'summary' => 'Ukuran 7 • Indoor/outdoor', 'units' => 12, 'max_qty' => 3, 'min_stock' => 3,
                'location' => 'Gudang Olahraga',
                'description' => 'Bola basket resmi ukuran 7 untuk latihan dan pertandingan antar kelas.',
                'specs' => ['Ukuran' => '7', 'Material' => 'Komposit', 'Penggunaan' => 'Indoor/outdoor'],
                'package' => 'Bola basket, jaring bola.',
                'checklist' => ['Bola tidak kempes', 'Permukaan tidak mengelupas', 'Jaring bola'],
                'video' => null,
            ],
        ];

        foreach ($items as $row) {
            $item = Item::query()->updateOrCreate(['code' => $row['code']], [
                'category_id' => $cat[$row['category']],
                'name' => $row['name'],
                'summary' => $row['summary'],
                'description' => $row['description'],
                'specs' => $row['specs'],
                'package_contents' => $row['package'],
                'checklist_template' => $row['checklist'],
                'location' => $row['location'],
                'max_loan_days' => 7,
                'max_qty_per_loan' => $row['max_qty'],
                'min_stock' => $row['min_stock'],
                'status' => 'active',
                'video_url' => $row['video'][0] ?? null,
                'video_title' => $row['video'][1] ?? null,
                'video_duration' => $row['video'][2] ?? null,
            ]);

            for ($n = 1; $n <= $row['units']; $n++) {
                ItemUnit::query()->updateOrCreate(
                    ['unit_code' => sprintf('%s-%02d', $row['code'], $n)],
                    ['item_id' => $item->id, 'status' => UnitStatus::Available->value]
                );
            }
        }
    }
}
