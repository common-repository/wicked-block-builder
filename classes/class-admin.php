<?php

namespace Wicked_Block_Builder;

use \finfo;
use \Exception;
use Wicked_Block_Builder\Util;
use Wicked_Block_Builder\Block;
use Wicked_Block_Builder\Plugin;
use Wicked_Block_Builder\Builder;
use Wicked_Block_Builder\Admin\Blocks_List;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Singleton containing plugin functionality related to the WordPress admin.
 */
final class Admin extends Singleton {

	const NOTICE_TYPE_SUCCESS 	= 'notice notice-success';
	const NOTICE_TYPE_WARNING 	= 'notice notice-warning';
	const NOTICE_TYPE_ERROR 	= 'notice notice-error';

	/**
	 * Stores a queue of notices to display when the admin_notices action is
	 * fired.
	 *
	 * @var array
	 */
	private $admin_notices = [];

    /**
	 * Holds the singleton instance of the class.
	 *
     * @var Admin
     */
    protected static $instance;

    protected function __construct() {
		Blocks_List::get_instance();

		add_action( 'admin_menu', 				array( $this, 'admin_menu' ) );
		add_action( 'admin_head', 				array( $this, 'admin_head' ) );
		add_action( 'admin_init', 				array( $this, 'admin_init' ) );
		add_action( 'admin_init', 				array( $this, 'redirect_edit_block_to_builder' ) );
		add_action( 'admin_enqueue_scripts', 	array( $this, 'enqueue_builder_scripts' ) );
		add_action( 'admin_enqueue_scripts', 	array( $this, 'enqueue_home_scripts' ) );
		add_action( 'admin_notices', 			array( $this, 'admin_notices' ) );

		add_filter( 'parent_file', 				array( $this, 'expand_block_builder_menu' ) );
		add_filter( 'submenu_file', 			array( $this, 'highlight_block_builder_sub_menu_item' ) );
	}

	/**
	 * Helper function that returns the 'action' parameter of a request.
	 *
	 * @return string|boolean
	 *  The action or false if no action parameter is set.
	 */
	private function get_action() {
		return isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : false;
	}

	/**
	 * Hooked to 'admin_init' action. Redirects URLs for adding or editing a
	 * block to the block builder.
	 */
	public function redirect_edit_block_to_builder() {
		global $typenow, $pagenow;

		$action 			= isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : false;
		$post_id 			= isset( $_GET['post'] ) ? absint( $_GET['post'] ) : false;
		$block_post_type 	= Block::$post_type_name;

		if ( 'wbb_block' == $typenow && 'post-new.php' == $pagenow ) {
			wp_redirect( admin_url( 'admin.php?page=wicked_block_builder_builder' ) );

			exit();
		}

		if ( 'post.php' == $pagenow && 'edit' == $action && $block_post_type == get_post_type( $post_id ) ) {
			wp_redirect( admin_url( 'admin.php?page=wicked_block_builder_builder&id=' . $post_id ) );

			exit();
		}
	}

	/**
	 * WordPress admin_init action.
	 */
	public function admin_init() {
		$this->maybe_add_notice();
		$this->maybe_import_blocks();
		$this->maybe_export_blocks();
	}

