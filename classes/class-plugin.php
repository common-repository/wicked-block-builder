<?php

namespace Wicked_Block_Builder;

use Wicked_Block_Builder\REST_API\v1\REST_API;
use Wicked_Block_Builder\REST_API\v1\Block_API;
use Wicked_Block_Builder\REST_API\v1\Block_Pattern_API;
use Wicked_Block_Builder\REST_API\v1\Builder_API;
use Wicked_Block_Builder\REST_API\v1\Generator_API;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Main plugin class.
 */
final class Plugin extends Singleton {

	protected static $instance;

    protected function __construct() {
		// Register autoload function
        spl_autoload_register( array( $this, 'autoload' ) );

		add_action( 'init', 						array( $this, 'init' ) );
		add_action( 'init', 						array( $this, 'register_block_pattern_categories' ) );
		add_action( 'rest_api_init', 				array( $this, 'rest_api_init' ) );
		add_action( 'enqueue_block_editor_assets', 	array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'wp_head', 						array( $this, 'wp_head' ) );
		add_action( 'before_delete_post', 			array( $this, 'before_delete_post' ), 10, 2 );
		add_action( 'wp_trash_post', 				array( $this, 'trash_post' ) );

		add_filter( 'block_categories_all', 		array( $this, 'block_categories_all' ), 10, 2 );
		add_filter(
			'plugin_action_links_wicked-block-builder/wicked-block-builder.php',
			array( $this, 'add_settings_to_plugin_action_links' )
		);

		Admin::get_instance();
    }

    /**
	 * Plugin activation hook.
	 */
	public static function activate() {
		$plugin_file = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'wicked-block-builder.php';

		// Check for multisite
		if ( is_multisite() && file_exists( $plugin_file ) && is_plugin_active_for_network( $plugin_file ) ) {
			$sites = get_sites( array( 'fields' => 'ids' ) );

			foreach ( $sites as $id ) {
				switch_to_blog( $id );

				self::activate_site();

				restore_current_blog();
			}
		} else {
			self::activate_site();
		}
    }

	/**
	 * Activates/initalizes settings for a single site.
	 */
	private static function activate_site() {
		self::setup_cababilities();
    }

    /**
     * Class autoloader.
     */
    public function autoload( $class ) {
        $path = strtolower( $class );
        $path = str_replace( '_', '-', $path );

        // Convert to an array
        $path = explode( '\\', $path );

        // Nothing to do if we don't have anything
        if ( empty( $path[0] ) ) return;

        // Only worry about our namespace
        if ( 'wicked-block-builder' != $path[0] ) return;

		if ( isset( $path[1] ) && 'pro' == $path[1] ) return;

        // Remove the root namespace
        unset( $path[0] );

        // Get the class name
        $class = array_pop( $path );

        // Glue it back together
        $path = join( DIRECTORY_SEPARATOR, $path );
        $path = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . 'class-' . $class . '.php';

        include_once( $path );
	}

	/**
	 * WordPress init action.
	 */
    public function init() {
		$this->setup_post_types();
		$this->setup_taxonomies();
		$this->register_blocks();
		$this->register_block_patterns();
    }

	/**
	 * WordPress rest_api_init action.  Loads our REST API.
	 */
	public function rest_api_init() {
		$block_api 			= new Block_API();
		$block_pattern_api 	= new Block_Pattern_API();
		$builder_api 		= new Builder_API();
		$generator_api 		= new Generator_API();
	}

	/**
	 * Returns the plugin's version.
	 * 
	 * Note: the pro version is used when the pro version of the plugin
	 * is installed.
	 *
	 * @return string
	 *  The plugin's current version.
	 */
	public function get_version() {
		static $version = false;

		$core_plugin_file 	= dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'wicked-block-builder.php';
		$pro_plugin_file 	= dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'wicked-block-builder-pro.php';

		if ( ! $version && function_exists( 'get_plugin_data' ) ) {
			// Core plugin file is removed from pro version to avoid invalid header upload activate after upload
			if ( file_exists( $core_plugin_file ) ) {
				$plugin_data 	= get_plugin_data( $core_plugin_file );
				$version 		= $plugin_data['Version'];
			} elseif ( file_exists( $pro_plugin_file ) ) {
				$plugin_data 	= get_plugin_data( $pro_plugin_file );
				$version 		= $plugin_data['Version'];
			}
		}

		return $version;
	}

    /**
     * Adds a settings link to the plugin's action links.
     */
    public function plugin_action_links( $links ) {
        $settings_link = '<a href="' . esc_url( menu_page_url( 'wicked_block_builder_settings', 0 ) ) . '">' . __( 'Settings', 'wicked-block-builder' ) . '</a>';

        array_unshift( $links, $settings_link );

        return $links;
    }

