<?php

namespace Wicked_Block_Builder\REST_API\v1;

use \finfo;
use \WP_Error;
use \Exception;
use \stdClass;
use \DOMDocument;
use \WP_Post;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_REST_Response;
use \WP_REST_Controller;
use \Wicked_Block_Builder\Block;
use \Wicked_Block_Builder\Block_Version;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

class Block_API extends REST_API {

	private $edit_capability;

    public function __construct() {
		$post_type_name 		= Block::$post_type_name;
		$this->edit_capability 	= "edit_{$post_type_name}s";

        $this->register_routes();
    }

	public function register_routes() {
		register_rest_route( $this->base, '/blocks', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
		) );

		register_rest_route( $this->base, '/blocks/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			),
		) );

        register_rest_route( $this->base, '/blocks/categories', array(
            'methods' 				=> WP_REST_Server::READABLE,
            'callback' 				=> array( $this, 'get_block_categories' ),
			'permission_callback' 	=> '__return_true',
        ) );

		register_rest_route( $this->base, '/blocks/icon', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'upload_icon' ),
				'permission_callback' => array( $this, 'upload_icon_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
		) );		
	}

	public function create_item_permissions_check( $request ) {
		return current_user_can( $this->edit_capability );
	}

	public function get_item_permissions_check( $request ) {
		return current_user_can( $this->edit_capability );
	}

	public function update_item_permissions_check( $request ) {
		return current_user_can( $this->edit_capability );
	}

	public function publish_block_permissions_check( $request ) {
		return current_user_can( $this->edit_capability );
	}

	public function upload_icon_permissions_check( $request ) {
		return current_user_can( $this->edit_capability );
	}

	public function get_item( $request ) {
		try {
			$id = $request->get_param( 'id' );

			return new Block( $id );
		} catch ( Exception $exception ) {
			return new WP_Error(
				'wbb_error',
				$exception->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	public function update_item( $request ) {
		try {
			// Decode the JSON ourself since WordPress decodes JSON as an array
			// and we want an object
			$json 	= json_decode( $request->get_body(), false );
			$block 	= new Block();
			$block->from_json( $json );	
			$block->save();				
			$block->publish_version_if_needed();
			$block->save_json();

			return $block;
		} catch ( Exception $exception ) {
			return new WP_Error(
				'wbb_error',
				$exception->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	public function create_item( $request ) {
		try {
			// Decode the JSON ourself since WordPress decodes JSON as an array
			// and we want an object
			$json 	= json_decode( $request->get_body(), false );
			$block 	= new Block();
			$block->from_json( $json );
			$block->save();

			// Create a snapshot of the block
			$block->publish_new_version();

			// Save block to JSON file
			$block->save_json();

			return $block;
		} catch ( Exception $exception ) {
			return new WP_Error(
				'wbb_error',
				$exception->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	public function get_block_categories( WP_REST_Request $request ) {
        $categories = get_block_categories( new WP_Post( new stdClass() ) );

		usort( $categories, function( $a, $b ){
			return strcmp( $a['title'], $b['title'] );
		} );

		return $categories;
	}

	public function upload_icon( WP_REST_Request $request ) {
		try {
			$files = $request->get_file_params();

			if ( ! isset( $files['icon'] ) ) {
				throw new Exception( __( 'Invalid file.', 'wicked-block-builer' ) );
			}

			$file = $files['icon'];

			// Per PHP docs...
			if ( ! isset( $file['error'] ) || is_array( $file['error'] ) ) {
				throw new Exception( __( 'Invalid file.', 'wicked-block-builer' ) );
			}

			if ( UPLOAD_ERR_OK != $file['error'] ) {
				throw new Exception( __( 'Invalid upload.', 'wicked-block-builer' ) );
			}

			// Limit size to 500kb
			if ( $file['size'] > ( 1024 * 500 ) ) {
				throw new Exception( __( 'Icon cannot exceed 500kb.', 'wicked-block-builer' ) );
			}

			// Check MIME type
			$finfo = new finfo( FILEINFO_MIME_TYPE );

			if ( false == in_array(
				$finfo->file( $file['tmp_name'] ),
				array(
					'image/svg',
					'image/svg+xml'
				),
				true
			) ) {
				throw new Exception(
					__( 'Invalid file format. Make sure your SVG does not contain an embedded graphic.',
					'wicked-block-builer'
				) );
			}

			$s = file_get_contents( $files['icon']['tmp_name'] );

			$doc = new DOMDocument();
	
			if ( false === @$doc->loadXml( $s ) ) {
				throw new Exception( __( 'Error reading SVG file. Make sure SVG syntax is valid.', 'wicked-block-builer' ) );
			}

			// This will strip out <?xml...
			$html = $doc->saveHtml();

			// Put everything on one line
			$html = preg_replace( '/\r|\n|\t/', '', $html );

			return $html;
		} catch ( Exception $exception ) {
			return new WP_Error(
				'wbb_error',
				$exception->getMessage(),
				array( 'status' => 500 )
			);
		}
	}
}
