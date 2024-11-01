<?php

namespace Wicked_Block_Builder;

use \Exception;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Represents a block object.
 */
class Block implements \JsonSerializable {

	/**
	 * When false, the block ID will not be included when serialized to JSON.
	 *
	 * @var boolean
	 */
	protected $serialize_id = true;

	/**
	 * The block's post type name.
	 *
	 * @var string
	 */
	public static $post_type_name = 'wbb_block';

	/**
	 * The block's category taxonomy name.
	 *
	 * @var string
	 */
	public static $category_taxonomy_name = 'wbb_block_category';

	/**
	 * The meta key used to hold the block's data.
	 *
     * @var string
     */
	public static $data_meta_key = '_wbb_data';

	/**
	 * The meta key used to hold the block's CSS.
	 *
     * @var string
     */
	public static $css_meta_key = '_wbb_css';

	/**
	 * The block's post ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The block's post title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * The block's post status.
	 *
	 * @var string
	 */
	public $status = 'draft';

	/**
	 * The block's namespace.
	 *
	 * @var string
	 */
	public $namespace = 'wicked-block-builder';

	/**
	 * The block's post slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Block meta data stored in a JSON-encoded meta field.
	 *
	 * @var object
	 */
	public $data;

	/**
	 * The block's CSS.
	 *
	 * @var string
	 */
	public $css;

	/**
	 * Timestamp of when the block was last modified.
	 *
	 * @var int
	 */
	public $modified = 0;

	/**
	 * Versions of the block.
	 *
	 * @var Block_Collection
	 */
	protected $versions;

	public function __construct( $id = false ) {
		$this->versions = new Block_Collection();

		if ( $id ) {
			$this->id = $id;

			$this->load_from_post();
		}
	}

	public function from_post( $post ) {
		if ( null === $post ) {
			throw new Exception( __( 'A block with that ID could not be found.', 'wicked-block-builder' ) );
		}

		$this->id 			= $post->ID;
		$this->title 		= $post->post_title;
		$this->status 		= $post->post_status;
		$this->slug 		= $post->post_name;
		$this->modified 	= strtotime( $post->post_modified_gmt );
		$this->css 			= get_post_meta( $this->id, self::$css_meta_key, true );

		$data = get_post_meta( $this->id, self::$data_meta_key, true );

		$data = json_decode( $data );
		$this->data = $data ? $data : $this->get_default_data();

	}

	public function load_from_post( $id = false ) {
		if ( $id ) $this->id = $id;

		if ( ! $this->id ) {
			throw new Exception( __( 'A block ID is required.', 'wicked-block-builder' ) );
		}

		$post = get_post( $this->id );

		$this->from_post( $post );

		return $this;
	}

