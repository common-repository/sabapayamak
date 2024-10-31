<?php

namespace SabaPayamak;

 if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WoocommerceIR_SMS_Bulk {

	public function __construct() {

		add_action( 'pwoosms_settings_form_bottom_sms_send', array( $this, 'bulkForm' ) );
		add_action( 'pwoosms_settings_form_admin_notices', array( $this, 'bulkNotice' ), 10 );

		if ( PWooSMS()->Options( 'enable_buyer' ) ) {
			add_action( 'admin_footer', array( $this, 'bulkScript' ), 10 );
			add_action( 'load-edit.php', array( $this, 'bulkAction' ) );
		}
	}

	public function bulkForm() { ?>

        <form class="initial-form" id="pwoosms-send-sms-bulk-form" method="post"
              action="<?php echo admin_url( 'admin.php?page=sabapayamak&tab=send' ) ?>">

			<?php wp_nonce_field( 'pwoosms_send_sms_nonce', '_wpnonce' ); ?>

            <p>
                <label for="pwoosms_mobile">شماره دریافت کننده</label><br>
                <input type="text" name="pwoosms_mobile" id="pwoosms_mobile"
                       value="<?php echo isset( $_POST['pwoosms_mobile'] ) ? esc_attr( $_POST['pwoosms_mobile'] ) : '' ?>"
                       style="direction:ltr; text-align:left; width:100% !important;"/><br>
                <span>شماره موبایل دریافت کننده پیامک را وارد کنید. شماره ها را با کاما (,) جدا نمایید.</span>
            </p>

            <p>
                <label for="pwoosms_message">متن پیامک</label><br>
                <textarea name="pwoosms_message" id="pwoosms_message" rows="10"
                          style="width:100% !important"><?php echo ! empty( $_POST['pwoosms_message'] ) ? esc_textarea( $_POST['pwoosms_message'], '<br>' ) : '' ?></textarea><br>
                <span>متن دلخواهی که میخواهید به دریافت کننده ارسال کنید را وارد کنید.</span>
            </p>

            <p>
                <input type="submit" class="button button-primary" name="pwoosms_send_sms"
                       value="ارسال پیامک">
            </p>
        </form>
		<?php
	}

	public function bulkNotice() {

		if ( isset( $_POST['pwoosms_send_sms'] ) ) {

			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pwoosms_send_sms_nonce' ) ) {
				wp_die( 'خطایی رخ داده است.' );
			}

			$data            = array();
			$data['type']    = 1;
			$data['mobile']  = $mobiles = ! empty( $_POST['pwoosms_mobile'] ) ? explode( ',', sanitize_text_field( $_POST['pwoosms_mobile'] ) ) : array();
			$data['message'] = ! empty( $_POST['pwoosms_message'] ) ? esc_textarea( $_POST['pwoosms_message'] ) : '';

			$response = PWooSMS()->SendSMS( $data );
			if ( $response === true ) { ?>
                <div class="notice notice-success below-h2">
                    <p>پیامک با موفقیت ارسال شد.<br><strong>تعداد مخاطبین با حذف شماره های
                            تکراری </strong>=> <?php echo count( $mobiles ) . ' شماره '; ?></p>
                </div>
				<?php
				return true;
			} ?>

            <div class="notice notice-error below-h2">
                <p><strong>خطا: </strong>پیامک ارسال نشد. پاسخ وبسرویس:
					<?php echo esc_html($response); ?>
                </p>
            </div>
			<?php
		}

		return false;
	}

	public function bulkScript() {

		global $post_type;
		if ( 'shop_order' == $post_type ) : ?>
            <script type="text/javascript">
                jQuery(function () {
                    jQuery('<option>').val('send_sms').text('ارسال پیامک دسته جمعی').appendTo("select[name='action']");
                    jQuery('<option>').val('send_sms').text('ارسال پیامک دسته جمعی').appendTo("select[name='action2']");
                });
            </script>
		<?php
		endif;
	}

	public function bulkAction() {

		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action        = $wp_list_table->current_action();
		if ( $action != 'send_sms' ) {
			return;
		}

		$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );
		$mobiles  = array();
		foreach ( $post_ids as $order_id ) {
			$mobiles[] = PWooSMS()->buyerMobile( $order_id );
		}

		$mobiles = implode( ',', array_unique( array_filter( $mobiles ) ) );

		echo '<form method="POST" name="pwoosms_posted_form" action="' . admin_url( 'admin.php?page=sabapayamak&tab=send' ) . '">
		<input type="hidden" value="' . esc_attr($mobiles) . '" name="ks_sms_numbers" />
		<input type="hidden" value="true" name="is_bulk_send" />
		</form>
		<script language="javascript" type="text/javascript">document.pwoosms_posted_form.submit(); </script>';
		exit();
	}
}

new WoocommerceIR_SMS_Bulk();