	/**
	 * WordPress admin_menu hook.
	 */
	public function admin_menu() {
		$post_type 			= Block::$post_type_name;
		$capability 		= "edit_{$post_type}s";
		$pattern_type 		= Block_Pattern::$post_type_name;
		$pattern_capability = "edit_{$pattern_type}s";

        add_menu_page(
            __( 'Wicked Block Builder', 'wicked-block-builder' ),
            __( 'Wicked Block Builder', 'wicked-block-builder' ),
            $capability,
            'wicked_block_builder_home',
            array( $this, 'builder_home_page' ),
            'dashicons-hammer',
            25
        );

        add_submenu_page(
            'wicked_block_builder_home',
            __( 'Wicked Block Builder', 'wicked-block-builder' ),
            __( 'Add New', 'wicked-block-builder' ),
            $capability,
            'wicked_block_builder_builder',
            array( $this, 'builder_page' ),
            0
        );

        add_submenu_page(
            'wicked_block_builder_home',
            __( 'Wicked Block Builder', 'wicked-block-builder' ),
            __( 'Home', 'wicked-block-builder' ),
            $capability,
            'wicked_block_builder_home',
            array( $this, 'builder_home_page' ),
            -10
        );

		add_submenu_page(
			'wicked_block_builder_home',
			__( 'Wicked Block Builder', 'wicked-block-builder' ),
			__( 'Categories', 'wicked-block-builder' ),
			$capability,
			'edit-tags.php?taxonomy=' . Block::$category_taxonomy_name . '&amp;post_type=' . Block::$post_type_name
			//admin_url( 'edit-tags.php?taxonomy=' . Block::$category_taxonomy_name . '&post_type=' . Block::$post_type_name )
		);

		add_submenu_page(
			'wicked_block_builder_home',
			__( 'Wicked Block Builder', 'wicked-block-builder' ),
			__( 'Pattern Categories', 'wicked-block-builder' ),
			$pattern_capability,
			'edit-tags.php?taxonomy=' . Block_Pattern::$category_taxonomy_name . '&amp;post_type=' . Block_Pattern::$post_type_name
			//admin_url( 'edit-tags.php?taxonomy=' . Block_Pattern::$category_taxonomy_name . '&post_type=' . Block_Pattern::$post_type_name )
		);
	}

	/**
	 * WordPress 'parent_file' filter.  Keeps the Wicked Block Builder menu item
	 * expanded when viewing block and pattern categories.
	 */
	public function expand_block_builder_menu( $parent_file ) {
		global $self;

		if ( 'edit-tags.php' == $self && isset( $_GET['taxonomy'] ) ) {
			if ( Block::$category_taxonomy_name == $_GET['taxonomy'] || Block_Pattern::$category_taxonomy_name == $_GET['taxonomy'] ) {
				$parent_file = 'wicked_block_builder_home';
			}
		}

		return $parent_file;
	}

	/**
	 * WordPress 'submenu_file' filter.  Fixes sub menu highlighting so 'Add New'
	 * is only highlighted when adding a new block (and not when editing an
	 * existing block).
	 */
	public function highlight_block_builder_sub_menu_item( $submenu_file ) {
		global $self;

		if ( 'admin.php' == $self && isset( $_GET['page'] ) && 'wicked_block_builder_builder' == $_GET['page'] && ! empty( $_GET['id'] ) ) {
			$submenu_file = 'edit.php?post_type=' . Block::$post_type_name;
		}

		return $submenu_file;
	}

