// Kalender rentang tanggal (Alpine). Dipisah dari app.js agar bisa diuji unit.
const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const BULAN_PENDEK = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

const pad = (n) => String(n).padStart(2, '0');
const toIso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const fromIso = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);

/** "2026-10-14" -> "14 Okt 2026" */
export const formatTanggal = (iso) => {
    if (!iso) return '';
    const d = fromIso(iso);
    return `${d.getDate()} ${BULAN_PENDEK[d.getMonth()]} ${d.getFullYear()}`;
};

/**
 * Kalender rentang tanggal.
 * cfg: { today, start, end, disabled: [iso], full: [iso], holidays: {iso: nama},
 *        disableWeekends: true, maxDays: 7, weekendDays: [0, 6] }
 * Tanggal penuh, akhir pekan, hari libur, dan masa lalu dinonaktifkan.
 */
export const rangeCalendar = (cfg = {}) => ({
    today: cfg.today || toIso(new Date()),
    start: cfg.start || '',
    end: cfg.end || '',
    disabled: new Set(cfg.disabled || []),
    full: new Set(cfg.full || []),
    holidays: cfg.holidays || {},
    disableWeekends: cfg.disableWeekends !== false,
    maxDays: cfg.maxDays || 7,
    hover: '',
    message: '',
    cursor: null,
    weekdays: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],

    init() {
        const base = this.start || this.today;
        const d = fromIso(base);
        this.cursor = new Date(d.getFullYear(), d.getMonth(), 1);
    },

    get title() { return `${BULAN[this.cursor.getMonth()]} ${this.cursor.getFullYear()}`; },
    get canGoPrev() {
        const t = fromIso(this.today);
        return this.cursor > new Date(t.getFullYear(), t.getMonth(), 1);
    },
    prev() { if (this.canGoPrev) this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() - 1, 1); },
    next() { this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() + 1, 1); },

    /** Sel kalender untuk bulan aktif, minggu mulai Senin. */
    get cells() {
        const y = this.cursor.getFullYear();
        const m = this.cursor.getMonth();
        const lead = (new Date(y, m, 1).getDay() + 6) % 7;
        const total = new Date(y, m + 1, 0).getDate();
        const out = [];
        for (let i = 0; i < lead; i++) out.push(null);
        for (let d = 1; d <= total; d++) out.push(`${y}-${pad(m + 1)}-${pad(d)}`);
        return out;
    },

    reason(iso) {
        if (iso < this.today) return 'Tanggal sudah lewat';
        if (this.disableWeekends && [0, 6].includes(fromIso(iso).getDay())) return 'Akhir pekan';
        if (this.holidays[iso]) return this.holidays[iso];
        if (this.full.has(iso)) return 'Stok penuh';
        if (this.disabled.has(iso)) return 'Tidak tersedia';
        return '';
    },
    isDisabled(iso) { return this.reason(iso) !== ''; },

    rangeBlocked(a, b) {
        for (let d = fromIso(a); toIso(d) <= b; d = addDays(d, 1)) {
            const iso = toIso(d);
            // Akhir pekan di tengah rentang diperbolehkan; hanya libur/penuh/nonaktif yang memblokir.
            if (this.holidays[iso] || this.full.has(iso) || this.disabled.has(iso)) return true;
        }
        return false;
    },

    select(iso) {
        if (!iso || this.isDisabled(iso)) return;
        this.message = '';
        if (!this.start || (this.start && this.end)) {
            this.start = iso; this.end = ''; return;
        }
        if (iso < this.start) { this.start = iso; return; }
        const days = Math.round((fromIso(iso) - fromIso(this.start)) / 86400000) + 1;
        if (days > this.maxDays) {
            this.message = `Maksimal peminjaman ${this.maxDays} hari.`;
            this.start = iso; this.end = ''; return;
        }
        if (this.rangeBlocked(this.start, iso)) {
            this.message = 'Rentang ini mencakup tanggal yang tidak tersedia.';
            this.start = iso; this.end = ''; return;
        }
        this.end = iso;
    },

    clear() { this.start = ''; this.end = ''; this.message = ''; },

    state(iso) {
        if (!iso) return '';
        if (this.isDisabled(iso)) return 'disabled';
        if (iso === this.start || iso === this.end) return 'edge';
        const to = this.end || this.hover;
        if (this.start && to && iso > this.start && iso < to) return 'between';
        return 'free';
    },

    cellClass(iso) {
        const base = 'h-10 w-full rounded-lg text-sm transition-colors';
        switch (this.state(iso)) {
            case 'disabled': return `${base} cursor-not-allowed text-slate-400 line-through decoration-slate-300`;
            case 'edge': return `${base} bg-emerald-700 font-semibold text-white`;
            case 'between': return `${base} bg-emerald-100 text-emerald-900`;
            default: return `${base} text-slate-800 hover:bg-slate-100`;
        }
    },

    get label() {
        if (!this.start) return 'Pilih tanggal pinjam';
        if (!this.end) return `${formatTanggal(this.start)} — pilih tanggal kembali`;
        return `${formatTanggal(this.start)} — ${formatTanggal(this.end)}`;
    },

    get duration() {
        if (!this.start || !this.end) return 0;
        return Math.round((fromIso(this.end) - fromIso(this.start)) / 86400000) + 1;
    },
});

