<?php

namespace Wicked_Block_Builder;

use \Exception;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Represents a block pattern object.
 */
class Block_Pattern {

	/**
	 * The pattern's post type name.
	 *
	 * @var string
	 */
	public static $post_type_name = 'wbb_block_patern';

	/**
	 * The block pattern category taxonomy name.
	 *
	 * @var string
	 */
	public static $category_taxonomy_name = 'wbb_block_pattern_category';

	/**
	 * The pattern's post ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The pattern's post title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * The pattern's post content.
	 *
	 * @var string
	 */
	public $content;

	/**
	 * The pattern's post status.
	 *
	 * @var string
	 */
	public $status = 'draft';

	/**
	 * The pattern's post slug.
	 *
	 * @var string
	 */
	public $slug;

	public function __construct() {
	}

	public function from_post( $post ) {
		if ( null === $post ) {
			throw new Exception( __( 'A block pattern with that ID could not be found.', 'wicked-block-builder' ) );
		}

		$this->id 		= $post->ID;
		$this->title 	= $post->post_title;
		$this->content 	= $post->post_content;
		$this->status 	= $post->post_status;
		$this->slug 	= $post->post_name;
	}

	/**
	 * Registers the block pattern with WordPress.
	 */
	public function register() {
		$categories = wp_get_object_terms(
			$this->id,
			$this::$category_taxonomy_name,
			array(
				'fields' => 'slugs',
			)
		);

		register_block_pattern(
		    "wicked-block-builder/{$this->slug}",
		    array(
		        'title' 		=> $this->title,
		        'content' 		=> $this->content,
				'categories' 	=> $categories,
		    )
		);
	}

	/**
	 * Registers the block pattern post type.
	 */
	public static function register_post_type() {
		register_post_type( self::$post_type_name, array(
			'label'					=> _x( 'Patterns', 'Post type plural name', 'wicked-block-builder' ),
			'labels'				=> array(
				'name'					=> _x( 'Patterns', 'Post type plural name', 'wicked-block-builder' ),
				'singular_name'			=> _x( 'Pattern', 'Post type singular name', 'wicked-block-builder' ),
				'add_new_item'			=> __( 'Add New Pattern', 'wicked-block-builder' ),
				'edit_item'				=> __( 'Edit Pattern', 'wicked-block-builder' ),
				'new_item'				=> __( 'New Pattern', 'wicked-block-builder' ),
				'view_item'				=> __( 'View Pattern', 'wicked-block-builder' ),
				'search_items'			=> __( 'Search Patterns', 'wicked-block-builder' ),
				'not_found'				=> __( 'No pattern found.', 'wicked-block-builder' ),
				'not_found_in_trash'	=> __( 'No patterns found in trash.', 'wicked-block-builder' ),
			),
			'description'			=> __( 'A block pattern created with Wicked Block Builder.', 'wicked-block-builder' ),
			'supports'				=> array( 'title', 'editor' ),
			'capability_type' 		=> self::$post_type_name,
			'map_meta_cap' 			=> true,
			'public'				=> false,
			'rewrite'				=> true,
			'show_in_rest' 			=> true,
			'show_ui' 				=> true,
			'show_in_menu' 			=> 'wicked_block_builder_home',
		) );
	}

	/**
	 * Registers a taxonomy to store custom block pattern categories.
	 */
	public static function register_category_taxonomy() {
		register_taxonomy( self::$category_taxonomy_name, self::$post_type_name, array(
			'label'				=> _x( 'Pattern Categories', 'Taxonomy plural name', 'custom' ),
			'labels'			=> array(
				'name'			=> _x( 'Pattern Categories', 'Taxonomy plural name', 'custom' ),
				'singular_name' => _x( 'Pattern Category', 'Taxonomy singular name', 'custom' ),
				'all_items'		=> __( 'All Categories', 'custom' ),
				'edit_item'		=> __( 'Edit Category', 'custom' ),
				'update_item'	=> __( 'Update Category', 'custom' ),
				'add_new_item'	=> __( 'Add New Category', 'custom' ),
				'new_item_name' => __( 'Add Category Name', 'custom' ),
				'menu_name'     => __( 'Categories', 'custom' ),
				'search_items'  => __( 'Search Categories', 'custom' ),
				'parent_item' 	=> __( 'Parent Category', 'custom' ),
			),
			'show_tagcloud' 	=> false,
			'hierarchical'		=> false,
			'public'        	=> false,
			'show_ui'       	=> true,
			'show_in_menu'  	=> 'wicked_block_builder_home',
			'show_in_rest' 		=> true,
			'show_admin_column' => false,
			'rewrite'			=> true,
		) );
	}
}
