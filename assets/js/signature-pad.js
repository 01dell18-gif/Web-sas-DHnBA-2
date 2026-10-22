/**
 * Canvas Signature Pad (Touch & Mouse Friendly)
 * Generator Absensi dan Berita Acara Ujian - SMK Negeri 1 Klungkung
 */

class SimpleSignaturePad {
    constructor(canvas, options = {}) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.strokeColor = options.strokeColor || '#000000';
        this.lineWidth = options.lineWidth || 2;
        this.onChange = options.onChange || null;

        this.isDrawing = false;
        this.hasDrawn = false;
        this.points = [];

        this.initCanvas();
        this.bindEvents();
    }

    initCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = this.canvas.getBoundingClientRect();
        
        // Simpan dimensi css
        const width = rect.width || this.canvas.offsetWidth || 300;
        const height = rect.height || this.canvas.offsetHeight || 140;

        this.canvas.width = width * ratio;
        this.canvas.height = height * ratio;
        this.ctx.scale(ratio, ratio);

        this.ctx.strokeStyle = this.strokeColor;
        this.ctx.lineWidth = this.lineWidth;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
    }

    getPos(event) {
        const rect = this.canvas.getBoundingClientRect();
        let clientX, clientY;

        if (event.touches && event.touches.length > 0) {
            clientX = event.touches[0].clientX;
            clientY = event.touches[0].clientY;
        } else {
            clientX = event.clientX;
            clientY = event.clientY;
        }

        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    bindEvents() {
        const start = (e) => {
            e.preventDefault();
            this.isDrawing = true;
            this.hasDrawn = true;
            const pos = this.getPos(e);
            this.ctx.beginPath();
            this.ctx.moveTo(pos.x, pos.y);
        };

        const move = (e) => {
            if (!this.isDrawing) return;
            e.preventDefault();
            const pos = this.getPos(e);
            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();
        };

        const stop = (e) => {
            if (this.isDrawing) {
                this.isDrawing = false;
                this.ctx.closePath();
                if (this.onChange) {
                    this.onChange(this.toDataURL());
                }
            }
        };

        // Mouse Events
        this.canvas.addEventListener('mousedown', start);
        window.addEventListener('mousemove', move);
        window.addEventListener('mouseup', stop);

        // Touch Events
        this.canvas.addEventListener('touchstart', start, { passive: false });
        this.canvas.addEventListener('touchmove', move, { passive: false });
        window.addEventListener('touchend', stop);
        this.canvas.addEventListener('touchcancel', stop);

        // Resize handler (Abaikan perubahan tinggi akibat scroll / address bar browser di HP)
        let lastWindowWidth = window.innerWidth;
        let resizeTimer = null;

        window.addEventListener('resize', () => {
            // Jika lebar layar tidak berubah (hanya tinggi yang berubah akibat scroll/address bar di HP), abaikan!
            if (window.innerWidth === lastWindowWidth) {
                return;
            }
            lastWindowWidth = window.innerWidth;

            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (this.hasDrawn) {
                    const data = this.toDataURL();
                    const prevWidth = this.canvas.width;
                    const prevHeight = this.canvas.height;
                    
                    this.initCanvas();
                    
                    if (data) {
                        const img = new Image();
                        img.onload = () => {
                            // Reset transform sementara untuk menggambar image berukuran pixel fisik
                            this.ctx.save();
                            this.ctx.setTransform(1, 0, 0, 1, 0, 0);
                            this.ctx.drawImage(img, 0, 0, prevWidth, prevHeight, 0, 0, this.canvas.width, this.canvas.height);
                            this.ctx.restore();
                        };
                        img.src = data;
                    }
                } else {
                    this.initCanvas();
                }
            }, 150);
        });
    }

    clear() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        this.ctx.clearRect(0, 0, this.canvas.width / ratio, this.canvas.height / ratio);
        this.hasDrawn = false;
        if (this.onChange) {
            this.onChange('');
        }
    }

    isEmpty() {
        return !this.hasDrawn;
    }

    toDataURL(format = 'image/png') {
        if (!this.hasDrawn) return '';
        return this.canvas.toDataURL(format);
    }
}
