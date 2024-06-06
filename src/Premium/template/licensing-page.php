<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://solbox.dev/
 * @since      1.0.0
 *
 * @package    solbox
 */

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="license-manager solbox-tab-content">
	<h3><?php esc_html_e( 'Register License Key', 'woo-sticky-add-to-cart' ); ?></h3>
	<div class="solbox-row input-wrapper">
		<div class="solbox-lic-wrap">
			<?php if ( ! $this->is_registered() ) : ?>
				<a href="#" class="lic-status"><?php _e( 'Unregistered', 'sbsa' ); ?></a>
				<?php else : ?>
					<a href="#" class="lic-status registered"><?php _e( 'Registered', 'sbsa' ); ?></a>
				<?php endif; ?>
			<a href="https://solbox.dev/my-account/" target="_blank" class="find-lic"><?php _e( 'Find my key', 'sbsa' ); ?></a>
		</div>
		<div class="solbox-input">
		<?php
		if ( 'valid' == $this->get_registered_license_status() && $this->get_license() ) {
				$plugin_license_key = $this->get_masked_license();
		} else {
				$plugin_license_key = '';
		}
		?>
			<input id="<?php echo esc_attr( $this->slug ); ?>-license-key" placeholder="<?php esc_html_e( 'Enter license key', 'sbsa' ); ?>" name="<?php echo esc_attr( $this->slug ); ?>_license_key" type="text" class="solbox-license-key" value="<?php echo esc_html( $plugin_license_key ); ?>" /> 
	  
			<div class="<?php echo esc_attr( $this->slug ); ?>-lic-submit solbox-submit-wrapper">

				<?php wp_nonce_field( $this->slug . 'license', $this->slug . 'license_nonce' ); ?>
				<?php if ( ! $this->is_registered() ) : ?>
					<button type="button" class="button-secondary lic-action-btn" id="<?php echo esc_attr( $this->slug ); ?>-license-activate">
					<?php _e( 'Register This License Key', 'sbsa' ); ?></button>
				<?php else : ?>
					<button type="button" class="button-primary lic-action-btn" id="<?php echo esc_attr( $this->slug ); ?>-license-deactivate" >
					<?php _e( 'Deactivate License', 'sbsa' ); ?></button>
				<?php endif; ?>
				<div class="loader-container"><div class="loader"></div></div>
			</div>
			<?php
			if ( $this->is_registered() ) {
						$expiration_date = $this->get_expiration_date();

				if ( 'lifetime' == $expiration_date ) {
					$license_desc = esc_html__( 'You have a lifetime licenses, it will never expire.', 'sbsa' );
				} else {
					$license_desc = sprintf(
						esc_html__( 'Your (%2$s) license key is valid until %s.', 'sbsa' ),
						'<strong>' . date_i18n( get_option( 'date_format' ), strtotime( $expiration_date, current_time( 'timestamp' ) ) ) . '</strong>',
						$this->get_license_type()
					);
				}

				if ( 'lifetime' != $expiration_date ) {
					$license_tooltip_desc = sprintf(
						esc_html__( 'The license will automatically renew, if you have an active subscription to the - at %s', 'sbsa' ),
						'<a href="https://solbox.dev/">SolBox.dev</a>'
					);
				} else {
					$license_tooltip_desc = '';
				}

				if ( $this->has_license_expired() ) {
					$license_desc          = sprintf(
						esc_html__( 'Your license key expired on %s. Please input a valid non-expired license key. If you think, that this license has not yet expired (was renewed already), then please save the settings, so that the license will verify again and get the up-to-date expiration date.', 'sbsa' ),
						'<strong>' . date_i18n( get_option( 'date_format' ), strtotime( $expiration_date, current_time( 'timestamp' ) ) ) . '</strong>'
					);
					$license_tooltip_title = '';
					$license_tooltip_desc  = '';

				}
						// echo 'Need help to get started, <a href="'. $this->get_docs_link() . '" target="_blank">view our Online Documentation</a> or <a href="' . $this->get_store_link() . '" target="_blank">contact our support team</a>';
						echo $license_desc . '<br /><i>' . $license_tooltip_desc . '</i>';
			} else {

				echo $this->get_registered_license_status();
			}
			?>
		</div>
	</div>
