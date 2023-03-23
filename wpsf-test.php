<?php
/**
 * Plugin Name: WP Settings Framework Example
 * Description: An example of the WP Settings Framework in action.
 * Version: 1.6.0
 * Author: Gilbert Pellegrom
 * Author URI: http://dev7studios.com
 *
 * @package sbsa
 */

 defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/src/SettingsAPI.php';

// autoloader.
if ( ! class_exists( \SolutionBoxSettings\SettingsAPI::class ) ) {
	require __DIR__ . '/vendor/autoload.php';
}



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
	 * sbsaTest constructor.
	 */
	public function __construct() {
		$this->plugin_path = plugin_dir_path( __FILE__ );

		$this->sbsa = new SolutionBoxSettings\SettingsAPI( $this->plugin_path . 'src/settings/example-settings.php', 'my_example_settings' );

		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );

		// Add an optional settings validation filter (recommended).
		add_filter( $this->sbsa->get_option_group() . '_settings_validate', array( &$this, 'validate_settings' ) );
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
