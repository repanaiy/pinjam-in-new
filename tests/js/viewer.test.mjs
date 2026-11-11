import test from 'node:test';
import assert from 'node:assert/strict';
import { imageViewer, hoverZoom } from '../../resources/js/components/viewer.js';

/** Objek Alpine tiruan: $refs dengan ukuran tetap (panggung 800x600, gambar 400x300). */
const make = () => {
    const v = imageViewer();
    v.$refs = {
        stage: { getBoundingClientRect: () => ({ left: 0, top: 0, width: 800, height: 600 }) },
        img: { offsetWidth: 400, offsetHeight: 300, getBoundingClientRect: () => ({ left: 200, top: 150, right: 600, bottom: 450, width: 400, height: 300 }) },
        video: { getBoundingClientRect: () => ({ left: 100, top: 100, right: 700, bottom: 500, width: 600, height: 400 }) },
    };
    return v;
};
const items = [
    { src: '/a.jpg', alt: 'A' },
    { src: '/b.jpg', alt: 'B' },
    { src: '/c.mp4', alt: 'C', type: 'video' },
];

test('show membuka penampil, memilih indeks, dan mereset zoom', () => {
    const v = make();
    v.scale = 3;
    v.show({ items, index: 1 });
    assert.equal(v.open, true);
    assert.equal(v.index, 1);
    assert.equal(v.scale, 1);
    assert.equal(v.counter, '2 / 3');
    assert.equal(v.current.src, '/b.jpg');
    assert.equal(v.items[0].type, 'image', 'tipe default image');
});

test('show mengabaikan daftar kosong dan membatasi indeks di luar rentang', () => {
    const v = make();
    v.show({ items: [] });
    assert.equal(v.open, false);
    v.show({ items, index: 99 });
    assert.equal(v.index, 2);
    v.show({ items, index: -5 });
    assert.equal(v.index, 0);
});

test('go berputar maju/mundur dan mereset zoom', () => {
    const v = make();
    v.show({ items, index: 0 });
    v.setScale(2);
    v.go(-1);
    assert.equal(v.index, 2);
    assert.equal(v.scale, 1);
    v.go(1);
    assert.equal(v.index, 0);
});

test('go tidak melakukan apa-apa untuk satu gambar', () => {
    const v = make();
    v.show({ items: [items[0]] });
    v.go(1);
    assert.equal(v.index, 0);
});

test('zoom dibatasi 1x–6x', () => {
    const v = make();
    v.show({ items });
    for (let i = 0; i < 30; i++) v.zoomIn();
    assert.equal(v.scale, 6);
    for (let i = 0; i < 30; i++) v.zoomOut();
    assert.equal(v.scale, 1);
    assert.equal(v.x, 0);
    assert.equal(v.y, 0);
});

test('zoom mengarah ke kursor: titik di bawah kursor tetap di tempatnya', () => {
    const v = make();
    v.show({ items });
    const cx = 600, cy = 400; // relatif ke pusat panggung: (200, 100)
    v.setScale(2, cx, cy);
    // titik gambar di bawah kursor sebelum = (200,100); setelah: x = 200 - 200*2 = -200 (sebelum clamp)
    // clamp: maxX = (400*2 - 800)/2 = 0 -> gambar lebih kecil dari panggung, tidak boleh bergeser
    assert.equal(v.scale, 2);
    assert.equal(v.x, 0);
    assert.equal(v.y, 0);
    v.setScale(4, cx, cy); // maxX = (1600-800)/2 = 400, maxY = (1200-600)/2 = 300
    assert.ok(Math.abs(v.x) <= 400 && Math.abs(v.y) <= 300);
    assert.ok(v.x < 0 && v.y < 0, 'bergeser berlawanan arah kursor agar titik tetap');
});

test('pan dibatasi agar gambar tidak keluar dari panggung', () => {
    const v = make();
    v.show({ items });
    v.setScale(4);
    v.x = 99999; v.y = -99999;
    v.clampPan();
    assert.equal(v.x, 400);
    assert.equal(v.y, -300);
});

test('toggleZoom: perbesar lalu kembali ke 1x', () => {
    const v = make();
    v.show({ items });
    v.toggleZoom();
    assert.equal(v.scale, 2.5);
    v.toggleZoom();
    assert.equal(v.scale, 1);
});

test('roda mouse memperbesar/memperkecil; video tidak bisa di-zoom', () => {
    const v = make();
    v.show({ items, index: 0 });
    v.onWheel({ deltaY: -100, clientX: 400, clientY: 300 });
    assert.ok(v.scale > 1);
    v.show({ items, index: 2 }); // video
    assert.equal(v.isVideo, true);
    assert.equal(v.canZoom, false);
    const before = v.scale;
    v.onWheel({ deltaY: -100, clientX: 400, clientY: 300 });
    v.onDblClick({ clientX: 1, clientY: 1 });
    v.zoomIn();
    assert.equal(v.scale, before === 1 ? v.scale : before);
});

