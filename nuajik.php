<?php

/*
Plugin Name: nuajik CDN
Author: nuajik
Description: Speed your Wordpress with nuajik CDN
Author: nuajik.io
Version: 0.1.0
Author URI: https://www.nuajik.io
Domain Path: /languages
Text Domain: nuajik
*/

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/inc/nuajik-url-rewriter-hook.class.php';


defined('ABSPATH') OR exit; // Prevent direct access to this file
global $NUAJIK_LOGO_URL;
// Setting Registration

add_action('admin_init', 'nuajik_initialize_theme_options');
function nuajik_initialize_theme_options() {
 
    // First, we register a section. This is necessary since all future options must belong to one. 
    add_settings_section(
        'general_settings_section',         // ID used to identify this section
        '<a href="https://www.nuajik.io/">
        <img src="'. plugin_dir_url( __FILE__ ) .'statics/img/mini-logo.png"
        alt="nuajik logo"/></a>',          // Title to be displayed on the page
        'nuajik_general_options_callback', // Callback used to rendersection description
        'nuajik'                           // Page ID were Wordpress will add this section
    );
     
    // Next, we will introduce the fields for toggling the visibility of content elements.
    add_settings_field( 
        'nuajik_api_key',                   // ID
        'Set your API Key:</br>Then press [Enter] to show associated slice',                          // Label for field
        'nuajik_set_api_key_callback',      // Callback which interface
        'nuajik',                          // The name of the plugin page
        'general_settings_section',         // The name of the section who contain which field
        array(                             // This array will be passed to callback function, contain data for display the field
            'name' =>  'nuajik_api_key',
            'placeholder' => "ex:  514f0d1b105b2f7d1a2b385e6aee1cb336dc2830",
            'description' => 'See your token on <a href="https://admin.nuajik.io/account/profile/">nuajik admin panel</a>',
        )
    );
     
    add_settings_field( 
        'nuajik_slice',                     
        'Slice',              
        'nuajik_choose_slice_callback',  
        'nuajik',                          
        'general_settings_section',         
        array(
            'name' => 'nuajik_slice',
            'description' => 'Choose slice you want to use',
        )
    );

    add_settings_field( 
        'nuajik_activate_service',                     
        'Enable nuajik CDN',              
        'nuajik_activate_service_callback',  
        'nuajik',                          
        'general_settings_section',         
        array(
            'name' => 'nuajik_activate_service',
            'description' => '',
        )
    );

     
    // Finally, we register the fields with WordPress
    register_setting(
        'nuajik',
        'nuajik_api_key'
    );

    register_setting(
        'nuajik',
        'nuajik_slice'
    );

    register_setting(
        'nuajik',
        'nuajik_activate_service'
    );

}


// Section Callbacks
 
function nuajik_general_options_callback() {
    echo '<p>Just input your API v2 key, choose a Slice and start using the nuajik CDN</p>';
}

// Field Callbacks

function nuajik_set_api_key_callback($args) {

    $placeholder = empty(get_option($args['name'])) ? ' placeholder="' . $args['placeholder'] . '"' : "" ;

    $html        = sprintf( '<input type="text" id="api_key_field" name="%1$s" value="%2$s" %3$s/>', $args['name'], get_option($args['name']), $placeholder );
    $html       .= '</br><i>'.$args['description'].'</i>';
    echo $html;
     
}
 