</div>

<style>

	.license-manager .solbox-input {
		max-width: 700px;
	}
	
	.license-manager .solbox-input input {
		max-width: 265px;
		border-radius: 0;
	}
	
	.solbox-input input {
		width: 100% !important;
		padding: 5px 10px;
		margin: 10px 0;
	}
	
	.solbox-lic-wrap a {
		display: inline-block;
		text-decoration: none;
		background: #9ca2a7;
		color: #fff;
		padding: 10px 20px;
		min-width: 90px;
		text-align: center;
		text-transform: capitalize;
	}
	
	.solbox-lic-wrap a.lic-status {
		background: #ffb817;
		color: #000;
	}
	
	.solbox-submit-wrapper button {
		max-width: 265px;
		width: 100%;
		padding: 3px 10px !important;
	}
	
	.solbox-lic-wrap a.registered {
		background: #84b31f;
	}
	
	.loader-container {
		display: none;
	}
	
	.loader-container.display {
		display: inline-block;
	}
	
	.loader {
		width: 30px;
		height: 30px;
		border-radius: 150px;
		border: 5px solid #fff;
		border-top-color: rgba(0, 0, 0, 0.3);
		box-sizing: border-box;
		animation: loader 1.2s linear infinite;
	}
	
	@keyframes loader {
	
		0% {
			transform: rotate(0deg);
		}
	
		100% {
			transform: rotate(360deg);
		}
	}
	
	@-webkit-keyframes loader {
	
		0% {
			-webkit-transform: rotate(0deg);
		}
	
		100% {
			-webkit-transform: rotate(360deg);
		}
	}
	
	
</style>
	

<script>
	(function ($) {

		const PLUGIN_SLUG = "<?php echo $this->slug; ?>"
	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	/**
	 * License activate/deactivate.
	 *
	 * @param {string} licenseKey lic key.
	 * @param {string} action either activate or deact
	 */



	 function licenseTrigger(licenseKey, action = 'activate') {
		const _nonce = $(`#${PLUGIN_SLUG}_license_nonce`).val();
		const formData = {
			action: `${PLUGIN_SLUG}_license`,
			[`${PLUGIN_SLUG}_license_nonce`]: _nonce,
		};

		if (action === 'activate') {
			formData[`${PLUGIN_SLUG}-license-activate`] = 'activate';
			formData[`${PLUGIN_SLUG}_license_key`] = licenseKey.trim();
		} else {
			formData[`${PLUGIN_SLUG}-license-deactivate`] = 'deactivate';
			formData[`${PLUGIN_SLUG}_license_key`] = '';
		}

		$.post({
			url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
			data: formData,
			beforeSend: () => {
				$(`.${PLUGIN_SLUG}-lic-submit button`).attr('disabled', 'disabled');
				$(`.${PLUGIN_SLUG}-lic-submit .loader-container`).addClass('display');
			},
			success: () => {
				window.location.reload();
			},
		});
	}

	$(() => {
		$(`#${PLUGIN_SLUG}-license-activate`).click(() => {
			const licenseKey = $(`#${PLUGIN_SLUG}-license-key`).val();

			if (!licenseKey.trim()) {
				$(`#${PLUGIN_SLUG}-license-key`).css({ border: '1px solid red' });
			} else {
				$(`#${PLUGIN_SLUG}-license-key`).attr('style', '');
				licenseTrigger(licenseKey);
			}
		});

		$(`#${PLUGIN_SLUG}-license-deactivate`).click(() => {
			licenseTrigger('', 'deactivate');
		});
	});
})(jQuery);
</script>
