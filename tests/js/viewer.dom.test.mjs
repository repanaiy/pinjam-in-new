// Uji integrasi: markup <x-image-viewer> + Alpine asli + logika viewer.js, dijalankan di jsdom.
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { JSDOM } from 'jsdom';

const markup = fs.readFileSync(new URL('../../resources/views/components/image-viewer.blade.php', import.meta.url), 'utf8')
    .replace(/\{\{--[\s\S]*?--\}\}/g, '');

const dom = new JSDOM(`<!doctype html><html><body>${markup}</body></html>`, { pretendToBeVisual: true, url: 'http://localhost/' });
// Alpine membaca banyak global peramban (ShadowRoot, MutationObserver, ...): salin semua dari jsdom.
for (const k of Object.getOwnPropertyNames(dom.window)) {
    // Kelas event milik Node berbeda "realm" dengan jsdom, sehingga harus diganti agar dispatchEvent diterima.
    const force = ['Event', 'CustomEvent', 'EventTarget', 'KeyboardEvent', 'MouseEvent', 'UIEvent', 'FocusEvent'].includes(k);
    if (k in globalThis && ! force) continue;
    try { Object.defineProperty(globalThis, k, { get: () => dom.window[k], configurable: true }); } catch { /* abaikan */ }
}

// Paket CJS Alpine bisa membungkus ekspor default sekali lagi saat diimpor dari Node.
const alpineModule = await import('alpinejs');
const Alpine = alpineModule.default?.data ? alpineModule.default : (alpineModule.default?.default ?? alpineModule.Alpine);
const { imageViewer } = await import('../../resources/js/components/viewer.js');
Alpine.data('imageViewer', imageViewer);
Alpine.start();

// jsdom menjalankan transisi x-show lewat requestAnimationFrame (~16 ms/frame): beri waktu cukup.
const tick = () => new Promise((r) => setTimeout(r, 150));
const $ = (sel) => dom.window.document.querySelector(sel);
const dialog = () => $('[role="dialog"]');
const visible = () => dialog().style.display !== 'none';
const open = async (detail) => { dom.window.dispatchEvent(new dom.window.CustomEvent('open-viewer', { detail })); await tick(); };
const press = async (key) => {
    dom.window.dispatchEvent(new dom.window.KeyboardEvent('keydown', { key, bubbles: true }));
    await tick();
    if (key === 'Escape') await new Promise((r) => setTimeout(r, 300)); // transisi keluar 150 ms
};
const click = async (sel) => { $(sel).dispatchEvent(new dom.window.MouseEvent('click', { bubbles: true })); await tick(); };

const items = [
    { src: '/foto/1.jpg', alt: 'Satu', caption: 'Foto satu' },
    { src: '/foto/2.jpg', alt: 'Dua', caption: 'Foto dua' },
    { src: '/video/3.mp4', alt: 'Tiga', caption: 'Video tiga', type: 'video' },
];

test('tertutup di awal', async () => {
    await tick();
    assert.equal(visible(), false);
});

test('event open-viewer membuka penampil pada gambar yang dipilih dengan keterangan & penghitung', async () => {
    await open({ items, index: 1 });
    assert.equal(visible(), true);
    assert.equal($('img[draggable="false"]').getAttribute('src'), '/foto/2.jpg');
    assert.match(dialog().textContent, /Foto dua/);
    assert.match(dialog().textContent, /2 \/ 3/);
    assert.equal(dialog().getAttribute('aria-modal'), 'true');
});

test('tombol berikutnya/sebelumnya berpindah; video tampil sebagai pemutar tanpa kontrol zoom', async () => {
    await click('button[aria-label="Berikutnya"]');
    assert.match(dialog().textContent, /3 \/ 3/);
    assert.ok($('video'), 'elemen video dirender untuk item video');
    assert.equal($('video').getAttribute('src'), '/video/3.mp4');
    assert.equal($('img[draggable="false"]'), null, 'gambar utama tidak dirender untuk video');
    assert.equal($('button[aria-label="Perbesar"]'), null, 'kontrol zoom disembunyikan untuk video');

    await click('button[aria-label="Berikutnya"]'); // berputar ke awal
    assert.match(dialog().textContent, /1 \/ 3/);
    assert.equal($('video'), null);
    assert.equal($('img[draggable="false"]').getAttribute('src'), '/foto/1.jpg');
    await click('button[aria-label="Sebelumnya"]');
    assert.match(dialog().textContent, /3 \/ 3/);
    await click('button[aria-label="Sebelumnya"]');
});

test('tombol zoom mengubah label persentase dan bisa disetel ulang', async () => {
    const label = () => $('button[aria-label="Setel ulang zoom"]').textContent.trim();
    assert.equal(label(), '100%');
    await click('button[aria-label="Perbesar"]');
    assert.equal(label(), '140%');
    await click('button[aria-label="Perbesar"]');
    assert.equal(label(), '196%');
    assert.match($('img[draggable="false"]').getAttribute('style'), /scale\(1\.96\)/);
    await click('button[aria-label="Perkecil"]');
    assert.equal(label(), '140%');
    await click('button[aria-label="Setel ulang zoom"]');
    assert.equal(label(), '100%');
});

test('thumbnail memilih gambar langsung', async () => {
    await click('ul[aria-label="Pilih gambar"] li:nth-of-type(2) button');
    assert.match(dialog().textContent, /2 \/ 3/);
    assert.equal($('ul[aria-label="Pilih gambar"] li:nth-of-type(2) button').getAttribute('aria-current'), 'true');
});

test('papan ketik: panah berpindah, + memperbesar, Esc menutup', async () => {
    await press('ArrowRight');
    assert.match(dialog().textContent, /3 \/ 3/);
    await press('ArrowLeft');
    await press('+');
    assert.equal($('button[aria-label="Setel ulang zoom"]').textContent.trim(), '140%');
    await press('Escape');
    assert.equal(visible(), false);
});

test('satu gambar: tanpa tombol navigasi dan thumbnail', async () => {
    await open({ items: [items[0]], index: 0 });
    assert.equal(visible(), true);
    assert.equal($('button[aria-label="Berikutnya"]'), null);
    assert.equal($('ul[aria-label="Pilih gambar"]'), null);
    await press('Escape');
});

test('daftar kosong tidak membuka penampil', async () => {
    await open({ items: [] });
    assert.equal(visible(), false);
});

test('tautan "buka ukuran asli" menunjuk ke sumber gambar aktif', async () => {
    await open({ items, index: 0 });
    assert.equal($('a[aria-label="Buka ukuran asli di tab baru"]').getAttribute('href'), '/foto/1.jpg');
    await press('Escape');
});

test('saat tertutup, papan ketik diabaikan', async () => {
    await press('ArrowRight');
    assert.equal(visible(), false);
});

test.after(() => { dom.window.close(); setTimeout(() => process.exit(0), 50); });
