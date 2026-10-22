/**
 * Logika Formulir & Live Preview Berita Acara Ujian
 * Generator Absensi dan Berita Acara Ujian - SMK Negeri 1 Klungkung
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Inisialisasi Tanda Tangan Canvas
    const canvas1 = document.getElementById('canvas_ttd_pengawas_1');
    const canvas2 = document.getElementById('canvas_ttd_pengawas_2');
    const hiddenTtd1 = document.getElementById('ttd_pengawas_1_data');
    const hiddenTtd2 = document.getElementById('ttd_pengawas_2_data');
    const btnClear1 = document.getElementById('btnClearTtd1');
    const btnClear2 = document.getElementById('btnClearTtd2');
    const signatureModeRadios = document.querySelectorAll('input[name="signature_mode"]');
    const canvasContainer = document.getElementById('canvasContainer');
    const manualSignNotice = document.getElementById('manualSignNotice');

    let pad1 = null;
    let pad2 = null;

    if (canvas1) {
        pad1 = new SimpleSignaturePad(canvas1, {
            onChange: (dataUrl) => {
                if (hiddenTtd1) hiddenTtd1.value = dataUrl;
                updatePreviewSignature();
            }
        });
        if (btnClear1) btnClear1.addEventListener('click', () => pad1.clear());
    }

    if (canvas2) {
        pad2 = new SimpleSignaturePad(canvas2, {
            onChange: (dataUrl) => {
                if (hiddenTtd2) hiddenTtd2.value = dataUrl;
                updatePreviewSignature();
            }
        });
        if (btnClear2) btnClear2.addEventListener('click', () => pad2.clear());
    }

    signatureModeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'digital') {
                if (canvasContainer) canvasContainer.style.display = 'block';
                if (manualSignNotice) manualSignNotice.style.display = 'none';
            } else {
                if (canvasContainer) canvasContainer.style.display = 'none';
                if (manualSignNotice) manualSignNotice.style.display = 'block';
            }
            updatePreviewSignature();
        });
    });

    // 2. Mobile Tabs Switcher
    const tabBtns = document.querySelectorAll('.mobile-tab-btn');
    const panelForm = document.getElementById('panelForm');
    const panelPreview = document.getElementById('panelPreview');

    // Pastikan pada layar HP (mobile) saat awal buka, pratinjau disembunyikan
    if (window.innerWidth <= 1023 && panelPreview) {
        panelPreview.style.display = 'none';
        if (panelForm) panelForm.style.display = 'block';
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const tab = this.getAttribute('data-tab');
            if (tab === 'form') {
                if (panelForm) panelForm.style.display = 'block';
                if (panelPreview) panelPreview.style.display = 'none';
            } else {
                if (panelForm) panelForm.style.display = 'none';
                if (panelPreview) {
                    panelPreview.style.display = 'block';
                    updateLivePreview();
                }
            }
        });
    });

    // 3. Pilihan Kelas -> Auto-set Jumlah Peserta Seharusnya
    const classSelect = document.getElementById('class_id');
    const inputSeharusnya = document.getElementById('peserta_seharusnya');
    const inputHadir = document.getElementById('peserta_hadir');
    const inputTidakHadir = document.getElementById('peserta_tidak_hadir');
    const validationAlert = document.getElementById('pesertaValidationAlert');

    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const classId = this.value;
            const opt = this.options[this.selectedIndex];
            const inputNamaKelas = document.getElementById('nama_kelas');
            if (opt && opt.value) {
                if (inputNamaKelas) {
                    inputNamaKelas.value = opt.getAttribute('data-nama') || opt.text.trim();
                }
            }
            if (!classId) {
                updateLivePreview();
                return;
            }

            fetch('/api/get-students.php?class_id=' + encodeURIComponent(classId))
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.count !== undefined) {
                        if (inputSeharusnya) inputSeharusnya.value = res.count;
                        if (inputHadir) inputHadir.value = res.count;
                        if (inputTidakHadir) inputTidakHadir.value = 0;
                        validatePeserta();
                        updateLivePreview();
                    }
                })
                .catch(() => {});
        });
    }

    // Validasi interaktif Peserta: Hadir + Tidak Hadir = Seharusnya
    function validatePeserta() {
        if (!inputSeharusnya || !inputHadir || !inputTidakHadir || !validationAlert) return true;
        const seh = parseInt(inputSeharusnya.value, 10) || 0;
        const had = parseInt(inputHadir.value, 10) || 0;
        const thad = parseInt(inputTidakHadir.value, 10) || 0;

        if (had + thad !== seh) {
            validationAlert.style.display = 'block';
            validationAlert.textContent = `Perhatian: Jumlah Hadir (${had}) + Tidak Hadir (${thad}) = ${had + thad}, tidak sama dengan Peserta Seharusnya (${seh}).`;
            return false;
        } else {
            validationAlert.style.display = 'none';
            return true;
        }
    }

    [inputSeharusnya, inputHadir, inputTidakHadir].forEach(el => {
        if (el) {
            el.addEventListener('input', () => {
                validatePeserta();
                updateLivePreview();
            });
        }
    });

    // 4. Sinkronisasi Live Preview A4 dengan Input & Template Dinamis
    const watchedInputs = [
        'selectTemplate', 'nama_ujian', 'tahun_ajaran', 'tanggal_ujian', 'jam_mulai', 'jam_selesai',
        'mata_pelajaran', 'ruang', 'class_id', 'nama_kelas', 'catatan_pelaksanaan',
        'blangko_berita_acara', 'blangko_daftar_hadir',
        'nama_pengawas_1', 'nip_pengawas_1', 'nama_pengawas_2', 'nip_pengawas_2'
    ];

    watchedInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updateLivePreview);
            el.addEventListener('change', updateLivePreview);
        }
    });

    // Helper untuk interpolasi placeholder editorial
    function renderEditorialHtml(text, vars, boldValues = true) {
        if (!text) return '';

        // Escape HTML dasar
        let escaped = String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        // Ganti setiap [placeholder] dengan nilainya
        for (const [key, val] of Object.entries(vars)) {
            const cleanVal = String(val ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const replacement = boldValues ? '<strong>' + cleanVal + '</strong>' : cleanVal;
            escaped = escaped.split('[' + key + ']').join(replacement);
        }

        // Konversi baris baru menjadi <br>
        return escaped.replace(/\r\n|\r|\n/g, '<br>');
    }

    function updateLivePreview() {
        // Ambil template aktif saat ini
        const templateId = document.getElementById('template_id')?.value;
        const templates = window.EXAM_TEMPLATES || [];
        let tpl = templates.find(t => String(t.id) === String(templateId));
        if (!tpl && templates.length > 0) {
            tpl = templates[0];
        }
        if (!tpl) {
            tpl = {};
        }

        const namaUjian = document.getElementById('nama_ujian')?.value.trim() || tpl.nama_ujian || 'SUMATIF AKHIR SEMESTER';
        const tahunAjaran = document.getElementById('tahun_ajaran')?.value.trim() || tpl.tahun_ajaran || '2026/2027';
        const mapel = document.getElementById('mata_pelajaran')?.value.trim() || '-';
        const ruang = document.getElementById('ruang')?.value.trim() || '-';
        const jamMulai = document.getElementById('jam_mulai')?.value || '07:30';
        const jamSelesai = document.getElementById('jam_selesai')?.value || '09:30';
        const tglStr = document.getElementById('tanggal_ujian')?.value || '';

        const seh = document.getElementById('peserta_seharusnya')?.value || '0';
        const thad = document.getElementById('peserta_tidak_hadir')?.value || '0';
        const had = document.getElementById('peserta_hadir')?.value || '0';

        const blangkoBa = document.getElementById('blangko_berita_acara')?.value || '1';
        const blangkoDh = document.getElementById('blangko_daftar_hadir')?.value || '1';
        const catatan = document.getElementById('catatan_pelaksanaan')?.value.trim() || 'Pelaksanaan ujian berjalan tertib, aman, dan lancar.';

        const namaP1 = document.getElementById('nama_pengawas_1')?.value.trim() || 'Pengawas I';
        const nipP1 = document.getElementById('nip_pengawas_1')?.value.trim() || '';
        const namaP2 = document.getElementById('nama_pengawas_2')?.value.trim() || 'Pengawas II';
        const nipP2 = document.getElementById('nip_pengawas_2')?.value.trim() || '';

        const sekolah = window.SCHOOL_NAME || 'SMK NEGERI 1 KLUNGKUNG';

        // Parse Tanggal Indonesia
        const tglParts = parseDateIndo(tglStr);
        const tglTeks = terbilang(tglParts.tanggal);
        const tahunTeks = terbilang(tglParts.tahun);

        // Ambil Nilai Kelas
        const inputNamaKelas = document.getElementById('nama_kelas');
        let kelasVal = inputNamaKelas ? inputNamaKelas.value.trim() : '';
        if (!kelasVal && classSelect && classSelect.selectedIndex > 0) {
            const opt = classSelect.options[classSelect.selectedIndex];
            kelasVal = opt.getAttribute('data-nama') || opt.text.trim();
        }
        if (!kelasVal) kelasVal = '-';

        // Kumpulan Variabel Pengganti
        const vars = {
            nama_ujian: namaUjian,
            tahun_ajaran: tahunAjaran,
            hari: tglParts.hari,
            tanggal: tglParts.tanggal,
            tanggal_teks: tglTeks,
            tanggal_terbilang: tglTeks,
            bulan: tglParts.bulan,
            tahun: tglParts.tahun,
            tahun_teks: tahunTeks,
            tahun_terbilang: tahunTeks,
            jam_mulai: jamMulai,
            jam_selesai: jamSelesai,
            mata_pelajaran: mapel,
            ruang: ruang,
            kelas: kelasVal,
            Kelas: kelasVal,
            peserta_seharusnya: seh,
            peserta_hadir: had,
            peserta_tidak_hadir: thad,
            blangko_berita_acara: blangkoBa,
            blangko_daftar_hadir: blangkoDh,
            sekolah: sekolah,
            catatan_pelaksanaan: catatan,
            catatan: catatan
        };

        // 1. Judul Dokumen (Mendukung Enter multi-baris)
        const pvDocTitle = document.getElementById('pvDocTitle');
        if (pvDocTitle) {
            const rawTitle = tpl.judul_berita_acara || 'BERITA ACARA\nPENYELENGGARAAN [nama_ujian]\nTAHUN AJARAN [tahun_ajaran]';
            pvDocTitle.innerHTML = renderEditorialHtml(rawTitle, vars, false);
        }

        // 2. KONTEN BERITA ACARA UTUH (WYSIWYG TERSATUKAN)
        const pvContent = document.getElementById('pvBeritaAcaraContent');
        if (pvContent) {
            let fullRedaksi = tpl.redaksi_isi || '';
            if (!fullRedaksi) {
                fullRedaksi = `<p>${tpl.redaksi_pembuka || 'Pada hari ini [hari], tanggal [tanggal] ([tanggal_teks]) bulan [bulan] tahun [tahun] ([tahun_teks]):'}</p>` +
                    `<ol style="margin: 6px 0 12px 24px; padding: 0;">` +
                    `<li style="margin-bottom: 8px;">Telah diselenggarakan <strong>[nama_ujian]</strong> Tahun Ajaran <strong>[tahun_ajaran]</strong> dari pukul <strong>[jam_mulai]</strong> sampai dengan pukul <strong>[jam_selesai]</strong> WITA.` +
                    `<ul style="list-style-type: disc; margin: 4px 0 6px 20px; padding: 0;">` +
                    `<li>Pada Sekolah: <strong>[sekolah]</strong></li>` +
                    `<li>Kelas: <strong>[kelas]</strong></li>` +
                    `<li>Mata Pelajaran: <strong>[mata_pelajaran]</strong></li>` +
                    `<li>Ruang: <strong>[ruang]</strong></li>` +
                    `<li>Jumlah Peserta Seharusnya: <strong>[peserta_seharusnya]</strong> orang</li>` +
                    `<li>Jumlah Peserta yang Tidak Hadir: <strong>[peserta_tidak_hadir]</strong> orang</li>` +
                    `<li>Jumlah Peserta yang Hadir: <strong>[peserta_hadir]</strong> orang</li>` +
                    `</ul></li>` +
                    `<li style="margin-bottom: 8px;">${(tpl.redaksi_poin_2 || '').replace(/^\s*2\.\s*/i, '').replace(/\n/g, '<br>')}</li>` +
                    `<li style="margin-bottom: 8px;"><strong>${(tpl.label_catatan || 'Catatan selama pelaksanaan ujian:').replace(/^\s*3\.\s*/i, '')}</strong><br><em>[catatan_pelaksanaan]</em></li>` +
                    `</ol><p style="margin-top: 15px;">Berita Acara ini dibuat dengan sesungguhnya.</p>`;
            }
            pvContent.innerHTML = renderWysiwygHtml(fullRedaksi, vars);
        }

        // 3. Nama & NIP Pengawas (Tukar: Pengawas II di Kiri, Pengawas I di Kanan; Sembunyikan jika Pengawas II kosong)
        const col1 = document.getElementById('pvColPengawas1');
        const col2 = document.getElementById('pvColPengawas2');
        const pvNama1 = document.getElementById('pvNamaPengawas1');
        const pvNip1 = document.getElementById('pvNipPengawas1');
        const pvNama2 = document.getElementById('pvNamaPengawas2');
        const pvNip2 = document.getElementById('pvNipPengawas2');

        if (pvNama1) pvNama1.textContent = namaP1;
        if (pvNip1) pvNip1.textContent = nipP1 ? 'NIP. ' + nipP1 : 'NIP. -';

        if (namaP2 && namaP2.trim() !== '') {
            if (col2) col2.style.display = 'block';
            if (col1) col1.style.marginLeft = '0';
            if (pvNama2) pvNama2.textContent = namaP2;
            if (pvNip2) pvNip2.textContent = nipP2 ? 'NIP. ' + nipP2 : 'NIP. -';
        } else {
            if (col2) col2.style.display = 'none';
            if (col1) col1.style.marginLeft = 'auto'; // Pengawas I tetap berada di kanan
        }

        updatePreviewSignature();
        updatePage2Metadata();
    }

    function renderWysiwygHtml(htmlTemplate, vars) {
        if (!htmlTemplate) return '';
        let result = htmlTemplate;
        for (const [k, v] of Object.entries(vars)) {
            const regex = new RegExp('\\[' + k + '\\]', 'gi');
            result = result.replace(regex, v);
        }
        return result;
    }

    function updatePreviewSignature() {
        const checkedRadio = document.querySelector('input[name="signature_mode"]:checked');
        const isDigital = checkedRadio ? checkedRadio.value === 'digital' : true;
        const imgP1 = document.getElementById('pvSignImg1');
        const imgP2 = document.getElementById('pvSignImg2');
        const namaP2 = document.getElementById('nama_pengawas_2')?.value.trim() || '';

        if (isDigital) {
            if (hiddenTtd1 && hiddenTtd1.value && imgP1) {
                imgP1.src = hiddenTtd1.value;
                imgP1.style.display = 'block';
            } else if (imgP1) {
                imgP1.style.display = 'none';
            }

            if (namaP2 && hiddenTtd2 && hiddenTtd2.value && imgP2) {
                imgP2.src = hiddenTtd2.value;
                imgP2.style.display = 'block';
            } else if (imgP2) {
                imgP2.style.display = 'none';
            }
        } else {
            if (imgP1) imgP1.style.display = 'none';
            if (imgP2) imgP2.style.display = 'none';
        }
    }

    function terbilang(n) {
        n = Math.abs(parseInt(n, 10)) || 0;
        const huruf = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        if (n < 12) return huruf[n];
        if (n < 20) return terbilang(n - 10) + " belas";
        if (n < 100) return terbilang(Math.floor(n / 10)) + " puluh" + (n % 10 !== 0 ? " " + terbilang(n % 10) : "");
        if (n < 200) return "seratus" + (n - 100 !== 0 ? " " + terbilang(n - 100) : "");
        if (n < 1000) return terbilang(Math.floor(n / 100)) + " ratus" + (n % 100 !== 0 ? " " + terbilang(n % 100) : "");
        if (n < 2000) return "seribu" + (n - 1000 !== 0 ? " " + terbilang(n - 1000) : "");
        if (n < 1000000) return terbilang(Math.floor(n / 1000)) + " ribu" + (n % 1000 !== 0 ? " " + terbilang(n % 1000) : "");
        return String(n);
    }

    function parseDateIndo(dateStr) {
        if (!dateStr) return { hari: '.......', tanggal: '...', bulan: '.........', tahun: '20..' };
        const d = new Date(dateStr);
        const parts = dateStr.split('-');

        const hariArr = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const bulanArr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return {
            hari: hariArr[d.getDay()] || '',
            tanggal: parseInt(parts[2], 10) || '',
            bulan: bulanArr[parseInt(parts[1], 10)] || '',
            tahun: parts[0] || ''
        };
    }

    // 5. Penanganan Foto Bukti Kegiatan & Live Preview Halaman 2
    const inputFoto = document.getElementById('foto_kegiatan');
    const fotoPreviewBox = document.getElementById('fotoPreviewBox');
    const fotoThumbnailsList = document.getElementById('fotoThumbnailsList');
    const fotoCountEl = document.getElementById('fotoCount');
    const btnClearPhotos = document.getElementById('btnClearPhotos');
    const a4Page2 = document.getElementById('a4Page2');
    const pvFotoGrid = document.getElementById('pvFotoGrid');

    let loadedPhotoDataUrls = [];

    // Kompresi dan optimasi gambar di sisi client (HTML5 Canvas)
    // Mengurangi ukuran 5MB-10MB foto kamera smartphone menjadi ~150KB-250KB
    // Mencegah error Chromium ERR_UPLOAD_FILE_CHANGED pada Android
    function compressImageFile(file, maxDimension = 1200, quality = 0.82) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    let w = img.width;
                    let h = img.height;
                    if (w > maxDimension || h > maxDimension) {
                        if (w > h) {
                            h = Math.round((h * maxDimension) / w);
                            w = maxDimension;
                        } else {
                            w = Math.round((w * maxDimension) / h);
                            h = maxDimension;
                        }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, w, h);
                    ctx.drawImage(img, 0, 0, w, h);
                    const dataUrl = canvas.toDataURL('image/jpeg', quality);
                    resolve({
                        name: file.name,
                        size: (Math.round((dataUrl.length * 3 / 4) / 1024)) + ' KB',
                        dataUrl: dataUrl
                    });
                };
                img.onerror = function() {
                    resolve({
                        name: file.name,
                        size: (file.size / 1024).toFixed(1) + ' KB',
                        dataUrl: e.target.result
                    });
                };
                img.src = e.target.result;
            };
            reader.onerror = function() {
                resolve(null);
            };
            reader.readAsDataURL(file);
        });
    }

    if (inputFoto) {
        inputFoto.addEventListener('change', async function() {
            const files = Array.from(this.files || []);
            if (files.length === 0) {
                clearPhotos();
                return;
            }

            if (files.length > 4) {
                alert('Maksimal 4 foto dokumentasi yang dapat dipilih.');
                this.value = '';
                clearPhotos();
                return;
            }

            loadedPhotoDataUrls = [];
            if (fotoThumbnailsList) {
                fotoThumbnailsList.innerHTML = '<div style="grid-column: 1 / -1; font-size: 0.75rem; color: #64748b; padding: 4px;">⏳ Memproses & mengoptimasi foto...</div>';
            }
            if (fotoPreviewBox) fotoPreviewBox.style.display = 'block';
            if (pvFotoGrid) pvFotoGrid.innerHTML = '';

            let loadedList = [];
            for (let idx = 0; idx < files.length; idx++) {
                const res = await compressImageFile(files[idx]);
                if (res) {
                    res.index = idx + 1;
                    loadedList.push(res);
                }
            }

            loadedPhotoDataUrls = loadedList;
            if (fotoThumbnailsList) fotoThumbnailsList.innerHTML = '';

            loadedPhotoDataUrls.forEach((item) => {
                if (fotoThumbnailsList) {
                    const thumb = document.createElement('div');
                    thumb.style.cssText = 'border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px; background: #fff; text-align: center;';
                    thumb.innerHTML = `
                        <img src="${item.dataUrl}" style="width: 100%; height: 60px; object-fit: cover; border-radius: 4px; display: block;" alt="Foto ${item.index}">
                        <div style="font-size: 0.65rem; color: #64748b; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${item.name}">${item.name} (${item.size})</div>
                    `;
                    fotoThumbnailsList.appendChild(thumb);
                }
            });

            if (fotoCountEl) fotoCountEl.textContent = loadedPhotoDataUrls.length;

            const hiddenBase64El = document.getElementById('fotoKegiatanBase64');
            if (hiddenBase64El) {
                hiddenBase64El.value = JSON.stringify(loadedPhotoDataUrls.map(item => item.dataUrl));
            }

            renderPage2Preview();
        });
    }

    if (btnClearPhotos) {
        btnClearPhotos.addEventListener('click', function() {
            clearPhotos();
        });
    }

    function clearPhotos() {
        if (inputFoto) inputFoto.value = '';
        loadedPhotoDataUrls = [];
        const hiddenBase64El = document.getElementById('fotoKegiatanBase64');
        if (hiddenBase64El) hiddenBase64El.value = '';
        if (fotoThumbnailsList) fotoThumbnailsList.innerHTML = '';
        if (fotoPreviewBox) fotoPreviewBox.style.display = 'none';
        if (fotoCountEl) fotoCountEl.textContent = '0';
        if (a4Page2) a4Page2.style.display = 'none';
        const pageSep = document.getElementById('pvPageSeparator');
        if (pageSep) pageSep.style.display = 'none';
        const btnNav2 = document.getElementById('btnNavPage2');
        if (btnNav2) btnNav2.style.display = 'none';
        if (pvFotoGrid) pvFotoGrid.innerHTML = '';
    }

    function renderPage2Preview() {
        if (!a4Page2 || !pvFotoGrid) return;

        const pageSep = document.getElementById('pvPageSeparator');
        const btnNav2 = document.getElementById('btnNavPage2');
        if (loadedPhotoDataUrls.length === 0) {
            a4Page2.style.display = 'none';
            if (pageSep) pageSep.style.display = 'none';
            if (btnNav2) btnNav2.style.display = 'none';
            return;
        }

        const wrapper = document.getElementById('a4Wrapper');
        if (wrapper) {
            wrapper.style.display = 'flex';
            wrapper.style.flexDirection = 'column';
        }

        a4Page2.style.display = 'block';
        if (pageSep) pageSep.style.display = 'flex';
        if (btnNav2) btnNav2.style.display = 'inline-block';
        pvFotoGrid.innerHTML = '';

        const total = loadedPhotoDataUrls.length;
        if (total === 1) {
            pvFotoGrid.style.gridTemplateColumns = '1fr';
        } else {
            pvFotoGrid.style.gridTemplateColumns = 'repeat(2, 1fr)';
        }

        loadedPhotoDataUrls.forEach((item) => {
            const card = document.createElement('div');
            card.style.cssText = 'border: 1px solid #94a3b8; background-color: #f8fafc; padding: 6px; border-radius: 4px; text-align: center;';
            const maxH = total === 1 ? '340px' : (total === 2 ? '220px' : '160px');
            card.innerHTML = `
                <img src="${item.dataUrl}" style="max-width: 100%; max-height: ${maxH}; object-fit: contain; margin: 0 auto; display: block;" alt="Foto ${item.index}">
                <div style="font-size: 8.5pt; color: #334155; margin-top: 4px; font-weight: bold;">
                    Foto ${item.index}: Dokumentasi Pelaksanaan Ujian
                </div>
            `;
            pvFotoGrid.appendChild(card);
        });

        updatePage2Metadata();
    }

    function updatePage2Metadata() {
        const namaUjian = document.getElementById('nama_ujian')?.value.trim() || 'Sumatif Akhir Semester';
        const tahunAjaran = document.getElementById('tahun_ajaran')?.value.trim() || '2026/2027';
        const mapel = document.getElementById('mata_pelajaran')?.value.trim() || '-';
        const ruang = document.getElementById('ruang')?.value.trim() || '-';
        const inputNamaKelas = document.getElementById('nama_kelas');
        let kelasVal = inputNamaKelas ? inputNamaKelas.value.trim() : '-';
        if (!kelasVal && classSelect && classSelect.selectedIndex > 0) {
            const opt = classSelect.options[classSelect.selectedIndex];
            kelasVal = opt.getAttribute('data-nama') || opt.text.trim();
        }
        const tglStr = document.getElementById('tanggal_ujian')?.value || '';
        const tglParts = parseDateIndo(tglStr);

        const elLpNama = document.getElementById('pvLpNamaUjian');
        const elLpMapel = document.getElementById('pvLpMapel');
        const elLpKelas = document.getElementById('pvLpKelas');
        const elLpRuang = document.getElementById('pvLpRuang');
        const elLpTanggal = document.getElementById('pvLpTanggal');

        if (elLpNama) elLpNama.textContent = `${namaUjian} (${tahunAjaran})`;
        if (elLpMapel) elLpMapel.textContent = mapel;
        if (elLpKelas) elLpKelas.textContent = kelasVal || '-';
        if (elLpRuang) elLpRuang.textContent = ruang;
        if (elLpTanggal) elLpTanggal.textContent = `${tglParts.hari}, ${tglParts.tanggal} ${tglParts.bulan} ${tglParts.tahun}`;

        // Sync signatures to page 2 as well
        const namaP1 = document.getElementById('nama_pengawas_1')?.value.trim() || 'Pengawas I';
        const nipP1 = document.getElementById('nip_pengawas_1')?.value.trim() || '';
        const namaP2 = document.getElementById('nama_pengawas_2')?.value.trim() || '';
        const nipP2 = document.getElementById('nip_pengawas_2')?.value.trim() || '';

        const pvNama1_P2 = document.getElementById('pvNamaPengawas1_Page2');
        const pvNip1_P2 = document.getElementById('pvNipPengawas1_Page2');
        const pvNama2_P2 = document.getElementById('pvNamaPengawas2_Page2');
        const pvNip2_P2 = document.getElementById('pvNipPengawas2_Page2');
        const col2_P2 = document.getElementById('pvColPengawas2_Page2');
        const col1_P2 = document.getElementById('pvColPengawas1_Page2');

        if (pvNama1_P2) pvNama1_P2.textContent = namaP1;
        if (pvNip1_P2) pvNip1_P2.textContent = nipP1 ? 'NIP. ' + nipP1 : 'NIP. -';

        if (namaP2 && namaP2.trim() !== '') {
            if (col2_P2) col2_P2.style.display = 'block';
            if (col1_P2) col1_P2.style.marginLeft = '0';
            if (pvNama2_P2) pvNama2_P2.textContent = namaP2;
            if (pvNip2_P2) pvNip2_P2.textContent = nipP2 ? 'NIP. ' + nipP2 : 'NIP. -';
        } else {
            if (col2_P2) col2_P2.style.display = 'none';
            if (col1_P2) col1_P2.style.marginLeft = 'auto';
        }

        // Digital sign image sync to page 2
        const checkedRadio = document.querySelector('input[name="signature_mode"]:checked');
        const isDigital = checkedRadio ? checkedRadio.value === 'digital' : true;
        const imgP1_P2 = document.getElementById('pvSignImg1_Page2');
        const imgP2_P2 = document.getElementById('pvSignImg2_Page2');

        if (isDigital) {
            if (hiddenTtd1 && hiddenTtd1.value && imgP1_P2) {
                imgP1_P2.src = hiddenTtd1.value;
                imgP1_P2.style.display = 'block';
            } else if (imgP1_P2) {
                imgP1_P2.style.display = 'none';
            }

            if (namaP2 && hiddenTtd2 && hiddenTtd2.value && imgP2_P2) {
                imgP2_P2.src = hiddenTtd2.value;
                imgP2_P2.style.display = 'block';
            } else if (imgP2_P2) {
                imgP2_P2.style.display = 'none';
            }
        } else {
            if (imgP1_P2) imgP1_P2.style.display = 'none';
            if (imgP2_P2) imgP2_P2.style.display = 'none';
        }
    }

    // Expose applyExamTemplate ke level global window
    window.applyExamTemplate = function(tplId) {
        const hiddenTpl = document.getElementById('template_id');
        if (hiddenTpl) hiddenTpl.value = tplId;

        const templates = window.EXAM_TEMPLATES || [];
        const tpl = templates.find(t => String(t.id) === String(tplId));
        if (tpl) {
            const elNama = document.getElementById('nama_ujian');
            if (elNama && tpl.nama_ujian) {
                elNama.value = tpl.nama_ujian;
            }
            const elTahun = document.getElementById('tahun_ajaran');
            if (elTahun && tpl.tahun_ajaran) {
                elTahun.value = tpl.tahun_ajaran;
            }
        }
        updateLivePreview();
    };

    // Expose updateLivePreview
    window.updateBeritaAcaraPreview = updateLivePreview;

    // Validasi Submit Formulir & Mencegah Error ERR_UPLOAD_FILE_CHANGED pada Android Chrome
    const formBA = document.getElementById('formBeritaAcara');
    if (formBA) {
        formBA.addEventListener('submit', function(e) {
            const classSelectEl = document.getElementById('class_id');
            if (classSelectEl && !classSelectEl.value) {
                e.preventDefault();
                alert('⚠️ Silakan pilih Kelas Ujian terlebih dahulu.');
                classSelectEl.focus();
                classSelectEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            const inputFotoEl = document.getElementById('foto_kegiatan');
            const hiddenBase64El = document.getElementById('fotoKegiatanBase64');

            // Cek apakah ada foto yang sudah termuat di memori atau dipilih
            if (loadedPhotoDataUrls.length === 0 && (!inputFotoEl.files || inputFotoEl.files.length === 0)) {
                e.preventDefault();
                alert('⚠️ Unggah Foto Bukti Pelaksanaan Ujian wajib diisi minimal 1 foto.');
                inputFotoEl.focus();
                inputFotoEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            // Jika foto sudah terkonversi ke Base64 di memori:
            if (loadedPhotoDataUrls.length > 0 && hiddenBase64El) {
                hiddenBase64El.value = JSON.stringify(loadedPhotoDataUrls.map(item => item.dataUrl));

                // Mencegah error net::ERR_UPLOAD_FILE_CHANGED pada Chromium / Chrome Android:
                // Lepas atribut name dari file input sebelum submit agar browser Chromium
                // tidak mencoba membaca ulang berkas mentah dari storage Android yang dipindahkan/diubah.
                inputFotoEl.removeAttribute('name');

                // Jika pengguna memilih "Tab Baru" (formtarget="_blank"), tab asal tetap aktif,
                // sehingga kembalikan atribut name setelah jeda singkat agar form tetap valid jika di-submit kembali.
                setTimeout(() => {
                    inputFotoEl.setAttribute('name', 'foto_kegiatan[]');
                }, 1200);
            }
        });
    }

    // Navigasi Scroll Halaman Pratinjau (Halaman 1 vs Halaman 2)
    window.scrollToPreviewPage = function(num) {
        const container = document.getElementById('panelPreview');
        const target = num === 1 ? document.getElementById('a4Page') : document.getElementById('a4Page2');
        if (container && target) {
            const headerEl = document.querySelector('.panel-preview-header');
            const headerH = headerEl ? headerEl.offsetHeight : 45;
            const containerRect = container.getBoundingClientRect();
            const targetRect = target.getBoundingClientRect();
            const targetTop = (targetRect.top - containerRect.top) + container.scrollTop - headerH - 12;
            container.scrollTo({ top: Math.max(0, targetTop), behavior: 'smooth' });
        }
    };

    // Fit / Zoom Pratinjau A4
    let previewIsFit = false;
    window.togglePreviewFit = function() {
        const wrapper = document.getElementById('a4Wrapper');
        const container = document.getElementById('panelPreview');
        const btn = document.getElementById('btnZoomToggle');
        const page = document.getElementById('a4Page');
        if (!wrapper || !container) return;

        previewIsFit = !previewIsFit;
        if (previewIsFit) {
            const availWidth = container.clientWidth - 40;
            const naturalWidth = (page ? page.offsetWidth : 0) || 794;
            let scale = availWidth / naturalWidth;
            if (scale > 1) scale = 1;
            if (scale < 0.35) scale = 0.35;
            
            wrapper.style.zoom = scale.toFixed(3);
            if (!('zoom' in document.body.style)) {
                wrapper.style.transform = `scale(${scale.toFixed(3)})`;
                wrapper.style.transformOrigin = 'top center';
            }

            if (btn) {
                btn.innerHTML = '<i class="bi bi-zoom-out"></i> 100%';
                btn.style.background = '#4657D9';
                btn.style.color = '#ffffff';
                btn.title = 'Kembali ke ukuran 100%';
            }
        } else {
            wrapper.style.zoom = '1';
            wrapper.style.transform = 'none';
            if (btn) {
                btn.innerHTML = '<i class="bi bi-aspect-ratio"></i> Pas Lebar';
                btn.style.background = 'rgba(255,255,255,0.15)';
                btn.style.color = '#ffffff';
                btn.title = 'Sesuaikan lebar halaman dengan layar';
            }
        }
    };

    // Sesuaikan zoom saat window di-resize jika mode fit sedang aktif
    window.addEventListener('resize', function() {
        if (previewIsFit) {
            const wrapper = document.getElementById('a4Wrapper');
            const container = document.getElementById('panelPreview');
            const page = document.getElementById('a4Page');
            if (wrapper && container) {
                const availWidth = container.clientWidth - 40;
                const naturalWidth = (page ? page.offsetWidth : 0) || 794;
                let scale = availWidth / naturalWidth;
                if (scale > 1) scale = 1;
                if (scale < 0.35) scale = 0.35;
                wrapper.style.zoom = scale.toFixed(3);
                if (!('zoom' in document.body.style)) {
                    wrapper.style.transform = `scale(${scale.toFixed(3)})`;
                    wrapper.style.transformOrigin = 'top center';
                }
            }
        }
    });

    // Scroll-spy untuk mengupdate warna tombol aktif Halaman 1 & Halaman 2
    const panelPreviewEl = document.getElementById('panelPreview');
    if (panelPreviewEl) {
        panelPreviewEl.addEventListener('scroll', function() {
            const p2 = document.getElementById('a4Page2');
            const btn1 = document.getElementById('btnNavPage1');
            const btn2 = document.getElementById('btnNavPage2');
            if (!btn1 || !btn2 || !p2 || p2.style.display === 'none') return;

            const headerEl = document.querySelector('.panel-preview-header');
            const headerH = headerEl ? headerEl.offsetHeight : 45;
            const p2Rect = p2.getBoundingClientRect();
            const containerRect = panelPreviewEl.getBoundingClientRect();
            const p2TopRelative = p2Rect.top - containerRect.top - headerH;

            if (p2TopRelative <= 150) {
                btn2.style.background = '#ffffff';
                btn2.style.color = '#0f172a';
                btn1.style.background = 'rgba(255,255,255,0.2)';
                btn1.style.color = '#ffffff';
            } else {
                btn1.style.background = '#ffffff';
                btn1.style.color = '#0f172a';
                btn2.style.background = 'rgba(255,255,255,0.2)';
                btn2.style.color = '#ffffff';
            }
        }, { passive: true });
    }

    // Inisialisasi awal
    validatePeserta();
    updateLivePreview();
});

