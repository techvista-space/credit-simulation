<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Mendefinisikan konstanta untuk nama tabel
global $wpdb;
define('CREDIT_PRODUCTS_TABLE', $wpdb->prefix . 'credit_products');

// Include file kelas WP_List_Table
require_once(plugin_dir_path(__FILE__) . '../includes/class-list-product.php');

class Credit_Products_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'credit_product',
            'plural'   => 'credit_products',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'product_name'  => 'Nama Produk',
            'interest_rate' => 'Bunga (%)',
            'interest_type' => 'Jenis'
        ];
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="product[]" value="%s" />', $item['id']);
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'credit_products';
        
        $data = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        $this->items = $data;
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'product_name':
            case 'interest_rate':
            case 'interest_type':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }
}

/**
 * Summary of cs_admin_menu
 * @return void
 */
function cs_admin_menu() {
    add_menu_page(
        'Credit Simulation',
        'Credit Simulation',
        'manage_options',
        'credit-simulation',
        'cs_page_content',
        'dashicons-calculator',
        20
    );
}
add_action('admin_menu', 'cs_admin_menu');

/**
 * Kaitkan fungsi pemroses form ke admin_init.
 * Ini adalah perbaikan utama untuk error "headers already sent".
 */
add_action('admin_init', 'cs_handle_actions');

function cs_handle_actions() {
    global $wpdb;

    // Cek apakah kita berada di halaman plugin kita
    if (!isset($_REQUEST['page']) || $_REQUEST['page'] !== 'credit-simulation') {
        return;
    }

    $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';
    $item_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

    // Menangani proses penyimpanan data (Add/Edit)
    if (isset($_POST['submit'])) {
        if (!isset($_POST['pk_form_nonce']) || !wp_verify_nonce($_POST['pk_form_nonce'], 'pk_form_action')) {
            die('Security check failed');
        }

        $id_to_process = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $nama = sanitize_text_field($_POST['product_name']);
        $bunga = floatval($_POST['interest_rate']);
        $jenis = sanitize_text_field($_POST['interest_type']);

        $data = ['product_name' => $nama, 'interest_rate' => $bunga, 'interest_type' => $jenis];
        $format = ['%s', '%f', '%s'];

        if ($id_to_process > 0) { // Update
            $wpdb->update(CREDIT_PRODUCTS_TABLE, $data, ['id' => $id_to_process], $format, ['%d']);
        } else { // Insert
            $wpdb->insert(CREDIT_PRODUCTS_TABLE, $data, $format);
        }
        
        wp_redirect(admin_url('admin.php?page=credit-simulation&message=saved'));
        exit;
    }

    // Menangani proses bulk action delete
    if ((isset($_POST['action']) && $_POST['action'] == 'delete') || (isset($_POST['action2']) && $_POST['action2'] == 'delete')) {
        if (isset($_POST['id']) && is_array($_POST['id'])) {
            $ids_to_delete = array_map('intval', $_POST['id']);
            if (!empty($ids_to_delete)) {
                $ids_placeholder = implode(',', array_fill(0, count($ids_to_delete), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM " . CREDIT_PRODUCTS_TABLE . " WHERE id IN ($ids_placeholder)", $ids_to_delete));
                wp_redirect(admin_url('admin.php?page=credit-simulation&message=deleted'));
                exit;
            }
        }
    }
    
    // Menangani proses single delete dari row action
    if ($action === 'delete' && $item_id > 0) {
        if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'pk_delete_nonce')) {
             $wpdb->delete(CREDIT_PRODUCTS_TABLE, ['id' => $item_id], ['%d']);
             wp_redirect(admin_url('admin.php?page=credit-simulation&message=deleted'));
             exit;
        }
    }
}

/**
 * Menampilkan konten halaman (router).
 * Fungsi ini sekarang hanya menampilkan halaman, tidak memproses data.
 */
function cs_page_content() {
    $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : 'list';
    $item_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

    switch ($action) {
        case 'add':
        case 'edit':
            cs_form_page($item_id);
            break;
        default:
            cs_list_page();
            break;
    }
}

/**
 * Menampilkan halaman daftar produk (tabel).
 */
function cs_list_page() {
    $list_table = new CS_List_Table();
    $list_table->prepare_items();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Produk Kredit</h1>
        <a href="?page=<?php echo esc_attr($_REQUEST['page']); ?>&action=add" class="page-title-action">Tambah Baru</a>
        
        <?php if (isset($_GET['message'])): ?>
            <div id="message" class="updated notice is-dismissible">
                <?php if ($_GET['message'] == 'saved'): ?>
                    <p>Data produk berhasil disimpan.</p>
                <?php elseif ($_GET['message'] == 'deleted'): ?>
                    <p>Data produk berhasil dihapus.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>">
            <?php
            $list_table->search_box('Cari Produk', 'search_id');
            $list_table->display();
            ?>
        </form>
    </div>
    <!-- DEskripsi nama shortcode -->
    <div class="wrap">
        <h2>Deskripsi Shortcode</h2>
        <p>Gunakan shortcode <code>[credit_simulation]</code> untuk menampilkan kalkulator kredit di halaman atau postingan Anda.</p>
        <p>Gunakan shortcode <code>[deposito_simulation]</code> untuk menampilkan kalkulator deposito di halaman atau postingan Anda.</p>
    <?php
}

/**
 * Menampilkan halaman form tambah/edit.
 */
function cs_form_page($item_id = 0) {
    global $wpdb;

    // Ambil data yang ada jika ini adalah halaman edit
    $item = ['id' => 0, 'product_name' => '', 'interest_rate' => '', 'interest_type' => 'flat'];
    if ($item_id > 0) {
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . CREDIT_PRODUCTS_TABLE . " WHERE id = %d", $item_id), ARRAY_A);
        if (!$item) { // Jika ID tidak ditemukan, reset ke form tambah baru
            $item = ['id' => 0, 'product_name' => '', 'interest_rate' => '', 'interest_type' => 'flat'];
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo $item_id > 0 ? 'Edit Produk Kredit' : 'Tambah Produk Kredit'; ?></h1>
        <form method="post">
            <input type="hidden" name="id" value="<?php echo $item_id; ?>">
            <?php wp_nonce_field('pk_form_action', 'pk_form_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="product_name">Nama Produk</label></th>
                        <td><input name="product_name" type="text" id="product_name" value="<?php echo esc_attr($item['product_name']); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="interest_rate">Besaran Bunga (%)</label></th>
                        <td><input name="interest_rate" type="number" step="0.01" id="interest_rate" value="<?php echo esc_attr($item['interest_rate']); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="interest_type">Jenis Bunga</label></th>
                        <td>
                            <select name="interest_type" id="interest_type">
                                <option value="flat" <?php selected($item['interest_type'], 'flat'); ?>>Flat</option>
                                <option value="efektif" <?php selected($item['interest_type'], 'efektif'); ?>>Efektif</option>
                                <option value="anuitas" <?php selected($item['interest_type'], 'anuitas'); ?>>Anuitas</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <a href="<?php echo admin_url('admin.php?page=credit-simulation'); ?>" class="button">Batal</a>
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Simpan Data">
            </p>
        </form>
    </div>
    <?php
}