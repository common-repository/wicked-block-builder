<?php

namespace Wicked_Block_Builder\REST_API\v1;

use \WP_Error;
use \Exception;
use \stdClass;
use \WP_Post;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_REST_Response;
use \WP_REST_Controller;
use \WP_Block_Pattern_Categories_Registry;
use \Wicked_Block_Builder\Block_Pattern;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

class Block_Pattern_API extends REST_API {

	private $edit_capability;

    public function __construct() {
		$post_type_name 		= Block_Pattern::$post_type_name;
		$this->edit_capability 	= "edit_{$post_type_name}s";

		add_action( "rest_insert_{$post_type_name}", array( $this, 'insert_block_pattern' ), 10, 3 );

		$this->register_routes();
    }

	public function register_routes() {
		register_rest_route( $this->base, '/patterns/categories', array(
			'methods' 				=> WP_REST_Server::READABLE,
			'callback' 				=> array( $this, 'get_block_pattern_categories' ),
			'permission_callback' 	=> '__return_true',
		) );
	}

	public function get_block_pattern_categories( WP_REST_Request $request ) {
		$response 	= array();
		$terms 		= get_terms( array(
			'hide_empty' 	=> false,
			'taxonomy' 		=> Block_Pattern::$category_taxonomy_name,
		) );

		foreach ( $terms as $term ) {
			$response[] = ( object ) array(
				'name' 	=> $term->slug,
				'label' => $term->name,
			);
		}

		return new WP_REST_Response( $response );
	}

	/**
	 * WordPress "rest_insert_{$post_type_name}" action.  Handles assigning a
	 * category to a block pattern when one is created via the REST API.
	 */
	public function insert_block_pattern( WP_Post $post, WP_REST_Request $request, bool $creating ) {
		$category = $request->get_param( 'wbbPatternCategory' );

		if ( $creating && $category ) {
			wp_set_object_terms( $post->ID, $category, Block_Pattern::$category_taxonomy_name );
		}
	}
}
