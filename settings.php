<?php
/**
 * Settings page for AI Summary Plugin
 */

echo '<h1>AI Summary Settings</h1>';

echo '<form method="post" action="options.php">
    settings_fields('ai-sum-settings');
    do_settings_sections('ai-sum-settings');
    
    echo '<label for="base_url">Base URL:</label>';
    echo '<input type="text" id="base_url" name="base_url" value="' . esc_attr(get_option('base_url')) . '" />';
    
    echo '<label for="api_key">API Key:</label>';
    echo '<input type="text" id="api_key" name="api_key" value="' . esc_attr(get_option('api_key')) . '" />';
    
    echo '<label for="model">Model:</label>';
    echo '<input type="text" id="model" name="model" value="' . esc_attr(get_option('model')) . '" />';
    
    submit_button();

echo '</form>';
?>
