<?php

namespace QuadLayers\WP_License_Client;

/**
 * Include the Composer autoload file if you're not using Composer in your package.
 *
 * require_once __DIR__ . '/../vendor/autoload.php';
 */

/**
 * Models
 */
use QuadLayers\WP_License_Client\Models\Plugin as Model_Plugin;
use QuadLayers\WP_License_Client\Models\UserData as Model_User_Data;
use QuadLayers\WP_License_Client\Models\Activation as Model_Activation;
/**
 * API
 */
use QuadLayers\WP_License_Client\Api\Rest\RoutesLibrary as API_Rest_Routes_Library;
/**
 * Controllers
 */
use QuadLayers\WP_License_Client\Backend\Plugin\Information as Controller_Plugin_Information;
use QuadLayers\WP_License_Client\Backend\Plugin\Update as Controller_Plugin_Update;
use QuadLayers\WP_License_Client\Backend\Plugin\Table as Controller_Plugin_Table;
use QuadLayers\WP_License_Client\Backend\Page\Load as Controller_Page;
use QuadLayers\WP_License_Client\Backend\Notice\Load as Controller_Notice;
use QuadLayers\WP_License_Client\Backend\Menu\Load as Controller_Menu;
use QuadLayers\WP_License_Client\Backend\Cron\Load as Controller_Cron;
/**
 * Class Load
 *
 * @package QuadLayers\WP_License_Client\Load
 */
final class Load {

	/**
	 * Client data initialized in the constructor.
	 *
	 * @var array
	 */
	public $client_data;

	/**
	 * Registered rest routes in the constructor of API_Rest_Routes_Library.
	 *
	 * @var API_Rest_Routes_Library
	 */
	public $routes;

	/**
	 * Instantiated Model_Plugin in the constructor.
	 *
	 * @var Model_Plugin
	 */
	public $plugin;

	/**
	 * Instantiated Model_Activation in the constructor.
	 *
	 * @var Model_Activation
	 */
	public $activation;

	/**
	 * Instantiated Model_User_Data in the constructor.
	 *
	 * @var Model_User_Data
	 */
	public $user_data;

	/**
	 * Setup client instance based on client data.
	 *
	 * @param array $client_data Client data.
	 */
	public function __construct( array $client_data ) {

		$this->client_data = $client_data;

		/**
		 * Get plugin file path
		 */
		if ( ! isset( $this->client_data['plugin_file'] ) ) {
			trigger_error( esc_html__( 'Please include a valid plugin_file.', 'wp-license-client' ), E_USER_NOTICE );
		}

		/**
		 * Rest API support
		 */
		$this->routes = new API_Rest_Routes_Library( $this->client_data );

		/**
		 * Don't load plugin models outside admin panel
		 */
		if ( ! is_admin() ) {
			return;
		}

		/**
		 * Load plugin models
		 */
		$this->plugin = new Model_Plugin( $this->client_data );

		if ( ! $this->plugin->is_valid() ) {
			trigger_error( sprintf( esc_html__( '%s is not a valid plugin file.', 'wp-license-client' ), esc_html( $this->plugin->get_file() ) ), E_USER_NOTICE );
		}

		$this->activation = new Model_Activation( $this->plugin );
		$this->user_data  = new Model_User_Data( $this->plugin );
		new Controller_Plugin_Information( $this->plugin, $this->activation, $this->user_data );
		new Controller_Plugin_Update( $this->plugin, $this->activation, $this->user_data );
		new Controller_Plugin_Table( $this->plugin, $this->activation, $this->user_data );
		new Controller_Page( $this->plugin, $this->activation, $this->user_data );
		new Controller_Notice( $this->plugin, $this->activation, $this->user_data );
		new Controller_Menu( $this->plugin, $this->activation );
		new Controller_Cron( $this->plugin, $this->activation );
	}
}
