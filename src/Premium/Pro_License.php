<?php

namespace Solution_Box_Settings\Premium;

/**
 *  License log....
 *
 * @since 1.0.0
 */
abstract class Pro_License {


	/**
	 * The name of the product on our store.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $product_name;

	/**
	 * ID of the product on company store.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public $product_id;

	/**
	 * URL of the store.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public $store_url;

	/**
	 * Plugin slug
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public $slug;


	/**
	 * Plugin update path
	 *
	 * @since 1.0.0
	 */
	public $upgrade_path;

	/**
	 * Pro Plugin version
	 *
	 * @since 1.0.0
	 */
	public $version;


	/**
	 * Option key for registration data.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public $registration_data_option_key;

	public function __construct( $slug, $product_name, $product_id, $store_url ) {

		if ( empty( $slug ) || empty( $product_name ) || empty( $product_id ) || empty( $store_url ) ) {
			throw new \InvalidArgumentException( 'Pro_License arguments couldn`t be empty  ' );
		}

		$this->product_name                 = $product_name;
		$this->product_id                   = $product_id;
		$this->store_url                    = $store_url;
		$this->slug                         = $slug;
		$this->registration_data_option_key = $this->slug . '_pro_registration_data';
		$this->upgrade_path                 = $this->get_upgrade_path();
		$this->version                      = $this->get_pro_version();

		add_action( 'admin_init', array( $this, 'init_plugin_updater' ) );
		add_action( 'admin_init', array( $this, 'manage_license' ) );
	}

	/**
	 * Plugin upgrade path
	 */
	abstract function get_upgrade_path(): string;
	/**
	 * Plugin version
	 */
	abstract function get_pro_version(): string;

	/**
	 * Admin hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_hooks() {
		add_action( 'wp_ajax_' . $this->slug . '_license', $this, 'manage_license' );
	}

	public function manage_license() {

		if ( isset( $_POST[ $this->slug . '-license-activate' ] ) ) {
			$registration_data = $this->activate_license( sanitize_text_field( wp_unslash( $_POST[ $this->slug . '_license_key' ] ) ) );

			if ( $registration_data['license_data']['success'] ) {

				update_option( $this->slug . '_license_key', trim( $_POST[ $this->slug . '_license_key' ] ) );
				wp_send_json_success( array( 'data' => $registration_data['license_data'] ) );
			} else {
				wp_send_json_error( array( 'error' => $registration_data['error_message'] ) );
			}
		}

		if ( isset( $_POST[ $this->slug . '-license-deactivate' ] ) ) {

			$license           = get_option( $this->slug . '_license_key' );
			$registration_data = $this->deactivate_license( sanitize_text_field( wp_unslash( $license ) ) );
			wp_die();
		}
	}

	/**
	 * Try to activate the supplied license on our store.
	 *
	 * @since 1.0.0
	 * @param string $license License key to activate.
	 * @return array
	 */
	public function activate_license( $license ) {
		$license = trim( $license );

		$result = array(
			'license_key'   => $license,
			'license_data'  => array(),
			'error_message' => '',
		);

		// Data to send in our API request.
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_id'    => $this->product_id,
			'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
			$this->store_url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		// Make sure the response is not WP_Error.
		if ( is_wp_error( $response ) ) {
			$result['error_message'] = $response->get_error_message() . esc_html__( 'If this error keeps displaying, please contact our support!', 'woo-bpo-pro' );

			return $result;
		}

		// Make sure the response is OK (200).
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$result['error_message'] = esc_html__( 'An error occurred, please try again.', 'woo-bpo-pro' ) . esc_html__( 'An error occurred, please try again. If this error keeps displaying, please contact our support at ', 'woo-bpo-pro' );

			return $result;
		}

		// Get the response data.
		$result['license_data'] = json_decode( wp_remote_retrieve_body( $response ), true );

