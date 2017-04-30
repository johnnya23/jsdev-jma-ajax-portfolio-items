<?php
/*
Plugin Name: JMA portfolio ajax
Description: Uses ajax to display  single portfolio items
Version: 1.0
Author: John Antonacci  
License: 
*/

function jma_ajax_input_screens_filter($screens){
	$screens[] = 'portfolio_item';
    $screens[] = 'landing_page';
	return $screens;
}
add_filter( 'input_screens_filter', 'jma_ajax_input_screens_filter' );

function jma_ajax_plugin_sidebar_layout( $sidebar_layout ) {
    if( 'portfolio_item' == get_post_type() || 'landing_page' == get_post_type()) {
        $sidebar_layout = 'sidebar_left';
    }
    return $sidebar_layout;
 
}
add_filter( 'themeblvd_sidebar_layout', 'jma_ajax_plugin_sidebar_layout' );


function jma_ajax_scripts() {
	global $wp_query;
	wp_register_script( 'jma_ajax_plugin_content', plugins_url('/ajax.js', __FILE__) , array('jquery'), '1.0', true );
	wp_localize_script( 'jma_ajax_plugin_content', 'ajaxposttype', array('ajaxurl' => admin_url( 'admin-ajax.php' ),'query_vars' => json_encode( $wp_query->query )));
	wp_enqueue_script('jma_ajax_plugin_content');
}

function jma_ajax_content() {
	global $jma_spec_options;
    $ajax_post_id = $_POST['postid'];
	if(function_exists('jma_get_gallery_field'))
		$gallery_field = jma_get_gallery_field($ajax_post_id);
	echo get_the_title($ajax_post_id);
	echo 'jmasp';
	$featured = '';
	if ( has_post_thumbnail( $ajax_post_id ) ) {
		$featured .= '<div class="featured-item featured-image standard popout">';
        $featured .= get_the_post_thumbnail( $ajax_post_id, 'large' );
		$featured .= '</div>';
    }
	$content_post = get_post($ajax_post_id);
	$content = $content_post->post_content;
	$content = apply_filters('the_content', $content);
	if(function_exists('jma_nivo') && function_exists('get_field') && get_field($gallery_field, $ajax_post_id, false))
		$content .= jma_nivo(array('acf_field' => $gallery_field,'class'=> 'new', 'post_id' => $ajax_post_id));
	$content = str_replace(']]>', ']]&gt;', $content);
	$content = '<div class="entry-content clearfix">' . $content . '</div>';
	echo $featured . $content;
	echo 'jmasp';
	$use_filter = function_exists('use_full_image') && use_big_slider($ajax_post_id)? 'use_full_image': 'portfolio_header_image_size';
	add_filter('header_image_code_size', $use_filter, 10);
	$header_image_size_string = jma_get_header_image_size_string();
	echo get_header_image_code($jma_spec_options, $header_image_size_string, $ajax_post_id);
		
die();
}
add_action( 'wp_ajax_nopriv_jma_ajax_content', 'jma_ajax_content' );
add_action( 'wp_ajax_jma_ajax_content', 'jma_ajax_content' );

function portfolio_header_image_size(){
	$jma_spec_options = jma_get_theme_values();	
	return $jma_spec_options['port_image_size'];
}

function jma_ajax_plugin_template_redirect() {
	//themeblvd_remove_sidebar_location( 'sidebar_left' );
	if( 'portfolio_item' == get_post_type() || 'landing_page' == get_post_type()) {
		add_action( 'wp_enqueue_scripts', 'jma_ajax_scripts' );
		add_action('themeblvd_sidebar_sidebar_left_before', 'display_post_type_nav');		
		add_filter('header_image_code_size', 'portfolio_header_image_size', 10);
        add_action('wp_footer', 'jma_ajax_script_footer');
	}
}
add_action('template_redirect', 'jma_ajax_plugin_template_redirect');

function jma_ajax_port_image_size( $sizes ) {
	global $jma_spec_options;
	if($jma_spec_options['ajax_port_image_width'] && $jma_spec_options['ajax_port_image_height']){
	    // image size for header slider 
	    $sizes['ajax_port_image_size']['name'] = 'Ajax Header';
	    $sizes['ajax_port_image_size']['width'] = $jma_spec_options['ajax_port_image_width'];
	    $sizes['ajax_port_image_size']['height'] = $jma_spec_options['ajax_port_image_height'];
	    $sizes['ajax_port_image_size']['crop'] = true;
	}
    return $sizes;
}
add_filter( 'themeblvd_image_sizes', 'jma_ajax_port_image_size' );



