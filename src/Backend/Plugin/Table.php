<?php

namespace QuadLayers\WP_License_Client\Backend\Plugin;

use QuadLayers\WP_License_Client\Models\Plugin as Model_Plugin;
use QuadLayers\WP_License_Client\Models\Activation as Model_Activation;

/**
 * Controller_Plugin_Table Class
 *
 * Implement plugin version notification in plugins table.
 *
 * @since 1.0.0
 */
class Table {

	/**
	 * Instantiated Model_Plugin in the constructor.
	 *
	 * @var Model_Plugin
	 */
	private $plugin;

	/**
	 * Instantiated Model_Activation in the constructor.
	 *
	 * @var Model_Activation
	 */
	private $activation;

	/**
	 * Setup class.
	 *
	 * @param Model_Plugin $plugin
	 */
	public function __construct( Model_Plugin $plugin, Model_Activation $activation ) {
		$this->plugin     = $plugin;
		$this->activation = $activation;
		add_action(
			'admin_init',
			function () {
				add_filter( 'plugins_api', array( $this, 'add_fetch_data' ), 10, 3 );
				// add_action( 'in_plugin_update_message-' . $this->plugin->get_base(), array( $this, 'add_update_notification' ), 10, 2 );
				add_action( 'after_plugin_row_' . $this->plugin->get_base(), array( $this, 'add_row_notification' ), 100, 2 );
			}
		);
	}

	/**
	 * Include fetched data in transient to the plugin in the plugins table.
	 *
	 * @param object $return
	 * @param string $action
	 * @param object $args
	 * @return object
	 */
	public function add_fetch_data( $return, $action, $args ) {

		if ( 'plugin_information' !== $action ) {
			return $return;
		}

		if ( $args->slug != $this->plugin->get_slug() ) {
			return $return;
		}

		$transient = get_site_transient( 'update_plugins' );

		if ( empty( $transient->no_update[ $this->plugin->get_base() ] ) ) {
			return $return;
		}

		$plugin = $transient->no_update[ $this->plugin->get_base() ];

		if ( ! $plugin ) {
			return $return;
		}

		if ( isset( $plugin->sections['screenshots'] ) && is_array( $plugin->sections['screenshots'] ) ) {
			$plugin->sections['screenshots'] = $this->add_screenshots( $plugin->sections['screenshots'] );
		}

		return $plugin;
	}

	/**
	 * Add product screenshots to the plugin in the View details modal of the plugins table.
	 *
	 * @param array $screenshots
	 * @return string
	 */
	public function add_screenshots( array $screenshots = array() ) {
		ob_start();
		?>
			<ol>
				<?php foreach ( $screenshots as $key => $image ) : ?>
					<li><a href="<?php echo esc_url( $image->src ); ?>"><img src="<?php echo esc_url( $image->src ); ?>" alt="<?php echo esc_html( $image->caption ); ?>"></a></li>
				<?php endforeach; ?>
			</ol>
		<?php
		return ob_get_clean();
	}

	/**
	 * Require license validation notification in the plugins table if the automatic updates are disabled.
	 *
	 * @param object $plugin_data
	 * @param object $response
	 * @return string
	 */
	// public function add_update_notification( $plugin_data, $response ) {

	// **
	// * Check if the license is activated. If not, show a notice.
	// */
	// if ( 'none' === $this->activation->status() ) {
	// printf(
	// '</p></div><span class="notice notice-error notice-alt" style="display:block; padding: 10px;"><b>%s</b> %s</span>',
	// esc_html__( 'Activate your license.', 'wp-license-client' ),
	// sprintf(
	// esc_html__( 'Please visit %1$s to activate the license or %2$s in our website.', 'wp-license-client' ),
	// sprintf(
	// '<a href="%s" target="_blank">%s</a>',
	// esc_url( $this->plugin->get_menu_license_url() ),
	// esc_html__( 'settings', 'wp-license-client' )
	// ),
	// sprintf(
	// '<a href="%s" target="_blank">%s</a>',
	// esc_url( $this->plugin->get_url() ),
	// esc_html__( 'purchase', 'wp-license-client' )
	// )
	// )
	// );
	// return;
	// }

	// **
	// * Check if the download link is valid. If not, show a notice.
	// */
	// if ( empty( $response->download_link ) || filter_var( $response->download_link, FILTER_VALIDATE_URL ) === false ) {
	// printf(
	// '</p></div><span class="notice notice-error notice-alt" style="display:block; padding: 10px;"><b>%s</b> %s</span>',
	// esc_html__( 'Automatic updates are disabled.', 'wp-license-client' ),
	// sprintf(
	// esc_html__( 'Please contact the plugin author %1$s.', 'wp-license-client' ),
	// sprintf(
	// '<a href="%s" target="_blank">%s</a>',
	// esc_url( $this->plugin->get_url() ),
	// esc_html__( 'here', 'wp-license-client' )
	// )
	// )
	// );
	// }
	// }

	/**
	 * Add error notification to the active plugin row.
	 *
	 * @return void
	 */
	public function add_row_notification( $plugin_file, $plugin_data ) {

		// Check if current user has the required capability and we are not in the network admin
		if ( is_network_admin() || ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$plugin_base = $this->plugin->get_base();

		// Check if the plugin is active
		$is_active = is_plugin_active( $plugin_base ) ? 'active' : '';

		if ( 'none' === $this->activation->status() ) {
			// Add notification for non-activated license
			echo '<tr class="plugin-update-tr installer-plugin-update-tr ' . esc_attr( $is_active ) . '" style="position:relative;top:-1px;">
			<td colspan="4" class="plugin-update colspanchange">
				<div class="update-message notice notice-error notice-alt inline">
					<p>' .
					'<b>' . esc_html__( 'The plugin license is not activated.', 'wp-license-client' ) . '</b> ' .
					sprintf(
						esc_html__( 'Please visit the %1$s to activate your license or %2$s one from our website.', 'wp-license-client' ),
						sprintf(
							'<a href="%s">%s</a>',
							esc_url( $this->plugin->get_menu_license_url() ),
							esc_html__( 'settings', 'wp-license-client' )
						),
						sprintf(
							'<a href="%s" target="_blank">%s</a>',
							esc_url( $this->plugin->get_url() ),
							esc_html__( 'purchase', 'wp-license-client' )
						)
					) . '</p>
				</div>
			</td>
		</tr>';
			return;
		}

		if ( 'expired' === $this->activation->status() ) {
			// Add notification for expired license
			echo '<tr class="plugin-update-tr installer-plugin-update-tr ' . esc_attr( $is_active ) . '" style="position:relative;top:-1px;">
			<td colspan="4" class="plugin-update colspanchange">
				<div class="update-message notice notice-error notice-alt inline">
					<p>' .
					'<b>' . esc_html__( 'Your plugin license has expired.', 'wp-license-client' ) . '</b> ' .
					sprintf(
						esc_html__( 'Please visit your %1$s to renew your license or %2$s a new one from our website.', 'wp-license-client' ),
						sprintf(
							'<a href="%s" target="_blank">%s</a>',
							esc_url( $this->plugin->get_license_key_url() ),
							esc_html__( 'account', 'wp-license-client' )
						),
						sprintf(
							'<a href="%s" target="_blank">%s</a>',
							esc_url( $this->plugin->get_url() ),
							esc_html__( 'purchase', 'wp-license-client' )
						)
					) . '</p>
				</div>
			</td>
		</tr>';
		}
	}
}
