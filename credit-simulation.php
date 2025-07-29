<?php
/**
 * Plugin Name:       Simulasi Kredit Perusahaan
 * Plugin URI:        https://www.tecvistamedia.com/
 * Description:       Plugin untuk menampilkan formulir simulasi kredit di halaman WordPress.
 * Version:           0.1.0
 * Author:            Bagus Tasuru Nadhirin / TechVista Media Indonesia
 * Author URI:        https://www.tecvistamedia.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cs
 */

/**
 * Membuat shortcode untuk menampilkan formulir.
 */
function cs_form_shortcode() {
    ob_start();
    ?>
    <div id="cs-calculator-wrapper">
        <h3>Simulasi Kredit</h3>
        <form id="cs-credit-form">
            <p>
                <label for="jumlah_pinjaman">Jumlah Pinjaman (Rp)</label><br>
                <input type="number" id="jumlah_pinjaman" name="jumlah_pinjaman" required>
            </p>
            <p>
                <label for="suku_bunga">Suku Bunga Tahunan (%)</label><br>
                <input type="number" step="0.1" id="suku_bunga" name="suku_bunga" required>
            </p>
            <p>
                <label for="jangka_waktu">Jangka Waktu (Bulan)</label><br>
                <input type="number" id="jangka_waktu" name="jangka_waktu" required>
            </p>
            
            <p>
                <label for="tipe_bunga">Tipe Bunga</label><br>
                <select id="tipe_bunga" name="tipe_bunga">
                    <option value="flat">Flat</option>
                    <option value="efektif">Efektif</option>
                    <option value="anuitas">Anuitas</option>
                </select>
            </p>
            <p>
                <button type="submit">Hitung Simulasi</button>
            </p>
        </form>
        
        <!-- <div id="cs-result-wrapper" style="display:none; margin-top: 20px;">
            <h4>Hasil Simulasi:</h4>
            <p><strong>Total Angsuran per Bulan:</strong> <span id="cs-monthly-payment"></span></p>
            <div id="cs-amortization-table"></div>
        </div> -->

        <div id="cs-result-wrapper" style="display:none; margin-top: 20px;">
            <div id="cs-summary-info" class="summary-container">
                <div class="summary-left">
                    <span class="summary-label">Angsuran Cicilan per Bulan (Rp)</span>
                    <span id="cs-monthly-payment" class="summary-amount"></span>
                </div>
                <div class="summary-right">
                    <span class="summary-title">TOTAL ANGSURAN PER BULAN</span>
                    <div id="cs-loan-details"></div>
                </div>
            </div>
            <div id="cs-amortization-table"></div>
        </div>

        <!-- Wrapper untuk disklaimer -->
        <div id="cs-disclaimer" class="cs-disclaimer">
            <stong>Disclaimer:</strong>
            <p>Simulasi ini hanya untuk tujuan informasi. Hasil yang diberikan tidak mengikat dan dapat berbeda tergantung pada kebijakan bank atau lembaga keuangan.</p>
            <p></p>Untuk informasi lebih lanjut, silakan hubungi bank atau lembaga keuangan terkait.</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
// Mendaftarkan shortcode
add_shortcode('credit_simulation', 'cs_form_shortcode');

/**
 * Mendaftarkan file CSS dan JavaScript.
 */
function cs_enqueue_scripts() {
    if ( is_a( get_post( get_the_ID() ), 'WP_Post' ) && has_shortcode( get_post( get_the_ID() )->post_content, 'credit_simulation') ) {
        
        wp_enqueue_style(
            'cs-style-css',
            plugin_dir_url(__FILE__) . 'css/style.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'cs-calculator-js',
            plugin_dir_url(__FILE__) . 'js/calculator.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('cs-calculator-js', 'cs_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cs_credit_calculator_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'cs_enqueue_scripts');

/**
 * Menangani kalkulasi via AJAX.
 */
function cs_calculate_credit_ajax_handler() {
    check_ajax_referer('cs_credit_calculator_nonce', 'nonce');

    // Ambil semua input
    $p = isset($_POST['jumlah_pinjaman']) ? floatval($_POST['jumlah_pinjaman']) : 0;
    $rate_tahunan = isset($_POST['suku_bunga']) ? floatval($_POST['suku_bunga']) : 0;
    $n = isset($_POST['jangka_waktu']) ? intval($_POST['jangka_waktu']) : 0;
    $tipe_bunga = isset($_POST['tipe_bunga']) ? sanitize_text_field($_POST['tipe_bunga']) : 'flat';

    if ($p <= 0 || $rate_tahunan <= 0 || $n <= 0) {
        wp_send_json_error(['message' => 'Semua field harus diisi dengan angka yang valid.']);
        return;
    }

    $rate_bulanan = ($rate_tahunan / 12) / 100;
    $tabel_angsuran = [];
    $sisa_pinjaman = $p;
    $angsuran_bulanan_text = '';

    // ... (Logika switch case untuk 'flat', 'efektif', 'anuitas' tetap sama) ...
    // ... (Tidak perlu diubah) ...
    switch ($tipe_bunga) {
        
        case 'flat':
            $angsuran_pokok_flat = $p / $n;
            $angsuran_bunga_flat = $p * $rate_bulanan;
            $total_angsuran_flat = $angsuran_pokok_flat + $angsuran_bunga_flat;
            $angsuran_bulanan_text = number_format($total_angsuran_flat, 2, ',', '.');

            for ($i = 1; $i <= $n; $i++) {
                $sisa_pinjaman -= $angsuran_pokok_flat;
                $tabel_angsuran[] = [
                    'periode' => date('M Y', strtotime("+$i month")),
                    'angsuran_bunga' => number_format($angsuran_bunga_flat, 2, ',', '.'),
                    'angsuran_pokok' => number_format($angsuran_pokok_flat, 2, ',', '.'),
                    'total_angsuran' => number_format($total_angsuran_flat, 2, ',', '.'),
                    'sisa_pinjaman' => number_format($sisa_pinjaman > 0 ? $sisa_pinjaman : 0, 2, ',', '.')
                ];
            }
            break;

        case 'efektif':
            $angsuran_pokok_efektif = $p / $n;
            $angsuran_bulanan_text = '(Bervariasi)';

            for ($i = 1; $i <= $n; $i++) {
                $angsuran_bunga_efektif = $sisa_pinjaman * $rate_bulanan;
                $total_angsuran_efektif = $angsuran_pokok_efektif + $angsuran_bunga_efektif;
                $sisa_pinjaman -= $angsuran_pokok_efektif;
                $tabel_angsuran[] = [
                    'periode' => date('M Y', strtotime("+$i month")),
                    'angsuran_bunga' => number_format($angsuran_bunga_efektif, 2, ',', '.'),
                    'angsuran_pokok' => number_format($angsuran_pokok_efektif, 2, ',', '.'),
                    'total_angsuran' => number_format($total_angsuran_efektif, 2, ',', '.'),
                    'sisa_pinjaman' => number_format($sisa_pinjaman > 0 ? $sisa_pinjaman : 0, 2, ',', '.')
                ];
            }
            break;
            
        case 'anuitas':
            $pangkat = pow(1 + $rate_bulanan, $n);
            $total_angsuran_anuitas = $p * ($rate_bulanan * $pangkat) / ($pangkat - 1);
            if(is_infinite($total_angsuran_anuitas) || is_nan($total_angsuran_anuitas)) {
                 wp_send_json_error(array('message' => 'Terjadi kesalahan kalkulasi anuitas.')); return;
            }
            $angsuran_bulanan_text = number_format($total_angsuran_anuitas, 2, ',', '.');
            
            for ($i = 1; $i <= $n; $i++) {
                $angsuran_bunga_anuitas = $sisa_pinjaman * $rate_bulanan;
                $angsuran_pokok_anuitas = $total_angsuran_anuitas - $angsuran_bunga_anuitas;
                $sisa_pinjaman -= $angsuran_pokok_anuitas;
                $tabel_angsuran[] = [
                    'periode' => date('M Y', strtotime("+$i month")),
                    'angsuran_bunga' => number_format($angsuran_bunga_anuitas, 2, ',', '.'),
                    'angsuran_pokok' => number_format($angsuran_pokok_anuitas, 2, ',', '.'),
                    'total_angsuran' => number_format($total_angsuran_anuitas, 2, ',', '.'),
                    'sisa_pinjaman' => number_format($sisa_pinjaman > 0 ? $sisa_pinjaman : 0, 2, ',', '.')
                ];
            }
            break;
    }

    // Kirim response
    $response_data = [
        'angsuran_bulanan' => $angsuran_bulanan_text,
        'tabel_angsuran'   => $tabel_angsuran,
        //  DATA BARU UNTUK RINGKASAN
        'info_pinjaman'    => [
            'nominal' => 'Rp ' . number_format($p, 0, ',', '.'),
            'jangka_waktu' => $n . ' Bulan',
            'suku_bunga' => $rate_tahunan . '%',
            'tipe_bunga' => ucfirst($tipe_bunga) // Mengubah 'flat' menjadi 'Flat'
        ]
    ];
    wp_send_json_success($response_data);
}
// Hook tetap sama
add_action('wp_ajax_cs_calculate_credit', 'cs_calculate_credit_ajax_handler');
add_action('wp_ajax_nopriv_cs_calculate_credit', 'cs_calculate_credit_ajax_handler');