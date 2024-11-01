<?php

namespace Wicked_Block_Builder\Admin;

use Wicked_Block_Builder\Block;
use Wicked_Block_Builder\Block_Collection;
use Wicked_Block_Builder\Singleton;
use Wicked_Block_Builder\Util;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Handles tweaks and functionality related to WordPress blocks list UI.
 *
 * @since 1.1.1
 */
final class Blocks_List extends Singleton {

    /**
	 * Holds the singleton instance of the class.
	 *
     * @var Blocks_List
     */
    protected static $instance;

	/**
	 * The current action being requested.
	 *
	 * @var string
	 */
	private $action = false;


    /**
     * The post status that the list is being filtered by.
     *
     * @var string
     */
    private $post_status = false;

    protected function __construct() {
		$this->action      		= isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : false;
        $this->post_status      = isset( $_GET['post_status'] ) ? sanitize_text_field( $_GET['post_status'] ) : false;

        $block_post_type 		= Block::$post_type_name;
		$block_columns_filter 	= "manage_{$block_post_type}_posts_columns";
		$block_column_action 	= "manage_{$block_post_type}_posts_custom_column";

        add_filter( 'post_row_actions', 		        array( $this, 'post_row_actions' ), 10, 2 );
        add_filter( "views_edit-{$block_post_type}",    array( $this, 'maybe_add_sync_view' ) );
        add_filter( $block_columns_filter,              array( $this, 'maybe_add_sync_status_column' ) );
        add_filter( $block_columns_filter,              array( $this, 'remove_date_column_from_blocks_table' ) );
        add_filter( "handle_bulk_actions-edit-{$block_post_type}", array( $this, 'handle_bulk_actions' ), 10, 3 );

		add_action( 'admin_init', 								array( $this, 'admin_init' ) );
        add_action( $block_column_action, 		                array( $this, 'block_column_content' ), 10, 2 );
        add_action( "bulk_actions-edit-{$block_post_type}",     array( $this, 'bulk_actions' ) );

        if ( 'missing' == $this->post_status ) {
            add_action( 'admin_footer',     array( $this, 'missing_blocks_admin_footer' ) );
            add_action( 'pre_get_posts',    array( $this, 'suppress_non_missing_blocks' ) );
        }
    }

	/**
	 * WordPress 'admin_init' action.
	 */
	public function admin_init() {
		$this->maybe_sync_block();
		$this->maybe_export_block();
		$this->maybe_duplicate_block();
	}

    public function maybe_add_sync_view( $views ) {
        global $wp_list_table, $wp_query;

        $blocks = new Block_Collection();

        $blocks->from_json_files();

		$missing    = $blocks->get_missing_blocks();
        $count      = $missing->count();

        if ( $count > 0 ) {
            $url 	= admin_url( 'edit.php' );
            $url 	= add_query_arg( 'post_type', Block::$post_type_name, $url );
            $url 	= add_query_arg( 'post_status', 'missing', $url );

            $views['missing'] = '<a href="' . esc_url( $url ) . '"' . ( 'missing' == $this->post_status ? ' class="current"' : '' ) . '>' . __( 'Missing', 'wicked-block-builder' ) . '</a> (' . $count . ')';

            if ( 'missing' == $this->post_status ) {
                $wp_list_table->set_pagination_args(
                    array(
                        'total_pages' => 1,
                        'total_items' => $count,
                        'per_page'    => $count,
                    )
                );

                $wp_query->post_count = 1;
            }
        }

        return $views;
    }

    /**
	 * WordPress 'manage_wbb_block_posts_columns' filter.
	 *
	 * Adds a column to the blocks table for sync status (if there are any JSON
	 * files).
	 */
	public function maybe_add_sync_status_column( $columns ) {
		$blocks = new Block_Collection();

		$blocks->from_json_files();

		if ( $blocks->count() > 0 ) {
			$columns['sync_status'] = __( 'Sync Status', 'wicked-block-builder' );
		}

		return $columns;
	}

