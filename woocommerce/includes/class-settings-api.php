<?php

namespace SabaPayamak;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WoocommerceIR_SMS_Settings_Api {

	private $settings_sections = array();
	private $settings_fields = array();
	private $desc_style = 'style="margin-top:4px;color:#616161;font-style:italic;font-size:13px;line-height:25px"';

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	function admin_enqueue_scripts() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'jquery' );
	}

	function set_sections( $sections ) {
		$this->settings_sections = $sections;

		return $this;
	}

	function add_section( $section ) {
		$this->settings_sections[] = $section;

		return $this;
	}

	function set_fields( $fields ) {
		$this->settings_fields = $fields;

		return $this;
	}

	function add_field( $section, $field ) {
		$defaults                            = array(
			'name'  => '',
			'label' => '',
			'desc'  => '',
			'type'  => 'text'
		);
		$arg                                 = wp_parse_args( $field, $defaults );
		$this->settings_fields[ $section ][] = $arg;

		return $this;
	}

	function admin_init() {
		foreach ( $this->settings_sections as $section ) {
			if ( false == get_option( $section['id'] ) ) {
				add_option( $section['id'] );
			}
			if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
				$section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';
				$callback        = create_function( '', 'echo "' . str_replace( '"', '\"', $section['desc'] ) . '";' );
			} else {
				$callback = '__return_false';
			}
			add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
		}

		foreach ( $this->settings_fields as $section => $field ) {
			foreach ( $field as $option ) {
				$type = isset( $option['type'] ) ? $option['type'] : 'text';
				$args = array(
					'id'                => $option['name'],
					'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
					'desc2'             => isset( $option['desc2'] ) ? $option['desc2'] : '',
					'label_for'         => $type != 'html' && isset( $option['name'] ) ? $section . '[' . $option['name'] . ']' : '',
					'name'              => $option['label'],
					'section'           => $section,
					'size'              => isset( $option['size'] ) ? $option['size'] : null,
					'row'               => isset( $option['row'] ) ? $option['row'] : '',
					'ltr'               => isset( $option['ltr'] ) ? $option['ltr'] : false,
					'options'           => isset( $option['options'] ) ? $option['options'] : '',
					'br'                => isset( $option['br'] ) ? $option['br'] : '',
					'std'               => isset( $option['default'] ) ? $option['default'] : '',
					'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
				);
				add_settings_field( $section . '[' . $option['name'] . ']', $option['label'], array(
					$this,
					'callback_' . $type
				), $section, $section, $args );
			}
		}

		foreach ( $this->settings_sections as $section ) {
			register_setting( $section['id'], $section['id'], array( $this, 'sanitize_options' ) );
		}
	}

	function callback_text( $args ) {
		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$style = '';
		if ( ! empty( $args['ltr'] ) && $args['ltr'] === true ) {
			$style .= 'text-align:left !important;direction:ltr !important';
		}
		$html = sprintf( '<input type="text" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" style="%5$s" />', $size, $args['section'], $args['id'], $value, $style );
		$html .= sprintf( '<div class="description" ' . $this->desc_style . '> %s</div>', $args['desc'] );

		$allowed_html = array(
			'input'      => array(
				'type'  => array(),
				'class' => array(),
				'id' => array(),
				'name' => array(),
				'value' => array(),
				'style' => array(),
			),
			'div'      => array(
				'class'  => array(),
				'style'  => array(),
			),
		);
		echo wp_kses( $html, $allowed_html );
	}

	function callback_checkbox( $args ) {
		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$html  = sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
		$html  .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s]" name="%1$s[%2$s]" value="on"%4$s />', $args['section'], $args['id'], $value, checked( $value, 'on', false ) );
		$html  .= sprintf( '<label for="wpuf-%1$s[%2$s]"> %3$s</label>', $args['section'], $args['id'], $args['desc'] );

		if ( ! empty( $args['desc2'] ) ) {
			$html .= sprintf( '<div class="description" ' . $this->desc_style . '> %s</div>', $args['desc2'] );
		}

		$allowed_html = array(
			'input'      => array(
				'type'  => array(),
				'class' => array(),
				'id' => array(),
				'name' => array(),
				'value' => array(),
				'checked' => array(),
			),
			'label'      => array(
				'for'  => array(),
			),
			'div'      => array(
				'class'  => array(),
				'style'  => array(),
			),
		);

		echo wp_kses( $html, $allowed_html );
	}

	function callback_multicheck( $args ) {
		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
		$html  = '';
		foreach ( $args['options'] as $key => $label ) {
			$checked = isset( $value[ $key ] ) ? $value[ $key ] : '0';
			$html    .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s"%4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
			$html    .= sprintf( '<label for="wpuf-%1$s[%2$s][%4$s]"> %3$s</label><br>', $args['section'], $args['id'], $label, $key );
		}
		$html .= sprintf( '<div class="description" ' . $this->desc_style . '> %s</div>', $args['desc'] );
		
		$allowed_html = array(
			'input'      => array(
				'type'  => array(),
				'class' => array(),
				'id' => array(),
				'name' => array(),
				'value' => array(),
				'checked' => array(),
			),
			'label'     => array(
				'for'  => array()
			),
			'br'     => array(),
			'div' => array(
				'class' => array(),
				'style' => array(),
			),
		);

		echo wp_kses( $html, $allowed_html );
	}

	function callback_radio( $args ) {
		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
		$html  = '';
		$style = is_rtl() ? 'margin-left:10px' : 'margin-right:10px';

		foreach ( $args['options'] as $key => $label ) {
			$label = sprintf( '<input type="radio" class="radio inline" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) ) . $label;
			$html  .= sprintf( '<label style="' . $style . '" for="wpuf-%1$s[%2$s][%4$s]"> %3$s</label>', $args['section'], $args['id'], $label, $key );
			if ( ! empty( $args['br'] ) ) {
				$html .= '<p></p>';
			}
		}
		if ( ! empty( $args['br'] ) ) {
			$html .= '<p></p>';
		}
		$html .= sprintf( '<div class="description" ' . $this->desc_style . '> %s</div>', $args['desc'] );
				
		$allowed_html = array(
			'input'      => array(
				'type'  => array(),
				'class' => array(),
				'id' => array(),
				'name' => array(),
				'value' => array(),
				'checked' => array(),
			),
			'label'     => array(
				'style'  => array(),
				'for'  => array()
			),
			'br'     => array(),
			'p'     => array(),
			'div' => array(
				'class' => array(),
				'style' => array(),
			),
		);

		echo wp_kses( $html, $allowed_html );
	}

	function callback_textarea( $args ) {
		$value = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$row   = ! empty( $args['row'] ) ? $args['row'] : '6';
		$style = 'width: 50%';
		$html  = sprintf( '<textarea rows="%6$s" style="%5$s" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]">%4$s</textarea>', $size, $args['section'], $args['id'], $value, $style, $row );
		$html  .= sprintf( '<br><div class="description" ' . $this->desc_style . '> %s</div>', $args['desc'] );
		
		$allowed_html = array(
			'textarea'      => array(
				'rows'  => array(),
				'style' => array(),
				'class' => array(),
				'id' => array(),
				'name' => array(),
			),
			'br'      => array(),
			'div'      => array(
				'class'  => array(),
				'style'  => array(),
			),
		);

		echo wp_kses( $html, $allowed_html );
	}

	function callback_html( $args ) {
		$html = sprintf( '<div class="description" ' . $this->desc_style . '> %s</div>', $args['desc'] );
		
		$allowed_html = array(
			'div'      => array(
				'class'  => array(),
				'style'  => array(),
			),
		);

		echo wp_kses( $html, $allowed_html );
	}

	function sanitize_options( $options ) {
		foreach ( $options as $option_slug => $option_value ) {
			$sanitize_callback = $this->get_sanitize_callback( $option_slug );
			if ( $sanitize_callback ) {
				$options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
				continue;
			}
		}

		return $options;
	}

	function get_sanitize_callback( $slug = '' ) {
		if ( empty( $slug ) ) {
			return false;
		}
		foreach ( $this->settings_fields as $section => $options ) {
			foreach ( $options as $option ) {
				if ( $option['name'] != $slug ) {
					continue;
				}

				return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
			}
		}

		return false;
	}

	function get_option( $option, $section, $default = '' ) {
		$options = get_option( $section );
		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default;
	}

	function show_navigation() {

		$html       = '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">';
		$tab_number = 0;
		$sabapayamak_tabs = array(
            array(
                'id'       => 'home',
                'title'    => 'تنظیمات',
                'form_tag' => false
            ),
            array(
                'id'       => 'l2fa',
                'title'    => 'ورود دومرحله‌ای',
                'form_tag' => false
            ),
            array(
                'id'       => 'events',
                'title'    => 'رویدادها',
                'form_tag' => false
            ),
            array(
                'id'       => 'send',
                'title'    => 'ارسال پیامک',
                'form_tag' => false
            ),
            array(
                'id'       => 'log',
                'title'    => 'گزارش',
                'form_tag' => false
            ),
        );
		$pwoosms_tabs = $this->settings_sections;

		if(Helpers::is_woocommerce_active())
            $sabapayamak_tabs = array_merge($sabapayamak_tabs, $pwoosms_tabs);

		foreach ( $sabapayamak_tabs as $form ) {
			$tab_number ++;
			$class = '';
			$tab   = ! empty( $_GET['tab'] ) ? sanitize_text_field($_GET['tab']) : '';
			if ( empty( $tab ) ) {
				if ( $tab_number == 1 ) {
					$class = 'nav-tab-active';
				}
			} elseif ( stripos( $form['id'], $tab ) !== false ) {
				$class = 'nav-tab-active';
			}

			$url = Helpers::clean_url(["page"]) . "&tab=$form[id]";

			$hide_tab = (isset($form['hide_tab']) && $form['hide_tab'] ) ? " hidden-tab" : "";

			$html .= sprintf( '<a href="%1$s" onclick="ks_enableLoadingAnimation();" class="nav-tab' . $hide_tab .' %3$s" id="%1$s-tab">%2$s</a>', $url, $form['title'], $class );
		}
		$html .= '</nav>';

		$allowed_html = array(
			'nav'      => array(
				'class' => array(),
			),
			'a'     => array(
				'class' => array(),
				'id'  => array(),
				'href'  => array(),
				'onclick'  => array(),
			),
		);
		
		echo wp_kses( $html, $allowed_html );
	}

	function show_forms() {
				
		$tab = ! empty( $_GET['tab'] ) ? sanitize_text_field($_GET['tab']) : 'home';

		if(!Helpers::is_sabapayamak_options_validated() && $tab != "home" ){
			KS_UI::options_not_validated_msg();
			return;
		}

		switch ($tab) {
			case 'home':
				KS_UI::admin_tab_home();
				break;

			case 'l2fa':
				KS_UI::admin_tab_l2fa();
				break;

			case 'events':
				KS_UI::admin_tab_events();
				break;

			case 'send':
				KS_UI::admin_tab_send();
				break;

			case 'log':
				KS_UI::admin_tab_log();
				break;

			case '':
				KS_UI::admin_tab_home();
				break;
				
			default:
				break;
		}

		if ( defined( 'WC_VERSION' ) ) {
			wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
			wp_enqueue_script( 'wc-enhanced-select' );
		}
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == 'true' ) {
			helpers::add_notice("تنظیمات ذخیره شدند.", "success");
		} else {
			do_action( 'pwoosms_settings_form_admin_notices' );
		}
		?>
        <style>
            table.form-table th {
                padding-left: 24px !important;
                position: relative;
                width: 240px;
            }
        </style>

		<?php
		$tab_number = 0;
		foreach ( $this->settings_sections as $form ) {
			$show_form = false;
			$tab_number ++;

			$display = false;
			$tab     = ! empty( $_GET['tab'] ) ? sanitize_text_field($_GET['tab']) : 'home';
			if ( empty( $tab ) ) {
				if ( $tab_number == 1 ) {
					$display = true;
				}
			} elseif ( stripos( $form['id'], $tab ) !== false ) {
				$display = true;
			}
			if ( empty( $display ) ) {
				continue;
			}


			?>
            <div id="<?php echo esc_attr($form['id']); ?>" class="group">
				<?php
				if ( ! isset( $form['form_tag'] ) || $form['form_tag'] !== false ) :
				$show_form = true; ?>
                <form method="post" action="options.php">
					<?php settings_fields( $form['id'] ); ?>
					<?php endif; ?>
					<?php
					do_action( 'pwoosms_settings_form_top_' . $form['id'], $form );
					do_settings_sections( $form['id'] );
					do_action( 'pwoosms_settings_form_bottom_' . $form['id'], $form );
					do_action( 'pwoosms_settings_form_submit_' . $form['id'], $form );
					?>
					<?php if ( ! empty( $show_form ) ) : ?>
					<?php remove_all_actions( 'pwoosms_settings_form_submit_' . $form['id'] ); ?>
                    <div style="padding-right: 10px">
						<?php submit_button(); ?>
                    </div>
                </form>
			<?php endif; ?>
            </div>
			<?php
			break;
		}

		$this->script();
	}

	function script() { ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {

                /*
                $('.group').hide();
                var activetab = '';
                if (typeof(localStorage) !== 'undefined') {
                    activetab = localStorage.getItem("activetab");
                }

                if (activetab !== '' && $(activetab).length) {
                    $(activetab).fadeIn();
                } else {
                    $('.group:first').fadeIn();
                }

                if (activetab !== '' && $(activetab + '-tab').length) {
                    $(activetab + '-tab').addClass('nav-tab-active');
                }
                else {
                    $('.nav-tab-wrapper a:first').addClass('nav-tab-active');
                }
                $('.nav-tab-wrapper a').click(function (evt) {
                    $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active').blur();
                    var clicked_group = $(this).attr('href');
                    if (typeof(localStorage) !== 'undefined') {
                        localStorage.setItem("activetab", $(this).attr('href'));
                    }

                    $('.group').hide();
                    $(clicked_group).fadeIn();
                    evt.preventDefault();
                });*/

                $('.wp-color-picker-field').wpColorPicker();

                $('.group .collapsed').each(function () {
                    $(this).find('input:checked').parent().parent().parent().nextAll().each(
                        function () {
                            if ($(this).hasClass('last')) {
                                $(this).removeClass('hidden');
                                return false;
                            }
                            $(this).filter('.hidden').removeClass('hidden');
                        });
                });

                var file_frame = null;
                $('.pwoosms-browse').on('click', function (event) {
                    event.preventDefault();
                    var self = $(this);
                    if (file_frame) {
                        file_frame.open();
                        return false;
                    }
                    file_frame = wp.media.frames.file_frame = wp.media({
                        title: self.data('uploader_title'),
                        button: {
                            text: self.data('uploader_button_text')
                        },
                        multiple: false
                    });
                    file_frame.on('select', function () {
                        var attachment = file_frame.state().get('selection').first().toJSON();

                        self.prev('.pwoosms-url').val(attachment.url);
                    });
                    file_frame.open();
                });
            });

        </script>
		<?php
	}
}