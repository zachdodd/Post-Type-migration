<?php

/*
Plugin Name: Migrate news Post Type
Description: Migrate posts from 'news' to 'Posts' inside specific categories based on 'news_pr_types' taxonomy.
Version: 1.0
Author: Zach
*/

function migrate_cpt_add_bulk_action($bulk_actions) {
    $bulk_actions['migrate_cpt'] = __('Migrate to Posts', 'textdomain');
    return $bulk_actions;
}
add_filter('bulk_actions-edit-news', 'migrate_cpt_add_bulk_action');

function migrate_cpt_handle_bulk_action($redirect_to, $doaction, $post_ids) {
    if ($doaction !== 'migrate_cpt') {
        return $redirect_to;
    }

    foreach ($post_ids as $post_id) {
        // Get current terms from 'news_pr_types' taxonomy
        $current_terms = wp_get_post_terms($post_id, 'news_pr_types', array('fields' => 'slugs'));

        // Determine the new category based on 'news_pr_types' terms
        $new_category_slug = '';
        if (in_array('article', $current_terms)) {
            $new_category_slug = 'news';
        } elseif (in_array('news', $current_terms)) {
            $new_category_slug = 'news';
        } elseif (in_array('press-release', $current_terms)) {
            $new_category_slug = 'press-release';
        } else {
            $new_category_slug = 'news';
        }

        // Get the ID of the new category
        $new_category_id = get_cat_ID($new_category_slug);

        // Update the post type and set the new category
        $post = get_post($post_id);
        $post->post_type = 'post';
        wp_update_post($post);
        if ($new_category_id) {
            wp_set_post_categories($post_id, array($new_category_id));
        }
    }

    $redirect_to = add_query_arg('migrated', count($post_ids), $redirect_to);
    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-news', 'migrate_cpt_handle_bulk_action', 10, 3);

function migrate_cpt_admin_notice() {
    if (!empty($_REQUEST['migrated'])) {
        $migrated_count = intval($_REQUEST['migrated']);
        printf('<div id="message" class="updated fade">' . _n('%s post migrated.', '%s posts migrated.', $migrated_count, 'textdomain') . '</div>', $migrated_count);
    }
}
add_action('admin_notices', 'migrate_cpt_admin_notice');
?>