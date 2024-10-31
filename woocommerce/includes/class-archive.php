<?php

namespace SabaPayamak;

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WoocommerceIR_SMS_Archive_List_Table extends \WP_List_Table
{

	public static $table = 'woocommerce_ir_sms_archive';

	public function __construct()
	{

		parent::__construct(array(
			'singular' => 'آرشیو پیامک های ووکامرس',
			'plural'   => 'آرشیو پیامک های ووکامرس',
			'ajax'     => false
		));
	}

	public function no_items()
	{
		echo 'موردی یافت نشد.';
	}

	public function get_sortable_columns()
	{
		$sortable_columns = array(
			'post_id'  => array('post_id', false),
			'type'     => array('type', false),
			'sender'   => array('sender', false),
			'reciever' => array('reciever', false),
			'date'     => array('date', false),
		);

		return $sortable_columns;
	}

	public function get_columns()
	{
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'date'     => 'زمان',
			'post_id'  => 'سفارش / محصول',
			'type'     => 'نوع پیام',
			'message'  => 'متن پیام',
			'reciever' => 'گیرندگان',
			// 'sender'   => 'وبسرویس',
			'result'   => 'نتیجه',
		);

		return $columns;
	}

	public function column_default($item, $column_name)
	{
		$align = is_rtl() ? 'right' : 'left';
		switch ($column_name) {

			case 'sender':
			case 'reciever':
				$value = Helpers::replace_digits_en2fa($item[$column_name]);
				return '<div style="direction:ltr !important;text-align:' . $align . ';">' . $value . '</div>';
				break;

			case 'message':
				$value = $item[$column_name];
				return "<p class='message'>$value</p>";

			default:
				if (is_string($item[$column_name])) {
					return nl2br($item[$column_name]);
				}

				return print_r($item[$column_name], true);
				break;
		}
	}

	public function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="item[]" value="%s" />',
			$item['id']
		);
	}

	public function column_post_id($item)
	{

		if (empty($item['post_id'])) {
			return '-';
		}

		$post_id   = intval($item['post_id']);
		$post_type = get_post_type($post_id);

		$value = array();
		switch ($post_type) {

			case 'shop_order':
				$edit_title   = 'مدیریت سفارش';
				$filter_title = 'مشاهده آرشیو پیامک های این سفارش';
				$value[]      = 'سفارش #' . Helpers::replace_digits_en2fa($post_id);
				break;

			case 'product':
				$edit_title   = 'مدیریت محصول';
				$filter_title = 'مشاهده آرشیو پیامک های این محصول';
				$value[]      = 'محصول';
				$value[]      = get_the_title($post_id);
				break;

			default:
				return '-';
		}

		$actions = array(
			'delete' => sprintf('<a target="_blank" href="%s">%s</a>', get_edit_post_link($post_id), $edit_title),
		);

		$post_id = '<a title="' . $filter_title . '" href="' . add_query_arg(array('id' => $post_id)) . '">' . implode(' :: ', $value) . '</a>';

		return sprintf('%1$s %2$s', $post_id, $this->row_actions($actions));
	}

	public function column_result($item)
	{

		$result = !empty($item['result']) ? $item['result'] : '';
		if (trim($result) == '_ok_') {
			$result = 'پیامک با موفقیت ارسال شد.';
		}

		return $result;
	}

	public function column_type($item)
	{

		if (empty($item['type'])) {
			return '-';
		}

		switch ($item['type']) {

			case '1':
				$value = 'ارسال دسته جمعی';
				break;

				/*مشتری*/
			case '2':
				$value = 'مشتری - خودکار - سفارش';
				break;

			case '3':
				$value = 'مشتری - دستی - متاباکس سفارش';
				break;

				/*مدیر کل*/
			case '4':
				$value = 'مدیر کل - خودکار - سفارش';
				break;

				/* مدیر محصول*/
			case '5':
				$value = 'مدیر محصول - خودکار - سفارش';
				break;

			case '6':
				$value = 'مدیر محصول - دستی - متاباکس محصول';
				break;

				/*مشترک مدیر کل و مدیر محصول*/
			case '7':
				$value = 'مدیران - خودکار - ناموجود شدن';
				break;

			case '8':
				$value = 'مدیران - خودکار - کم بودن موجودی';
				break;

				/*خبرنامه*/
			case '9':
				$value = 'خبرنامه - حراج شدن - اتوماتیک';
				break;

			case '10':
				$value = 'خبرنامه - حراج شدن - دستی';
				break;
				/*--*/
			case '11':
				$value = 'خبرنامه - موجود شدن - اتوماتیک';
				break;

			case '12':
				$value = 'خبرنامه - موجود شدن - دستی';
				break;
				/*--*/
			case '13':
				$value = 'خبرنامه - کم بودن موجودی - اتوماتیک';
				break;

			case '14':
				$value = 'خبرنامه - کم بودن موجودی - دستی';
				break;
				/*--*/
			case '15':
				$value = 'خبرنامه - گزینه های دلخواه - دستی';
				break;

			default:
				$value = '';
		}

		return $value;
	}

	public function column_date($item)
	{

		$delete_nonce = wp_create_nonce('pwoosms_delete_archive');

		$url = add_query_arg(array(
			'action'   => 'delete',
			'item'     => absint($item['id']),
			'_wpnonce' => $delete_nonce
		));

		$actions = array(
			'delete' => sprintf('<a href="%s">%s</a>', $url, 'حذف'),
		);

		$date = date_i18n('Y-m-d H:i:s', strtotime($item['date']));
		$date = Helpers::replace_digits_en2fa(PWooSMS()->mayBeJalaliDate($date,));

		return sprintf('%1$s %2$s', $date, $this->row_actions($actions));
	}

	public function prepare_items()
	{
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->process_bulk_action();

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$total_items  = $this->record_count();

		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		));
		$this->items = $this->get_items($per_page, $current_page);
	}

	public function get_bulk_actions()
	{
		$actions = array(
			'bulk_delete' => 'حذف'
		);

		return $actions;
	}

	public function process_bulk_action()
	{

		$action = $this->current_action();

		if ('delete' === $action) {

			if (!empty($_REQUEST) && !wp_verify_nonce(sanitize_text_field($_REQUEST['_wpnonce']), 'pwoosms_delete_archive')) {
				die('خطایی رخ داده است. بعدا تلاش کنید.');
			}

			$this->delete_item(absint($_REQUEST['item']));

			echo '<div class="updated notice is-dismissible below-h2"><p>آیتم حذف شد.</p></div>';
		} else if ($action == 'bulk_delete') {

			if (!empty($_REQUEST) && !wp_verify_nonce(sanitize_text_field($_REQUEST['_wpnonce']), 'bulk-' . $this->_args['plural'])) {
				die('خطایی رخ داده است. بعدا تلاش کنید.');
			}

			$delete_ids = isset($_REQUEST['item']) ? array_filter(array_map('absint',esc_sql($_REQUEST['item']))) : array();
			foreach ((array) $delete_ids as $id) {
				$this->delete_item($id);
			}

			echo '<div class="updated notice is-dismissible below-h2"><p>آیتم ها حذف شدند.</p></div>';
		}
	}

	/*--------------------------------------------*/
	public static function table()
	{
		global $wpdb;

		return $wpdb->prefix . self::$table;
	}

	private function table_exists()
	{
		global $wpdb;

		return $wpdb->get_var("SHOW TABLES LIKE '" . self::table() . "'");
	}

	private function get_query($count = false)
	{

		global $wpdb;

		$select = $count ? 'count(*)' : '*';

		$sql = "SELECT $select FROM " . self::table();

		if (isset($_REQUEST['s'])) {
			$s   = $wpdb->esc_like(sanitize_text_field($_REQUEST['s']));
			$s   = ltrim($s, '0');
			$sql .= ' WHERE (`message` LIKE "%' . $s . '%" OR `reciever` LIKE "%' . $s . '%"  OR `sender` LIKE "%' . $s . '%")';
		}

		if (!empty($_REQUEST['id'])) {
			$post_id = !is_array($_REQUEST['id']) ? explode(',', sanitize_text_field(((string) $_REQUEST['id']))) : array_map('intval', $_REQUEST['id']);
			$post_id = array_unique(array_filter(array_map('intval', $post_id)));
			$sql     .= (isset($s) ? ' AND' : ' WHERE') . ' (`post_id` IN (' . implode(',', $post_id) . '))';
		}

		if (!empty($_REQUEST['orderby'])) {
			$sql .= ' ORDER BY ' . esc_sql(sanitize_text_field($_REQUEST['orderby']));
			$sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql(sanitize_text_field($_REQUEST['order'])) : ' ASC';
		} else {
			$sql .= ' ORDER BY id DESC';
		}

		return $sql;
	}

	public function record_count()
	{

		if (!$this->table_exists()) {
			return 0;
		}

		global $wpdb;

		return $wpdb->get_var($this->get_query(true));
	}

	public function get_items($per_page = 20, $page_number = 1)
	{

		global $wpdb;
		if (!$this->table_exists()) {
			return array();
		}

		$sql    = $this->get_query();
		$sql    .= " LIMIT $per_page";
		$sql    .= ' OFFSET ' . ($page_number - 1) * $per_page;
		$result = $wpdb->get_results($sql, 'ARRAY_A');

		return $result;
	}

	public function delete_item($id)
	{
		global $wpdb;

		$wpdb->delete(self::table(), array('id' => $id));
	}
}