		// Generate the error message.
		if ( false === $result['license_data']['success'] ) {

			switch ( $result['license_data']['error'] ) {

				case 'expired':
					$result['error_message'] = sprintf(
						esc_html__( 'Your license key expired on %s.', 'woo-bpo-pro' ),
						date_i18n( get_option( 'date_format' ), strtotime( $result['license_data']['expires'], current_time( 'timestamp' ) ) )
					);
					break;

				case 'revoked':
					$result['error_message'] = esc_html__( 'Your license key has been disabled.', 'woo-bpo-pro' );
					break;

				case 'missing':
					$result['error_message'] = esc_html__( 'Your license key is Invalid.', 'woo-bpo-pro' );
					break;

				case 'invalid':
				case 'site_inactive':
					$result['error_message'] = esc_html__( 'Your license is not active for this URL.', 'woo-bpo-pro' );
					break;

				case 'item_name_mismatch':
					$result['error_message'] = sprintf( esc_html__( 'This appears to be an invalid license key for %s.', 'woo-bpo-pro' ), $this->product_name );
					break;

				case 'no_activations_left':
					$result['error_message'] = esc_html__( 'Your license key has reached its activation limit.', 'woo-bpo-pro' );
					break;

				default:
					$result['error_message'] = esc_html__( 'An error occurred, please try again.', 'woo-bpo-pro' );
					break;
			}
		}

		update_option( $this->registration_data_option_key, $result );

