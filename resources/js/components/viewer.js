// Penampil gambar/video layar penuh (zoom, geser, cubit, galeri) dan zoom hover.
// Fungsi murni berbasis objek Alpine agar bisa diuji unit di Node (lihat tests/js).

const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
const dist = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);

export const imageViewer = () => ({
    MIN: 1,
    MAX: 6,
    open: false,
    items: [],
    index: 0,
    scale: 1,
    x: 0,
    y: 0,
    dragging: false,
    pinching: false,
    moved: false,
    ptrs: new Map(),
    pinchStart: 0,
    scaleStart: 1,
    lastTap: 0,
    downAt: null,
    opener: null,

    // ------------------------------------------------------------ keadaan turunan
    get current() { return this.items[this.index] || {}; },
    get isVideo() { return this.current.type === 'video'; },
    get canZoom() { return this.open && !this.isVideo; },
    get zoomLabel() { return Math.round(this.scale * 100) + '%'; },
    get counter() { return `${this.index + 1} / ${this.items.length}`; },
    get imgStyle() {
        return `transform: translate3d(${this.x}px, ${this.y}px, 0) scale(${this.scale}); cursor: ${this.scale > 1 ? (this.dragging ? 'grabbing' : 'grab') : 'zoom-in'};`;
    },

    // ------------------------------------------------------------ buka/tutup
    /** detail: { items: [{src, alt, caption, type: 'image'|'video'}], index } */
    show(detail) {
        const items = (detail && detail.items) || [];
        if (!items.length) return;
        this.items = items.map((i) => ({ type: 'image', ...i }));
        this.index = clamp(Number(detail.index) || 0, 0, this.items.length - 1);
        this.reset();
        this.opener = typeof document !== 'undefined' ? document.activeElement : null;
        this.open = true;
        if (typeof document !== 'undefined') document.body.classList.add('overflow-hidden');
        if (this.$nextTick) this.$nextTick(() => this.$refs.close && this.$refs.close.focus());
    },
    close() {
        this.open = false;
        this.ptrs.clear();
        this.dragging = this.pinching = false;
        if (typeof document !== 'undefined') document.body.classList.remove('overflow-hidden');
        if (this.opener && this.opener.focus) this.opener.focus();
    },
    reset() { this.scale = 1; this.x = 0; this.y = 0; },
    go(step) {
        if (this.items.length < 2) return;
        this.index = (this.index + step + this.items.length) % this.items.length;
        this.reset();
    },
    select(i) { this.index = clamp(i, 0, this.items.length - 1); this.reset(); },

    // ------------------------------------------------------------ zoom & geser
    stageRect() {
        const el = this.$refs && this.$refs.stage;
        return el ? el.getBoundingClientRect() : { left: 0, top: 0, width: 800, height: 600 };
    },
    imageSize() {
        const el = this.$refs && this.$refs.img;
        return el ? { w: el.offsetWidth, h: el.offsetHeight } : { w: 800, h: 600 };
    },
    clampPan() {
        const r = this.stageRect();
        const { w, h } = this.imageSize();
        const maxX = Math.max(0, (w * this.scale - r.width) / 2);
        const maxY = Math.max(0, (h * this.scale - r.height) / 2);
        this.x = clamp(this.x, -maxX, maxX) || 0; // `|| 0` membuang -0
        this.y = clamp(this.y, -maxY, maxY) || 0;
    },
    /** Ubah skala dengan titik (cx, cy) di layar tetap berada di bawah kursor/jari. */
    setScale(next, cx, cy) {
        const s2 = Math.round(clamp(next, this.MIN, this.MAX) * 1000) / 1000; // hindari galat desimal bertumpuk
        if (s2 === this.scale) return;
        const r = this.stageRect();
        const px = (cx ?? r.left + r.width / 2) - (r.left + r.width / 2);
        const py = (cy ?? r.top + r.height / 2) - (r.top + r.height / 2);
        const ratio = s2 / this.scale;
        this.x = px - (px - this.x) * ratio;
        this.y = py - (py - this.y) * ratio;
        this.scale = s2;
        if (s2 === this.MIN) { this.x = 0; this.y = 0; } else { this.clampPan(); }
    },
    zoomIn() { this.setScale(this.scale * 1.4); },
    zoomOut() { this.setScale(this.scale / 1.4); },
    toggleZoom(cx, cy) { this.scale > 1 ? this.reset() : this.setScale(2.5, cx, cy); },

    // ------------------------------------------------------------ input
    onWheel(e) {
        if (!this.canZoom) return;
        this.setScale(this.scale * (e.deltaY < 0 ? 1.15 : 1 / 1.15), e.clientX, e.clientY);
    },
    onDblClick(e) { if (this.canZoom) this.toggleZoom(e.clientX, e.clientY); },
    onDown(e) {
        if (!this.canZoom) return;
        this.ptrs.set(e.pointerId, { x: e.clientX, y: e.clientY });
        this.downAt = { x: e.clientX, y: e.clientY, t: Date.now() };
        this.moved = false;
        if (e.currentTarget && e.currentTarget.setPointerCapture) { try { e.currentTarget.setPointerCapture(e.pointerId); } catch (_) { /* abaikan */ } }
        if (this.ptrs.size === 2) {
            const [a, b] = [...this.ptrs.values()];
            this.pinching = true;
            this.pinchStart = dist(a, b) || 1;
            this.scaleStart = this.scale;
        } else {
            this.dragging = this.scale > 1;
        }
    },
    onMove(e) {
        const prev = this.ptrs.get(e.pointerId);
        if (!prev) return;
        const cur = { x: e.clientX, y: e.clientY };
        this.ptrs.set(e.pointerId, cur);

        if (this.pinching && this.ptrs.size >= 2) {
            const [a, b] = [...this.ptrs.values()];
            this.moved = true;
            this.setScale(this.scaleStart * (dist(a, b) / this.pinchStart), (a.x + b.x) / 2, (a.y + b.y) / 2);
            return;
        }
        if (Math.abs(cur.x - this.downAt.x) > 4 || Math.abs(cur.y - this.downAt.y) > 4) this.moved = true;
        if (this.dragging) {
            this.x += cur.x - prev.x;
            this.y += cur.y - prev.y;
            this.clampPan();
        }
    },
    onUp(e) {
        const had = this.ptrs.delete(e.pointerId);
        if (!had) return;
        if (this.ptrs.size < 2) this.pinching = false;
        if (this.ptrs.size === 0) {
            const wasDragging = this.dragging;
            this.dragging = false;
            const dx = e.clientX - (this.downAt ? this.downAt.x : e.clientX);
            const dy = e.clientY - (this.downAt ? this.downAt.y : e.clientY);

            // Geser horizontal saat tidak diperbesar = pindah gambar.
            if (!wasDragging && this.scale === 1 && Math.abs(dx) > 60 && Math.abs(dy) < 50) {
                this.go(dx < 0 ? 1 : -1);
                return;
            }
            // Ketuk dua kali (layar sentuh) = zoom bolak-balik.
            if (!this.moved) {
                const now = Date.now();
                if (now - this.lastTap < 300) { this.lastTap = 0; this.toggleZoom(e.clientX, e.clientY); } else { this.lastTap = now; }
            }
        }
    },
    /**
     * Klik pada latar (di luar kotak gambar/video) menutup penampil bila tidak sedang diperbesar/digeser.
     * Memakai koordinat, bukan e.target: dengan pointer capture, event click diarahkan ke panggung
     * sehingga klik pada gambar pun tampak sebagai klik latar.
     */
    onBackdrop(e) {
        if (this.moved || this.scale !== 1) return;
        const el = this.isVideo ? this.$refs.video : this.$refs.img;
        if (el && el.getBoundingClientRect) {
            const r = el.getBoundingClientRect();
            const inside = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
            if (inside) return;
        }
        this.close();
    },
    onKey(e) {
        if (!this.open) return;
        switch (e.key) {
            case 'Escape': e.preventDefault(); this.close(); break;
            case 'ArrowRight': e.preventDefault(); this.go(1); break;
            case 'ArrowLeft': e.preventDefault(); this.go(-1); break;
            case '+': case '=': if (this.canZoom) { e.preventDefault(); this.zoomIn(); } break;
            case '-': case '_': if (this.canZoom) { e.preventDefault(); this.zoomOut(); } break;
            case '0': if (this.canZoom) { e.preventDefault(); this.reset(); } break;
            case 'Tab': this.trapFocus(e); break;
            default: break;
        }
    },
    trapFocus(e) {
        const root = this.$refs && this.$refs.dialog;
        if (!root) return;
        const f = [...root.querySelectorAll('button, a[href], video[controls]')].filter((n) => !n.disabled && n.offsetParent !== null);
        if (!f.length) return;
        const first = f[0];
        const last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    },
});

/** Zoom saat kursor mouse berada di atas gambar (desktop); sentuhan diabaikan. */
export const hoverZoom = (factor = 2) => ({
    factor,
    active: false,
    ox: 50,
    oy: 50,
    get style() {
        return this.active ? `transform: scale(${this.factor}); transform-origin: ${this.ox}% ${this.oy}%;` : '';
    },
    move(e) {
        if (e.pointerType && e.pointerType !== 'mouse') return;
        const r = e.currentTarget.getBoundingClientRect();
        this.ox = clamp(((e.clientX - r.left) / r.width) * 100, 0, 100);
        this.oy = clamp(((e.clientY - r.top) / r.height) * 100, 0, 100);
        this.active = true;
    },
    out() { this.active = false; },
});