    /**
	 * WordPress 'manage_wbb_block_posts_custom_column' action.
	 *
	 * Populates content of columns in blocks table.
	 */
	public function block_column_content( $column, $post_id ) {
		if ( 'sync_status' == $column ) {
			$block 	= new Block( $post_id );
			$blocks = new Block_Collection();

			$blocks->from_json_files();

			$json_block = $blocks->get_by_slug( $block->slug );

            if ( 0 == $post_id ) {
                _e( 'Awaiting import', 'wicked-block-builder' );
            } elseif ( false === $json_block ) {
				_e( 'Awaiting save', 'wicked-block-builder' );
			} elseif ( $json_block->modified > $block->modified ) {
				_e( 'Outdated', 'wicked-block-builder' );
			} else {
				_e( 'Synced', 'wicked-block-builder' );
			}
		}
	}

    /**
	 * WordPress 'manage_wbb_block_posts_columns' filter.
	 *
	 * Removes the 'Date' column.
	 */
	public function remove_date_column_from_blocks_table( $columns ) {
		unset( $columns['date'] );

		return $columns;
	}

    /**
     * Generates and outputs the markup to display the table list view of
     * missing blocks.
     */
    public function missing_blocks_admin_footer() {
        global $wp_list_table;

        $blocks     = new Block_Collection();
        $columns    = $wp_list_table->get_columns();
        $hidden     = get_hidden_columns( $wp_list_table->screen );
        $sync_url   = $this->get_url();
        $sync_url 	= add_query_arg( 'action', 'wbb_sync_block', $sync_url );
        $sync_url 	= wp_nonce_url( $sync_url, 'wbb_sync_block' );

        $blocks->from_json_files();

		$missing = $blocks->get_missing_blocks();

        include( dirname( dirname( dirname( __FILE__ ) ) ) . '/templates/missing-blocks.php' );
    }

    public function suppress_non_missing_blocks( $query ) {
        $query->set( 'posts_per_page', 1 );
    }

    /**
     * Adds a 'Sync' bulk action.
     */
    public function bulk_actions( $actions ) {
        if ( 'missing' == $this->post_status ) {
            // Remove all actions
            $actions = array();
        }

		if ( 'trash' != $this->post_status ) {
			$label = __( 'missing' == $this->post_status ? 'Import' : 'Sync', 'wicked-block-builder' );
			// Keep 'Move to trash' as last item
			if ( array_key_exists( 'trash', $actions ) ) {
				Util::array_insert_before_key( $actions, 'trash', array(
					'wbb_bulk_sync_blocks' => $label,
				) );
			} else {
				$actions['wbb_bulk_sync_blocks'] = $label;
			}
		}

		if ( 'missing' != $this->post_status && 'trash' != $this->post_status ) {
			Util::array_insert_after_key( $actions, 'wbb_bulk_sync_blocks', array(
				'wbb_export_blocks' => __( 'Export', 'wicked-block-builder' ),
			) );
		}

        return $actions;
    }

    /**
     * Handle block bulk actions.
     */
    public function handle_bulk_actions( $redirect, $action, $post_ids ) {
        if ( 'wbb_bulk_sync_blocks' == $action ) {
            $blocks         = new Block_Collection();
            $notice         = __( 'Successfully synced requested blocks.', 'wicked-block-builder' );
            $redirect       = remove_query_arg( 'post_status', $this->get_url() );
            $redirect       = add_query_arg( 'wbb_notice', $notice, $redirect );

            $blocks->from_json_files();

            // The 'missing' status screen uses post slugs instead of IDs
            if ( 'missing' == $this->post_status ) {
                $slugs = array_map( 'sanitize_key', $_GET['post'] );
            } else {
                $posts = get_posts( array(
                    'post_type'         => Block::$post_type_name,
                    'posts_per_page'    => -1,
                    'post__in'          => $post_ids,
                ) );

                foreach ( $posts as $post ) {
                    $slugs[] = $post->post_name;
                }
            }

            // Now that we know which specific blocks to sync...
            foreach ( $slugs as $slug ) {
                $json_block = $blocks->get_by_slug( $slug );

                // Skip if we don't have a block
                if ( ! $json_block ) continue;

                // Load the block so we have an ID (if the block already exists)
        		$json_block->load_from_slug( $json_block->slug );

                // Blocks that are missing won't have an ID so this should
                // create a new block in that case
        		$json_block->save();

        		// Re-save the JSON so the modified timestamp is correct
        		$json_block->save_json();

        		// Import versions
        		foreach ( $json_block->get_versions() as $version ) {
        			$version->parent = $json_block->id;

        			$version->save();
        		}
            }

            wp_redirect( $redirect );

            exit();
        }

		if ( 'wbb_export_blocks' == $action ) {
			$blocks = new Block_Collection();
			$blocks->from_post_ids( $post_ids, true );
			$blocks->export_to_file();
		}
    }

