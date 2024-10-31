<?php

namespace SabaPayamak;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WoocommerceIR_SMS_Gateways {

	public $mobile = array();
	public $message = '';
	public $senderNumber = '';

	private $username = '';
	private $password = '';

	private static $_instance;

	public static function init() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		$options = Helpers::get_sabapayamak_options();
		$this->username     = $options['user_name'];
		$this->password     = $options['password'];
		$this->senderNumber = $options['v_number'];
	}

	public static function get_sms_gateway() {

		$gateway = array(
			'sunwaysms'     => 'SunwaySMS.com',
			'parandsms'     => 'ParandSMS.ir',
			'gamapayamak'   => 'GAMAPayamak.com',
			'limoosms'      => 'LimooSMS.com',
			'smsfa'         => 'SMSFa.ir',
			'aradsms'       => 'Arad-SMS.ir',
			'farapayamak'   => 'FaraPayamak.ir',
			'payamafraz'    => 'PayamAfraz.com',
			'niazpardaz'    => 'SMS.NiazPardaz.com',
			'niazpardaz_'   => 'Login.NiazPardaz.ir',
			'yektasms'      => 'Yektatech.ir',
			'smsbefrest'    => 'SmsBefrest.ir',
			'relax'         => 'Relax.ir',
			'paaz'          => 'Paaz.ir',
			'postgah'       => 'Postgah.info',
			'idehpayam'     => 'IdehPayam.com',
			'azaranpayamak' => 'Azaranpayamak.ir',
			'smsir'         => 'SMS.ir',
			'manirani'      => 'Manirani.ir',
			'tjp'           => 'TJP.ir',
			'websms'        => 'S1.Websms.ir',
			'payamresan'    => 'Payam-Resan.com',
			'bakhtarpanel'  => 'Bakhtar.xyz',
			'parsgreen'     => 'ParsGreen.com',
			'avalpayam'     => 'Avalpayam.com',
			'iransmsserver' => 'IranSmsServer.com',
			'melipayamak'   => 'MeliPayamak.com',
			'melipayamakpattern'   => 'MeliPayamak.com خدماتی',
			'loginpanel'    => 'LoginPanel.ir',
			'smshooshmand'  => 'SmsHooshmand.com',
			'smsfor'        => 'SMSFor.ir',
			'chaparpanel'   => 'ChaparPanel.IR',
			'firstpayamak'  => 'FirstPayamak.ir',
			'netpaydar'     => 'SMS.Netpaydar.com',
			'smspishgaman'  => 'Panel.SmsPishgaman.com',
			'parsianpayam'  => 'ParsianPayam.ir',
			'hostiran'      => 'Hostiran.com',
			'iransms'       => 'IranSMS.co',
			'negins'        => 'Negins.com',
			'kavenegar'     => 'Kavenegar.com',
			'afe'           => 'Afe.ir',
			'aradpayamak'   => 'Aradpayamak.net',
			'isms'          => 'ISms.ir',
			'razpayamak'    => 'RazPayamak.com',
			'_0098'         => '0098SMS.com',
			'sefidsms'      => 'SefidSMS.ir',
			'chapargah'     => 'Chapargah.com',
			'hafezpayam'    => 'HafezPayam.com',
			'mehrpanel'     => 'MehrPanel.ir',
			'kianartpanel'  => 'KianArtPanel.ir',
			'farstech'      => 'Sms.FarsTech.ir',
			'berandet'      => 'Berandet.ir',
			'nicsms'        => 'NikSms.com',
			'asanak'        => 'Asanak.ir',
			'ssmss'         => 'SSMSS.ir',
			'hiro_sms'      => 'Hiro-Sms.com',
			'sabanovin'     => 'SabaNovin.com',
			'trez'          => 'SmsPanel.Trez.ir',
			'raygansms'     => 'RayganSms.com',
			'sepahansms'    => 'SepahanSms.com (SepahanGostar.com)',
			'_3300'         => 'Sms.3300.ir',
			'smsnegar'      => 'Sms.SmsNegar.com',
			'behsadade'     => 'Sms.BehsaDade.com',
			'flashsms'      => 'FlashSms.ir (AdminPayamak.ir)',
			'payamsms'      => 'PayamSms.com',
			'hadafwp'      	=> 'sms.hadafwp.Com',
			'mehrafraz'    	=> 'mehrafraz.com',
			'irpayamak'     => 'IRPayamak.Com',
			'gamasystems'	=> 'Gama.systems',
			'smsmelli'		=> 'SMSMelli.com',
			'smsmeli'		=> 'SMS-Meli.com',
			'kavenegar_lookUp'	=> 'Kavenegar.com(lookup)',
			'atlaspayamak'	=> 'Atlaspayamak.ir',
			'parsiansms'	=> 'Parsian-SMS.ir',
			'panelsms20'     => 'panelsms20.ir',
			'newsms'     => 'NewSMS.ir',
			'parsiantd'     => 'sms.parsiantd.com',
			'sahandsms'     => 'sahandsms.com',
			'aryana'     => 'PayamKotah.com',
			'npsms'     =>  'npsms.com',
			'sabapayamak' => 'sabapayamak.com'
		);

		return apply_filters( 'pwoosms_sms_gateways', $gateway );
	}

	public function sabapayamak() {
		
		$sms = new SMS();

		$result = $sms->send_sms("پیامک ووکامرس", $this->mobile, $this->message);

		return $result;
	}
}