		return $result;
	}

	/**
	 * Deactivate the supplied license on our store.
	 *
	 * @since 1.0.0
	 * @param string $license License key to activate.
	 * @return array $result response result.
	 */
	public function deactivate_license( $license ) {
		$license = trim( $license );

		$result = array(
			'license_key'   => $license,
			'license_data'  => array(),
			'error_message' => '',
		);

		// Data to send in our API request.
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_id'    => $this->product_id,
			'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
			$this->store_url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		// Make sure the response is not WP_Error.
		if ( is_wp_error( $response ) ) {
			$result['error_message'] = $response->get_error_message() . esc_html__( 'If this error keeps displaying, please contact our support', 'woo-bpo-pro' );

			return $result;
		}

		// Make sure the response is OK (200).
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$result['error_message'] = esc_html__( 'An error occurred, please try again.', 'woo-bpo-pro' ) . esc_html__( 'An error occurred, please try again. If this error keeps displaying, please contact our support!', 'woo-bpo-pro' );

			return $result;
		}

		// Get the response data.
		$result['license_data'] = json_decode( wp_remote_retrieve_body( $response ), true );

		// Generate the error message.
		if ( false === $result['license_data']['success'] ) {

			switch ( $result['license_data']['error'] ) {
				case 'expired':
					$result['error_message'] = sprintf(
						esc_html__( 'Your license key expired on %s.', 'woo-bpo-pro' ),
						date_i18n( get_option( 'date_format' ), strtotime( $result['license_data']['expires'], current_time( 'timestamp' ) ) )
					);
					break;

				case 'revoked':
					$result['error_message'] = esc_html__( 'Your license key has been disabled.', 'woo-bpo-pro' );
					break;

				case 'missing':
					$result['error_message'] = esc_html__( 'Your license key is Invalid.', 'woo-bpo-pro' );
					break;

				case 'invalid':
				case 'site_inactive':
					$result['error_message'] = esc_html__( 'Your license is not active for this URL.', 'woo-bpo-pro' );
					break;

				case 'item_name_mismatch':
					$result['error_message'] = sprintf( esc_html__( 'This appears to be an invalid license key for %s.', 'woo-bpo-pro' ), $this->product_name );
					break;

				case 'no_activations_left':
					$result['error_message'] = esc_html__( 'Your license key has reached its activation limit.', 'woo-bpo-pro' );
					break;

				default:
					$result['error_message'] = esc_html__( 'An error occurred, please try again.', 'woo-bpo-pro' );
					break;
			}
		}

		update_option( $this->registration_data_option_key, $result );

		return $result;
	}

	/**
	 * Check and get the license data.
	 *
	 * @since 1.0.0
	 * @param string $license The license key.
	 * @return false|array
	 */
	public function check_license( $license ) {
		$license = trim( $license );

		$api_params = array(
			'edd_action' => 'check_license',
			'license'    => $license,
			'item_id'    => $this->product_id,
			'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
			$this->store_url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Get the registration data helper function.
	 *
	 * @since 1.0.0
	 * @return false|array
	 */
	public function get_registration_data() {
		return get_option( $this->registration_data_option_key );
	}

	/**
	 * Check the license is activated.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_activated() {

		$data = $this->get_registration_data();

		if ( empty( $data ) ) {
			return false;
		}

		if ( ! empty( $data['license_data']['license'] ) && 'valid' === $data['license_data']['license'] ) {
			return true;
		} else {
			return false;
		}

	}


	/**
	 * Get the license type.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_license_type() {

		$data = $this->get_registration_data();

		if ( empty( $data ) ) {
			return false;
		}

		if ( ! empty( $data['license_data']['success'] ) && ! empty( $data['license_data']['license'] ) && 'valid' === $data['license_data']['license'] ) {

			if ( 1 == $data['license_data']['price_id'] ) {
				return 'Personal';
			}
			if ( 2 == $data['license_data']['price_id'] ) {
				return 'Small Business';
			}
			if ( 3 == $data['license_data']['price_id'] ) {
				return 'Life Time';
			}
		}

	}

	/**
	 * Get license id.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function get_license_id() {

		$data = $this->get_registration_data();

		if ( empty( $data ) ) {
			return false;
		}

		if ( ! empty( $data['license_data']['success'] ) && ! empty( $data['license_data']['license'] ) && 'valid' === $data['license_data']['license'] ) {

			return $data['license_data']['price_id'];
		}

	}

	/**
	 * Check if the license is registered (has/had a valid license).
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_registered() {
		$data = $this->get_registration_data();

		if ( empty( $data ) ) {
			return false;
		}

		if ( ! empty( $data['license_data']['success'] ) && ! empty( $data['license_data']['license'] ) && 'valid' === $data['license_data']['license'] ) {
			return true;
		}

		return false;
	}


	/**
	 * Mask on License Key.
	 *
	 * @since 1.0.0
	 * @param string $key license key.
	 * @return string masked license key.
	 */
	public function mask_license( $key ) {

		$license_parts  = str_split( $key, 4 );
		$i              = count( $license_parts ) - 1;
		$masked_license = '';

		foreach ( $license_parts as $license_part ) {
			if ( $i == 0 ) {
				$masked_license .= $license_part;
				continue;
			}

			$masked_license .= str_repeat( '&bull;', strlen( $license_part ) ) . '&ndash;';
			--$i;
		}

		return $masked_license;

	}

	/**
	 * Get masked license.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function get_masked_license() {
		return $this->mask_license( $this->get_license() );
	}

	/**
	 * Get up baby -).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function get_license() {
		return get_option( $this->slug . '_license_key' );
	}

	/**
	 * Get the registered license key.
	 *
	 * @since 1.0.0
	 * @return bool|string
	 */
	public function get_registered_license_key() {
		$data = $this->get_registration_data();

		if ( empty( $data ) ) {
			return '';
		}

		if ( empty( $data['license_key'] ) ) {
			return '';
		}

		return $data['license_key'];
	}


	/**
	 * Get the registered license status.
	 *
	 * @since 1.0.0
	 * @return bool|string
	 */
	public function get_registered_license_status() {
		$data = $this->get_registration_data();

		if ( empty( $data ) ) {
			return '';
		}

		if ( ! empty( $data['error_message'] ) ) {
			return $data['error_message'];
		}

		switch ( $data['license_data']['license'] ) {
			case 'deactivated':
				$message = sprintf(
					esc_html__( 'Your license key has been deactivated on %s. Please Activate your license key to continue using Automatic Updates and Premium Support.', 'woo-bpo-pro' ),
					'<strong>' . date_i18n( get_option( 'date_format' ), strtotime( current_time( 'timestamp' ) ) ) . '</strong>'
				);
				delete_option( $this->slug . '_license_key' );
				return $message;
			break;

			case 'revoked':
				$message = esc_html__( 'Your license key has been disabled.', 'woo-bpo-pro' );
				break;
		}

		return $data['license_data']['license'];
	}


	/**
	 * Check, if the registered license has expired.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function has_license_expired() {
		$data = $this->get_registration_data();

		if ( empty( $data ) ) {
			return true;
		}

		if ( empty( $data['license_data']['expires'] ) ) {
			return true;
		}

		// If it's a lifetime license, it never expires.
		if ( 'lifetime' == $data['license_data']['expires'] ) {
			return false;
		}

		$now             = new \DateTime();
		$expiration_date = new \DateTime( $data['license_data']['expires'] );

		$is_expired = $now > $expiration_date;

		if ( ! $is_expired ) {
			return false;
		}

		$prevent_check = get_transient( $this->slug . '-dont-check-license' );

		if ( $prevent_check ) {
			return true;
		}

		$new_license_data = $this->check_license( $this->get_registered_license_key() );
		set_transient( $this->slug . '-dont-check-license', true, DAY_IN_SECONDS );

		if ( empty( $new_license_data ) ) {
			return true;
		}

		if (
		! empty( $new_license_data['success'] ) &&
		! empty( $new_license_data['license'] ) &&
		'valid' === $new_license_data['license']
		) {
			$new_expiration_date = new \DateTime( $new_license_data['expires'] );

			$new_is_expired = $now > $new_expiration_date;

			if ( ! $new_is_expired ) {
				$data['license_data']['expires'] = $new_license_data['expires'];

				update_option( $this->registration_data_option_key, $data );
			}

			return $new_is_expired;
		}

		return true;
	}

	/**
	 * Get license expiration date.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_expiration_date() {
		$data = $this->get_registration_data();

		if ( empty( $data ) ) {
			return '';
		}

		return ( ! empty( $data['license_data']['expires'] ) ) ? $data['license_data']['expires'] : '';
	}



	/**
	 * Del .....
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function del_license_data() {
		delete_option( $this->slug . '_license_key' );
		delete_option( $this->registration_data_option_key );
	}

	/**
	 * Initialize the plugin updater class.
	 *
	 * @return void
	 */
	public function init_plugin_updater() {
		// Skip the plugn updater init, if the plugin is not registered, or if the license has expired.
		// if ( ! $this->is_registered() || $this->has_license_expired() ) {
		// return false;
		// }

		// Retrieve our license key from the DB.
		$license_key = $this->get_registered_license_key();

		// Setup the updater.
		$edd_updater = new Plugin_Updater(
			$this->store_url,
			$this->upgrade_path,
			array(
				'version' => $this->version,
				'license' => $license_key,
				'item_id' => $this->product_id,
				'author'  => 'jacksl',
				'beta'    => false,
			)
		);
	}

	/**
	 * Validated the license in every 24 hours.
	 *
	 * @since 1.0.0
	 * @return bool either valid or not.
	 */
	public function valid_license() {

		$prevent_check = get_transient( $this->slug . '-dont-check-license' );

		if ( ! $prevent_check && ! empty( $this->get_registered_license_key() ) ) {
			$this->activate_license( $this->get_registered_license_key() );
			set_transient( $this->slug . '-dont-check-license', true, DAY_IN_SECONDS );
		}

		return $this->is_registered();
	}

	public function get_license_page() {

		include 'template/licensing-page.php';
	}
}