    /**
     * Helper function that returns the URL for the blocks list admin page.
     */
    private function get_url() {
        $url = admin_url( 'edit.php' );
        $url = add_query_arg( 'post_type', Block::$post_type_name, $url );

        if ( $this->post_status ) {
            $url = add_query_arg( 'post_status', $this->post_status, $url );
        }

        return $url;
    }

    /**
	 * WordPress post_row_actions filter.  Adds a 'sync' link if there is a
	 * JSON file for the block with a newer timestamp.
	 */
	public function post_row_actions( $actions, $post ) {
		// We only care about blocks
		if ( Block::$post_type_name == $post->post_type ) {
			$block 	= new Block();

			$block->from_post( $post );

			$actions = $this->add_duplicate_post_row_action( $actions, $block );
			$actions = $this->add_sync_post_row_action( $actions, $block );
			$actions = $this->add_export_post_row_action( $actions, $block );
		}

		return $actions;
	}

	/**
	 * Adds a duplicate link to the post row actions for blocks.
	 */
	private function add_duplicate_post_row_action( $actions, $block ) {
		// Don't add duplicate to 'missing' blocks or trash views
		if ( 'missing' != $this->post_status && 'trash' != $this->post_status ) {
			$url 	= $this->get_url();
			$url 	= add_query_arg( 'action', 'wbb_duplicate_block', $url );
			$url 	= add_query_arg( 'id', $block->id, $url );
			$url 	= wp_nonce_url( $url, 'wbb_duplicate_block' );
			$label 	= __( 'Duplicate', 'wicked-block-builder' );
			$action = array(
				'wbb_duplicate' => '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>',
			);

			// Check for trash key on the off chance another plugin
			// removed the trash action
			if ( array_key_exists( 'trash', $actions ) ) {
				Util::array_insert_before_key( $actions, 'trash', $action );
			} else {
				$actions += $action;
			}
		}

		return $actions;
	}

	/**
	 * Adds a sync link to the post row actions for blocks.
	 */
	private function add_sync_post_row_action( $actions, $block ) {
		$blocks = new Block_Collection();

		$blocks->from_json_files();

		$json_block = $blocks->get_by_slug( $block->slug );

		// Block might not have been saved to JSON
		if ( false !== $json_block ) {
			// We only want to sync JSON blocks that have a newer timestamp
			if ( $json_block->modified > $block->modified ) {
				$url 	= admin_url( 'edit.php' );
				$url 	= add_query_arg( 'post_type', Block::$post_type_name, $url );
				$url 	= add_query_arg( 'slug', $block->slug, $url );
				$url 	= add_query_arg( 'action', 'wbb_sync_block', $url );
				$url 	= wp_nonce_url( $url, 'wbb_sync_block' );
				$label 	= __( 'Sync', 'wicked-block-builder' );
				$action = array(
					'wbb_sync' => '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>',
				);

				// Check for trash key on the off chance another plugin
				// removed the trash action
				if ( array_key_exists( 'trash', $actions ) ) {
					Util::array_insert_before_key( $actions, 'trash', $action );
				} else {
					$actions += $action;
				}
			}
		}

		return $actions;
	}

