<?php

/**
 * Class MallCockpitDeviceWizard
 */
class MallCockpitDeviceWizard
{
    /**
     * @return void
     */
    public function init()
    {
        add_action('admin_bar_menu', [$this, 'addLink'], 100);
        add_action('admin_enqueue_scripts', function() {
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('e2b-admin-ui-css','https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css',false,"1.9.0",false);
            wp_enqueue_media();
            wp_enqueue_script('playlist-wizard', plugin_dir_url(MC_DEVICE_PLUGIN_DIR) . '/assets/js/admin-playlist-wizard.js', ['jquery']);
            wp_enqueue_style('playlist-wizard', plugin_dir_url(MC_DEVICE_PLUGIN_DIR) . '/assets/css/admin-playlist-wizard.css');

            wp_localize_script( 'playlist-wizard', 'playlist_wizard_ajax', array(
                    'ajaxurl' => admin_url( 'admin-ajax.php' )
                )
            );
        });

        add_action( 'wp_ajax_nopriv_playlistwizard', [$this, 'save']);
        add_action( 'wp_ajax_playlistwizard', [$this, 'save']);

        add_action( 'wp_ajax_nopriv_categories_list', [$this, 'categoriesList']);
        add_action( 'wp_ajax_categories_list', [$this, 'categoriesList']);

        add_action( 'wp_ajax_update_row_repeater', [$this,'update_row_repeater'] );

        function serversidefunction() {
            $responseData = array("voll cooler AJAX Kram!!!");
            echo json_encode($responseData);
        }
		
        add_action('admin_print_scripts', function() {
			
            $centerItems = get_posts([
                'post_type' => 'center',
                'posts_per_page' => -1,
				'post__in' => get_user_restricted_shops(),
				
            ]);
            $json = [];
            foreach ($centerItems as $centerItem) {
                $json[] = ['id' => $centerItem->ID, 'name' => get_field('center_name', $centerItem->ID)];
            }
            sort($json);
            ?>
            <script>window.centerItems = <?= json_encode($json); ?>;</script>
            <?php
        });
    }

