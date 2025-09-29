<?php
namespace Ninjalytics\Admin;


abstract class Page {
	
	protected $reviewUrl, $docsUrl, $supportUrl;
	
	function render() {
		$this->header();
		if (!apply_filters('berrypress_admin_page_replace_body', false, $this)) {
			$this->body();
		}
		$this->footer();
	}
	
	function header() {

	$nav = apply_filters('berrypress_admin_page_nav', array(), $this);
	$top_nav = apply_filters('berrypress_admin_page_top_nav', array(), $this);
	$logo          = apply_filters( 'berrypress_admin_page_logo', '' );
	$product_url    = apply_filters( 'berrypress_admin_page_header_url', '' );
	$header_text   = apply_filters( 'berrypress_admin_page_header_text', '' );
	$display_sidebar   = apply_filters( 'berrypress_admin_page_display_sidebar', true , '' );
	$display_top_nav   = apply_filters( 'berrypress_admin_page_display_top_nav', true , '' );
	$display_top_right_nav   = apply_filters( 'berrypress_admin_page_display_top_right_nav', true , '' );

?>
    <div class="berrypress-settings-container">

        <header class="berrypress-header">
            <button id="berrypress-toggle-menu-mobile" class="berrypress-btn berrypress-btn-icon" aria-label="Toggle Sidebar"><i class="berrypress-icon-menu" aria-hidden="true"></i></button>
            <a href="<?php echo esc_url( $product_url ); ?>" target="_blank" class="berrypress-header-left">
                <img src="<?php
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                echo esc_url($logo);
                ?>" alt="<?php esc_attr_e("Logo", "product-sales-report-for-woocommerce"); ?>">
                <span class="berrypress-logo">
					<?php echo esc_html( $header_text ); ?>
					<span style="font-weight:300">Alpha</span>
				</span>
            </a>

	        <?php if ($display_top_nav) : ?>
                <nav class="berrypress-nav">
			        <?php foreach ($top_nav as $navItem) { ?>
                        <a href="<?php echo(esc_url($navItem['link'])); ?>" <?php if ($navItem['active'] ?? false) { ?> class="active" <?php } ?>
					        <?php if (!empty($navItem['link_id'])) : ?>  id="<?php echo esc_attr($navItem['link_id']); ?>" <?php endif; ?>>
				            <?php if (!empty($navItem['icon'])) : ?> <i class="<?php echo(esc_attr($navItem['icon'])); ?>"></i> <?php endif; ?>
					        <?php echo(esc_html($navItem['title'])); ?>
                        </a>
			        <?php } ?>
                </nav>
	        <?php endif; ?>
	        <?php if ($display_top_right_nav) : ?>
                <div class="berrypress-header-right">
                    <a class="berrypress-help-btn last" href="<?php echo esc_url( $this->supportUrl ); ?>" target="_blank" rel="noopener">
                        <i class="berrypress-icon-help"></i> Help
                    </a>

			        <?php if ($this->reviewUrl) { ?>
                        <a href="<?php echo(esc_url($this->reviewUrl)); ?>" target="_blank"><i class="berrypress-icon-star"></i><?php esc_html_e( 'Leave a Review', 'product-sales-report-for-woocommerce' ); ?></a>
			        <?php } ?>

			        <?php if ($this->docsUrl) { ?>
                        <a href="<?php echo(esc_url($this->docsUrl)); ?>" target="_blank"><i class="berrypress-icon-library_books"></i><?php esc_html_e( 'Documentation', 'product-sales-report-for-woocommerce' ); ?></a>
			        <?php } ?>

			        <?php if ($this->supportUrl) { ?>
                        <a href="<?php echo(esc_url($this->supportUrl)); ?>" target="_blank"><i class="berrypress-icon-help"></i><?php esc_html_e( 'Support', 'product-sales-report-for-woocommerce' ); ?></a>
			        <?php } ?>
                </div>
	        <?php endif; ?>
        </header>

        <!--  Sidebar + Content -->
        <div class="berrypress-main">
            <!-- Sidebar -->
            <aside id="berrypress-sidebar" class="berrypress-sidebar <?php echo !$display_sidebar ? 'collapsed' : ''; ?>">
                <div class="berrypress-sidebar-header">
                    <h2><?php esc_html_e( 'Menu', 'product-sales-report-for-woocommerce' ); ?></h2>
                    <button class="berrypress-toggle-sidebar berrypress-btn berrypress-btn-icon" aria-label="Toggle Sidebar"><i class="berrypress-icon-first_page" aria-hidden="true"></i></button>
                </div>

                <ul class="berrypress-menu">
                    <?php foreach ($nav as $navItem) { ?>
                        <li <?php if ($navItem['active'] ?? false) { ?> class="active"<?php } ?>>
                            <a href="<?php echo(esc_url($navItem['link'])); ?>"
	                            <?php if (!empty($navItem['link_id'])) : ?>  id="<?php echo esc_attr($navItem['link_id']); ?>" <?php endif; ?>>
	                            <?php if (!empty($navItem['icon'])) : ?> <i class="<?php echo(esc_attr($navItem['icon'])); ?>"></i> <?php endif; ?>
					            <?php echo(esc_html($navItem['title'])); ?>
                            </a></li>
		            <?php } ?>
                </ul>

                <ul class="berrypress-menu">
                    <li><h3><?php esc_html_e( 'External Links', 'product-sales-report-for-woocommerce' ); ?></h3></li>

                    <li><a href="https://berrypress.com/" target="_blank"><i class="berrypress-icon-home"></i>BerryPress Website</a></li>
					
					<?php if ($this->reviewUrl) { ?>
                    <li><a href="<?php echo(esc_url($this->reviewUrl)); ?>" target="_blank"><i class="berrypress-icon-star"></i><?php esc_html_e( 'Leave a Review', 'product-sales-report-for-woocommerce' ); ?></a></li>
					<?php } ?>
					
					<?php if ($this->docsUrl) { ?>
                    <li><a href="<?php echo(esc_url($this->docsUrl)); ?>" target="_blank"><i class="berrypress-icon-library_books"></i><?php esc_html_e( 'Documentation', 'product-sales-report-for-woocommerce' ); ?></a></li>
					<?php } ?>
					
					<?php if ($this->supportUrl) { ?>
                    <li><a href="<?php echo(esc_url($this->supportUrl)); ?>" target="_blank"><i class="berrypress-icon-help"></i><?php esc_html_e( 'Support', 'product-sales-report-for-woocommerce' ); ?></a></li>
					<?php } ?>
                </ul>
				
				<a href="https://help.berrypress.com/open.php?topicId=18" target="_blank" class="berrypress-btn berrypress-btn-secondary ags-psr-ml-10">Report a Bug</a>

                <div class="berrypress-upgrade-box">
                    <div>
                        <i class="berrypress-icon-filled berrypress-icon-lock"></i>
                        <h4><?php esc_html_e( 'Unlock More Features!', 'product-sales-report-for-woocommerce' ); ?></h4>
                        <ul><li><?php esc_html_e( 'Get ready for premium enhancements coming soon.', 'product-sales-report-for-woocommerce' ); ?></li></ul>
                        <a href="https://mailchi.mp/c7d970d75c8c/3iul0s96fr" target="_blank" class="berrypress-btn berrypress-btn-primary"><?php esc_html_e( 'Stay Tuned for Pro', 'product-sales-report-for-woocommerce' ); ?></a>
                    </div>
                </div>
            </aside>

            <!-- Main -->
            <main id="berrypress-content" class="berrypress-content">
                <div class="wrap"><h2></h2></div>
<?php
	}