	/**
	 * Adds an export link to the post row actions for blocks.
	 */
	private function add_export_post_row_action( $actions, $block ) {
		// Don't add export link to 'missing' blocks or trash views
		if ( 'missing' != $this->post_status && 'trash' != $this->post_status ) {
			$url 	= $this->get_url();
			$url 	= add_query_arg( 'action', 'wbb_export_block', $url );
			$url 	= add_query_arg( 'id', $block->id, $url );
			$url 	= wp_nonce_url( $url, 'wbb_export_block' );
			$label 	= __( 'Export', 'wicked-block-builder' );
			$action = array(
				'wbb_export' => '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>',
			);

			// Check for trash key on the off chance another plugin
			// removed the trash action
			if ( array_key_exists( 'trash', $actions ) ) {
				Util::array_insert_before_key( $actions, 'trash', $action );
			} else {
				$actions += $action;
			}
		}

		return $actions;
	}

	/**
	 * Handles requests to sync a block from JSON.
	 */
	private function maybe_sync_block() {
		if ( 'wbb_sync_block' != $this->action ) return;

		check_admin_referer( 'wbb_sync_block' );

		$slug 		= sanitize_text_field( $_GET['slug'] );
		$block 		= new Block();
		$blocks 	= new Block_Collection();

		$block->load_from_slug( $slug );
		$block->delete_versions();

		$blocks->from_json_files();

		$json_block = $blocks->get_by_slug( $slug );

		// Assign existing ID to block that was loaded from JSON so it gets
		// overwritten
		$json_block->id = $block->id;

		$json_block->save();

		// Re-save the JSON so the modified timestamp is correct
		$json_block->save_json();

		// Import versions
		foreach ( $json_block->get_versions() as $version ) {
			$version->parent = $json_block->id;

			$version->save();
		}

		$url = admin_url( 'edit.php' );
		$url = add_query_arg( 'post_type', Block::$post_type_name, $url );
		$url = add_query_arg( 'wbb_message', __( 'Successfully synced block.', 'wicked-block-builder' ), $url );

		wp_redirect( $url );

		exit();
	}

	/**
	 * Handles requests to export a block.
	 */
	private function maybe_export_block() {
		if ( 'wbb_export_block' != $this->action ) return;

		check_admin_referer( 'wbb_export_block' );

		$id 	= absint( $_GET['id'] );
		$blocks = new Block_Collection();

		$blocks->from_post_ids( array( $id ), true );
		$blocks->export_to_file();

		exit();
	}

	/**
	 * Handles requests to duplicate a block.
	 */
	private function maybe_duplicate_block() {
		global $wpdb;

		if ( 'wbb_duplicate_block' != $this->action ) return;

		check_admin_referer( 'wbb_duplicate_block' );

		$n 		= 1;
		$id 	= absint( $_GET['id'] );
		$url 	= $this->get_url();
		$url 	= add_query_arg( 'wbb_notice', __( 'Successfully duplicated block.', 'wicked-block-builder' ), $url );
		$block 	= new Block( $id );
		$title 	= $block->title . ' (' . sprintf( __( 'copy %d', 'wicked-block-builder' ), $n ) . ')';
		$titles = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status NOT IN ('trash', 'inherit', 'auto-draft' )",
				Block::$post_type_name
			)
		);

		// Generate a unique title for the block
		while ( in_array( $title, $titles ) ) {
			$n++;

			$title = $block->title . ' (' . sprintf( __( 'copy %d', 'wicked-block-builder' ), $n ) . ')';
		}

		// Unset the block's ID so that when we save it, it will create a new block
		$block->id 		= false;
		$block->title 	= $title;
		$block->status 	= 'draft';
		$block->slug 	= sanitize_title( $title );

		// Save the block
		$block->save();

		wp_redirect( $url );

		exit();
	}
}