function display_post_type_nav($post_id = 0){
	$post_type = 'portfolio_item';
	$taxes = array('portfolio', 'portfolio_tag');
	global $post;
	if(!$post_id && is_single())
		$post_id = $post->ID;
	ob_start();
    foreach ($taxes as $tax){
        if(count($taxes) > 1){
        $taxonomy = get_taxonomy($tax);
        echo '<h2>' . $taxonomy->labels->name . '</h2>';
        $terms= get_the_terms($post_id, $tax);
        if(is_array($terms))//create and array of ids for this post
            foreach($terms as $term){
                $this_post_term_ids[] = $term->term_id;
            }
        }
        $categories = get_terms( array( 'taxonomy' =>$tax, 'hierarchical' => false, 'orderby' => 'slug') );//echo '<pre>';print_r($categories);echo
        // '</pre>';
        echo '<div class="panel-group post_type-accordion tb-accordion" id="accordion-' . $tax . '">';
        foreach ($categories as $category) {
            $args = array(
                'post_type' => $post_type,
                'tax_query' => array(
                    array(
                        'taxonomy' => $tax,
                        'field' => 'id',
                        'terms' => $category->term_id
                    )
                ),
                //'orderby' => 'slug',
                //'order' => 'ASC',
                'posts_per_page' => -1

            );
            $x = new WP_Query($args);
            if($x->have_posts()){
                if($this_post_term_ids){
                    $in = in_array($category->term_id, $this_post_term_ids)? ' in': '';
                    $trigger = in_array($category->term_id, $this_post_term_ids)? ' active-trigger': '';
                    $sign = in_array($category->term_id, $this_post_term_ids)? 'minus': 'plus';
                }
                echo '<div class="tb-toggle panel panel-default">';// panel-default
                echo '<div class="panel-heading">';//panel-heading
                echo '<strong>';
                echo '<a class="post_type-cat ' . $category->slug . $trigger . '" data-toggle="collapse" data-parent="#accordion" href="#jmacollapse' . $category->term_id .  '">';
                echo '<i class="fa fa-' . $sign . '-circle fa-fw switch-me"></i>';
                echo $category->name;
                echo '</a>';
                echo '</strong>';
                echo '</div><!--panel-heading-->';
                echo '<ul id="jmacollapse' . $category->term_id .  '" class="panel-collapse collapse' . $in . '">';
                while ( $x->have_posts() ) : $x->the_post();
                        $current = get_the_id() == $post_id? ' current': '';
                        echo '<li data-postid="' . get_the_id() . '" class="post-type-link' . $current . '">';
                        echo '<a href="';
                        the_permalink();
                        echo '">';
                        the_title();
                        echo '</a>';
                        echo '</li>';
                endwhile;
                wp_reset_postdata();
                echo '</ul></div><!--panel-default-->';
            }
        }
        echo '</div><!--panel-group-->';
	}

	$x = ob_get_contents();
	ob_end_clean();
	echo $x;
}

function jma_ajax_script_footer(){ ?>

    <script type="text/javascript">
        jQuery(document).ready(function($){
            $('.panel-heading').find('a').click(function(){
                $(this).parents('.panel-group').siblings().find('.panel-default').each(function(){console.log('ddd');
                    $this = $(this);console.log($this);
                    $link = $this.find('a');
                    $link.removeClass('active-trigger');
                    $link.addClass('collapsed');
                    $link.attr('aria-expanded','false');
                    $icon = $this.find('i');
                    $icon.removeClass('fa-minus-circle');
                    $icon.addClass('fa-plus-circle');
                    $this.find('ul').removeClass('in');
                });
            });
        });
    </script>

<?php }


