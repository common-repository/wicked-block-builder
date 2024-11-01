<?php

namespace Wicked_Block_Builder;

use Exception;
use DirectoryIterator;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Holds a collection of blocks.
 */
class Block_Collection extends Object_Collection implements \JsonSerializable {

    /**
     * Add a block.
     *
     * @param Block
     *  The block to add.
     */
    public function add( $item ) {
        $this->add_if( $item, 'Wicked_Block_Builder\Block' );
    }

	public function jsonSerialize(): array {
		$json = array();

		foreach ( $this->items as $block ) {
			$json[] = $block->jsonSerialize();
		}

		return $json;
	}

	/**
	 * Populates the collection with the current version of published blocks and
	 * returns the collection.
	 *
	 * @return Block_Collection
	 */
	public function get_published_blocks() {
		// Get published blocks
		$blocks = get_posts( array(
		    'post_type'         => Block::$post_type_name,
		    'posts_per_page'    => -1,
			'post_status' 		=> 'publish',
		) );

		foreach ( $blocks as $post ) {
			$block = new Block();
			$block->from_post( $post );

			$this->add( $block );
		}

		return $this;
	}

	/**
	 * Gets the block with the specified slug.
	 *
	 * @param string $slug
	 *  The block's slug.
	 *
	 * @return Block|boolean
	 *  A Block object if a matching block is found, false otherwise.
	 */
	public function get_by_slug( $slug ) {
		foreach ( $this->items as $block ) {
			if ( $block->slug == $slug ) {
				return $block;
			}
		}

		return false;
	}

	/**
	 * Returns the CSS for the blocks in the collection.
	 *
	 * @return string
	 */
	public function get_css() {
		$css = array();

		foreach ( $this->items as $block ) {
			$css[] = $block->css;
		}

		$css = implode( "\n\n", $css );
		$css .= "\n";

		return $css;
	}

	/**
	 * Registers all blocks in the collection.
	 */
	public function register() {
		foreach ( $this->items as $block ) {
			$block->register();
		}

		return $this;
	}

	/**
	 * Sets the serialize ID property for all blocks in the collection.
	 *
	 * @see Block::set_serialize_id
	 */
	public function set_serialize_id( $serialize ) {
		foreach ( $this->items as $block ) {
			$block->set_serialize_id( $serialize );
		}

		return $this;
	}

	/**
	 * Populates the collection from an array of block post IDs.
	 *
	 * @param array $post_ids
	 *  Array of block post IDs to load.
	 *
	 * @param boolean $load_versions
	 *  When true, load each block's previous versions.
	 *
	 * @return Block_Collection
	 *  The current collection instance.
	 */
	public function from_post_ids( $post_ids, $load_versions = false ) {
		$this->empty();

		$post_ids 	= array_map( 'absint', $post_ids );
		$posts 		= get_posts( array(
			'post_type'         => Block::$post_type_name,
			'posts_per_page'    => -1,
			'post__in'          => $post_ids,
			'post_status' 		=> array( 'any', 'trash' ),
		) );

		foreach ( $posts as $post ) {
			$block = new Block();
			$block->from_post( $post );

			if ( true === $load_versions ) {
				$block->load_versions();
			}

			$this->add( $block );
		}

		return $this;
	}

	/**
	 * Populates the collection with block posts returned by WP_Query.
	 *
	 * @param array $query
	 *  The query args to pass to WP_Query.
	 *
	 * @param boolean $load_versions
	 *  When true, load each block's previous versions.
	 *
	 * @return Block_Collection
	 *  The current collection instance.
	 */
	public function from_query( $query, $load_versions = false ) {
		$this->empty();

		// Set some defaults
		$query += array(
			'post_type'         => Block::$post_type_name,
			'posts_per_page'    => -1,
		);

		$posts = get_posts( $query );

		foreach ( $posts as $post ) {
			$block = new Block();
			$block->from_post( $post );

			if ( true === $load_versions ) {
				$block->load_versions();
			}

			$this->add( $block );
		}

		return $this;
	}