function nuajik_choose_slice_callback($args) {
    $nuajik_api_key = get_option('nuajik_api_key');
    if (empty($nuajik_api_key)) {
        $html = "Please enter your API key to see available slices";
    }
    else{
        try {
            $api_wrapper = new NuajikApi(get_option('nuajik_api_key'));
            $slices = $api_wrapper->get_slice_list();
            $current_value = esc_attr(get_option($args['name']));
            $html = sprintf('<select name="%1$s" id="%1$s">', $args['name']);
            $html .= '<p>Current Value'.$current_value.'</p>';
            foreach ( $slices as $slice ) {

		$slice_status = $slice->state!="active" ? ['disabled', ' (not active)'] : ['', ''] ;
                if ($slice->origin != get_site_url()) $slice_status = ['disabled', ''];

                $slice_status[0] = $slice->origin != parse_url(get_site_url())['host'] ?  'disabled': '';


		$html .= sprintf('<option value="%1$s" %2$s %3$s >%4$s%5$s</option>', $slice->public_domain, selected($current_value, $slice->public_domain, false), $slice_status[0], $slice->origin,  $slice_status[1] );
            }
            $html .= sprintf( '</select>' );
            $html .='</br><i>'.$args['description'].'</i>';
        
        } catch (Exception $e) {
            $html ='</br><strong>'.$e->getMessage().'</strong>';
        }

    }
    echo $html;
} 
 

function nuajik_activate_service_callback( $args ) {
    if (empty(get_option('nuajik_api_key')) ) {
        $html = "An API key and an active Slice are necessary to activate the service ";
    }
    else{
        try {
            $actual_value = esc_attr( get_option( $args['name']) );
            $html  = '<fieldset>';
            $html  .= sprintf( '<label for="nuajik-%1$s">', $args['name'] );
            $html  .= sprintf( '<input type="checkbox" class="checkbox" id="nuajik-%1$s" name="%1$s" value="1" %2$s/>', $args['name'], checked(1, $actual_value, 0));
            $html  .= '</fieldset>';
        } catch (Exception $e) { 
            $html ='</br><strong>'.$e->getMessage().'</strong>';
        }
}
    echo $html;
}

// Record Wordpress menu entrie and set Global page
 
add_action('admin_menu', 'nuajik_add_menu');
function nuajik_add_menu() {
    add_menu_page(
        'nuajik',
        'nuajik settings',
        'manage_options',
        'nuajik',
        'nuajik_options_page_callback',
        'dashicons-performance');
}


function nuajik_options_page_callback()
{
    // check user capabilities
    if (!current_user_can('manage_options')) {
    return;
    }

    // Cache purge execution
    if (isset($_POST['purge_cache']) and $_POST['purge_cache'] =="true"){
        try {
            $api_wrapper = new NuajikApi(get_option('nuajik_api_key'));
            $slice_id = explode(".", get_option('nuajik_slice'));
            $slices = $api_wrapper->slice_purge($slice_id[0]);
            add_settings_error('nuajik_messages', 'nuajik_message', __('Cache have been purged', 'nuajik'), 'updated');
            update_option("nuajik_purge_cache", "off");
        
        } catch (Exception $e) {
            add_settings_error('nuajik_messages', 'nuajik_message', __($e->getMessage(), 'nuajik'), 'updated');
        }
    }

    // add error/update messages
    if (isset($_GET['settings-updated'])) {
        // add settings saved message with the class of "updated"
        add_settings_error('nuajik_messages', 'nuajik_message', __('Settings saved', 'nuajik'), 'updated');
    }

    // show error/update messages
    settings_errors('nuajik_messages');
    ?>

 <div class="wrap">
 <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
 <form id="nuajik-main-form" action="options.php" method="post">
 <?php
    settings_fields('nuajik');
    do_settings_sections('nuajik');
    submit_button('Save');
    ?>
 </form>
 <?php if (get_option('nuajik_slice')!='' and get_option('nuajik_api_key')!='' and get_option('nuajik_activate_service')=='1'){
    $html  = '<form method="post" name="nuajik-purge-cache">
    <input type="hidden" name="purge_cache" value="true">
    <button class="button button-primary" type="submit" >Purge cache <span style="line-height: 1.3;" class="dashicons dashicons-image-rotate"></span></button></form>';
echo $html;
    }
?>
 </div>
 <?php
}

if ( get_option("nuajik_activate_service")== '1' and get_option('nuajik_api_key') ){
	$url_rewriter = new nuajik_url_rewrite_hook(get_home_url(), get_option('nuajik_slice') );
    $url_rewriter->add_hooks();
}


?>