$ajax_options = array(
    array(    
    'name'      => __( 'Category Background Color', 'themeblvd' ),
    'desc'      => __( 'The color for the category sidebar background (leave blank to match footer bg)', 'themeblvd' ),
    'id'        => 'ajax_cat_bg',
    'std'       => '',
    'type'      => 'color'        
    ),
    array(    
    'name'      => __( 'Category Font Color', 'themeblvd' ),
    'desc'      => __( 'The color for the category sidebar font (leave blank to match footer font)', 'themeblvd' ),
    'id'        => 'ajax_cat_font',
    'std'       => '',
    'type'      => 'color'        
    ),
    array(    
    'name'      => __( 'Category Background Hover Color', 'themeblvd' ),
    'desc'      => __( 'The color for the category sidebar background on hover (leave blank to match footer font)', 'themeblvd' ),
    'id'        => 'ajax_cat_hover_bg',
    'std'       => '',
    'type'      => 'color'        
    ),
    array(    
    'name'      => __( 'Category Font Hover Color', 'themeblvd' ),
    'desc'      => __( 'The color for the category sidebar font on hover (leave blank to match footer bg)', 'themeblvd' ),
    'id'        => 'ajax_cat_hover_font',
    'std'       => '',
    'type'      => 'color'        
    ),
    array(    
    'name'      => __( 'Category Background Current Color', 'themeblvd' ),
    'desc'      => __( 'The color for the category sidebar background for open category (leave blank to match footer font)', 'themeblvd' ),
    'id'        => 'ajax_cat_current_bg',
    'std'       => '',
    'type'      => 'color'        
    ),
    array(    
    'name'      => __( 'Category Font Current Color', 'themeblvd' ),
    'desc'      => __( 'The color for the category sidebar font for open category (leave blank to match footer bg)', 'themeblvd' ),
    'id'        => 'ajax_cat_current_font',
    'std'       => '',
    'type'      => 'color'        
    ),	
    array(    
    'name'      => __( 'Item Background Color', 'themeblvd' ),
    'desc'      => __( 'The color for the item sidebar background (leave blank for transparent)', 'themeblvd' ),
    'id'        => 'ajax_item_bg',
    'std'       => '',
    'type'      => 'color'        
    ),
    array(    
    'name'      => __( 'Item Font Color', 'themeblvd' ),
    'desc'      => __( 'The color for the item sidebar font (leave blank default link color)', 'themeblvd' ),
    'id'        => 'ajax_item_font',
    'std'       => '',
    'type'      => 'color'        
    ),
    array(    
    'name'      => __( 'Item Background Hover Color', 'themeblvd' ),
    'desc'      => __( 'The color for the item sidebar background on hover (leave blank for #eeeeee)', 'themeblvd' ),
    'id'        => 'ajax_item_hover_bg',
    'std'       => '#eeeeee',
    'type'      => 'color'        
    ),
    array(    
    'name'      => __( 'Item Font Hover Color', 'themeblvd' ),
    'desc'      => __( 'The color for the item sidebar font on hover (leave blank for #333333)', 'themeblvd' ),
    'id'        => 'ajax_item_hover_font',
    'std'       => '#333333',
    'type'      => 'color'        
    ),
    array(    
    'name'      => __( 'Item Background Current Color', 'themeblvd' ),
    'desc'      => __( 'The color for the item sidebar background for open item (leave blank for #eeeeee)', 'themeblvd' ),
    'id'        => 'ajax_item_current_bg',
    'std'       => '#eeeeee',
    'type'      => 'color'        
    ),
    array(    
    'name'      => __( 'Item Font Current Color', 'themeblvd' ),
    'desc'      => __( 'The color for the item sidebar font for open item (leave blank for #333333)', 'themeblvd' ),
    'id'        => 'ajax_item_current_font',
    'std'       => '#333333',
    'type'      => 'color'        
    ),
    array(    
    'name'      => __( 'Portfolio Header Image Size', 'themeblvd' ),
    'desc'      => __( 'Size of the header image on portfolio item posts', 'themeblvd' ),
    'id'        => 'port_image_size',
    'std'       => 'jma-interior-header',
    'type'      => 'radio',
    'options'   => array(
        'jma-interior-header' => 'Interior Page Size',
        'jma-home-header' => 'Home Page Size',
        'ajax_port_image_size' => 'Custom'
		)             
    ),
    array(    
    'name'      => __( 'Portfolio Header Image Width', 'themeblvd' ),
    'desc'      => __( 'don\'t include px', 'themeblvd' ),
    'id'        => 'ajax_port_image_width',
    'std'       => '',
    'type'      => 'text'        
    ),
    array(    
    'name'      => __( 'Portfolio Header Image Height', 'themeblvd' ),
    'desc'      => __( 'don\'t include px', 'themeblvd' ),
    'id'        => 'ajax_port_image_height',
    'std'       => '',
    'type'      => 'text'        
    ),
);
function jma_add_ajax_options() {	
	global $ajax_options;
	if(function_exists('themeblvd_add_option')){
		themeblvd_add_option_section( 'jma_styles_page', 'jma_styles_ajax', 'Portfolio Sidebar Menu Options', 'Style options for sidebar accordian on Portfolio Item pages', $ajax_options );
	}
}
add_action('after_setup_theme', 'jma_add_ajax_options', 15);

