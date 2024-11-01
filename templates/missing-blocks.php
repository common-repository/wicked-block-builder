<div style="display: none;">
    <table>
        <tbody id="missing-blocks">
            <?php foreach ( $missing as $block ) : ?>
                <tr>
                    <?php foreach ( $columns as $key => $label ) : ?>
                        <?php if ( 'cb' == $key ) : ?>
                            <th class="check-column" scope="row">
                                <label for="cb-select-<?php echo esc_attr( $block->slug ); ?>" class="screen-reader-text">
                                    <?php echo esc_html( sprintf( __( 'Select %s', 'wicked-block-builder' ), $block->title ) ) ?>
                                </label>
                                <input
                                    id="cb-select-<?php echo esc_attr( $block->slug ); ?>"
                                    type="checkbox"
                                    value="<?php echo esc_attr( $block->slug ); ?>"
                                    name="post[]"
                                />
                            </th>
                        <?php endif; ?>

                        <?php if ( 'title' == $key ) : ?>
                            <td class="title column-title has-row-actions column-primary page-title" data-colname="<?php echo esc_attr( $label ); ?>">
                                <strong>
                                    <?php echo esc_html( $block->title ); ?>
                                </strong>
                                <div class="row-actions">
                                    <a href="<?php echo esc_url( add_query_arg( 'slug', $block->slug, $sync_url ) ); ?>">
                                        <?php _e( 'Import', 'wicked-block-builder' ); ?>
                                    </a>
                                </div>
                            </td>
                        <?php endif; ?>

                        <?php if ( 'cb' !== $key && 'title' != $key ) : ?>
                            <td class="<?php echo esc_attr( $key ); ?> column-<?php echo esc_attr( $key ); ?><?php if ( in_array( $key, $hidden ) ) echo ' hidden'; ?>" data-colname="<?php echo esc_attr( $label ); ?>">
                                <?php $this->block_column_content( $key, $block->id ); ?>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
    ( function( $ ){
        $( '#the-list' ).html( $( '#missing-blocks' ).children() );
    } )( jQuery );
</script>
