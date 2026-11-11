import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import { rangeCalendar, formatTanggal } from './components/calendar';
import { imageViewer, hoverZoom } from './components/viewer';

window.Alpine = Alpine;
window.Chart = Chart;
window.formatTanggal = formatTanggal;

Alpine.data('rangeCalendar', rangeCalendar);
Alpine.data('imageViewer', imageViewer);
Alpine.data('hoverZoom', hoverZoom);

/** Stepper jumlah unit. */
Alpine.data('qtyStepper', (cfg = {}) => ({
    value: cfg.value || 1,
    min: cfg.min || 1,
    max: cfg.max || 1,
    dec() { if (this.value > this.min) this.value--; },
    inc() { if (this.value < this.max) this.value++; },
}));

/** Dropzone unggah berkas dengan validasi sisi klien (server tetap memvalidasi ulang). */
Alpine.data('dropzone', (cfg = {}) => ({
    maxMb: cfg.maxMb || 5,
    accept: cfg.accept || [],
    previewUrl: null,
    pick(files) {
        const f = files && files[0];
        this.error = '';
        if (!f) return;
        if (this.accept.length && !this.accept.includes(f.type)) {
            this.error = 'Format berkas tidak didukung.'; this.file = null; this.$refs.input.value = ''; this.previewUrl = null; return;
        }
        if (f.size > this.maxMb * 1024 * 1024) {
            this.error = `Ukuran berkas maksimal ${this.maxMb} MB.`; this.file = null; this.$refs.input.value = ''; this.previewUrl = null; return;
        }
        this.file = f;
        if (f.type.startsWith('image/')) {
            this.previewUrl = URL.createObjectURL(f);
        } else {
            this.previewUrl = null;
        }
    },
    drop(e) {
        this.over = false;
        const dt = e.dataTransfer;
        if (dt && dt.files.length) { this.$refs.input.files = dt.files; this.pick(dt.files); }
    },
    clear() { this.file = null; this.error = ''; this.$refs.input.value = ''; this.previewUrl = null; },
    size() { return this.file ? `${(this.file.size / 1024 / 1024).toFixed(2)} MB` : ''; },
}));

/** Toast global: dipakai oleh <x-toast> dan dapat dipanggil dari JS: window.toast({...}). */
Alpine.store('toasts', {
    items: [],
    push(t) {
        const id = Date.now() + Math.random();
        this.items.push({ id, type: 'info', title: '', message: '', ...t });
        setTimeout(() => this.remove(id), t.duration ?? 6000);
    },
    remove(id) { this.items = this.items.filter((i) => i.id !== id); },
});
window.toast = (t) => Alpine.store('toasts').push(t);

/** Real-time loan status listener for authenticated user. */
if (window.Echo && window.Laravel?.userId) {
    window.Echo.private(`user.${window.Laravel.userId}`)
        .listen('.loan.status.updated', (e) => {
            window.toast({
                type: e.new_status === 'approved' ? 'success' : e.new_status === 'rejected' ? 'error' : 'info',
                title: 'Pembaruan Status',
                message: `Peminjaman ${e.loan.request_code}: ${e.message || e.loan.status_label}`,
                duration: 10000,
            });
            // Reload halaman detail jika sedang dibuka
            if (window.location.pathname.includes(`/siswa/riwayat/${e.loan.id}`)) {
                setTimeout(() => window.location.reload(), 2000);
            }
        });
}

Alpine.start();
