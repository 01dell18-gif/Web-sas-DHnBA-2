/**
 * Logika Formulir & Live Preview Daftar Hadir
 * Generator Absensi dan Berita Acara Ujian - SMK Negeri 1 Klungkung
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Elemen Formulir
    const form = document.getElementById('formAbsensi');
    const classSelect = document.getElementById('class_id');
    const studentsContainer = document.getElementById('studentsContainer');
    const studentLoading = document.getElementById('studentLoading');
    const btnSetAllHadir = document.getElementById('btnSetAllHadir');
    const signatureModeRadios = document.querySelectorAll('input[name="signature_mode"]');
    const canvasContainer = document.getElementById('canvasContainer');
    const manualSignNotice = document.getElementById('manualSignNotice');
    
    // Tanda Tangan Canvas
    const canvas1 = document.getElementById('canvas_ttd_pengawas_1');
    const canvas2 = document.getElementById('canvas_ttd_pengawas_2');
    const hiddenTtd1 = document.getElementById('ttd_pengawas_1_data');
    const hiddenTtd2 = document.getElementById('ttd_pengawas_2_data');
    const btnClear1 = document.getElementById('btnClearTtd1');
    const btnClear2 = document.getElementById('btnClearTtd2');

    let pad1 = null;
    let pad2 = null;

    if (canvas1) {
        pad1 = new SimpleSignaturePad(canvas1, {
            onChange: (dataUrl) => {
                hiddenTtd1.value = dataUrl;
                updatePreviewSignature();
            }
        });
        btnClear1.addEventListener('click', () => pad1.clear());
    }

    if (canvas2) {
        pad2 = new SimpleSignaturePad(canvas2, {
            onChange: (dataUrl) => {
                hiddenTtd2.value = dataUrl;
                updatePreviewSignature();
            }
        });
        btnClear2.addEventListener('click', () => pad2.clear());
    }

    // Toggle Mode Tanda Tangan
    signatureModeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'digital') {
                canvasContainer.style.display = 'block';
                manualSignNotice.style.display = 'none';
            } else {
                canvasContainer.style.display = 'none';
                manualSignNotice.style.display = 'block';
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

    // 3. Ambil Siswa saat Kelas Dipilih
    let activeStudents = [];

    classSelect.addEventListener('change', function() {
        const classId = this.value;
        if (!classId) {
            studentsContainer.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Silakan pilih kelas terlebih dahulu.</div>';
            activeStudents = [];
            updateStudentTablePreview([]);
            updateRekap();
            return;
        }

        studentLoading.style.display = 'block';
        studentsContainer.innerHTML = '';

        fetch('/api/get-students.php?class_id=' + encodeURIComponent(classId))
            .then(res => res.json())
            .then(res => {
                studentLoading.style.display = 'none';
                if (res.success && res.students) {
                    activeStudents = res.students;
                    renderStudentFormInputs(res.students);
                    updateStudentTablePreview(res.students);
                    updateRekap();
                } else {
                    studentsContainer.innerHTML = `<div class="alert alert-danger">${res.message || 'Gagal memuat siswa.'}</div>`;
                }
            })
            .catch(err => {
                studentLoading.style.display = 'none';
                studentsContainer.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan koneksi saat memuat data siswa.</div>';
            });
    });

    // Render baris input kehadiran siswa di formulir
    function renderStudentFormInputs(students) {
        if (students.length === 0) {
            studentsContainer.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Tidak ada siswa aktif di kelas ini.</div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table" style="font-size: 0.85rem;">';
        html += '<thead><tr><th style="width:30px;">No</th><th>Nama Siswa</th><th style="text-align:center;">Status Kehadiran</th></tr></thead><tbody>';

        students.forEach((s, idx) => {
            html += `
                <tr>
                    <td>${idx + 1}</td>
                    <td>
                        <strong>${escapeHtml(s.nama)}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${s.nis ? 'NIS: ' + escapeHtml(s.nis) : ''}</div>
                        <input type="hidden" name="students[${s.id}][id]" value="${s.id}">
                        <input type="hidden" name="students[${s.id}][nama]" value="${escapeHtml(s.nama)}">
                        <input type="hidden" name="students[${s.id}][nis]" value="${escapeHtml(s.nis || '')}">
                    </td>
                    <td style="text-align: center; white-space: nowrap;">
                        <select name="students[${s.id}][status]" class="form-select form-select-sm student-status-select" data-id="${s.id}" style="width: auto; display: inline-block;">
                            <option value="Hadir" selected>Hadir</option>
                            <option value="Dispensasi">Dispensasi</option>
                            <option value="Izin">Izin</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Tidak Hadir">Tidak Hadir (Alpa)</option>
                        </select>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        studentsContainer.innerHTML = html;

        // Pasang event change ke seluruh status select
        document.querySelectorAll('.student-status-select').forEach(select => {
            select.addEventListener('change', function() {
                updateStudentStatusInPreview(this.getAttribute('data-id'), this.value);
                updateRekap();
            });
        });
    }

    // Tombol Cepat: Set Semua Hadir
    if (btnSetAllHadir) {
        btnSetAllHadir.addEventListener('click', function() {
            document.querySelectorAll('.student-status-select').forEach(select => {
                select.value = 'Hadir';
            });
            // Update preview semua
            activeStudents.forEach(s => {
                updateStudentStatusInPreview(s.id, 'Hadir');
            });
            updateRekap();
        });
    }

    // 4. Hitung Rekapitulasi Otomatis
    function updateRekap() {
        const statuses = {
            'Hadir': 0,
            'Dispensasi': 0,
            'Izin': 0,
            'Sakit': 0,
            'Tidak Hadir': 0
        };

        const selects = document.querySelectorAll('.student-status-select');
        selects.forEach(s => {
            if (statuses[s.value] !== undefined) {
                statuses[s.value]++;
            }
        });

        const total = selects.length;

        // Update di Form
        if (document.getElementById('rekapHadirForm')) document.getElementById('rekapHadirForm').textContent = statuses['Hadir'];
        if (document.getElementById('rekapDispensasiForm')) document.getElementById('rekapDispensasiForm').textContent = statuses['Dispensasi'];
        if (document.getElementById('rekapIzinForm')) document.getElementById('rekapIzinForm').textContent = statuses['Izin'];
        if (document.getElementById('rekapSakitForm')) document.getElementById('rekapSakitForm').textContent = statuses['Sakit'];
        if (document.getElementById('rekapTidakHadirForm')) document.getElementById('rekapTidakHadirForm').textContent = statuses['Tidak Hadir'];
        if (document.getElementById('rekapTotalForm')) document.getElementById('rekapTotalForm').textContent = total;

        // Update di Live Preview A4
        if (document.getElementById('pvRekapHadir')) document.getElementById('pvRekapHadir').textContent = statuses['Hadir'];
        if (document.getElementById('pvRekapDispensasi')) document.getElementById('pvRekapDispensasi').textContent = statuses['Dispensasi'];
        if (document.getElementById('pvRekapIzin')) document.getElementById('pvRekapIzin').textContent = statuses['Izin'];
        if (document.getElementById('pvRekapSakit')) document.getElementById('pvRekapSakit').textContent = statuses['Sakit'];
        if (document.getElementById('pvRekapTidakHadir')) document.getElementById('pvRekapTidakHadir').textContent = statuses['Tidak Hadir'];
        if (document.getElementById('pvRekapTotal')) document.getElementById('pvRekapTotal').textContent = total;
    }

    // 5. Sinkronisasi Live Preview A4
    const inputFields = [
        'nama_ujian', 'tahun_ajaran', 'semester', 'tanggal_ujian', 'mata_pelajaran',
        'sesi', 'ruang', 'jam_mulai', 'jam_selesai',
        'nama_pengawas_1', 'nip_pengawas_1', 'nama_pengawas_2', 'nip_pengawas_2'
    ];

    inputFields.forEach(f => {
        const el = document.getElementById(f);
        if (el) {
            el.addEventListener('input', updateLivePreview);
            el.addEventListener('change', updateLivePreview);
        }
    });

    function updateLivePreview() {
        // Nama Ujian & Tahun
        const namaUjian = document.getElementById('nama_ujian').value.trim() || 'SUMATIF AKHIR SEMESTER';
        const tahunAjaran = document.getElementById('tahun_ajaran').value.trim() || '2026/2027';
        document.getElementById('pvNamaUjian').textContent = namaUjian.toUpperCase();
        document.getElementById('pvTahunAjaran').textContent = tahunAjaran;

        // Metadata Ujian
        const tglStr = document.getElementById('tanggal_ujian').value;
        document.getElementById('pvHariTanggal').textContent = formatTanggalIndo(tglStr);
        document.getElementById('pvMataPelajaran').textContent = document.getElementById('mata_pelajaran').value || '-';
        document.getElementById('pvSesi').textContent = document.getElementById('sesi').value || '-';
        document.getElementById('pvRuang').textContent = document.getElementById('ruang').value || '-';
        
        const jamMulai = document.getElementById('jam_mulai').value;
        const jamSelesai = document.getElementById('jam_selesai').value;
        document.getElementById('pvWaktu').textContent = (jamMulai && jamSelesai) ? `${jamMulai} - ${jamSelesai} WITA` : '-';

        // Kelas
        const selectedClassOption = classSelect.options[classSelect.selectedIndex];
        document.getElementById('pvKelas').textContent = (selectedClassOption && classSelect.value) ? selectedClassOption.text : '-';

        // Identitas Pengawas
        const namaP1 = document.getElementById('nama_pengawas_1').value.trim() || 'Pengawas I';
        const nipP1 = document.getElementById('nip_pengawas_1').value.trim();
        document.getElementById('pvNamaPengawas1').textContent = namaP1;
        document.getElementById('pvNipPengawas1').textContent = nipP1 ? 'NIP. ' + nipP1 : 'NIP. -';

        const rawNamaP2 = document.getElementById('nama_pengawas_2').value.trim();
        const nipP2 = document.getElementById('nip_pengawas_2').value.trim();
        const col1 = document.getElementById('pvColPengawas1');
        const col2 = document.getElementById('pvColPengawas2');

        if (rawNamaP2 !== '') {
            if (col2) col2.style.display = 'block';
            if (col1) col1.style.marginLeft = '0';
            document.getElementById('pvNamaPengawas2').textContent = rawNamaP2;
            document.getElementById('pvNipPengawas2').textContent = nipP2 ? 'NIP. ' + nipP2 : 'NIP. -';
        } else {
            if (col2) col2.style.display = 'none';
            if (col1) col1.style.marginLeft = 'auto'; // Pengawas I tetap berada di kanan
        }

        updatePreviewSignature();
    }

    function updateStudentTablePreview(students) {
        const tbody = document.getElementById('pvStudentTbody');

        if (students.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 1.5rem; color:#666;">(Pilih kelas untuk menampilkan daftar siswa)</td></tr>';
            return;
        }

        let html = '';
        students.forEach((s, idx) => {
            html += `
                <tr id="pvStudentRow_${s.id}">
                    <td class="center" style="width: 35px;">${idx + 1}</td>
                    <td class="center" style="width: 85px;">${escapeHtml(s.nis || '-')}</td>
                    <td>${escapeHtml(s.nama)}</td>
                    <td class="center" id="pvStatus_${s.id}" style="font-weight: 600; width: 100px;">Hadir</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function updateStudentStatusInPreview(studentId, status) {
        const el = document.getElementById(`pvStatus_${studentId}`);
        if (el) {
            el.textContent = status;
            if (status === 'Tidak Hadir') {
                el.style.color = '#dc2626';
            } else if (status === 'Sakit' || status === 'Izin') {
                el.style.color = '#ca8a04';
            } else if (status === 'Dispensasi') {
                el.style.color = '#0284c7';
            } else {
                el.style.color = '#000000';
            }
        }
    }

    function updatePreviewSignature() {
        const isDigital = document.querySelector('input[name="signature_mode"]:checked').value === 'digital';
        const imgP1 = document.getElementById('pvSignImg1');
        const imgP2 = document.getElementById('pvSignImg2');
        const rawNamaP2 = document.getElementById('nama_pengawas_2')?.value.trim();

        if (isDigital) {
            if (hiddenTtd1.value) {
                imgP1.src = hiddenTtd1.value;
                imgP1.style.display = 'block';
            } else {
                imgP1.style.display = 'none';
            }

            if (rawNamaP2 && hiddenTtd2.value) {
                imgP2.src = hiddenTtd2.value;
                imgP2.style.display = 'block';
            } else {
                imgP2.style.display = 'none';
            }
        } else {
            // Mode Manual: sembunyikan gambar, sediakan ruang kosong
            imgP1.style.display = 'none';
            imgP2.style.display = 'none';
        }
    }

    // Helper format tanggal Indonesia sederhana
    function formatTanggalIndo(dateStr) {
        if (!dateStr) return '-';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;

        const d = new Date(dateStr);
        const hariArr = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const bulanArr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        const hari = hariArr[d.getDay()] || '';
        const day = parseInt(parts[2], 10);
        const month = bulanArr[parseInt(parts[1], 10)] || '';
        const year = parts[0];

        return `${hari}, ${day} ${month} ${year}`;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

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
            
            // Terapkan zoom dan transform scale agar didukung semua browser
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

    // Inisialisasi awal
    updateLivePreview();
});