    function update_row_repeater() {
        if ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $post_id = (int)$_POST['post_id'];
            $row = (int)$_POST['row_id'];
            $field_key = $_POST['repeater_id'];
            $url_img = $_POST['url_img'];

            $field_order = 'order_nr';
            $field_startdate = 'startdate';
            $field_enddate = 'enddate';
            $field_client = 'kunden';
            $field_file = 'file';


            $order_nr = (int)$_POST['playlist_order_number'];

            $startDateCampaign = $_POST['start_date'];
		    $endDateCampaign = $_POST['end_date'];
		    $clients = $_POST['client'];

            if(!empty($clients) && is_array($clients)) {
                $term_client_id = [];

                foreach ($clients as $client) :
                    $term = get_term_by('name', $client, 'kunden');
                    $term_client_id[] = $term->term_id;
                endforeach;
                update_sub_field( array($field_key, $row, $field_client), $term_client_id, $post_id);
            }

            if(!empty($order_nr)) {
                update_sub_field( array($field_key, $row, $field_order), $order_nr, $post_id);
            }
            if(!empty($url_img)) {
                $attachment_id = attachment_url_to_postid($url_img);
                if($attachment_id) {
                    update_sub_field( array($field_key, $row, $field_file), $attachment_id, $post_id);
                }
            }

            if( $startDateCampaign ) {
                $sub_id = 1;
                foreach( $startDateCampaign as $rowDate ) {
                    update_sub_field( array($field_key, 1, 'period', $sub_id, $field_startdate), $rowDate, $post_id);
                    $sub_id++;
                }
            }

            if( $endDateCampaign ) {
                $sub_id = 1;
                foreach( $endDateCampaign as $rowDate ) {
                    // $endDateCampaign = $row['period']['enddate'];
                    update_sub_field( array($field_key, 1, 'period', $sub_id, $field_enddate), $rowDate, $post_id);
                    $sub_id++;
                }
            }



            // update_sub_field( array($field_key, 1, $field), $new_value, $post_id);

            // var_dump($_POST['start_date'], $startDateCampaign, $endDateCampaign, $post_id, 'test');
        }
        $respons = 'Data updated';
        echo JSON_encode($respons);
        wp_die();
    }

    function categoriesList()
    {
        $terms = get_terms([
            'taxonomy'   => 'kampagnen',
            'hide_empty' => false,
        ]);

        $response = [];

        foreach ($terms as $term) {
            array_push($response, [
                'id'    => $term->term_id,
                'name'  => $term->name,
            ]);
        }

        wp_send_json_success($response);
    }

    function save()
    {
        $playlistIds = [];
        $fileType = get_post_mime_type((int) $_POST['file']);
        $fileType = ($fileType == 'video/mp4' || $fileType == 'video/webm' ? 'video' : 'bild');


        $term_kamp_name = term_exists($_POST['kamp_name'], 'kampagnen');


        if(!$term_kamp_name) {
            
            $kamp_name = $_POST['kamp_name'];
            $kamp_array = wp_insert_term( $kamp_name, 'kampagnen', array(
                'description' => '',
                'parent'      => 0,
                'slug'        => '',
            ) );

            $kamp_id = $kamp_array['term_id'];

        } else {

            $kamp_id = $term_kamp_name;

        }

        if(is_array($kamp_id)) {
            $kamp_id = $kamp_id['term_id'];
        }

        

        $term_kunden_name = term_exists($_POST['kunden'], 'kunden');

        if(!$term_kunden_name) {
            $kunden_name = $_POST['kunden'];
            $kunde_array = wp_insert_term( $kunden_name, 'kunden', array(
                'description' => '',
                'parent'      => 0,
                'slug'        => '',
            ) );

            $kunde_id = $kunde_array['term_id'];
        } else {
            $kunde_id = $term_kunden_name;
        }

        if(is_array($kunde_id)) {
            $kunde_id = $kunde_id['term_id'];
        }




        $value = [
            'acf_fc_layout' => 'list_item_' . $fileType,
            'file' => (int) $_POST['file'],
            'advertiser_id' => (int) $_POST['advertiser'],
            'duration' => !empty($_POST['length']) ? (int) $_POST['length'] : null,
            'period' => [],

            'kamp_name' => $kamp_id,
            'kunden' => $kunde_id,
            'order_nr' => $_POST['order_nr']
        ];


        $rows = count($_POST['date-start']);
        for ($row = 0; $row < $rows; $row++) {
            $value['period'][$row] = [
                'startdate' => date('Y-m-d', strtotime($_POST['date-start'][$row])),
                'enddate' => date('Y-m-d', strtotime($_POST['date-end'][$row])),
                'repeats_per_hour' => !empty($_POST['repeats'][$row]) ? (int) $_POST['repeats'][$row] : null,
                'daytimes' => []
            ];

            $subRows = count($_POST['time-from'][$row]);
            for ($subRow = 0; $subRow < $subRows; $subRow++) {
                $value['period'][$row]['daytimes'][] = [
                    'starthour' => $_POST['time-from'][$row][$subRow],
                    'endhour' => $_POST['time-to'][$row][$subRow],
                    'weekdays' => !empty($_POST['time-weekdays'][$row][$subRow]) ? $_POST['time-weekdays'][$row][$subRow] : []
                ];
            }
        }

        $output = '';
        $format = $_POST['format'];
        foreach ($_POST['center'] as $center) {
            if ($output != '') {
                $output .= '<br />';
            }
            $centerId = (int) $center;

            $output .= '<strong>Center: '.get_field('center_name', $centerId).'</strong><ul>';
            $devices = get_posts([
                'post_type' => 'devices',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'device_center',
                        'value' => $centerId
                    ],
                    [
                        'key' => 'device_dimension',
                        'value' => $format
                    ]
                ]
            ]);

            $playlistId = '';
            foreach ($devices as $device) {
                $playlistIdT = get_field('device_playlist', $device->ID);
                $playlistIdTl = get_the_title($playlistIdT);
                if ($format == 'portrait') {
                    $formatN = 'Portrait';
                }
                if ($format == 'landscape') {
                    $formatN = 'Landscape';
                }
                $pos = strpos($playlistIdTl, $formatN);
                if ($pos) {
                    if(!$playlistId) {
                        $playlistId = get_field('device_playlist', $device->ID);
                    }
                }
            }


            $standort = $_POST['center'];
            $order_nr = (int) $_POST['order_nr'];
            $datei = (int) $_POST['file'];

            $date_start = $_POST['date-start'][0];
            $date_end = $_POST['date-end'][0];
            $repeats = $_POST['repeats'][0];

            update_term_meta($kamp_id, 'standort_center', $standort[0]);
            update_term_meta($kamp_id, 'playlist', $playlistId);
            update_term_meta($kamp_id, 'kunde', $kunde_id);
            update_term_meta($kamp_id, 'order-nr', $order_nr);
            update_term_meta($kamp_id, 'file', $datei);
            update_term_meta($kamp_id, 'orientation', $format);

            update_term_meta($kamp_id, 'start_der_kampagne', $date_start);
            update_term_meta($kamp_id, 'ende_der_kampagne', $date_end);
            update_term_meta($kamp_id, 'spotshour', $repeats);

