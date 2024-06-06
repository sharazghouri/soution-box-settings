<?php
/**
 * Plugin Name: WP Settings Framework Example
 * Description: An example of the WP Settings Framework in action.
 * Version: 1.0.0
 * Author: Sharaz
 * Author URI: https://github.com/sharazghouri
 *
 * @package sbsa
 */

 defined( 'ABSPATH' ) || exit;

// require_once __DIR__ . '/src/SettingsAPI.php';

// autoloader.
// if ( ! class_exists( \Solution_Box_Settings\SettingsAPI::class ) ) {
	require __DIR__ . '/vendor/autoload.php';
// }



/**
 * SBSATest Class.
 */
class SBSATest {
	/**
	 * Plugin path.
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * WordPress Settings Framework instance.
	 *
	 * @var WordPressSettingsFramework
	 */
	private $sbsa;

	/**
	 * Solution Box Settings Test constructor.
	 */
	public function __construct() {
		$this->plugin_path = plugin_dir_path( __FILE__ );

		$this->sbsa = new Solution_Box_Settings\SettingsAPI( $this->plugin_path . 'src/settings/example-settings.php', 'my_example_settings' );

		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );

		// Add an optional settings validation filter (recommended).
		add_filter( $this->sbsa->get_option_group() . '_settings_validate', array( &$this, 'validate_settings' ) );

	}

	/**
	 * Add settings page.
	 */
	public function add_settings_page() {
		$this->sbsa->add_settings_page(
			array(
				'parent_slug' => 'woocommerce',
				'page_title'  => esc_html__( 'Page Title', 'text-domain' ),
				'menu_title'  => esc_html__( 'menu Title', 'text-domain' ),
				'capability'  => 'manage_woocommerce',
			)
		);
	}

	/**
	 * Validate settings.
	 *
	 * @param mixed $input Input data.
	 *
	 * @return mixed $input
	 */
	public function validate_settings( $input ) {
		// Do your settings validation here
		// Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting.
		return $input;
	}
}

$sbsa_test = new SBSATest();
