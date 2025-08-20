<?php
/**
 * Plugin Name:       Simulasi Kredit Perusahaan
 * Plugin URI:        https://www.bprmaa.com/
 * Description:       Plugin untuk menampilkan formulir simulasi kredit di halaman WordPress.
 * Version:           0.1.0
 * Author:            Bagus Tasuru Nadhirin / BPR Mandiri Artha Abadi
 * Author URI:        https://www.bprmaa.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cs
 */
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'deposito-simulation.php';

// Aktivasi: buat tabel untuk produk
register_activation_hook(__FILE__, 'cs_create_table');
function cs_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'credit_products';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_name varchar(100) NOT NULL,
        interest_rate float NOT NULL,
        interest_type varchar(20) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/**
 * 
 * @return bool|string
 */
function cs_form_shortcode() {
    global $wpdb;
    // Ambil semua data produk dari tabel custom
    $produk_list = $wpdb->get_results("SELECT id, product_name, interest_rate, interest_type FROM " . CREDIT_PRODUCTS_TABLE, ARRAY_A);

    ob_start();
    ?>
    <div id="cs-calculator-wrapper">
        <h3>Simulasi Kredit</h3>
        <form id="cs-credit-form">
            <div class="row mb-3">
                <div class="form-group mb-3 col-md-6">
                    <label for="jumlah_pinjaman">Jumlah Pinjaman (Rp)</label>
                    <input type="number" class="form-control" id="jumlah_pinjaman" name="jumlah_pinjaman" required>
                </div>
                <div class="form-group mb-3 col-md-6">
                    <label for="produk_kredit_pilihan">Pilih Produk</label>
                    <select name="produk_kredit_pilihan" class="form-select my-1 mr-sm-2" id="produk_kredit_pilihan">
                        <option value="">-- Pilih Produk --</option>
                        <?php if (!empty($produk_list)) : ?>
                            <?php foreach ($produk_list as $produk) : ?>
                                <?php
                                    // Format teks untuk option: Nama Produk (Besaran Bunga%)
                                    $option_text = sprintf(
                                        '%s - (%s%%) - %s',
                                        $produk['product_name'],
                                        number_format($produk['interest_rate'], 2, ',', ''),
                                        $produk['interest_type'] // Menambahkan tipe bunga
                                    );
                                ?>
                                <option 
                                    value="<?php echo esc_attr($produk['id']); ?>"
                                    data-interest-rate="<?php echo esc_attr($produk['interest_rate']); ?>"
                                    data-interest-type="<?php echo esc_attr($produk['interest_type']); ?>"
                                >
                                    <?php echo esc_html($option_text); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group mb-3 col-md-6">
                    <label for="interest_rate">Bunga</label>
                    <input type="number" class="form-control bg-light" step="0.1" name="interest_rate" id="interest_rate" value="" readonly>
                </div>
                <div class="form-group mb-3 col-md-6">
                    <label for="interest_type">Tipe Bunga</label>
                    <input type="text" class="form-control bg-light" name="interest_type" id="interest_type" value="" readonly>
                </div>
                <div class="form-group mb-3 col-md-6">
                    <label for="jangka_waktu">Jangka Waktu (Bulan)</label>
                    <input type="number" class="form-control" id="jangka_waktu" name="jangka_waktu" required>
                </div>
            </div>
            <button type="submit" class="btn btn-danger">Hitung Simulasi</button>
        </form>
        
        <div id="cs-result-wrapper" style="display:none; margin-top: 20px;">
            <div id="cs-summary-info" class="row mb-3">
                <div class="col-md-4">
                    <p class="text-muted">Angsuran Cicilan per Bulan (Rp)</p>
                    <p id="cs-monthly-payment" class="text-danger fs-2 text-danger fw-bolder"></p>
                </div>
                <div class="col-md-8">
                    <p class="fw-bolder text-uppercase text-danger">Rincian</p>
                    <div id="cs-loan-details"></div>
                </div>
            </div>
            <div class="table-responsive">
                <div id="cs-amortization-table"></div>
            </div>
        </div>

        <!-- Wrapper untuk disklaimer -->
        <div id="cs-disclaimer" class="mt-4 p-3 bg-light">
            <stong>Disclaimer:</strong>
            <p>Simulasi ini hanya untuk tujuan informasi. Hasil yang diberikan tidak mengikat dan dapat berbeda tergantung pada kebijakan bank atau lembaga keuangan.  Untuk informasi lebih lanjut, silakan hubungi ke <span class="fw-bolder text-danger">(024) 3554444</span> atau (WA) <span class="fw-bolder text-danger">0882008708988</span>.</p>
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

        // Jika kita berada di halaman yang benar, muat CSS Bootstrap dari CDN.
        wp_enqueue_style(
            'bootstrap-css', // Handle unik untuk stylesheet
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', // URL ke file CSS
            [], // Tidak ada dependensi
            '5.3.2' // Versi
        );
        
        // wp_enqueue_style(
        //     'cs-style-css',
        //     plugin_dir_url(__FILE__) . 'assets/css/style.css',
        //     array(),
        //     '1.0.0'
        // );

        wp_enqueue_script(
            'cs-calculator-js',
            plugin_dir_url(__FILE__) . 'assets/js/calculator.js',
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
    $rate_tahunan = isset($_POST['interest_rate']) ? floatval($_POST['interest_rate']) : 0;
    $n = isset($_POST['jangka_waktu']) ? intval($_POST['jangka_waktu']) : 0;
    $interest_type = isset($_POST['interest_type']) ? sanitize_text_field($_POST['interest_type']) : 'flat';

    if ($p <= 0 || $rate_tahunan <= 0 || $n <= 0) {
        wp_send_json_error(['message' => 'Semua field harus diisi dengan angka yang valid.']);
        return;
    }

    $rate_bulanan = ($rate_tahunan / 12) / 100;
    $tabel_angsuran = [];
    $sisa_pinjaman = $p;
    $angsuran_bulanan_text = '';

    // Logika perhitungan berdasarkan jenis bunga
    switch ($interest_type) {
        
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
            'interest_rate' => $rate_tahunan . '%',
            'interest_type' => ucfirst($interest_type) // Mengubah 'flat' menjadi 'Flat'
        ]
    ];
    wp_send_json_success($response_data);
}
// Menangani permintaan AJAX untuk kalkulasi kredit
add_action('wp_ajax_cs_calculate_credit', 'cs_calculate_credit_ajax_handler');
add_action('wp_ajax_nopriv_cs_calculate_credit', 'cs_calculate_credit_ajax_handler');