<?php

function cs_deposito_shortcode() {
    // Jika kita berada di halaman yang benar, muat CSS Bootstrap dari CDN.
    wp_enqueue_style(
        'bootstrap-css', // Handle unik untuk stylesheet
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', // URL ke file CSS
        [], // Tidak ada dependensi
        '5.3.2' // Versi
    );

    ob_start();
    ?>
    <div class="">
        <h1>Simulasi Kalkulator Deposito</h1>
        <div class="mt-4 p-0">
            <div class="card">
                <div class="card-header">
                    Masukkan Detail Deposito
                </div>
                <div class="card-body">
                    <form id="kalkulatorDeposito">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="jumlah_deposito" class="form-label">Jumlah Deposito (Rp)</label>
                                <input type="number" class="form-control" id="jumlah_deposito" placeholder="Contoh: 10000000" required>
                            </div>
                            <div class="col-md-6">
                                <label for="jangka_waktu" class="form-label">Jangka Waktu</label>
                                <select id="jangka_waktu" class="form-select">
                                    <option value="">--- Pilih Jangka Waktu ---</option>
                                    <option value="1">1 Bulan (Bunga 4.75%)</option>
                                    <option value="3">3 Bulan (Bunga 5.00%)</option>
                                    <option value="6">6 Bulan (Bunga 5.50%)</option>
                                    <option value="12">12 Bulan (Bunga 5.75%)</option>
                                    <option value="24">24 Bulan (Bunga 6.00%)</option>
                                </select>
                            </div>
                             <div class="col-12">
                                <label for="pajak" class="form-label">Pajak Bunga (%)</label>
                                <input type="number" step="0.01" class="form-control" id="pajak" value="20" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-danger mt-4">Hitung Simulasi</button>
                    </form>
                </div>
            </div>

            <div id="hasilKalkulasi" class="mt-4 p-4 bg-light border rounded" style="display: none;">
                <h3 class="mb-3">Hasil Simulasi</h3>
                <table class="table">
                    <tbody>
                        <tr>
                            <td>Jumlah Pokok Deposito</td>
                            <td id="hasilPokok" class="text-end fw-bold"></td>
                        </tr>
                         <tr>
                            <td>Suku Bunga Pilihan</td>
                            <td id="hasilBungaPilihan" class="text-end fw-bold"></td>
                        </tr>
                        <tr>
                            <td>Bunga Kotor (Sebelum Pajak)</td>
                            <td id="hasilBungaKotor" class="text-end fw-bold"></td>
                        </tr>
                        <tr>
                            <td>Pajak</td>
                            <td id="hasilPajak" class="text-end fw-bold text-danger"></td>
                        </tr>
                        <tr class="table-success">
                            <td class="fw-bold">Bunga Bersih (Setelah Pajak)</td>
                            <td id="hasilBungaBersih" class="text-end fw-bold fs-5"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Wrapper untuk disklaimer -->
            <div id="cs-disclaimer" class="mt-4 p-3 bg-light">
                <stong>Disclaimer:</strong>
                <p>Simulasi ini hanya untuk tujuan informasi. Hasil yang diberikan tidak mengikat dan dapat berbeda tergantung pada kebijakan bank atau lembaga keuangan.  Untuk informasi lebih lanjut, silakan hubungi ke <span class="fw-bolder text-danger">(024) 3554444</span> atau (WA) <span class="fw-bolder text-danger">0882008708988</span>.</p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('kalkulatorDeposito').addEventListener('submit', function(event) {
            // Mencegah form dari reload halaman
            event.preventDefault();

            // Definisikan suku bunga berdasarkan jangka waktu
            const sukuBunga = {
                '1': 4.75,
                '3': 5.00,
                '6': 5.50,
                '12': 5.75,
                '24': 6.00 // Asumsi untuk 24 bulan
            };

            // Ambil nilai dari input
            const pokok = parseFloat(document.getElementById('jumlah_deposito').value);
            const tenorBulan = parseInt(document.getElementById('jangka_waktu').value);
            const pajakPersen = parseFloat(document.getElementById('pajak').value);
            
            // Dapatkan suku bunga tahunan dari objek sukuBunga
            const bungaTahunan = sukuBunga[tenorBulan];

            // Validasi input
            if (isNaN(pokok) || isNaN(bungaTahunan) || isNaN(tenorBulan) || isNaN(pajakPersen)) {
                alert('Mohon isi semua field dengan angka yang valid.');
                return;
            }

            // Lakukan kalkulasi
            // Rumus: Bunga = Pokok * (Suku Bunga Tahunan / 100) * (Jangka Waktu (bulan) / 12)
            const bungaKotor = pokok * (bungaTahunan / 100) * (tenorBulan / 12);
            const jumlahPajak = bungaKotor * (pajakPersen / 100);
            const bungaBersih = bungaKotor - jumlahPajak;
            
            // Fungsi untuk format angka ke format Rupiah
            const formatRupiah = (angka) => {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(angka);
            };

            // Tampilkan hasil
            document.getElementById('hasilPokok').innerText = formatRupiah(pokok);
            document.getElementById('hasilBungaPilihan').innerText = bungaTahunan.toFixed(2) + '%';
            document.getElementById('hasilBungaKotor').innerText = formatRupiah(bungaKotor);
            document.getElementById('hasilPajak').innerText = '- ' + formatRupiah(jumlahPajak);
            document.getElementById('hasilBungaBersih').innerText = formatRupiah(bungaBersih);

            // Tampilkan blok hasil
            document.getElementById('hasilKalkulasi').style.display = 'block';
        });
    </script>
    <?php
    return ob_get_clean();
}
// Mendaftarkan shortcode
add_shortcode('deposito_simulation', 'cs_deposito_shortcode');