function jma_ajax_filter( $dynamic_styles ) {
	$jma_spec_options = jma_get_theme_values();	
	
	$dynamic_styles['ajax'] =  array('.post_type-accordion.panel-group .panel',
			array('margin-top', ' 0'),
	);	
	$dynamic_styles['ajax05'] =  array('.post_type-accordion .panel-default, .post_type-accordion .panel',
			array('border', 'none'),
			array('background', 'none'),
	);	
	$dynamic_styles['ajax10'] =  array('.post_type-accordion .panel ul',
			array('padding-left', ' 0'),
			array('margin-bottom', ' 0'),
	);	
	
	$dynamic_styles['ajax20'] =  array('.post_type-accordion .panel ul li',
			array('list-style', 'none'),
			array('border-bottom', 'solid 1px transparent'),
	);		
	
	$dynamic_styles['ajax30'] =  array('.post_type-accordion .panel a',
			array('padding', '10px 20px 10px 10px'),
			array('display', 'block'),
	);	
	
	
	$color = $jma_spec_options['ajax_cat_font']? $jma_spec_options['ajax_cat_font']: $jma_spec_options['footer_font_color'];
	$bg = $jma_spec_options['ajax_cat_bg']? $jma_spec_options['ajax_cat_bg']: $jma_spec_options['footer_background_color'];
	$dynamic_styles['ajax40'] =  array('.post_type-accordion .panel .panel-heading a',
			array('color', $color),
			array('background', $bg),
	);	
	
	$color = $jma_spec_options['ajax_cat_hover_font']? $jma_spec_options['ajax_cat_hover_font']: $jma_spec_options['footer_background_color'];
	$bg = $jma_spec_options['ajax_cat_hover_bg']? $jma_spec_options['ajax_cat_hover_bg']: $jma_spec_options['footer_font_color'];
	$dynamic_styles['ajax50'] =  array('.post_type-accordion .panel .panel-heading a:hover',
			array('color', $color),
			array('background', $bg),
	);		
	
	$color = $jma_spec_options['ajax_cat_current_font']? $jma_spec_options['ajax_cat_current_font']: $jma_spec_options['footer_background_color'];
	$bg = $jma_spec_options['ajax_cat_current_bg']? $jma_spec_options['ajax_cat_current_bg']: $jma_spec_options['footer_font_color'];
	$dynamic_styles['ajax60'] =  array('.post_type-accordion .panel .panel-heading a.active-trigger',
			array('color', $color),
			array('background', $bg),
	);	
	
	
	if($jma_spec_options['ajax_item_bg'] || $jma_spec_options['ajax_item_font']){		
		$color = $jma_spec_options['ajax_item_font']? array('color', $jma_spec_options['ajax_item_font']): '';
		$bg = $jma_spec_options['ajax_item_bg']? array('background', $jma_spec_options['ajax_item_bg']): '';
		$dynamic_styles['ajax70'] =  array('.post_type-accordion .panel-collapse li a',
				$color,
				$bg,
		);	
	}

	$dynamic_styles['ajax80'] =  array('.post_type-accordion .panel-collapse li a:hover',
			array('color', $jma_spec_options['ajax_item_hover_font']),
			array('background', $jma_spec_options['ajax_item_hover_bg']),
	);		

	$dynamic_styles['ajax90'] =  array('.post_type-accordion .panel-collapse li.current a',
			array('color', $jma_spec_options['ajax_item_current_font']),
			array('background', $jma_spec_options['ajax_item_current_bg']),
	);

    $dynamic_styles['ajax100'] =  array('.working',
        array('background-image', 'url("' . plugins_url('/page-loader-sm.gif', __FILE__) . '")'),
        array('background-repeat', 'no-repeat'),
        array('background-position', 'center center'),
    );



    return $dynamic_styles;
}
add_filter( 'dynamic_styles_filter', 'jma_ajax_filter' );


function register_ajax_post_types() {

    $labels = array(
        'name' => _x( 'Landing Pages', 'themeblvd' ),
        'singular_name' => _x( 'Landing Page', 'themeblvd' ),
        'add_new' => _x( 'Add New', 'themeblvd' ),
        'add_new_item' => _x( 'Add New Landing Page', 'themeblvd' ),
        'edit_item' => _x( 'Edit Landing Page', 'themeblvd' ),
        'new_item' => _x( 'New Landing Page', 'themeblvd' ),
        'view_item' => _x( 'View Landing Page', 'themeblvd' ),
        'search_items' => _x( 'Search Landing Pages', 'themeblvd' ),
        'not_found' => _x( 'No Landing Pages found', 'themeblvd' ),
        'not_found_in_trash' => _x( 'No Landing Pages found in Trash', 'themeblvd' ),
        'parent_item_colon' => _x( 'Parent Landing Page:', 'themeblvd' ),
        'menu_name' => _x( 'Landing Pages', 'themeblvd' ),
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => false,
        'supports' => array( 'title', 'editor', 'custom-fields', 'thumbnail' ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => array( 'slug' => 'project-group'),
        'capability_type' => 'post'
    );

    register_post_type( 'landing_page', $args );

}

function add_ajax_portfolio_taxonomies() {
    register_taxonomy_for_object_type('portfolio', 'landing_page');
    register_taxonomy_for_object_type('portfolio_tag', 'landing_page');
}
function my_ajax_cpt_init() {
    register_ajax_post_types();
    add_ajax_portfolio_taxonomies();
}
add_action( 'init', 'my_ajax_cpt_init', 999 );

function jma_ajax_rewrite_flush() {
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'jma_ajax_rewrite_flush' );
