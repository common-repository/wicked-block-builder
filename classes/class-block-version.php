<?php

namespace Wicked_Block_Builder;

use \Exception;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Represents a block version object.
 */
class Block_Version extends Block {

	/**
	 * The block's post type name.
	 *
	 * @var string
	 */
	public static $post_type_name = 'wbb_block_version';

	/**
	 * The post ID of the block that the version belongs to.
	 *
	 * @var int
	 */
	public $parent;

	/**
	 * Registers the block version's post type.
	 */
	public static function register_post_type() {
		register_post_type( self::$post_type_name, array(
			'label'					=> _x( 'Block Versions', 'Post type plural name', 'wicked-block-builder' ),
			'labels'				=> array(
				'name'					=> _x( 'Block Versions', 'Post type plural name', 'wicked-block-builder' ),
				'singular_name'			=> _x( 'Block Version', 'Post type singular name', 'wicked-block-builder' ),
				'add_new_item'			=> __( 'Add New Block Version', 'wicked-block-builder' ),
				'edit_item'				=> __( 'Edit Block Version', 'wicked-block-builder' ),
				'new_item'				=> __( 'New Block Version', 'wicked-block-builder' ),
				'view_item'				=> __( 'View Block Version', 'wicked-block-builder' ),
				'search_items'			=> __( 'Search Block Versions', 'wicked-block-builder' ),
				'not_found'				=> __( 'No block version found.', 'wicked-block-builder' ),
				'not_found_in_trash'	=> __( 'No block versions found in trash.', 'wicked-block-builder' ),
			),
			'description'			=> __( 'A version of a block created with Wicked Block Builder.', 'wicked-block-builder' ),
			'supports'				=> array( 'title' ),
			'capability_type' 		=> self::$post_type_name,
			'map_meta_cap' 			=> true,
			'public'				=> false,
			'rewrite'				=> false,
			'show_in_rest' 			=> true,
			'show_ui' 				=> false,
		) );
	}

	/**
	 * See Block->from_post().
	 */
	public function from_post( $post ) {
		parent::from_post( $post );

		$this->parent = $post->post_parent;
	}
}