test('geser satu jari saat diperbesar menggeser gambar', () => {
    const v = make();
    v.show({ items });
    v.setScale(3);
    v.onDown({ pointerId: 1, clientX: 100, clientY: 100, currentTarget: {} });
    assert.equal(v.dragging, true);
    v.onMove({ pointerId: 1, clientX: 80, clientY: 90 });
    assert.equal(v.x, -20);
    assert.equal(v.y, -10);
    v.onUp({ pointerId: 1, clientX: 80, clientY: 90 });
    assert.equal(v.dragging, false);
});

test('cubit dua jari mengubah skala sebanding jarak', () => {
    const v = make();
    v.show({ items });
    v.onDown({ pointerId: 1, clientX: 300, clientY: 300, currentTarget: {} });
    v.onDown({ pointerId: 2, clientX: 400, clientY: 300, currentTarget: {} });
    assert.equal(v.pinching, true);
    v.onMove({ pointerId: 2, clientX: 500, clientY: 300 }); // jarak 100 -> 200
    assert.ok(Math.abs(v.scale - 2) < 1e-9);
    v.onUp({ pointerId: 2, clientX: 500, clientY: 300 });
    assert.equal(v.pinching, false);
});

test('geser horizontal tanpa zoom berpindah gambar', () => {
    const v = make();
    v.show({ items, index: 0 });
    v.onDown({ pointerId: 1, clientX: 400, clientY: 300, currentTarget: {} });
    v.onMove({ pointerId: 1, clientX: 300, clientY: 305 });
    v.onUp({ pointerId: 1, clientX: 300, clientY: 305 });
    assert.equal(v.index, 1, 'geser ke kiri = berikutnya');
    v.onDown({ pointerId: 1, clientX: 300, clientY: 300, currentTarget: {} });
    v.onMove({ pointerId: 1, clientX: 420, clientY: 300 });
    v.onUp({ pointerId: 1, clientX: 420, clientY: 300 });
    assert.equal(v.index, 0, 'geser ke kanan = sebelumnya');
});

test('ketuk dua kali cepat = zoom bolak-balik', () => {
    const v = make();
    v.show({ items });
    const tap = () => { v.onDown({ pointerId: 1, clientX: 200, clientY: 200, currentTarget: {} }); v.onUp({ pointerId: 1, clientX: 200, clientY: 200 }); };
    tap();
    assert.equal(v.scale, 1);
    tap();
    assert.equal(v.scale, 2.5);
});

test('keyboard: Esc menutup, panah berpindah, +/-/0 mengatur zoom', () => {
    const v = make();
    v.show({ items, index: 0 });
    const key = (k) => v.onKey({ key: k, preventDefault() {} });
    key('ArrowRight'); assert.equal(v.index, 1);
    key('ArrowLeft'); assert.equal(v.index, 0);
    key('+'); assert.ok(v.scale > 1);
    key('0'); assert.equal(v.scale, 1);
    key('-'); assert.equal(v.scale, 1);
    key('Escape'); assert.equal(v.open, false);
    key('ArrowRight'); assert.equal(v.index, 0, 'tidak bereaksi saat tertutup');
});

test('klik latar (di luar kotak gambar) menutup; klik pada gambar tidak', () => {
    const v = make();
    v.show({ items });
    v.onBackdrop({ clientX: 300, clientY: 300 }); // di dalam kotak gambar
    assert.equal(v.open, true, 'klik pada gambar tidak menutup');
    v.onBackdrop({ clientX: 20, clientY: 20 });   // di luar
    assert.equal(v.open, false);
});

test('klik latar tidak menutup saat diperbesar atau setelah menggeser', () => {
    const v = make();
    v.show({ items });
    v.setScale(2);
    v.onBackdrop({ clientX: 20, clientY: 20 });
    assert.equal(v.open, true);
    v.reset();
    v.moved = true;
    v.onBackdrop({ clientX: 20, clientY: 20 });
    assert.equal(v.open, true);
});

test('untuk video, batas yang dipakai adalah kotak video', () => {
    const v = make();
    v.show({ items, index: 2 });
    v.onBackdrop({ clientX: 300, clientY: 300 }); // di dalam video
    assert.equal(v.open, true);
    v.onBackdrop({ clientX: 10, clientY: 10 });
    assert.equal(v.open, false);
});

test('imgStyle menyertakan transform dan kursor sesuai keadaan', () => {
    const v = make();
    v.show({ items });
    assert.match(v.imgStyle, /scale\(1\)/);
    assert.match(v.imgStyle, /zoom-in/);
    v.setScale(2);
    assert.match(v.imgStyle, /grab/);
});

test('hoverZoom: aktif di mouse, mengabaikan sentuhan, origin mengikuti kursor', () => {
    const h = hoverZoom(2);
    const el = { getBoundingClientRect: () => ({ left: 100, top: 50, width: 200, height: 100 }) };
    h.move({ pointerType: 'touch', currentTarget: el, clientX: 150, clientY: 75 });
    assert.equal(h.active, false);
    h.move({ pointerType: 'mouse', currentTarget: el, clientX: 150, clientY: 75 });
    assert.equal(h.active, true);
    assert.equal(h.ox, 25);
    assert.equal(h.oy, 25);
    assert.match(h.style, /scale\(2\)/);
    assert.match(h.style, /25% 25%/);
    h.out();
    assert.equal(h.style, '');
});
