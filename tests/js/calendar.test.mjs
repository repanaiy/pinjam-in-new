import test from 'node:test';
import assert from 'node:assert/strict';
import { rangeCalendar, formatTanggal } from '../../resources/js/components/calendar.js';

const make = (over = {}) => {
    const c = rangeCalendar({ today: '2026-10-19', maxDays: 7, holidays: { '2026-10-22': 'Libur uji' }, full: ['2026-10-27'], ...over });
    c.init();
    return c;
};

test('formatTanggal memakai bulan singkat Indonesia', () => {
    assert.equal(formatTanggal('2026-10-14'), '14 Okt 2026');
    assert.equal(formatTanggal('2026-08-01'), '1 Agu 2026');
    assert.equal(formatTanggal(''), '');
});

test('grid bulan: minggu mulai Senin, 31 hari Oktober 2026 (1 Okt = Kamis)', () => {
    const c = make();
    const cells = c.cells;
    assert.equal(c.title, 'Oktober 2026');
    assert.equal(cells.slice(0, 3).every((x) => x === null), true, 'tiga sel kosong sebelum Kamis');
    assert.equal(cells[3], '2026-10-01');
    assert.equal(cells.filter(Boolean).length, 31);
    assert.equal(cells.filter(Boolean).at(-1), '2026-10-31');
});

test('alasan tanggal tidak tersedia', () => {
    const c = make();
    assert.equal(c.reason('2026-10-16'), 'Tanggal sudah lewat');
    assert.equal(c.reason('2026-10-24'), 'Akhir pekan');
    assert.equal(c.reason('2026-10-25'), 'Akhir pekan');
    assert.equal(c.reason('2026-10-22'), 'Libur uji');
    assert.equal(c.reason('2026-10-27'), 'Stok penuh');
    assert.equal(c.reason('2026-10-21'), '');
    assert.equal(c.isDisabled('2026-10-19'), false, 'hari ini boleh');
});

test('tanggal yang dinonaktifkan tidak bisa dipilih', () => {
    const c = make();
    c.select('2026-10-22');
    c.select('2026-10-24');
    c.select('2026-10-16');
    assert.equal(c.start, '');
});

test('memilih rentang: klik pertama = mulai, kedua = selesai, ketiga = mulai ulang', () => {
    const c = make();
    c.select('2026-10-20');
    assert.equal(c.start, '2026-10-20');
    assert.equal(c.end, '');
    assert.equal(c.label, '20 Okt 2026 — pilih tanggal kembali');
    c.select('2026-10-21');
    assert.equal(c.end, '2026-10-21');
    assert.equal(c.duration, 2);
    assert.equal(c.label, '20 Okt 2026 — 21 Okt 2026');
    c.select('2026-10-28');
    assert.equal(c.start, '2026-10-28');
    assert.equal(c.end, '');
});

test('klik tanggal sebelum tanggal mulai memindahkan tanggal mulai', () => {
    const c = make();
    c.select('2026-10-21');
    c.select('2026-10-20');
    assert.equal(c.start, '2026-10-20');
    assert.equal(c.end, '');
});

test('durasi maksimal ditegakkan dan memberi pesan', () => {
    const c = make({ holidays: {}, full: [] });
    c.select('2026-10-19');
    c.select('2026-10-26'); // 8 hari
    assert.equal(c.message, 'Maksimal peminjaman 7 hari.');
    assert.equal(c.start, '2026-10-26');
    assert.equal(c.end, '');
    c.select('2026-10-27'); // 2 hari
    assert.equal(c.end, '2026-10-27');
    assert.equal(c.message, '');
});

test('tepat 7 hari diterima (akhir pekan di tengah boleh)', () => {
    const c = make({ holidays: {}, full: [] });
    c.select('2026-10-20'); // Selasa
    c.select('2026-10-26'); // Senin berikutnya = 7 hari, melewati Sabtu-Minggu
    assert.equal(c.end, '2026-10-26');
    assert.equal(c.duration, 7);
});

test('rentang yang melewati hari libur atau stok penuh ditolak', () => {
    const c = make();
    c.select('2026-10-21');
    c.select('2026-10-23'); // melewati libur 22 Okt
    assert.equal(c.message, 'Rentang ini mencakup tanggal yang tidak tersedia.');
    assert.equal(c.start, '2026-10-23');
    assert.equal(c.end, '');

    const d = make({ holidays: {} });
    d.select('2026-10-26');
    d.select('2026-10-28'); // melewati 27 Okt (stok penuh)
    assert.equal(d.end, '');
    assert.match(d.message, /tidak tersedia/);
});

test('clear menghapus pilihan', () => {
    const c = make();
    c.select('2026-10-20'); c.select('2026-10-21');
    c.clear();
    assert.deepEqual([c.start, c.end, c.label], ['', '', 'Pilih tanggal pinjam']);
});

test('navigasi bulan: tidak bisa mundur sebelum bulan berjalan', () => {
    const c = make();
    assert.equal(c.canGoPrev, false);
    c.prev();
    assert.equal(c.title, 'Oktober 2026');
    c.next();
    assert.equal(c.title, 'November 2026');
    assert.equal(c.canGoPrev, true);
    c.prev();
    assert.equal(c.title, 'Oktober 2026');
});

test('kalender dibuka pada bulan tanggal mulai yang sudah terisi (mis. setelah error form)', () => {
    const c = rangeCalendar({ today: '2026-10-19', start: '2026-12-02', end: '2026-12-03' });
    c.init();
    assert.equal(c.title, 'Desember 2026');
});

test('state sel untuk pewarnaan', () => {
    const c = make({ holidays: {}, full: [] });
    c.select('2026-10-20'); c.select('2026-10-22');
    assert.equal(c.state('2026-10-20'), 'edge');
    assert.equal(c.state('2026-10-21'), 'between');
    assert.equal(c.state('2026-10-22'), 'edge');
    assert.equal(c.state('2026-10-23'), 'free');
    assert.equal(c.state('2026-10-24'), 'disabled');
    assert.equal(c.state(null), '');
});

test('hover memberi pratinjau rentang sebelum tanggal selesai dipilih', () => {
    const c = make({ holidays: {}, full: [] });
    c.select('2026-10-20');
    c.hover = '2026-10-23';
    assert.equal(c.state('2026-10-22'), 'between');
});