	/**
	 * Loads the block post based on a slug.
	 *
	 * @param string $slug
	 *  The block's slug.
	 */
	public function load_from_slug( $slug ) {
		global $wpdb;

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_name = %s",
				Block::$post_type_name,
				$slug
			)
		);

		if ( $id ) $this->load_from_post( $id );

		return $this;
	}

	/**
	 * Saves the post to the database.
	 *
	 * @return int
	 *  The post ID of the newly created block.
	 */
	public function save() {
		$post = array(
			'post_title' 	=> $this->title ? $this->title : __( '(untitled block)', 'wicked-block-builder' ),
			'post_status' 	=> $this->status,
			'post_name' 	=> $this->slug,
			'post_type' 	=> static::$post_type_name,
		);

		// Block versions have a parent property
		if ( isset( $this->parent ) ) $post['post_parent'] = $this->parent;

		if ( ! empty( $this->id ) ) {
			$post['ID'] = $this->id;

			$post_id = wp_update_post( $post );
		} else {
			$post_id = wp_insert_post( $post );
		}

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( $post_id->get_error_message() );
		}

		$this->id = $post_id;

		update_post_meta( $this->id, self::$data_meta_key, wp_slash( json_encode( $this->data ) ) );
		update_post_meta( $this->id, self::$css_meta_key, $this->css );

		// Refresh post data to get things like slug
		$post = get_post( $post_id );

		$this->load_from_post( $post );

		// Keep latest version of block in sync with changes that don't trigger
		// a new version.  Reason for this is that we're keeping the door open
		// for adding a 'Publish' button to the UI.  We don't want to trigger a
		// new version when certain things like the sidebar are changed since
		// WordPress doesn't care about that and it would unnecessarily bloat
		// things.  In the future, we may want to add a 'Publish' button so that
		// a block can be saved without those changes being published until the
		// author manually publishes the new version (at which point we'll
		// remove the below code).
		if ( ! ( $this instanceof Block_Version ) ) {
			$version = $this->get_latest_version();

			if ( $version ) {
				$version->data->category 	= $this->data->category;
				$version->data->description = $this->data->description;
				$version->data->keywords 	= $this->data->keywords;
				$version->data->supports 	= $this->data->supports;
				$version->data->sidebar 	= $this->data->sidebar;
				$version->data->icon 		= $this->data->icon;
				$version->data->parent 		= isset( $this->data->parent ) ? $this->data->parent : array();
				$version->data->ancestor 	= isset( $this->data->ancestor ) ? $this->data->ancestor : array();
				$version->css 				= $this->css;

				$version->save();
			}
		}

		return $this->id;
	}

	/**
	 * Saves the block to a JSON file.
	 *
	 * @return boolean
	 *  True if block was saved, false if not or there was an error.
	 */
	public function save_json() {
		try {
			$path = $this->get_json_save_path();

			// Make sure path exists
			if ( is_dir( $path ) ) {
				// Load versions to ensure they get saved to JSON
				$this->load_versions();

				$file = "{$path}{$this->slug}.json";
				$json = $this->jsonSerialize();

				// Exclude post ID when saving to JSON file (otherwise it will
				// cause issues when we try to import the JSON)
				unset( $json['id'] );

				foreach ( $json['versions'] as $index => $version ) {
					unset( $json['versions'][ $index ]['id'] );
				}

				$json = json_encode( $json, JSON_PRETTY_PRINT );

				// Save to file
				$result = @file_put_contents( $file, $json );

				if ( false === $result ) {
					throw new Exception(
						sprintf(
							__( 'Error saving block JSON to %s. Make sure the file is writable.', 'wicked-block-builder' ),
							$file
						)
					);
				}

				return true;
			}
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
		}

		return false;
	}

	/**
	 * Publishes a new version of the block.
	 */
	public function publish_new_version() {
		if ( empty( $this->id ) ) {
			throw new Exception( __( 'A block ID is required before publishing.', 'wicked-block-builder' ) );
		}

		$version 			= new Block_Version();
		$version->title 	= $this->title;
		$version->parent 	= $this->id;
		$version->data 		= $this->data;
		$version->css 		= $this->css;
		$version->status 	= 'publish';
		$version->save();

		return $version;
	}

	/**
	 * Registers the block's post type.
	 */
	public static function register_post_type() {
		register_post_type( self::$post_type_name, array(
			'label'					=> _x( 'Blocks', 'Post type plural name', 'wicked-block-builder' ),
			'labels'				=> array(
				'name'					=> _x( 'Blocks', 'Post type plural name', 'wicked-block-builder' ),
				'singular_name'			=> _x( 'Block', 'Post type singular name', 'wicked-block-builder' ),
				'add_new_item'			=> __( 'Add New Block', 'wicked-block-builder' ),
				'edit_item'				=> __( 'Edit Block', 'wicked-block-builder' ),
				'new_item'				=> __( 'New Block', 'wicked-block-builder' ),
				'view_item'				=> __( 'View Block', 'wicked-block-builder' ),
				'search_items'			=> __( 'Search Blocks', 'wicked-block-builder' ),
				'not_found'				=> __( 'No block found.', 'wicked-block-builder' ),
				'not_found_in_trash'	=> __( 'No blocks found in trash.', 'wicked-block-builder' ),
			),
			'description'			=> __( 'A block created with Wicked Block Builder.', 'wicked-block-builder' ),
			'menu_icon' 			=> 'dashicons-hammer',
			'supports'				=> array( 'title', 'custom-fields' ),
			'rest_controller_class' => '\Wicked_Block_Builder\REST_API\v1\Block_API',
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
	 * Registers the block category taxonomy.
	 */
	public static function register_category_taxonomy() {
		register_taxonomy( self::$category_taxonomy_name, self::$post_type_name, array(
			'label'				=> _x( 'Categories', 'Taxonomy plural name', 'custom' ),
			'labels'			=> array(
				'name'			=> _x( 'Categories', 'Taxonomy plural name', 'custom' ),
				'singular_name' => _x( 'Category', 'Taxonomy singular name', 'custom' ),
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

	/**
	 * Returns a default structure for the block's data property.
	 *
	 * @return object
	 */
	public function get_default_data() {
		return ( object ) array(
			'isDynamic' 	=> false,
			'icon' 			=> '',
			'description' 	=> '',
			'keywords' 		=> '',
			'category' 		=> '',
			'wbbApiVersion' => 1,
			'apiVersion' 	=> 2,
			'parent' 		=> array(),
			'ancestor' 		=> array(),
			'attributes' 	=> array(),
			'edit' 			=> ( object ) array( 'nodes' => array() ),
			'save' 			=> ( object ) array( 'nodes' => array() ),
			'sidebar' 		=> ( object ) array( 'nodes' => array() ),
			'supports' 		=> ( object ) array(
				'align' 			=> array(),
				'anchor' 			=> false,
	            'customClassName' 	=> true,
	            'multiple' 			=> true,
	            'inserter' 			=> true,
	            'typography' 		=> ( object ) array(
	                'fontSize' 			=> false,
	                'lineHeight' 		=> false,
	            ),
	            'spacing' 			=> ( object ) array(
	                'margin' 			=> false,
	                'padding' 			=> false,
	            ),
	            'color' 			=> false,				
			),
		);
	}

 	public function from_json( $json ) {
		$this->id 			= isset( $json->id ) ? $json->id : false;
		$this->title 		= $json->title;
		$this->status 		= $json->status;
		$this->namespace 	= $json->namespace;
		$this->slug 		= $json->slug;
		$this->data 		= $json->data;
		$this->css 			= $json->css;
		$this->modified 	= $json->modified;

		foreach ( $json->versions as $version_json ) {
			$version = new Block_Version();
			$version->from_json( $version_json );

			$this->versions->add( $version );
		}
	}

	public function jsonSerialize(): array {
		$data = array(
			'id' 		=> $this->id,
			'title' 	=> $this->title,
			'status' 	=> $this->status,
			'namespace' => $this->namespace,
			'slug' 		=> $this->slug,
			'modified' 	=> $this->modified,
			'data' 		=> $this->data,
			'css' 		=> $this->css,
			'versions' 	=> $this->versions->jsonSerialize(),
		);

		if ( ! $this->serialize_id ) {
			unset( $data['id'] );
		}

		return $data;
	}

	/**
	 * Gets the block's versions.
	 *
	 * @return Block_Collection
	 *  Collection of the block's versions.
	 */
	public function get_versions() {
		return $this->versions;
	}

	/**
	 * Initializes/overrides the block's data and CSS to the latest version of
	 * the block.
	 */
	public function load_latest_version() {
		$latest_version = $this->get_latest_version();

		if ( $latest_version ) {
			$this->data = $latest_version->data;
			$this->css 	= $latest_version->css;
		}
	}

	/**
	 * Loads the block's versions from the database.  Versions are statically
	 * cached.
	 */
	public function load_versions() {
		static $versions = array();

		if ( isset( $versions[ $this->id ] ) ) {
			$this->versions = $versions[ $this->id ];
		} else {
			// Reset property so that we don't end up with duplicates
			$this->versions = new Block_Collection();

			$posts = get_posts( array(
				'post_type'         => Block_Version::$post_type_name,
				'posts_per_page'    => -1,
				'order'             => 'DESC',
				'orderby'           => 'date',
				'post_parent'		=> $this->id,
			) );

			foreach ( $posts as $post ) {
				$version = new Block_Version();
				$version->from_post( $post );
				$version->slug = $this->slug;
				$this->versions->add( $version );
			}

			$versions[ $this->id ] = $this->versions;
		}

		return $this->versions;
	}

	/**
	 * @return Block_Version
	 */
	public function get_latest_version() {
		$this->load_versions();

		return $this->versions->get_first_item();
	}

	/**
	 * Compares the block's attributes, edit, and save values to the latest
	 * version and creates a new version if they are different.
	 */
	public function publish_version_if_needed() {
		$previous = $this->get_latest_version();

		if ( $previous ) {
			if (
				$this->data->isDynamic != $previous->data->isDynamic ||
				$this->data->attributes != $previous->data->attributes ||
				$this->data->edit->nodes != $previous->data->edit->nodes ||
				$this->data->save->nodes != $previous->data->save->nodes
			) {
				$this->publish_new_version();

				return true;
			}
		} else {
			// No previous version; create one
			$this->publish_new_version();

			return true;
		}

		return false;
	}

	/**
	 * Registers the block with WordPress.
	 */
	public function register() {
		$settings = array(
			'api_version' 	=> $this->data->apiVersion,
			'title' 		=> $this->title,
			'attributes' 	=> $this->get_attribute_schema(),
		);

		if ( $this->data->isDynamic ) {
			$settings['render_callback'] = array( $this, 'render' );
		}

		register_block_type( "{$this->namespace}/{$this->slug}", $settings );
	}

	/**
	 * Renders a dynamic block.
	 */
	public function render( $attributes, $content, $block ) {
		ob_start();

		// Prior to 1.4.1, variables were passed using locate_template function which
		// places arguments in an $args variable.  Keep the variable for backwards
		// compatibility.
		$args = array(
			'attributes' 	=> $attributes,
			'content' 		=> $content,
			'block' 		=> $this,
		);

		// TODO: make location filterable
		$template = locate_template( "wbb-blocks/{$this->slug}.php", false );

		if ( $template ) require( $template );

		return ob_get_clean();
	}

	/**
	 * Deletes the block's JSON file (if one exists).
	 *
	 * @return boolean
	 *  True if a file was succesfully deleted, false if no file was found or if
	 *  there was an error.
	 */
	public function delete_json() {
		$result = false;
		$path 	= $this->get_json_save_path();
		$file 	= "{$path}{$this->slug}.json";

		if ( file_exists( $file ) ) {
			$result = @unlink( $file );

			if ( false === $result ) {
				error_log(
					sprintf(
						__( 'Error deleting %s.  Make sure the file is writable.', 'wicked-block-builder' ),
						$file
					)
				);
			}
		}

		return $result;
	}

	/**
	 * Deletes the block's versions from the database.
	 */
	public function delete_versions() {
		$post_ids = get_posts( array(
			'post_type'         => Block_Version::$post_type_name,
			'posts_per_page'    => -1,
			'fields'            => 'ids',
		) );

		foreach ( $post_ids as $id ) {
			// Second parameter forces deletion (i.e. bypasses trash)
			wp_delete_post( $id, true );
		}
	}

	/**
	 * Returns the path that block JSON should be saved to.
	 *
	 * @return string
	 *  The absolute path to save the block's JSON file to.
	 */
	public function get_json_save_path() {
		$path = trailingslashit( get_stylesheet_directory() ) . 'wbb-json';

		/**
		 * Filters the path where block's JSON files should be saved.
		 *
		 * @since 0.1.2
		 *
		 * @param Block $this The instance of the block being saved.
		 */
		$path = apply_filters( 'wbb_save_json_path', $path, $this );

		return trailingslashit( $path );
	}

	/**
	 * Controls the serialize ID setting.
	 *
	 * @param boolean $serialize
	 *  True to include the block ID in JSON serialization, false to exclude.
	 */
	public function set_serialize_id( $serialize ) {
		$this->serialize_id = $serialize;
	}

	/**
	 * Build the block's attribute property schema.
	 */
	public function get_attribute_schema() {
		$atts = array(
			'_wbbBlockId' => array(
				'type' 		=> 'string',
				'source' 	=> 'attribute',
			),
			'_wbbImages' => array(
				'type' 		=> 'array',
				'selector' 	=> 'img',
			),
		);

		foreach ( $this->data->attributes as $attribute ) {
			$atts[ $attribute->name ] = array(
				'type' 		=> $attribute->type,
				'selector' 	=> isset( $attribute->selector ) ? $attribute->selector : null,
				'default' 	=> isset( $attribute->default ) ? $attribute->default : null,
			);

			// Per docs, no source should be supplied when the block's comment delimiter is used
			if ( isset( $attribute->source ) && 'block' != $attribute->source ) {
				$atts[ $attribute->name ]['source'] = $attribute->source;
			}
		}

		return $atts;
	}

	/**
	 * Returns the block's JSON definition as an array.
	 * 
	 * @return array
	 *  Array matching https://schemas.wp.org/trunk/block.json schema.
	 */
	public function json() {
		$json = array(
			'$schema' 		=> 'https://schemas.wp.org/trunk/block.json',
			'apiVersion' 	=> $this->data->apiVersion,
			'title' 		=> $this->title,
			'name' 			=> "{$this->namespace}/{$this->slug}",
			'category' 		=> $this->data->category,
			'attributes' 	=> $this->get_attribute_schema(),
			'editorScript' 	=> 'file:./../build/index.js',
		);

		if ( $this->css ) {
			$json['editorStyle'] = 'file:./../build/index.css';
			$json['style'] = 'file:./../build/index.css';
		}

		if ( $this->data->isDynamic ) {
			$json['render'] = "file:./{$this->slug}.php";
		}

		return $json;
	}
}