	/**
	 * Registers post types used by the plugin.
	 */
	private function setup_post_types() {
		Block::register_post_type();
		Block_Version::register_post_type();
		Block_Pattern::register_post_type();
	}

	/**
	 * Registers taxonomies used by the plugin.
	 */
	private function setup_taxonomies() {
		Block::register_category_taxonomy();
		Block_Pattern::register_category_taxonomy();
	}

	/**
	 * Called during plugin activation. Adds custom capabilities to roles.
	 */
	private static function setup_cababilities() {
		$block 		= Block::$post_type_name;
		$blocks 	= "{$block}s";
		$version 	= Block_Version::$post_type_name;
		$versions 	= "{$version}s";
		$pattern 	= Block_Pattern::$post_type_name;
		$patterns 	= "{$pattern}s";
		$role 		= get_role( 'administrator' );

		// Add block capabilities
		$role->add_cap( "read_{$block}" );
		$role->add_cap( "read_private_{$blocks}" );
		$role->add_cap( "edit_{$block}" );
		$role->add_cap( "edit_{$blocks}" );
		$role->add_cap( "edit_others_{$blocks}" );
		$role->add_cap( "edit_private_{$blocks}" );
		$role->add_cap( "edit_published_{$blocks}" );
		$role->add_cap( "publish_{$blocks}" );
		$role->add_cap( "delete_{$block}" );
		$role->add_cap( "delete_{$blocks}" );
		$role->add_cap( "delete_others_{$blocks}" );
		$role->add_cap( "delete_private_{$blocks}" );
		$role->add_cap( "delete_published_{$blocks}" );

		// Add version capabilities
		$role->add_cap( "read_{$version}" );
		$role->add_cap( "read_private_{$versions}" );
		$role->add_cap( "edit_{$version}" );
		$role->add_cap( "edit_{$versions}" );
		$role->add_cap( "edit_others_{$versions}" );
		$role->add_cap( "edit_private_{$versions}" );
		$role->add_cap( "edit_published_{$versions}" );
		$role->add_cap( "publish_{$versions}" );
		$role->add_cap( "delete_{$version}" );
		$role->add_cap( "delete_{$versions}" );
		$role->add_cap( "delete_others_{$versions}" );
		$role->add_cap( "delete_private_{$versions}" );
		$role->add_cap( "delete_published_{$versions}" );

		// Add pattern capabilities
		$role->add_cap( "read_{$pattern}" );
		$role->add_cap( "read_private_{$patterns}" );
		$role->add_cap( "edit_{$pattern}" );
		$role->add_cap( "edit_{$patterns}" );
		$role->add_cap( "edit_others_{$patterns}" );
		$role->add_cap( "edit_private_{$patterns}" );
		$role->add_cap( "edit_published_{$patterns}" );
		$role->add_cap( "publish_{$patterns}" );
		$role->add_cap( "delete_{$pattern}" );
		$role->add_cap( "delete_{$patterns}" );
		$role->add_cap( "delete_others_{$patterns}" );
		$role->add_cap( "delete_private_{$patterns}" );
		$role->add_cap( "delete_published_{$patterns}" );
	}

	/**
	 * Currently registers dynamic blocks.
	 */
	private function register_blocks() {
		$blocks = new Block_Collection();
		$blocks->get_published_blocks();
		$blocks->register();
	}

	/**
	 * Registers all published block patterns.
	 */
	private function register_block_patterns() {
		$patterns = new Block_Pattern_Collection();
		$patterns->load_published_block_patterns();
		$patterns->register();
	}

	/**
	 * Adds categories from our block category taxonomy.
	 */
	public function block_categories_all( $categories, $context ) {
		$terms = get_terms( array(
			'taxonomy' 		=> Block::$category_taxonomy_name,
			'hide_empty' 	=> false,
		) );

		foreach ( $terms as $term ) {
			$categories[] = array(
				'slug' 	=> $term->slug,
				'title' => $term->name,
			);
		}

		return $categories;
	}

	/**
	 * Registers custom block pattern categories.  Tied to 'init' action.
	 */
	public function register_block_pattern_categories() {
		$terms = get_terms( array(
			'taxonomy' 		=> Block_Pattern::$category_taxonomy_name,
			'hide_empty' 	=> false,
		) );

		foreach ( $terms as $term ) {
			register_block_pattern_category( $term->slug, array(
				'label' => $term->name,
			) );
		}
	}

