<?php
/**
 * Plugin Name: JKWS Regenerate Image Meta Data
 * Description: Fixes images that lost their meta data.
 * Version: 0.1
 * Author: Jos Klever
 * Author URI: https://joskleverwebsupport.nl
 * Credits: https://github.com/bomsn
 * Based on the snippet found on https://alikhallad.com/how-to-restore-regenerate-missing-attachment-metadata-in-wordpress/
 *
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */
 
 /**
 * Usage:
 * Load your website with the parameter ?regenerate_attach_meta=1
 * ex: example.com/?regenerate_attach_meta=1
 */

add_action('wp_head', 'jkws_regenerate_attach_meta');
function jkws_regenerate_attach_meta()
{
    // Check whether the query parameter `regenerate_attach_meta` exists in the URL
    if (!isset($_GET['regenerate_attach_meta'])) {
        return;
    }

    // Run our code if the query parameter `regenerate_attach_meta` was found


    $attachments = get_posts(
        array(
            'post_type' => 'attachment',
            'posts_per_page' => 100,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_wp_attachment_metadata',
                    'compare' => 'NOT EXISTS'
                ),
            ),
        ),
    );

    if (!empty($attachments)) {

        // Ensure `wp_crop_image` is loaded
        if (!function_exists('wp_crop_image')) {
            include(ABSPATH . 'wp-admin/includes/image.php');
        }
        // Ensure `wp_read_video_metadata` is loaded
        if (!function_exists('wp_read_video_metadata')) {
            include(ABSPATH . 'wp-admin/includes/media.php');
        }

        // Loop through all the attachments with missing metadata
        $i = 0;
        foreach ($attachments as $attach_id) {
            // Check if the attachment has a file meta attached to it
            $file = get_attached_file($attach_id);
            // If no files was found, generate one and attach it
            if (empty($file)) {
                $attach_obj = get_post($attach_id);
                $filename = str_replace(site_url('/wp-content/uploads/', 'https'), '', $attach_obj->guid);
                $filename = str_replace(site_url('/wp-content/uploads/', 'http'), '', $filename);
                $filename = str_replace(site_url('/', 'https'), '', $filename);
                $filename = str_replace(site_url('/', 'http'), '', $filename);

                update_attached_file($attach_id, $filename);
                $file = get_attached_file($attach_id);
            }

            // Now generate the metadata and update it for the current attachment
            if ($file) {
                $attach_data = wp_generate_attachment_metadata($attach_id, $file);
                wp_update_attachment_metadata($attach_id, $attach_data);
            }

            // Save count
            $i++;
        }

        die($i . ' attachment(s) metadata restored');
    }

    // Show a custom message if all attachments have their metadata in the `wp_postmeta` table
    die('No missing metadata');
}
