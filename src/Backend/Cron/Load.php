<?php
namespace QuadLayers\WP_License_Client\Backend\Cron;

use QuadLayers\WP_License_Client\Models\Plugin as Model_Plugin;
use QuadLayers\WP_License_Client\Models\Activation as Model_Activation;
use QuadLayers\WP_License_Client\Models\UserData as Model_User_Data;
use QuadLayers\WP_License_Client\Api\Fetch\Activation\Create as API_Fetch_Activation_Create;

/**
 * Class Cron
 */
class Load {
	protected $plugin;
	protected $activation;
	protected $plugin_slug;
	protected $plugin_file;
	protected $user_data;

	/**
	 * Constructor to initialize the plugin and activation models.
	 *
	 * @param Model_Plugin     $model_plugin
	 * @param Model_Activation $model_activation
	 */
	public function __construct( Model_Plugin $model_plugin, Model_Activation $model_activation, Model_User_Data $model_user_data = null ) {
		$this->plugin      = $model_plugin;
		$this->activation  = $model_activation;
		$this->user_data   = $model_user_data;
		$this->plugin_slug = $this->plugin->get_slug(); // Get the plugin slug for unique cron prefix
		$this->plugin_file = $this->plugin->get_file(); // Get the plugin file for deactivation

		// Add custom cron schedule for monthly execution
		add_filter( 'cron_schedules', array( $this, 'add_custom_monthly_cron_schedule' ) );

		// Schedule the cron event with a unique action name using the plugin slug
		if ( ! wp_next_scheduled( $this->plugin_slug . '_monthly_license_validation' ) ) {
			wp_schedule_event( time(), 'monthly', $this->plugin_slug . '_monthly_license_validation' );
		}

		// Add action hook for the scheduled cron job
		add_action( $this->plugin_slug . '_monthly_license_validation', array( $this, 'validate_license' ) );

		// Register deactivation hook to clean up the cron event
		register_deactivation_hook( $this->plugin_file, array( $this, 'deactivate' ) );
	}

	/**
	 * Adds a custom 'monthly' cron schedule.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public function add_custom_monthly_cron_schedule( $schedules ) {
		$schedules['monthly'] = array(
			'interval' => 30 * DAY_IN_SECONDS, // 30 days
			'display'  => __( 'Once Monthly', 'wp-license-client' ),
		);
		return $schedules;
	}

	/**
	 * Function to validate the license.
	 * If the license is invalid, it deletes the activation data.
	 */
	public function validate_license() {
		$user_data = $this->user_data->get();

		if ( ! isset( $user_data['license_email'], $user_data['license_key'] ) ) {
			return;
		}

		// Assuming the license data contains a 'status' field
		if ( 'none' === $this->activation->status() ) {
			return;
		}

		$activation = ( new API_Fetch_Activation_Create( $this->plugin ) )->get_data(
			array(
				'license_email'   => $user_data['license_email'],
				'license_key'     => $user_data['license_key'],
				'activation_site' => $this->plugin->get_activation_site(),
			)
		);

		if ( isset( $activation->error ) ) {
			$this->activation->delete();
            return;
		}

		$this->activation->create( (array) $activation );
	}

	/**
	 * Deactivates the cron event on plugin deactivation.
	 */
	public function deactivate() {
		$timestamp = wp_next_scheduled( $this->plugin_slug . '_monthly_license_validation' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->plugin_slug . '_monthly_license_validation' );
		}
	}
}