class WoocommerceIR_SMS_Archive
{

	public function __construct()
	{
		add_action('pwoosms_settings_form_bottom_sms_archive', array($this, 'archiveTable'));
		add_action('init', array($this, 'createTable'));
	}

	public function createTable()
	{

		if (get_option('pwoosms_table_archive')) {
			return;
		}

		if (!function_exists('dbDelta')) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		}

		global $wpdb;

		$charset_collate = '';
		if (!empty($wpdb->charset)) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if (!empty($wpdb->collate)) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		$table = WoocommerceIR_SMS_Archive_List_Table::table();
		dbDelta("CREATE TABLE IF NOT EXISTS $table (
			id mediumint(8) unsigned NOT NULL auto_increment,
			post_id mediumint(8) unsigned,
            type tinyint(2),
			reciever TEXT NOT NULL,
			message TEXT NOT NULL,
			sender VARCHAR(100),
			result TEXT,
			date DATETIME,
			PRIMARY KEY  (id)
		) $charset_collate;");

		update_option('pwoosms_table_archive', '1');
	}

	public static function insertRecord($data)
	{

		global $wpdb;

		$time = time();
		if (function_exists('wc_timezone_offset')) {
			$time += wc_timezone_offset();
		}

		$wpdb->insert(WoocommerceIR_SMS_Archive_List_Table::table(), array(
			'post_id'  => !empty($data['post_id'])	? $data['post_id'] : 0,
			'type'     => !empty($data['type']) 	? $data['type'] : 0,
			'reciever' => !empty($data['reciever'])	? $data['reciever']	: '',
			'message'  => !empty($data['message']) 	? $data['message'] : '',
			'sender'   => !empty($data['sender']) 	? $data['sender'] : '',
			'result'   => !empty($data['result']) 	? $data['result'] : '',
			'date'     => gmdate('Y-m-d H:i:s', $time),
		), array('%d', '%d', '%s', '%s', '%s', '%s', '%s'));
	}

	public function archiveTable()
	{
		$list = new WoocommerceIR_SMS_Archive_List_Table();
		$list->prepare_items();

		echo '<span class="description">پیامک‌های مربوط به ووکامرس اینجا نمایش داده می‌شوند.</span>';

		echo '<style type="text/css">';
		echo '.wp-list-table .column-id { max-width: 5%; }';
		echo '</style>';
?>

		<?php if (!empty($_GET['id'])) : ?>
			<a class="page-title-action" href="<?php echo esc_url(remove_query_arg(array('id'))); ?>">بازگشت به لیست آرشیو
				همه پیامک های ووکامرس</a>
		<?php endif; ?>

		<form method="post" class="karans-table">
			<input type="hidden" name="page" value="WoocommerceIR_SMS_Archive_list_table">
			<?php
			$list->search_box('جستجوی گیرنده', 'search_id');
			$list->display();
			?>
		</form>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('.delete a, a.delete, .button.action').on('click', function(e) {
					var action1 = $('select[name="action"]').val();
					var action2 = $('select[name="action2"]').val();
					if ($(this).is('a') || action1 === 'bulk_delete' || action2 === 'bulk_delete') {
						if (!confirm('آیا از انجام عملیات حذف مطمئن هستید؟ این عمل غیرقابل برگشت است.')) {
							e.preventDefault();
							return false;
						}
					}
				});
			});
		</script>
<?php
	}
}

new WoocommerceIR_SMS_Archive();