	/**
	 * WordPress 'enqueue_block_editor_assets' action. Enqueues the block
	 * builder's generator app.
	 */
	public function enqueue_block_editor_assets() {
		$deps 		= array( 'lodash', 'react', 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-data', 'wp-element', 'wp-polyfill', 'wp-data-controls', 'wp-edit-post', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable' );
		$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
		$version 	= $this->get_version();

		if ( defined( 'WICKED_BLOCK_BUILDER_GENERATOR_APP' ) ) {
			wp_enqueue_script( 'wicked-block-builder-generator', WICKED_BLOCK_BUILDER_GENERATOR_APP, $deps );
		} else {
			wp_enqueue_script( 'wicked-block-builder-generator', $plugin_url . 'dist/generator.js', $deps, $version );
			wp_enqueue_style( 'wicked-block-builder-generator', $plugin_url . 'dist/generator.css', array(), $version );
		}

		if ( defined( 'WICKED_BLOCK_BUILDER_PATTERNS_APP' ) ) {
			wp_enqueue_script( 'wicked-block-builder-patterns', WICKED_BLOCK_BUILDER_PATTERNS_APP, $deps );
		} else {
			wp_enqueue_script( 'wicked-block-builder-patterns', $plugin_url . 'dist/patterns.js', $deps, $version );
		}

		wp_localize_script( 'wicked-block-builder-generator', 'wickedBlockBuilder', $this->get_generator_localize_data() );
	}

	/**
	 * Gets the data to output for the generator script.
	 *
	 * @return array
	 *  Data to inject into the 'wickedBlockBuilder' JavaScript variable.
	 */
	public function get_generator_localize_data() {
		$icons 			= array();
		$all_icons 		= Util::icons();
		$blocks 		= new Block_Collection();
		$block_posts 	= get_posts( array(
		    'post_type'         => Block::$post_type_name,
		    'posts_per_page'    => -1,
		) );

		foreach ( $block_posts as $block_post ) {
			$block = new Block();
			$block->from_post( $block_post );
			$block->load_latest_version();

			$icon 		= $block->data->icon;
			$versions 	= $block->load_versions();

			// Reduce output by removing data we don't need
			$block->css = '';

			foreach ( $versions as $version ) {
				$version->css = '';
			}

			// Reduce output by only outputting data for icons that are in use
			if ( $icon && isset( $all_icons[ $icon ] ) ) {
				$icons[ $icon ] = $all_icons[ $icon ];
			}

			$blocks->add( $block );
		}

		return array(
			'restRoot' 	=> get_rest_url(),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'blocks' 	=> $blocks,
			'icons' 	=> ( object ) $icons,
		);
	}

	/**
	 * Adds a 'Settings' link to our plugin actions.
	 */
	public function add_settings_to_plugin_action_links( $links ) {
        $settings_link = '<a href="' . esc_url( menu_page_url( 'wicked_block_builder_home', 0 ) ) . '">' . __( 'Settings', 'wicked-block-builder' ) . '</a>';

        array_unshift( $links, $settings_link );

        return $links;
    }

	/**
	 * Whether or not the pro version of the plugin is active.  The pro plugin
	 * uses this filter to change the value to true.
	 */
	public function is_pro() {
		return apply_filters( 'wicked_block_builder_is_pro', false );
	}

	/**
	 * WordPress 'before_delete_post' action.  Checks if the post type being
	 * deleted is a block and, if so, deletes the associated versions.  Note:
	 * the block's JSON file is deleted when the post is trashed.
	 */
	public function before_delete_post( $post_id, $post ) {
		// We only care about blocks
		if ( Block::$post_type_name == $post->post_type ) {
			$block = new Block();

			$block->id = $post->ID;

			$block->delete_versions();
		}
	}

	/**
	 * WordPress 'wp_trash_post' action.  Deletes block's JSON file.
	 */
	public function trash_post( $post_id ) {
		// We only care about blocks
		if ( Block::$post_type_name == get_post_type( $post_id ) ) {
			$block = new Block( $post_id );

			$block->delete_json();
		}
	}

	/**
	 * WordPress 'wp_head' action. Currently outputs block CSS.
	 */
	public function wp_head() {
		$blocks = new Block_Collection();
		$css 	= $blocks->get_published_blocks()->get_css();

		// Exit if we don't have any CSS
		if ( ! $css ) return;

		?>
<style type="text/css">
<?php echo wp_strip_all_tags( $css ); ?>
</style>
		<?php
	}

	/**
	 * Whether or not the plugin is running in demo mode.
	 */
	public function is_demo() {
		return defined( 'WICKED_BLOCK_BUILDER_DEMO' ) && true === WICKED_BLOCK_BUILDER_DEMO;
	}
}