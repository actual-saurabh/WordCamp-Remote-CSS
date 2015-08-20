<?php

namespace WordCamp\RemoteCSS\Webhook;

function get_webhook_id($regenerate=false){
    
    $webhook_id = get_option( '_wordcamp-remote-css-webhook-id', false);
    if(!$webhook_id || $regenerate===true){
        $webhook_id = wp_generate_password(12);
        update_option( '_wordcamp-remote-css-webhook-id',$webhook_id);
    }
    return $webhook_id;
}

function get_webhook_url($regenerate){
    $webhook = get_option('admin-url'). 'admin-ajax.php?action=remote_css_listener&remote-css-update=' . get_webhook_id($regenerate);
}

function webhook_listen(){
    if($_GET['action'] !== 'remote_css_listener'){
        return;
    }
    $hook_id = !empty($_GET['remote-css-update'])? $_GET['remote-css-update']:'';
    if(empty($hook_id)){
        return;
    }
    if( get_option( '_wordcamp-remote-css-webhook-id', false) != $hook_id){
        return;
    }
    
    \WordCamp\RemoteCSS\Engine\save_remote_css();
    
}

add_action('wp_ajax_remote_css_listener', __NAMESPACE__.'\webhook_listen');
add_action('wp_ajax_nopriv_remote_css_listener', __NAMESPACE__.'\webhook_listen');