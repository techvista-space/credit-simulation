<?php
// Mencegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

// Memastikan kelas WP_List_Table sudah ada
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Kelas untuk membuat tabel data produk kredit.
 */
class CS_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'Produk Kredit', // Nama item tunggal
            'plural'   => 'Produk Kredit', // Nama item jamak
            'ajax'     => false // Tidak menggunakan AJAX
        ]);
    }

    /**
     * Mendefinisikan kolom-kolom tabel.
     * @return array
     */
    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'product_name'  => 'Nama Produk',
            'interest_rate' => 'Besaran Bunga (%)',
            'interest_type' => 'Jenis Bunga'
        ];
    }

    /**
     * Mendefinisikan kolom yang bisa di-sorting.
     * @return array
     */
    protected function get_sortable_columns() {
        return [
            'product_name'  => ['product_name', true], // true berarti default sorting
            'interest_rate' => ['interest_rate', false],
            'interest_type' => ['interest_type', false]
        ];
    }
    
    /**
     * Mendefinisikan bulk actions.
     * @return array
     */
    protected function get_bulk_actions() {
        return ['delete' => 'Delete'];
    }

    /**
     * Menampilkan checkbox untuk setiap baris.
     * @param array $item Data item
     * @return string
     */
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
    }
    
    /**
     * Menampilkan action link (Edit, Delete) di bawah nama produk.
     * @param array $item Data item
     * @return string
     */
    public function column_product_name($item) {
        $delete_nonce = wp_create_nonce('pk_delete_nonce');
        $actions = [
            'edit'   => sprintf('<a href="?page=%s&action=edit&id=%s">Edit</a>', esc_attr($_REQUEST['page']), $item['id']),
            'delete' => sprintf(
                '<a href="?page=%s&action=delete&id=%s&_wpnonce=%s" onclick="return confirm(\'Apakah Anda yakin ingin menghapus item ini?\')">Delete</a>', 
                esc_attr($_REQUEST['page']), 
                $item['id'],
                $delete_nonce
            )
        ];
        return sprintf('<strong>%1$s</strong> %2$s', $item['product_name'], $this->row_actions($actions));
    }

    /**
     * Menangani output default untuk setiap kolom.
     * @param array $item Data item
     * @param string $column_name Nama kolom
     * @return mixed
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'interest_rate':
                return number_format($item[$column_name], 2) . '%';
            case 'interest_type':
                return ucfirst($item[$column_name]);
            default:
                return print_r($item, true); // Untuk debugging
        }
    }

    /**
     * Fungsi utama untuk mengambil data dari database dan mempersiapkannya untuk tabel.
     */
    public function prepare_items() {
        global $wpdb;

        $per_page = 10;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        // Menangani sorting
        $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'product_name';
        $order = (!empty($_REQUEST['order'])) ? sanitize_key($_REQUEST['order']) : 'asc';
        
        // Menangani pencarian
        $search_term = (!empty($_REQUEST['s'])) ? trim($_REQUEST['s']) : '';
        
        $query = "SELECT * FROM " . CREDIT_PRODUCTS_TABLE;
        if ($search_term) {
            $query .= $wpdb->prepare(" WHERE product_name LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
        }
        
        // Menghitung total item sebelum pagination
        $total_items = $wpdb->get_var( str_replace("SELECT *", "SELECT COUNT(id)", $query) );

        $query .= " ORDER BY $orderby $order";
        
        // Menangani pagination
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);
        
        $this->items = $wpdb->get_results($query, ARRAY_A);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }
}