<?php


function cutv_add_channel()
{

    // The $_REQUEST contains all the data sent via ajax
    if (isset($_REQUEST)) {
        global $wpdb;

        $cat_id = wp_insert_category(
            array(
                'cat_name' => $_REQUEST['channelName'],
                'category_description' => '',
                'category_nicename' => $_REQUEST['slug'],
                'category_parent' => ''
            )
        );

        // Set Channel Status
        update_term_meta($cat_id, 'cutv_channel_enabled', $_REQUEST['enabled']);


        $playlists = $wpdb->get_results( 'SELECT * FROM ' . SNAPTUBE_PLAYLISTS );

        $query = $wpdb->prepare("INSERT INTO " . SNAPTUBE_PLAYLISTS . " (pid, playlist_name, playlist_slugname, playlist_desc, is_publish, playlist_order) VALUES ( %d, %s, %s, %s, %d, %d )",
            array($cat_id, $_REQUEST['channelName'], $_REQUEST['slug'], '', 1, count($playlists))
        );
        $wpdb->query($query);

    }

    // Always die in functions echoing ajax content
    die();
}
add_action('wp_ajax_cutv_add_channel', 'cutv_add_channel');

function cutv_remove_channel()
{

    // The $_REQUEST contains all the data sent via ajax
    if (isset($_REQUEST)) {
        global $wpdb;

        $cat_id =  $_REQUEST['id'];

        wp_delete_category($cat_id);
        $wpdb->delete( $wpdb->termmeta, array( 'term_id' => $cat_id ) );
        $wpdb->delete( SNAPTUBE_PLAYLISTS, array( 'pid' => $cat_id ) );
        $wpdb->delete( SNAPTUBE_PLAYLIST_RELATIONS, array( 'playlist_id' => $cat_id ) );

        header("HTTP/1.1 200 Ok");

    }

    // Always die in functions echoing ajax content
    die();
}
add_action('wp_ajax_cutv_remove_channel', 'cutv_remove_channel');


function cutv_update_channel() {

    // The $_REQUEST contains all the data sent via ajax
    if (isset($_REQUEST)) {
        global $wpdb;

        $channel_id = $_REQUEST['channel'];

        if (isset($_REQUEST['enabled'])) {
            $enabled = $_REQUEST['enabled'];
            update_term_meta($channel_id, 'cutv_channel_enabled', $enabled);
        }

        // update channel image
        if (isset($_REQUEST['image']) && $_REQUEST['image'] !== '') {
            update_term_meta($channel_id, 'cutv_channel_img', $_REQUEST['image']);
        }

        // update channel term & meta
        if (isset($_REQUEST['name'])) {
            $channel_name = $_REQUEST['name'];
            $channel_slug = sanitize_title_with_dashes($channel_name);

            // update snaptube playlist name & slugname
            $wpdb->update(
                SNAPTUBE_PLAYLISTS,
                array(
                    'playlist_name' => $channel_name,	// string
                    'playlist_slugname' => $channel_slug	// integer (number)
                ),
                array( 'pid' => $channel_id ),
                array(
                    '%s',	// value1
                    '%s',	// value1
                ),
                array( '%d' )
            );
            // update term name & slug
            $wpdb->update(
                $wpdb->terms,
                array(
                    'name' => $channel_name,	// string
                    'slug' => $channel_slug	// integer (number)
                ),
                array( 'term_id' => $channel_id ),
                array(
                    '%s',	// value1
                    '%s',	// value1
                ),
                array( '%d' )
            );
        }


        $channel = cutv_get_channel($channel_id);

        header('Content-Type: application/json');
        echo json_encode($channel);
    }

    // Always die in functions echoing ajax content
    die();

}
add_action('wp_ajax_cutv_update_channel', 'cutv_update_channel');


function cutv_get_channel($channel_id) {

    global $wpdb;
    $channel = $wpdb->get_row("SELECT * FROM " . SNAPTUBE_PLAYLISTS ." WHERE pid = $channel_id" );
    $channel->cutv_channel_img = get_term_meta( $channel_id, 'cutv_channel_img', true );
    $channel->enabled = get_term_meta( $channel_id, 'cutv_channel_enabled', true );

    return $channel;
}
function cutv_get_channels() {
    global $wpdb;
    $channels_rows = $wpdb->get_results("SELECT * FROM " . SNAPTUBE_PLAYLISTS ." WHERE pid > 1" );

    $channels = [];
    foreach ($channels_rows as $channel) {
        $channels[] = cutv_get_channel($channel->pid);
    };


    if (isset($_REQUEST) && isset($_REQUEST['json'])) {
//        header('Content-Type: application/json');
        echo json_encode($channels);
    } else {
        return $channels;
    }

    die();

}
add_action('wp_ajax_nopriv_cutv_get_channels', 'cutv_get_channels');
add_action('wp_ajax_cutv_get_channels', 'cutv_get_channels');


function cutv_get_snaptube_post_data($video_post, $wpvr_id) {
    $video_post->snaptube_vid = intval(cutv_get_snaptube_vid($wpvr_id));
    $video_post->snaptube_id = intval(cutv_get_snaptube_post_id($wpvr_id));
    $video_post->source_id = intval(get_post_meta( $wpvr_id, 'wpvr_video_sourceId', true ));
    // $video_post->snaptube_link_id = get_post_meta($wpvr_id, '_cutv_snaptube_video', true);
    $video_post->youtube_thumbnail = get_post_meta($wpvr_id, 'wpvr_video_service_thumb', true );
    $video_post->video_duration = convert_youtube_duration(get_post_meta($wpvr_id, 'wpvr_video_duration', true));

    if ($video_post->snaptube_vid == ! null) {
        $video_post->post_status = 'publish';
    }
    return $video_post;
}


function get_the_catalog_cat( $id = false ) {
    $categories = get_the_terms( $id, 'catablog-terms' );
    if ( ! $categories || is_wp_error( $categories ) )
        $categories = array();

    $categories = array_values( $categories );

    foreach ( array_keys( $categories ) as $key ) {
        _make_cat_compat( $categories[$key] );
    }

    /**
     * Filters the array of categories to return for a post.
     *
     * @since 3.1.0
     * @since 4.4.0 Added `$id` parameter.
     *
     * @param array $categories An array of categories to return for the post.
     * @param int   $id         ID of the post.
     */
    return apply_filters( 'get_the_categories', $categories, $id );
}
