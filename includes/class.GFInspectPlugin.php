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
	const MIN_VERSION_GF		= '1.9.10';

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

		add_filter('gform_form_list_columns', array($this, 'gformsFormListColumns'));
		add_action('gform_form_list_column_inspectgf_icons', array($this, 'gformsColumnInspectGF'));

		// this action is removed if custom form list columns are supported (GF 2.0+)
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
			$links[] = sprintf('<a href="https://shop.webaware.com.au/donations/?donation_for=Inspect+Gravity+Forms" target="_blank">%s</a>', _x('Donate', 'plugin details links', 'inspect-gravityforms'));
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
	* insert custom form list column for Gravity Forms 2.0+
	* @param array $columns
	* @return array
	*/
	public function gformsFormListColumns($columns) {
		$columns['inspectgf_icons'] = esc_html_x('Inspector', 'forms list column name', 'inspect-gravityforms');

		// no need to add icons to form actions when we can add a column
		remove_filter('gform_form_actions', array($this, 'gformsFormActions'), 100, 2);

		return $columns;
	}

	/**
	* content for custom form list column
	* @param object $form
	*/
	public function gformsColumnInspectGF($form) {
		$icons = $this->getFormIcons($form->id);

		if (!empty($icons)) {
			printf('<span class="inspectgf-icons">%s</span>', implode(' ', $icons));
		}
	}

	/**
	* add icons to form actions, showing which forms have credit card fields and feeds
	* @param array $actions
	* @param int $form_id
	* @return array
	*/
	public function gformsFormActions($actions, $form_id) {
		$icons = $this->getFormIcons($form_id);

		if (!empty($icons)) {
			$actions['inspectgf-icons'] = sprintf('<span class="inspectgf-icons">%s</span>', implode(' ', $icons));
		}

		return $actions;
	}

	/**
	* get icons for form
	* @param int $form_id
	* @return array
	*/
	protected function getFormIcons($form_id) {
		$form = GFFormsModel::get_form_meta($form_id);
		$feeds = GFAPI::get_feeds(null, $form_id);

		$icons = array();

		$ccfields = GFFormsModel::get_fields_by_type($form, 'creditcard', true);
		if (!empty($ccfields)) {
			$icons['credit-card-field'] = self::buildIconHTML(esc_attr_x('Credit Card field', 'form list icon', 'inspect-gravityforms'), 'fa fa-credit-card');

			if (!empty($ccfields[0]->forceSSL)) {
				$icons['force-ssl'] = self::buildIconHTML(esc_attr_x('Force SSL', 'form list icon', 'inspect-gravityforms'), 'fa fa-lock');
			}
		}

		// find what feeds the form has
		if (!is_wp_error($feeds)) {
			foreach ($feeds as $feed) {
				$addon_slug = $feed['addon_slug'];
				if (!isset($icons[$addon_slug])) {

					switch ($addon_slug) {

						case 'gravityformspaypal':
							$icons[$addon_slug] = self::buildIconHTML(esc_attr_x('PayPal Standard', 'form list icon', 'inspect-gravityforms'), 'fa fa-paypal');
							break;

						case 'gravityformspaypalpaymentspro':
							$icons[$addon_slug] = self::buildIconHTML(esc_attr_x('PayPal Payments Pro', 'form list icon', 'inspect-gravityforms'), 'fa fa-paypal');
							break;

						case 'gravityformsauthorizenet':
							$icons[$addon_slug] = self::buildIconHTML(esc_attr_x('Authorize.Net', 'form list icon', 'inspect-gravityforms'), 'fa fa-credit-card-alt');
							break;

						case 'gravityformsstripe':
							$icons[$addon_slug] = self::buildIconHTML(esc_attr_x('Stripe', 'form list icon', 'inspect-gravityforms'), 'fa fa-credit-card-alt');
							break;

						case 'gravityforms-eway-pro':
							$icons[$addon_slug] = self::buildIconHTML(esc_attr_x('eWAY', 'form list icon', 'inspect-gravityforms'), 'fa fa-credit-card-alt');
							break;

						case 'gravity-forms-dps-pxpay':
							$icons[$addon_slug] = self::buildIconHTML(esc_attr_x('DPS PxPay', 'form list icon', 'inspect-gravityforms'), 'fa fa-credit-card-alt');
							break;

						case 'gravityformsuserregistration':
							$icons[$addon_slug] = self::buildIconHTML(esc_attr_x('User Registration', 'form list icon', 'inspect-gravityforms'), 'fa fa-user-plus');
							break;

						case 'gravityformscampaignmonitor':
							$icons[$addon_slug] = self::buildIconHTML(esc_attr_x('Campaign Monitor', 'form list icon', 'inspect-gravityforms'), 'fa fa-bullhorn');
							break;

						case 'gravityformsmailchimp':
							$icons[$addon_slug] = self::buildIconHTML(esc_attr_x('MailChimp', 'form list icon', 'inspect-gravityforms'), 'fa fa-bullhorn');
							break;

						case 'gravityformshelpscout':
							$icons[$addon_slug] = self::buildIconHTML(esc_attr_x('Help Scout', 'form list icon', 'inspect-gravityforms'), 'fa fa-question-circle');
							break;

						case 'gravityformscoupons':
							$icons[$addon_slug] = self::buildIconHTML(esc_attr_x('Coupon', 'form list icon', 'inspect-gravityforms'), 'fa fa-money');
							break;

						default:
							$icons[$addon_slug] = self::buildIconHTML($addon_slug, 'fa fa-filter');
							break;

					}
				}
			}
		}

		if ($this->hasZapierFeed($form_id)) {
			$icons['gravityformszapier'] = self::buildIconHTML(esc_attr_x('Zapier', 'form list icon', 'inspect-gravityforms'), 'fa fa-asterisk');
		}

		// allow hookers to change the list, e.g. add their own icons
		$icons = apply_filters('inspect_gravityforms_icon_list', $icons, $form_id, $form, $feeds);

		return $icons;
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
	* check for Gravity Forms Zapier feed (not on add-on framework)
	* @param int $form_id
	* @return bool
	*/
	protected function hasZapierFeed($form_id) {
		if (!class_exists('GFZapierData', false)) {
			return false;
		}

		$feeds = GFZapierData::get_feed_by_form($form_id);

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
