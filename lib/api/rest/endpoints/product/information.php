<?php
namespace QUADLAYERS\LicenseClient\Api\Rest\Endpoints\Product;

use QUADLAYERS\LicenseClient\Api\Rest\Endpoints\Base as Base;
use QUADLAYERS\LicenseClient\Api\Fetch\Product\Information as API_Fetch_Product_Information;
use QUADLAYERS\LicenseClient\Models\Plugin as Model_Plugin;
use QUADLAYERS\LicenseClient\Models\UserData as Model_User_Data;
use QUADLAYERS\LicenseClient\Models\Activation as Model_Activation;

/**
 * API_Rest_Request_Product_Information Class
 *
 * @since 1.0.0
 */
class Information extends Base {

	/**
	 * Define rest route path
	 *
	 * @var string
	 */
	protected $rest_route = 'product/information';

	/**
	 * Process rest request. Ej: /wp-json/ql/licenseClient/xxx/product/information
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Request data.
	 * @param Model_Plugin     $model_plugin Model_Plugin instance.
	 * @param Model_Activation $model_activation Model_Activation instance.
	 * @param Model_User_Data  $model_user_data Model_User_Data instance.
	 * @return array
	 */
	public function callback( \WP_REST_Request $request, Model_Plugin $model_plugin, Model_Activation $model_activation, Model_User_Data $model_user_data ) {

		$body = json_decode( $request->get_body() );

		if ( empty( $body->activation_instance ) ) {
			$response = array(
				'error'   => 1,
				'message' => __ql_translate( 'activation_instance not setted.' ),
			);
			return $this->handle_response( $response );
		}

		if ( empty( $body->license_key ) ) {
			$response = array(
				'error'   => 1,
				'message' => __ql_translate( 'license_key not setted.' ),
			);
			return $this->handle_response( $response );
		}

		$activation_instance = trim( $body->activation_instance );
		$license_key         = trim( $body->license_key );

		$fetch = new API_Fetch_Product_Information( $model_plugin );

		$product = $fetch->get_data(
			array(
				'license_key'         => $license_key,
				'activation_instance' => $activation_instance,
			)
		);

		if ( isset( $product->error ) ) {
			$response = array(
				'error'   => isset( $product->error ) ? $product->error : null,
				'message' => isset( $product->message ) ? $product->message : null,
			);
			return $this->handle_response( $product );
		}

		return $this->handle_response( $product );
	}

	/**
	 * Get rest method
	 *
	 * @return string GET
	 */
	public function get_rest_method() {
		return \WP_REST_Server::READABLE;
	}
}