	/**
	 * Loads collection from block JSON files.  Statically caches results to
	 * avoid hitting file system more than once per request.
	 */
	public function from_json_files() {
		static $blocks = null;

		if ( null === $blocks ) {
			$blocks = array();
			$block 	= new Block();
			$paths 	= array( $block->get_json_save_path() );

			/**
			 * Filters the paths to search for block JSON files.
			 *
			 * @since 1.1.1
			 *
			 * @param array $paths  Array of file paths to search for blocks.
			 */
			$paths = apply_filters( 'wbb_load_json_paths', $paths );

			foreach ( $paths as $path ) {
				$path = trailingslashit( $path );

				try {
					if ( ! is_dir( $path ) ) {
						throw new Exception(
							sprintf(
								__( '%s does not exist', 'wicked-block-builder' ),
								$path
							)
						);
					};

					$files = new DirectoryIterator( $path );

					foreach ( $files as $file ) {
						if ( ! $file->isDot() ) {
							try {
								$block 		= new Block();
								$filename 	= $file->getFilename();
								$slug 		= basename( $filename, '.json' );
								$json 		= file_get_contents( "{$path}{$filename}" );
								$json 		= json_decode( $json );

								$block->from_json( $json );

								$blocks[] = $block;
							} catch ( Exception $e ) {
								error_log(
									sprintf(
										__( 'Error loading block from JSON: %s', 'wicked-block-builder' ),
										$path,
										$e->getMessage()
									)
								);
							}
						}
					}
				} catch ( Exception $e ) {
					error_log(
						sprintf(
							__( 'Error scanning for block JSON files: %s', 'wicked-block-builder' ),
							$e->getMessage()
						)
					);
				}
			}
		}

		foreach ( $blocks as $block ) {
			$this->add( $block );
		}
	}

	/**
	 * Searches the collection and returns a new collection containing any
	 * blocks that exist in the collection but not in the site's database.
	 *
	 * @return Block_Collection
	 *  Blocks missing from database.
	 */
	public function get_missing_blocks() {
		global $wpdb;

		$missing_blocks = new Block_Collection();
		$slugs 			= $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_name FROM {$wpdb->posts} WHERE post_type = %s",
				Block::$post_type_name
			)
		);

		foreach ( $this->items as $block ) {
			if ( ! in_array( $block->slug, $slugs ) ) {
				$missing_blocks->add( $block );
			}
		}

		return $missing_blocks;
	}

	/**
	 * Exports the blocks in the collection to a JSON file.
	 */
	public function export_to_file() {
		// Clean the output buffer to avoid any unintended output being included
		// in the export file
		ob_clean();

		// Suppress block IDs from export
		$this->set_serialize_id( false );

		// Display name of the file
		$name = sprintf( 'wbb-blocks-%s.json', date( 'Y-m-d' ) );
		$data = json_encode( $this, JSON_PRETTY_PRINT );

		// Tweak things a bit if we're only working with one block
		if ( $this->count() == 1 ) {
			$block = $this->get_first_item();

			$name = sprintf( '%s.json', $block->slug );
			$data = json_encode( $block, JSON_PRETTY_PRINT );
		}

		// Give others a chance to change the file name or data
		$name = apply_filters( 'wbb_export_blocks_filename', $name, $this );
		$data = apply_filters( 'wbb_export_blocks_data', $data, $this );

		// Create a temporary file
		$file = tempnam( '/tmp', $name );

		if ( false === $file ) {
			throw new Exception(
				__( 'Unable to create temporary file.', 'wicked-block-builder' )
			);
		}

		// Write the data
		$result = file_put_contents( $file, $data );

		if ( false === $result ) {
			throw new Exception(
				__( 'Unable to write data to export file.', 'wicked-block-builder' )
			);
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'Content-Disposition: attachment; filename=' . $name );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );

		readfile( $file );

		exit();
	}
}