	public function enqueue_builder_scripts() {
		$deps 		= array( 'lodash', 'react', 'wp-components', 'wp-data', 'wp-element', 'wp-polyfill', 'wp-data-controls', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable' );
		$page 		= isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : false;
		$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
		$version 	= Plugin::get_instance()->get_version();

		if ( 'wicked_block_builder_builder' == $page ) {
			wp_enqueue_code_editor( array( 'type' => 'text/css' ) );

			// Note: wp-components depedancy ensures that styles for WordPress
			// components are loaded
			wp_enqueue_style( 'wicked-block-builder-roboto', 'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;1,400;1,500;1,700&amp;family=Source+Code+Pro:wght@500&amp;display=swap', array( 'wp-components' ) );

			if ( defined( 'WICKED_BLOCK_BUILDER_BUILDER_APP' ) ) {
				wp_enqueue_script( 'wicked-block-builder-builder', WICKED_BLOCK_BUILDER_BUILDER_APP, $deps, $version, true );
			} else {
				wp_enqueue_script( 'wicked-block-builder-builder', $plugin_url . 'dist/builder.js', $deps, $version, true );
				wp_enqueue_style( 'wicked-block-builder-builder', $plugin_url . 'dist/builder.css', array(), $version );
			}

			wp_localize_script( 'wicked-block-builder-builder', 'wickedBlockBuilder', $this->get_builder_localize_data() );
		}
	}

	public function get_builder_localize_data() {
		return array(
			'blockId' 	=> isset( $_GET['id'] ) ? absint( $_GET['id'] ) : false,
			'icons' 	=> ( object ) Util::icons(),
		);
	}

	/**
	 * Enqueues scripts and styles for the plugin home page.
	 */
	public function enqueue_home_scripts() {
		$page 		= isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : false;
		$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
		$version 	= Plugin::get_instance()->get_version();

		if ( 'wicked_block_builder_home' == $page ) {
			wp_enqueue_style( 'wicked-block-builder-roboto', 'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;1,400;1,500;1,700&amp;family=Source+Code+Pro:wght@500&amp;display=swap' );

			if ( defined( 'WICKED_BLOCK_BUILDER_HOME_APP' ) ) {
				wp_enqueue_script( 'wicked-block-builder-home', WICKED_BLOCK_BUILDER_HOME_APP, array(), $version, true );
			} else {
				wp_enqueue_style( 'wicked-block-builder-home', $plugin_url . 'dist/home.css', array(), $version );
			}
		}
	}

	/**
	 * WordPress 'admin_head' action. Currently outputs block CSS when in the
	 * editor.
	 */
	public function admin_head() {
		$current_screen = get_current_screen();

		if ( $current_screen->is_block_editor() ) {
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
	}

	/**
	 * Wordpress 'admin_notices' function. Outputs admin notices.
	 */
	public function admin_notices() {
		foreach ( $this->admin_notices as $notice ) {
			printf(
				'<div class="%1$s"><p>%2$s</p></div>',
				$notice['type'],
				esc_html( $notice['message'] )
			);
		}
	}

	public function add_notice( $message, $type = self::NOTICE_TYPE_SUCCESS ) {
		$this->admin_notices[] = array(
			'message' 	=> $message,
			'type' 		=> $type,
		);
	}

	/**
	 * Wicked Block Builder home page.
	 */
	public function builder_home_page() {
		$is_pro = Plugin::get_instance()->is_pro();
		$blocks = new Block_Collection();

		$blocks->from_query( array( 'post_status' => array( 'publish', 'draft' ) ) );
		$blocks->sort( 'title' );
		
		$upgrade_url = trailingslashit( Util::wicked_plugins_url() );
		$upgrade_url = "{$upgrade_url}plugins/wicked-block-builder/";
		$upgrade_url = add_query_arg( 'utm_source', 'home_screen', $upgrade_url );
		$upgrade_url = add_query_arg( 'utm_campaign', 'wicked_block_builder', $upgrade_url );
		$upgrade_url = add_query_arg( 'utm_content', 'upgrade_button', $upgrade_url );

		$guide_url = trailingslashit( Util::wicked_plugins_url() );
		$guide_url = "{$guide_url}support/wicked-block-builder/getting-started/building-your-first-block/";
		$guide_url = add_query_arg( 'utm_source', 'home_screen', $guide_url );
		$guide_url = add_query_arg( 'utm_campaign', 'wicked_block_builder', $guide_url );
		$guide_url = add_query_arg( 'utm_content', 'view_guide_button', $guide_url );

		$docs_url = trailingslashit( Util::wicked_plugins_url() );
		$docs_url = "{$docs_url}support/wicked-block-builder/";
		$docs_url = add_query_arg( 'utm_source', 'home_screen', $docs_url );
		$docs_url = add_query_arg( 'utm_campaign', 'wicked_block_builder', $docs_url );
		$docs_url = add_query_arg( 'utm_content', 'view_documentation_button', $docs_url );

		include( dirname( dirname( __FILE__ ) ) . '/templates/home.php' );
	}

	/**
	 * Checks query string for a notice message and, if one exists, adds it to
	 * the notices queue.
	 */
	private function maybe_add_notice() {
		if ( ! empty( $_GET['wbb_notice'] ) ) {
			$this->add_notice( sanitize_text_field( $_GET['wbb_notice'] ) );
		}

		if ( ! empty( $_GET['wbb_warning'] ) ) {
			$this->add_notice( sanitize_text_field( $_GET['wbb_warning'] ), self::NOTICE_TYPE_WARNING );
		}

		if ( ! empty( $_GET['wbb_error'] ) ) {
			$this->add_notice( sanitize_text_field( $_GET['wbb_error'] ), self::NOTICE_TYPE_ERROR );
		}
	}

	/**
	 * Handles requests to import blocks.
	 */
	private function maybe_import_blocks() {
		if ( 'wbb_import' != $this->get_action() ) return;

		check_admin_referer( 'wbb_import', 'nonce' );

		$url 	= admin_url( 'admin.php' );
		$url 	= add_query_arg( 'page', 'wicked_block_builder_home', $url );
		$count 	= 0;

		try {
			if ( UPLOAD_ERR_OK != $_FILES['import']['error'] || false == is_uploaded_file( $_FILES['import']['tmp_name'] ) ) {
				throw new Exception( __( 'Error uploading file.', 'wicked-block-builder' ) );
			}

			$data = file_get_contents( $_FILES['import']['tmp_name'] );

			if ( false === $data ) {
				throw new Exception( __( 'Could not read file.', 'wicked-block-builder' ) );
			}

			$file_info = new finfo( FILEINFO_MIME_TYPE );
			$file_type = $file_info->file( $_FILES['import']['tmp_name'] );
			
			if ( ! in_array( $file_type, array( 'application/json', 'text/plain' ) ) ) {
				throw new Exception( __( 'Invalid file type.', 'wicked-block-builder' ) );
			}

			$data = json_decode( $data, false );

			if ( false === $data || null === $data || '' == $data ) {
				throw new Exception( __( 'Could not parse JSON.', 'wicked-block-builder' ) );
			}

			$blocks = new Block_Collection();

			// Export can be a single block or an array of blocks
			if ( is_array( $data ) ) {
				foreach ( $data as $block_json ) {
					$block = new Block();

					$block->from_json( $block_json );

					$blocks->add( $block );
				}
			} else {
				$block = new Block();

				$block->from_json( $data );

				$blocks->add( $block );

			}

			// Import each block
			foreach ( $blocks as $block ) {
				// The export should not include an ID but, just in case, set it
				// to false to ensure a new block is created
				$block->id = false;

				$block->save();

				// Import versions
				foreach ( $block->get_versions() as $version ) {
					$version->parent = $block->id;

					$version->save();
				}

				$count++;
			}

			$message = sprintf(
				__( 'Successfully imported %d blocks.', 'wicked-block-builder' ),
				$count
			);

			$url = add_query_arg( 'wbb_notice', $message, $url );

			wp_redirect( $url );

			exit();
		} catch ( Exception $e ) {
			$url = add_query_arg( 'wbb_error', $e->getMessage(), $url );

			wp_redirect( $url );

			exit();
		}
	}

	/**
	 * Handles requests to export blocks.
	 */
	private function maybe_export_blocks() {
		if ( 'wbb_export' != $this->get_action() ) return;

		check_admin_referer( 'wbb_export', 'nonce' );

		// Pro version handles plugin format so only worry about JSON format here
		$format = isset( $_POST['format'] ) ? $_POST['format'] : 'json';

		if ( empty( $_POST['id'] ) ) {
			$url = admin_url( 'admin.php' );
			$url = add_query_arg( 'page', 'wicked_block_builder_home', $url );
			$url = add_query_arg( 'wbb_error', __( 'No blocks were selected.', 'wicked-block-builder' ), $url );
			$url = add_query_arg( 'format', $format, $url );

			wp_redirect( $url );

			exit();
		} elseif ( 'json' == $format ) {
			$ids 	= array_map( 'absint', $_POST['id'] );
			$blocks = new Block_Collection();
			
			$blocks->from_post_ids( $ids, true );
			$blocks->export_to_file();

			exit();
		}
	}

	/**
	 * Block editor page.
	 */
	public function builder_page() {
		?>
			<div id="wicked-block-builder-app"></div>
		<?php
	}
}
