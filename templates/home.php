<div class="wrap">
    <div id="wbb-home">
        <h1><?php _e( 'Welcome to Wicked Block Builder', 'wicked-block-builder' ); ?></h1>
        <p class="actions"><a class="add-block" href="<?php echo get_admin_url( null, 'admin.php?page=wicked_block_builder_builder' ); ?>">Add a New Block</a></p>
        <?php if ( ! $is_pro ) : ?>
            <div class="pro-callout">
                <h2><?php _e( 'Get More With Wicked Block Builder Pro', 'wicked-block-builder' ); ?></h2>
                <div class="content-wrapper">
                    <div class="content">
                        <ul>
                            <li><?php _e( 'Repeater', 'wicked-block-builder' ); ?></li>
                            <li><?php _e( 'Conditional logic', 'wicked-block-builder' ); ?></li>
                            <li><?php _e( 'PostSelect component', 'wicked-block-builder' ); ?></li>
                            <li><?php _e( 'TermSelect component', 'wicked-block-builder' ); ?></li>
                            <li><?php _e( 'InnerBlocks component', 'wicked-block-builder' ); ?></li>
                            <li><?php _e( 'Export blocks to plugin', 'wicked-block-builder' ); ?></li>
                        </ul>
                    </div>
                    <div class="action">
                        <a class="btn accent" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank">
                            <?php _e( 'Learn More', 'wicked-block-builder' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <h2><?php _e( 'Helpful Resources', 'wicked-block-builder' ); ?></h2>
        <div class="cards">
            <div>
                <div class="with-action">
                    <h3><?php _e( 'Build Your First Block', 'wicked-block-builder' ); ?></h3>
                    <p><?php _e( 'New to Wicked Block Builder?  Check out our guide to building your first block to see how Wicked Block Builder works.', 'wicked-block-builder' ); ?></p>
                    <p class="action">
                        <a class="btn" href="<?php echo esc_url( $guide_url ); ?>" target="_blank">
                            <?php _e( 'View the Guide', 'wicked-block-builder' ); ?>
                        </a>
                    </p>
                </div>
            </div>
            <div>
                <div class="with-action">
                    <h3><?php _e( 'Documentation', 'wicked-block-builder' ); ?></h3>
                    <p><?php _e( 'We’ve put together a comprehensive set of documentation to help you get the most out of Wicked Block Builder.', 'wicked-block-builder' ); ?></p>
                    <p class="action">
                        <a class="btn" href="<?php echo esc_url( $docs_url ); ?>" target="_blank">
                            <?php _e( 'View Documentation', 'wicked-block-builder' ); ?>
                        </a>
                    </p>
                </div>
            </div>
            <div>
                <div class="with-action">
                    <h3><?php _e( 'Videos', 'wicked-block-builder' ); ?></h3>
                    <p><?php _e( 'Check out our YouTube channel for helpful videos and walkthroughs.', 'wicked-block-builder' ); ?></p>
                    <p class="action">
                        <a class="btn" href="<?php echo esc_url( 'https://www.youtube.com/channel/UCuJpUMvnAccoehv4EJfNa7Q/videos' ); ?>" target="_blank">
                            <?php _e( 'View Videos', 'wicked-block-builder' ); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <h2>
            <?php _e( 'Import and Export Blocks', 'wicked-block-builder' ); ?>
        </h2>
        <div class="cards">
            <div class="auto-height">
                <div class="import-card">
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="wbb_import" />
                        <?php wp_nonce_field( 'wbb_import', 'nonce' ); ?>
                        <h3><?php _e( 'Import Blocks', 'wicked-block-builder' ); ?></h3>
                        <p>
                            <label class="screen-reader-text" for="wbb-import-file">
                                <?php _e( 'Select file', 'wicked-block-builder' ); ?>
                            </label>
                            <input id="wbb-import-file" name="import" type="file" accept=".json,text/plain" />
                        </p>
                        <button class="btn" type="submit">
                            <?php _e( 'Import', 'wicked-block-builder' ); ?>
                        </button>
                    </form>
                </div>
            </div>
            <div class="span-2">
                <div class="export-card">
                    <form action="" method="post">
                        <input type="hidden" name="action" value="wbb_export" />
                        <?php wp_nonce_field( 'wbb_export', 'nonce' ); ?>
                        <h3><?php _e( 'Export Blocks', 'wicked-block-builder' ); ?></h3>
                        <?php if ( $blocks->count() > 0 ) : ?>
                            <ul class="block-list">
                                <li>
                                    <input
                                        id="export-all-blocks"
                                        type="checkbox"
                                        name="all"
                                        value=""
                                    />
                                    <label for="export-all-blocks">
                                        <?php _e( '(select all)', 'wicked-block-builder' ); ?>
                                    </label>
                                </li>
                                <?php foreach ( $blocks as $block ) : ?>
                                    <li>
                                        <input
                                            id="export-block-<?php echo esc_attr( $block->id ); ?>"
                                            type="checkbox"
                                            name="id[]"
                                            value="<?php echo esc_attr( $block->id ); ?>"
                                        />
                                        <label for="export-block-<?php echo esc_attr( $block->id ); ?>">
                                            <?php echo esc_html( $block->title ); ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php do_action( 'wbb_export_options' ); ?>
                            <button class="btn" type="submit">
                                <?php _e( 'Export', 'wicked-block-builder' ); ?>
                            </button>
                        <?php else : ?>
                            <p>
                                <?php _e( "You haven't created any blocks yet.", 'wicked-block-builder' ); ?>
                            </p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <?php if ( $is_pro ) : ?>
            <h2><?php _e( 'Settings', 'wicked-block-builder' ); ?></h2>
            <div class="cards">
                <?php do_action( 'wbb_license_settings' ); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    ( function( $ ){
        $( function(){
            $( '.block-list [type="checkbox"]' ).change( function(){
                var $list = $( this ).parents( '.block-list' );
                var checked = $( this ).prop( 'checked' );
                var count = $list.find( '[type="checkbox"]:not([name="all"])' ).length;
                var checkedCount = $list.find( '[type="checkbox"]:not([name="all"]):checked' ).length;

                if ( 'all' == $( this ).attr( 'name' ) ) {
                    $list.find( '[type="checkbox"]' ).prop( 'checked', checked );
                } else {
                    $list.find( '[name="all"]' ).prop( 'checked', count == checkedCount );
                }
            } );
        } );
    } )( jQuery );
</script>