//            foreach ($devices as $device) {
//                $playlistId = get_field('device_playlist', $device->ID);
//                $output.='a'.$playlistId.'b';
//                if (in_array($playlistId, $playlistIds) || isset($playlistIds[$playlistId])) {
//                    //$output .= '<li><a href="http://localhost:8080/wp-admin/post.php?action=edit&post='.$playlistId.'" target="_blank">Playlist: ' . get_the_title($playlistId) . '</a></li>';
//                    $output .= '<li><a href="https://dooh-staging.ourweb.space/wp-admin/post.php?action=edit&post='.$playlistId.'" target="_blank">Playlist: ' . get_the_title($playlistId) . '</a></li>';
//                    continue;
//                }
//                $playlistIds[$playlistId] = $playlistId;
//                //$output .= '<li><a href="http://localhost:8080/wp-admin/post.php?action=edit&post='.$playlistId.'" target="_blank">Playlist: ' . get_the_title($playlistId) . '</a></li>';
//                $output .= '<li><a href="https://dooh-staging.ourweb.space/wp-admin/post.php?action=edit&post='.$playlistId.'" target="_blank">Playlist: ' . get_the_title($playlistId) . '</a></li>';
//
//                $playlist = get_field('playlist_list', $playlistId);
//                if (!is_array($playlist)) {
//                    $playlist = [];
//                }
//                $playlist[] = $value;
//                update_field('playlist_list', $playlist, $playlistId);
//            }

//            $playlistId = get_field('device_playlist', $devices[0]);
            $playlist_site_link = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $playlist_site_link = 'https://dooh-staging.mall-cockpit.de';

            $output .= '<li><a href="' . $playlist_site_link . '/wp-admin/post.php?action=edit&post=' . $playlistId.'" target="_blank">Playlist: ' . get_the_title($playlistId) . '</a></li>';
          
			$playlist = get_field('playlist_list', $playlistId);
            if (!is_array($playlist)) {
                $playlist = [];
            }
            $playlist[] = $value;
            update_field('playlist_list', $playlist, $playlistId);

            $output .= '</ul>';

        }

        echo json_encode(['output' => $output]);
        exit;
    }

    /**
     * @param $admin_bar
     */
    function addLink($admin_bar)
    {
        $admin_bar->add_menu( array( 'id'=>'playlist-wizard','title'=>'Kampagnen-Wizard','href'=>'#' ) );
    }
}

add_action( 'edited_kampagnen', 'edited_kampagnen_playlist' );

add_action( 'kampagnen_edit_form_fields', 'kampagnen_edit_form_fields_add', 10, 2 );

function edited_kampagnen_playlist( $kamp_id ) {



    $playlist = get_term_meta( $kamp_id, 'playlist', true );
    $order_nr = get_term_meta( $kamp_id, 'order-nr', true );
    $file = get_term_meta( $kamp_id, 'file', true );

    $start_der_kampagne = get_term_meta( $kamp_id, 'start_der_kampagne', true );
    $ende_der_kampagne = get_term_meta( $kamp_id, 'ende_der_kampagne', true );
    $spotshour = get_term_meta( $kamp_id, 'spotshour', true );

    $playlist_list = get_field('playlist_list', $playlist);

    $i = 0;
    foreach ($playlist_list as $playlist_l) {
        if($playlist_l['kamp_name'] == $kamp_id) {
            $n = $i;
        }
        $i++;
    }


    update_field( 'playlist_list_'.$n.'_order_nr', $order_nr, $playlist );
    update_field( 'playlist_list_'.$n.'_file', $file, $playlist );

    update_field( 'playlist_list_'.$n.'_period_0_startdate', $start_der_kampagne, $playlist );
    update_field( 'playlist_list_'.$n.'_period_0_enddate', $ende_der_kampagne, $playlist );
    update_field( 'playlist_list_'.$n.'_period_0_repeats_per_hour', $spotshour, $playlist );


}

function kampagnen_edit_form_fields_add( $term, $taxonomy ) {

    $standort_center = get_term_meta( $term->term_id, 'standort_center', true );
    $playlist = get_term_meta( $term->term_id, 'playlist', true );
    $client = get_term_meta( $term->term_id, 'kunde', true );

    $post_standort = get_post( $standort_center );
    $post_playlist = get_post( $playlist );
    $term_client = get_term( $client, 'kunden' );


    echo '<tr class="form-field">
	<th>
		<label for="seo_title">Standort (Center)</label>
	</th>
	<td>
		<a href="/wp-admin/post.php?post='.esc_attr($post_standort->ID).'&action=edit" target="_blank">'.get_field('center_name',$post_standort->ID).' ('.get_field('center_shortname',$post_standort->ID).')'.'</a>
	</td>
	</tr>
	<tr class="form-field">
	<th>
		<label for="seo_title">Playlist</label>
	</th>
	<td>
		<a href="/wp-admin/post.php?post='.esc_attr($post_playlist->ID).'&action=edit" target="_blank">'.esc_attr($post_playlist->post_title).'</a>
	</td>
	</tr>
	<tr class="form-field">
	<th>
		<label for="seo_title">Kunde</label>
	</th>
	<td>
		<a href="/wp-admin/term.php?taxonomy=clients&tag_ID='.esc_attr($term_client->term_id).'&post_type=playlist" target="_blank">'.esc_attr($term_client->name).'</a>
	</td>
	</tr>';

}
function hide_description_row() {
    echo "<style>.term-description-wrap,#wp-admin-bar-view{ display:none; }</style>";
}

add_action( 'edited_kampagnen', 'hide_description_row');
add_action( 'kampagnen_edit_form_fields', 'hide_description_row');