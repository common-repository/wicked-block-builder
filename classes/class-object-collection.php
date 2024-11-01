<?php

namespace Wicked_Block_Builder;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Iterates through an array of objects.
 */
abstract class Object_Collection implements \Countable, \Iterator {

    /**
	 * The current index within the collection.
	 *
     * @var integer
     */
    protected $index = 0;

    /**
	 * Holds the collection's objects.
	 *
     * @var array
     */
    protected $items = array();

    /**
	 * The current object.
	 *
     * @var Block
     */
    protected $item = null;

    /**
     * Count implementation.
     */
    public function count(): int {
        return count( $this->items );
    }

    /**
     * Current implementation.
     */
    public function current(): Block {
        return $this->items[ $this->index ];
    }

    /**
     * Key implementation.
     */
    public function key(): int {
        return $this->index;
    }

    /**
     * Next implementation.
     */
    public function next(): void {
        ++$this->index;
    }

    /**
     * Rewind implementation.
     */
    public function rewind(): void {
        $this->index = 0;
    }

    /**
     * Valid implementation.
     */
    public function valid(): bool {
        return isset( $this->items[ $this->index ] );
    }

	/**
	 * Add an item to the collection.
	 */
	public abstract function add( $item );

	/**
	 * Empties the collection and resets the index.
	 */
	public function empty() {
		$this->items 	= array();
		$this->item 	= null;

		$this->rewind();
	}
	/**
	 * Sorts the items in the collection by their 'order' property (if one exists).
	 */
	public function sort( $key = 'order' ) {
		usort( $this->items, function( $a, $b ) use ( $key ) {
			$a_order = $a->{ $key };
			$b_order = $b->{ $key };

			if ( $a_order == $b_order ) return 0;

			return ( $a_order < $b_order ) ? -1 : 1;
		} );

		return $this->items;
	}

	/**
	 * Filters the items within the collection.
	 *
	 * @see wp_filter_object_list()
	 *
	 * @param array $args
	 *  An array of property value pairs to filter by.
	 * @param string $operator
	 *  The comparision operator to use. Either 'and' or 'or'.
	 * @return Object_Collection
	 *  A new collection with the matching items.
	 */
	public function filter( $args = array(), $operator = 'and' ) {
		// Get current class name
		$class = get_class( $this );

		// Instantiate a new collection
		$collection = new $class();

		// Filter the items in the current collection
		$items = wp_filter_object_list( $this->items, $args, $operator );

		// Now add them to our new collection
		foreach ( $items as $item ) {
			$collection->add( $item );
		}

		return $collection;
	}

	/**
	 * Adds the item to the collection if it is the correct type, otherwise
	 * throws an error.
	 *
	 * @param mixed $item
	 *  The item to add.
	 * @param class
	 *  The class object that the item must be.
	 */
	protected function add_if( $item, $type ) {
		if ( $item ) {
			if ( is_a( $item, $type ) ) {
				$this->items[] = $item;
			} else {
				throw new \Exception( __( 'Item must be ', 'wicked-block-builder' ) . $type );
			}
		}
	}

	/**
	 * Returns whether or not the collection is empty.
	 *
	 * @return boolen
	 *  True if the collection contains no items, false otherwise.
	 */
	public function is_empty() {
		return $this->count() < 1;
	}

	/**
	 * Returns the first item in the collection.
	 *
	 * @return mixed
	 *  The first item in the collection or false if the collection is empty.
	 */
	public function get_first_item() {
		return $this->is_empty() ? false : $this->items[ 0 ];
	}

	/**
	 * Gets an item by an 'id' property.
	 *
	 * @param int
	 *  The ID to fetch the item by.
	 * @return mixed|boolean
	 *  Item matching the ID or false if not found.
	 */
	public function get( $id ) {
		return $this->get_by_key( 'id', $id );
	}

	/**
	 * Gets an item with a property matching the specified value.
	 *
	 * @param string $property
	 *  An object property to filter by.
	 * @param mixed $value
	 *  The value to filter by.
	 * @return mixed|boolean
	 *  The first item found with a matching property and value or false if a
	 *  matching item wasn't found.
	 */
	public function get_by_key( $key, $value ) {
		foreach ( $this->items as $item ) {
			if ( property_exists( $item, $key ) && $item->{ $key } == $value ) {
				return $item;
			}
		}

		return false;
	}
}
