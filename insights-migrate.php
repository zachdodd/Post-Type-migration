<?php

/*
Plugin Name: Migrate Custom Post Types
Description: Migrate posts from 'insights-old' to 'Posts' inside 'insights' category.
Version: 1.0
Author: Zach
*/

function migrate_cpt_add_bulk_action($bulk_actions) {
    $bulk_actions['migrate_cpt'] = __('Migrate to Posts', 'textdomain');
    return $bulk_actions;
}
add_filter('bulk_actions-edit-insights-old', 'migrate_cpt_add_bulk_action');

function migrate_cpt_handle_bulk_action($redirect_to, $doaction, $post_ids) {
    if ($doaction !== 'migrate_cpt') {
        return $redirect_to;
    }

    foreach ($post_ids as $post_id) {
        $post = get_post($post_id);
        $post->post_type = 'post';
        wp_update_post($post);

        wp_set_post_categories($post_id, array(get_cat_ID('insights')));
        
        $taxonomies = get_object_taxonomies($post);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
            wp_set_object_terms($post_id, $terms, $taxonomy);
        }
    }

    $redirect_to = add_query_arg('migrated', count($post_ids), $redirect_to);
    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-insights-old', 'migrate_cpt_handle_bulk_action', 10, 3);

function migrate_cpt_admin_notice() {
    if (!empty($_REQUEST['migrated'])) {
        $migrated_count = intval($_REQUEST['migrated']);
        printf('<div id="message" class="updated fade">' . _n('%s post migrated.', '%s posts migrated.', $migrated_count, 'textdomain') . '</div>', $migrated_count);
    }
}
add_action('admin_notices', 'migrate_cpt_admin_notice');
?>