	function footer() {
?>
            </main>

     </div>
</div>
<script>
    jQuery(document).ready(function($) {
        const $bpToggleBtn = $(".berrypress-toggle-sidebar");
        const $bpSidebar   = $("#berrypress-sidebar");
        const $mobileButton = $("#berrypress-toggle-menu-mobile");

        $bpToggleBtn.on("click", function() {
            $bpSidebar.toggleClass("collapsed");
        });
        $mobileButton.on("click", function() {
            $bpSidebar.toggleClass("collapsed");
        });
    });

    document.fonts.ready.then(() => {
        document.documentElement.classList.add('berrypress-font-loaded');
    });

    jQuery(document).ready(function($) {

        // Sidebar Auto Collapse on Small Screens
        function berrypress_checkSidebar() {
            if ($(window).width() > 699) {
                $(".berrypress-sidebar").addClass("collapsed");
            }
        }

        berrypress_checkSidebar();
        $(window).on("resize", berrypress_checkSidebar);

        // Ensure custom settings button triggers WordPress default settings link
        // $('#berrypress-show-settings-link').on('click', function(event) {
        //     event.preventDefault();
        //     $('#show-settings-link').trigger('click');
        // });

    });
    /*
     jQuery(document).ready(function($) {
			// Check Table Width & Apply Class
			function berrypress_checkTableWidth() {
				$(".berrypress-table").each(function() {
					if ($(this).width() < 800) {
						$(this).addClass("berrypress-table-small");
					} else {
						$(this).removeClass("berrypress-table-small");
					}
				});
			}

			berrypress_checkTableWidth();
			$(window).on("resize", berrypress_checkTableWidth);

			function berrypress_adjustSidebarHeight() {
				var sidebar = document.getElementById('berrypress-sidebar');

				if (sidebar) {
					var viewportWidth = window.innerWidth;
					var viewportHeight = window.innerHeight;

					// Skip on small screens (e.g. mobile)
					if (viewportWidth < 600) {
						$(sidebar).css('min-height', '');
						return;
					}

					var rectTop = sidebar.getBoundingClientRect().top;

					if (Math.abs(rectTop - 32) < 2) {
						$(sidebar).css('min-height', '');
					} else {
						var visibleHeight = viewportHeight - rectTop;
						$(sidebar).css('min-height', visibleHeight + 'px');
					}
				}
			}

			berrypress_adjustSidebarHeight();
			$(window).on('resize scroll', berrypress_adjustSidebarHeight); // scroll też!


			// Plugin Specific

		});

	*/
</script>
<?php
	}
	
	abstract function body();
	
}
