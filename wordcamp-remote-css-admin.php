<?php
namespace WordCamp\RemoteCSS\Admin;

function process(){
    $user_intent = !empty($_POST['save_remote_url'])? $_POST['save_remote_url']:'';
    if($user_intent){
        $url = !empty($_POST['remote_url'])? $_POST['remote_url']:'';
        if(!empty($url) && 1 == $user_intent){
            update_option( '_wordcamp-remote-css-url', $url );
        }
        \WordCamp\RemoteCSS\Engine\save_remote_css();
        return $user_intent;
    }
    if($_POST['regenerate_webhook']){
        $webhook = \WordCamp\RemoteCSS\Webhook\get_webhook_url(true);
        return $webhook;
    }
    return false;
}

function ui(){
    ?>
<h2>Remote CSS</h2>
    <?php
}