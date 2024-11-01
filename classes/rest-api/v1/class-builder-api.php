<?php

namespace Wicked_Block_Builder\REST_API\v1;

use \WP_Error;
use \Exception;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_REST_Response;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

class Builder_API extends REST_API {

    public function __construct() {
        $this->register_routes();
    }

	public function register_routes() {
		register_rest_route( $this->base, '/builder/post-types', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_types' ),
				'permission_callback' => array( $this, 'get_post_types_permissions_check' ),
			),
		) );
	}

	public function get_post_types_permissions_check( $request ) {
		return current_user_can( 'edit_posts' );
	}

	public function get_post_types( $request ) {
		/**
		 * There may be cases when others want to allow post types that don't match our arguments.
		 * Given them an opportunity to change them.
		 * 
		 * @since 1.4.1
		 * 
		 * @param array $args
		 *  The arguments to pass to get_post_types.
		 * @param WP_REST_Request $request
		 *  The current REST API request.
		 */
		$args 		= apply_filters( 'wbb_builder_api_get_post_types_args', array( 'show_ui' => true ), $request );
		$post_types = get_post_types( $args, 'objects' );

		return new WP_REST_Response( $post_types, 200 );
	}
}
