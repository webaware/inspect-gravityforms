<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* class for managing the plugin
*/
class GFInspectPlugin {

	public $options;									// array of plugin options

	private $validationMessage	= '';					// current feed mapping form fields to payment fields
	private $feed				= null;					// current feed mapping form fields to payment fields
	private $formData			= null;					// current form data collected from form

	// minimum versions required
	const MIN_VERSION_GF		= '1.8.8';

	/**
	* static method for getting the instance of this singleton object
	* @return self
	*/
	public static function getInstance() {
		static $instance = null;

		if (is_null($instance)) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	* initialise plugin
	*/
	private function __construct() {
		add_action('init', array($this, 'loadTextDomain'));
		add_filter('plugin_row_meta', array($this, 'pluginDetailsLinks'), 10, 2);
		add_action('admin_print_styles-toplevel_page_gf_edit_forms', array($this, 'adminCSS'));
		add_filter('gform_form_actions', array($this, 'gformsFormActions'), 100, 2);
	}

	/**
	* load text translations
	*/
	public function loadTextDomain() {
		load_plugin_textdomain('inspect-gravityforms');
	}

	/**
	* add plugin details links
	*/
	public function pluginDetailsLinks($links, $file) {
		if ($file === GFINSPECT_PLUGIN_NAME) {
			$links[] = sprintf('<a href="https://wordpress.org/support/plugin/inspect-gravityforms" target="_blank">%s</a>', _x('Get help', 'plugin details links', 'inspect-gravityforms'));
			$links[] = sprintf('<a href="https://wordpress.org/plugins/inspect-gravityforms/" target="_blank">%s</a>', _x('Rating', 'plugin details links', 'inspect-gravityforms'));
			$links[] = sprintf('<a href="https://translate.wordpress.org/projects/wp-plugins/inspect-gravityforms" target="_blank">%s</a>', _x('Translate', 'plugin details links', 'inspect-gravityforms'));
			$links[] = sprintf('<a href="http://shop.webaware.com.au/donations/?donation_for=Inspect+Gravity+Forms" target="_blank">%s</a>', _x('Donate', 'plugin details links', 'inspect-gravityforms'));
		}

		return $links;
	}

	/**
	* add custom CSS to footer, but only on pages we handle
	*/
	public function adminCSS() {
		// only show on form list, not on subviews or form edit page
		if (empty($_GET['subview']) && empty($_GET['id'])) {
			echo "<style>\n";
			readfile(GFINSPECT_PLUGIN_ROOT . 'css/admin.css');
			echo "</style>\n";
		}
	}

	/**
	* add icons to form actions, showing which forms have credit card fields and feeds
	* @param array $actions
	* @param int $form_id
	* @return array
	*/
	public function gformsFormActions($actions, $form_id) {
		$form = GFFormsModel::get_form_meta($form_id);
		$feeds = GFAPI::get_feeds(null, $form_id);

		$icons = array();
		if (GFCommon::has_credit_card_field($form)) {
			$icons['credit-card-field'] = self::buildIconHTML(esc_attr_x('Credit Card field', 'form list icon', 'inspect-gravityforms'), 'fa fa-credit-card');
		}

		// find what feeds the form has
		if (!is_wp_error($feeds)) {

			$feed_slugs = array();
			foreach ($feeds as $feed) {
				$feed_slugs[$feed['addon_slug']] = 1;
			}

			if (isset($feed_slugs['gravityformspaypal'])) {
				$icons['gravityformspaypal'] = self::buildIconHTML(esc_attr_x('PayPal Standard feed', 'form list icon', 'inspect-gravityforms'), 'fa fa-paypal');
			}

			if (isset($feed_slugs['gravityformspaypalpaymentspro'])) {
				$icons['gravityformspaypalpaymentspro'] = self::buildIconHTML(esc_attr_x('PayPal Payments Pro feed', 'form list icon', 'inspect-gravityforms'), 'fa fa-paypal');
			}

			if (isset($feed_slugs['gravityformsauthorizenet'])) {
				$icons['gravityformsauthorizenet'] = self::buildIconHTML(esc_attr_x('Authorize.Net feed', 'form list icon', 'inspect-gravityforms'), 'fa fa-credit-card-alt');
			}

			if (isset($feed_slugs['gravityformsstripe'])) {
				$icons['gravityformsstripe'] = self::buildIconHTML(esc_attr_x('Stripe feed', 'form list icon', 'inspect-gravityforms'), 'fa fa-credit-card-alt');
			}

			if (isset($feed_slugs['gravityforms-eway-pro'])) {
				$icons['gravityforms-eway-pro'] = self::buildIconHTML(esc_attr_x('eWAY feed', 'form list icon', 'inspect-gravityforms'), 'fa fa-credit-card-alt');
			}

			if (isset($feed_slugs['gravityformsuserregistration'])) {
				$icons['gravityformsuserregistration'] = self::buildIconHTML(esc_attr_x('User Registration feed', 'form list icon', 'inspect-gravityforms'), 'fa fa-user-plus');
			}

			if (isset($feed_slugs['gravityformscampaignmonitor'])) {
				$icons['gravityformscampaignmonitor'] = self::buildIconHTML(esc_attr_x('Campaign Monitor feed', 'form list icon', 'inspect-gravityforms'), 'fa fa-bullhorn');
			}

			if (isset($feed_slugs['gravityformsmailchimp'])) {
				$icons['gravityformsmailchimp'] = self::buildIconHTML(esc_attr_x('MailChimp feed', 'form list icon', 'inspect-gravityforms'), 'fa fa-bullhorn');
			}

			if (isset($feed_slugs['gravityformshelpscout'])) {
				$icons['gravityformshelpscout'] = self::buildIconHTML(esc_attr_x('Help Scout feed', 'form list icon', 'inspect-gravityforms'), 'fa fa-question-circle');
			}

		}

		if ($this->hasDpsPxPayFeed($form_id)) {
			$icons['gravity-forms-dps-pxpay'] = self::buildIconHTML(esc_attr_x('DPS PxPay feed', 'form list icon', 'inspect-gravityforms'), 'fa fa-credit-card-alt');
		}

		// allow hookers to change the list, e.g. add their own icons
		$icons = apply_filters('inspect_gravityforms_icon_list', $icons, $form_id, $form, $feeds);

		if (!empty($icons)) {
			$actions['inspectgf-icons'] = sprintf('<span class="inspectgf-icons">%s</span>', implode(' ', $icons));
		}

		return $actions;
	}

	/**
	* build HTML for icon, with fallback for screen readers
	* @param string $title
	* @param string $icon_class
	* @return string
	*/
	protected static function buildIconHTML($title, $icon_class) {
		return sprintf('<i class="%1$s" aria-hidden="true" title="%2$s"></i><span class="screen-reader-text">%2$s</span>', $icon_class, $title);
	}

	/**
	* check for Gravity Forms DPS PxPay feed (not on add-on framework)
	* @param int $form_id
	* @return bool
	*/
	protected function hasDpsPxPayFeed($form_id) {
		if (!defined('GFDPSPXPAY_TYPE_FEED')) {
			return false;
		}

		$feeds = get_posts(array(
			'post_type'			=> GFDPSPXPAY_TYPE_FEED,
			'orderby'			=> 'none',
			'posts_per_page'	=> 1,
			'meta_key'			=> '_gfdpspxpay_form',
			'meta_value'		=> $form_id,
		));

		return !empty($feeds);
	}

	/**
	* compare Gravity Forms version against target
	* @param string $target
	* @param string $operator
	* @return bool
	*/
	public static function versionCompareGF($target, $operator) {
		if (class_exists('GFCommon', false)) {
			return version_compare(GFCommon::$version, $target, $operator);
		}

		return false;
	}

	/**
	* compare Gravity Forms version against minimum required version
	* @return bool
	*/
	public static function hasMinimumGF() {
		return self::versionCompareGF(self::MIN_VERSION_GF, '>=');
	}

}
