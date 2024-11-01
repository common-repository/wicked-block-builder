<?php

namespace Wicked_Block_Builder;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Holds a collection of block patterns.
 */
class Block_Pattern_Collection extends Object_Collection {

    /**
     * Add a block pattern.
     *
     * @param Block_Pattern
     *  The block pattern to add.
     */
    public function add( $item ) {
        $this->add_if( $item, 'Wicked_Block_Builder\Block_Pattern' );
    }

	/**
	 * Registers the block pattern's in the collection.
	 */
	public function register() {
		foreach ( $this->items as $pattern ) {
			$pattern->register();
		}
	}

	/**
	 * Populates the collection with all published block patterns.
	 *
	 * @return Block_Pattern_Collection
	 *  The current collection instance for chaining.
	 */
	public function load_published_block_patterns() {
		// Clear items so there aren't duplicates if this method is called
		// multiple times on the same instance
		$this->items = array();

		// Get published block patterns
		$patterns = get_posts( array(
		    'post_type'         => Block_Pattern::$post_type_name,
		    'posts_per_page'    => -1,
			'post_status' 		=> 'publish',
		) );

		foreach ( $patterns as $post ) {
			$pattern = new Block_Pattern();
			$pattern->from_post( $post );

			$this->add( $pattern );
		}

		return $this;
	}
}
