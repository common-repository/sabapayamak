<?php

namespace SabaPayamak;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;


function settings_section_callback(){
    // echo "تنظیمات اصلی صباپیامک";
}

function textbox_callback_function($args)
{
    $options = get_option('sabapayamak_options', Helpers::sabapayamak_default_options());

    $id    = isset($args['id'])    ? $args['id']    : '';
    $label = isset($args['label']) ? $args['label'] : '';
    $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';

    $type = isset($args['type']) ? $args['type'] : 'text';

    $value = isset($options[$id]) ? sanitize_text_field($options[$id]) : '';

    if (isset($args['numbers_only']) && $args['numbers_only'] == true) {
        $numbers_only = ' class="only-numbers-allowed"';
    }
    else{
        $numbers_only = '';
    }
    echo "<p>";
    echo '<input style="width: 100% !important;" id="sabapayamak_options_' . esc_attr($id) . '"' . esc_attr($numbers_only) . ' name="sabapayamak_options[' . esc_attr($id) . ']" type="' . esc_attr($type) . '" size="40" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '"><br />';
    echo '<label for="sabapayamak_options_' . esc_attr($id) . '">' . esc_html($label) . '</label>';
    echo "</p>";
}

function radio_callback_function($args)
{
    $options = get_option('sabapayamak_options', Helpers::sabapayamak_default_options());

    $id    = isset($args['id'])    ? $args['id']    : '';
    $option_label = isset($args['label']) ? $args['label'] : '';

    $selected_option = isset($options[$id]) ? sanitize_text_field($options[$id]) : '';

    if ($args['id'] === 'ks_send_method') {
        $radio_options = array(
    
            'GET'  => 'ارسال از طریق متد GET',
            'POST' => 'ارسال از طریق متد POST'
        );
    }
    elseif ($args['id'] === 'ks_api_method') {
        $radio_options = array(
    
            'web_service' => 'اتصال از طریق Web service',
            'API'  => 'اتصال از طریق API'
        );
    }

    echo "<p>";
    foreach ($radio_options as $value => $label) {

        $checked = checked($selected_option === $value, true, false);

        echo '<label><input name="sabapayamak_options[' . esc_attr($id) . ']" type="radio" value="' . esc_attr($value) . '"' . esc_attr($checked) . '> ';
        echo '<span>' . esc_html($label) . '</span></label><br />';
    }
    echo '<label for="sabapayamak_options_' . esc_attr($id) . '">' . esc_attr($option_label) . '</label>';
    echo "</p>";
}

function textarea_callback_function($args)
{
    $options = get_option('sabapayamak_options', Helpers::sabapayamak_default_options());

    $id    = isset($args['id'])    ? $args['id']    : '';
    $label = isset($args['label']) ? $args['label'] : '';
    
    $allowed_tags = wp_kses_allowed_html( 'post' );
    
    $value = isset( $options[$id] ) ? wp_kses( stripslashes_deep( $options[$id] ), $allowed_tags ) : '';

    echo '<textarea id="sabapayamak_options_' . esc_attr($id) . '" name="sabapayamak_options[' . esc_attr($id) . ']" rows="5" cols="50">' . esc_html($value) . '</textarea><br />';
}


function checkbox_callback_function($args)
{
    $options = get_option('sabapayamak_options', Helpers::sabapayamak_default_options());

    $id    = isset($args['id'])    ? $args['id']    : '';
    $label = isset($args['label']) ? $args['label'] : '';

    $checked = isset($options[$id]) ? checked($options[$id], 1, false) : '';

    echo '<input id="sabapayamak_options_' . esc_attr($id) . '" name="sabapayamak_options[' . esc_attr($id) . ']" type="checkbox" value="1"' . esc_attr($checked) . '> ';
    echo '<label for="sabapayamak_options_' . esc_attr($id) . '">' . esc_html($label) . '</label>';
}

function select_callback_function( $args ) {
    
    $options = get_option('sabapayamak_options', Helpers::sabapayamak_default_options());
    
    $id    = isset( $args['id'] )    ? $args['id']    : '';
    $label = isset( $args['label'] ) ? $args['label'] : '';
    
    $selected_option = isset( $options[$id] ) ? sanitize_text_field( $options[$id] ) : '';
    
    $select_options = array(
        'light'     => 'روشن',
        'blue'      => 'آبی',
        'dark'      => 'تیره'
    );
    
    echo '<select id="sabapayamak_options_'. esc_attr($id) .'" name="sabapayamak_options['. esc_attr($id) .']">';
    
    foreach ( $select_options as $value => $option ) {
        
        $selected = selected( $selected_option === $value, true, false );
        
        echo '<option value="'. esc_attr($value) .'"'. esc_attr($selected) .'>'. esc_attr($option) .'</option>';
        
    }
    
}