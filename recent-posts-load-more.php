<?php
/**
 * Plugin Name: Recent Posts Load More
 * Description: Display recent posts with a "Load More" button using AJAX and ACF for customization.
 * Version: 1.0
 * Author: Hossam Omran
 * Text Domain: recent-posts-load-more
 * 
 * Shortcode: [recent_posts-workmotion]
 * This plugin uses ACF Fields to allow easy customziations for the content like the name , categories , posts per page and so on.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Check if ACF is active.
if (!class_exists('ACF')) {
    add_action('admin_notices', 'acf_not_active_notice');
    function acf_not_active_notice() {
        echo '<div class="notice notice-error"><p>' . __('The Recent Posts Load More plugin requires Advanced Custom Fields Pro to be installed and activated.', 'recent-posts-load-more') . '</p></div>';
    }
    return;
}

// Register ACF fields.
add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key' => 'group_66a97cab09e2d',
		'title' => 'Recent Posts Settings',
		'fields' => array(
			array(
				'key' => 'field_66a97cabf8ee3',
				'label' => 'Posts Per Row',
				'name' => 'posts_per_row',
				'aria-label' => '',
				'type' => 'number',
				'instructions' => 'Insert Number of posts per row	example 2 , 3 or 4
	            This will affect only the desktop , tabelt and mobile always showing 1 col per row',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => 3,
				'min' => 2,
				'max' => 4,
				'placeholder' => '',
				'step' => '',
				'prepend' => '',
				'append' => '',
			),
			array(
				'key' => 'field_66a97d12f8ee4',
				'label' => 'Category',
				'name' => 'category-selected',
				'aria-label' => '',
				'type' => 'taxonomy',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'taxonomy' => 'category',
				'add_term' => 0,
				'save_terms' => 0,
				'load_terms' => 0,
				'return_format' => 'id',
				'field_type' => 'multi_select',
				'allow_null' => 1,
				'multiple' => 0,
			),
			array(
				'key' => 'field_66a97d3bf8ee5',
				'label' => 'Posts Per Page',
				'name' => 'posts_per_page',
				'aria-label' => '',
				'type' => 'number',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => 5,
				'min' => '',
				'max' => '',
				'placeholder' => '',
				'step' => '',
				'prepend' => '',
				'append' => '',
			),
            array(

                'key' => 'field_gqtujbt7ckseb',
                'label' => 'Heading Text',
                'name' => 'more_posts_heading',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
		),
		'location' => array(
			array(
				array(
					'param' => 'options_page',
					'operator' => '==',
					'value' => 'recent-posts-settings',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => '',
		'show_in_rest' => 0,
	) );
} );


// Add options page for ACF fields.
if (function_exists('acf_add_options_page')) {
    acf_add_options_page(array(
        'page_title'    => 'Recent Posts Settings',
        'menu_title'    => 'Recent Posts Settings',
        'menu_slug'     => 'recent-posts-settings',
        'capability'    => 'edit_posts',
        'redirect'      => false
    ));
}

// Enqueue Bootstrap and custom scripts.
function enqueue_custom_scripts() {
    // register styles and scripts to be used
    //plugin css
    wp_register_style('load-more-css', plugin_dir_url(__FILE__) . '/assets/css/custom-styles-min.css');
    //bootstrap
    wp_register_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css', array(), '5.1.3');
    //custom JS for AJAX
    wp_register_script('custom-ajax-script', plugin_dir_url(__FILE__) . '/assets/js/custom-ajax-min.js', array('jquery'), null, true);
    //pass the following data to the js file ajax url , nonce for security posts per page and categories if any. 
    wp_localize_script('custom-ajax-script', 'custom_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('load_more_posts'),
        'posts_per_page' => get_field('posts_per_page', 'option') ?: 5,
        'category' => get_field('category-selected', 'option') ?: '',
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');


function custom_recent_posts_shortcode($atts) {
    // Enqueue CSS & JS only when the shortcode is used
    //Optimized**
      wp_enqueue_style('bootstrap-css');
      wp_enqueue_style('load-more-css');
      wp_enqueue_script('custom-ajax-script');

    // Fetch ACF field values
    $posts_per_row = get_field('posts_per_row', 'option') ?: 3;
    $categories = get_field('category-selected', 'option');
    $posts_per_page = get_field('posts_per_page', 'option') ?: 5;
    $more_posts_heading = get_field('more_posts_heading', 'option') ?: 'Recent Posts';

    // Ensure categories is an array
    if (!is_array($categories)) {
        $categories = $categories ? array($categories) : array();
    }

    // Initial query to get posts in descending order acording to date of publishing
    $query_args = array(
        'posts_per_page' => $posts_per_page,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    //Check if their are any categories passed from the ACF Option page
    if (!empty($categories)) {
        $query_args['category__in'] = $categories;
    }

    $query = new WP_Query($query_args);

    // Render initial posts and prevent Header already sent issues
    ob_start();
    if ($query->have_posts()) { ?>
        <section class="blog-page-container mt-4 w-100">
            <div class="tile-wrapper w-100">
                <div class="row clearfix w-100">
                    <div class="content-side col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <p class="display-3 mb-md-4 my-5 fw-medium text-center"> <?php echo  $more_posts_heading; ?></p>
                        <div class="row mt-5" id="recent-posts">
                            <?php while ($query->have_posts()) {
                                $query->the_post();
                                $post_categories = get_the_category();
                                $category_name = !empty($post_categories) ? esc_html($post_categories[0]->name) : ''; ?>
                                <div class="col-lg-<?php echo 12 / $posts_per_row; ?> col-md-12 col-sm-12 d-flex">
                                    <div class="inner-box w-100 d-flex flex-column justify-content-between">
                                        <a href="<?php the_permalink(); ?>" class="d-flex flex-column w-100" style="text-decoration:none;">
                                            <div class="image">
                                                <?php if (has_post_thumbnail()) { ?>
                                                    <img src="<?php echo get_the_post_thumbnail_url(); ?>" class="img-fluid" alt="<?php the_title(); ?>">
                                                <?php } ?>
                                            </div>
                                            <div class="lower-content ps-4 pe-4 flex-grow-1">
                                                <div class="lower-box">
                                                    <div class='categories-container mb-3'>
                                                        <small class="text-muted"><?php echo 'Category: ' .$category_name; ?></small>
                                                    </div>    
                                                    <h3><?php the_title(); ?></h3>
                                                    <p><?php the_excerpt(20); ?></p>
                                                </div>
                                            </div>
                                      
                                            <div class="read-more-container">
                                                <a href="<?php the_permalink(); ?>" class="btn btn-primary">Read More</a>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="load-more-container d-flex justify-content-center">
                <button id="load-more-posts" class="btn btn-primary mt-4">Load More</button>
            </div>
        </section>
    <?php }

    //don't forget to reset the post data to prevent conflicts with other CPT's
    wp_reset_postdata();

    return ob_get_clean();
}
//register the shortcode to be used in any page
add_shortcode('recent_posts-workmotion', 'custom_recent_posts_shortcode');

// AJAX Handler 
function load_more_posts_ajax_handler() {
    check_ajax_referer('load_more_posts', 'nonce');

    $categories = isset($_POST['category']) ? $_POST['category'] : [];
    if (!is_array($categories)) {
        $categories = $categories ? array($categories) : array();
    }
    $categories = array_map('intval', $categories);

    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 5;

    $query_args = array(
        'posts_per_page' => $posts_per_page,
        'offset' => $offset,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    if (!empty($categories)) {
        $query_args['category__in'] = $categories;
    }
    $query = new WP_Query($query_args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_categories = get_the_category();
            $category_name = !empty($post_categories) ? esc_html($post_categories[0]->name) : ''; ?>
            <div class="col-lg-<?php echo 12 / get_field('posts_per_row', 'option'); ?> col-md-12 col-sm-12 d-flex">
                <div class="inner-box w-100 d-flex flex-column justify-content-between">
                    <a href="<?php the_permalink(); ?>" class="d-flex flex-column w-100" style="text-decoration:none;">
                        <div class="image">
                            <?php if (has_post_thumbnail()) { ?>
                                <img src="<?php echo get_the_post_thumbnail_url(); ?>" class="img-fluid" alt="<?php the_title(); ?>">
                            <?php } ?>
                        </div>
                        <div class="lower-content ps-4 pe-4 flex-grow-1">
                            <div class="lower-box">
                                <div class='categories-container mb-3'>
                                    <small class="text-muted"><?php echo 'Category: ' .$category_name; ?></small>
                                </div> 
                                <h3><?php the_title(); ?></h3>
                                <p><?php the_excerpt(15); ?></p>
                            </div>
                        </div>
                    </a>
                    <div class="read-more-container">
                        <a href="<?php the_permalink(); ?>" class="btn btn-primary">Read More</a>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="col-12">
            <p>No more posts to load.</p>
        </div>
        <?php
    }
    wp_die();
}
//allow ajax for both logged in and logged out users and return the ajax call results
add_action('wp_ajax_load_more_posts', 'load_more_posts_ajax_handler');
add_action('wp_ajax_nopriv_load_more_posts', 'load_more_posts_ajax_handler');


// Remove [...] from excerpt
function custom_excerpt_more( $more ) {
    return '';
}
add_filter( 'excerpt_more', 'custom_excerpt_more', 11 );