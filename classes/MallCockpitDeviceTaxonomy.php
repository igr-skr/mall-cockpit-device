<?php

/**
 * Class MallCockpitDeviceTaxonomy
 */
class MallCockpitDeviceTaxonomy
{
	/**
	 * @return void
	 */
	public function init()
	{
		// $list = get_field('playlist_list', 132);
		// print_r($list);

		// $queriedObject = 'kampagnen' . '_' . 145;
		// $file = get_field('file', $queriedObject);
		// print_r($file);

//        print_r(get_post_meta(6409));
//
//        print_r($GLOBALS['acf_register_field_group']);

        wp_enqueue_media();

		add_action('saved_term', [$this, 'savedTermHookCallback'], 10, 5);
        add_action("kampagnen_edit_form", [$this, 'addPlaylistsField'], 10, 2);

        add_filter( 'posts_where', function ( $where ) {
            $where = str_replace( "meta_key = 'playlist_list_$", "meta_key LIKE 'playlist_list_%", $where );
            return $where;
        });
	}

	/**
	 *
	 *
	 * @return void
	 */
	public function savedTermHookCallback($term_id, $tt_id, $taxonomy, $update, $args)
	{
		if ($taxonomy !== 'kampagnen') return;

		$queriedObject = $taxonomy . '_' . $term_id;

		$orderNr = get_field('order-nr', $queriedObject);
		$startDateCampaign = get_field('start_der_kampagne', $queriedObject);
		$endDateCampaign = get_field('ende_der_kampagne', $queriedObject);
		$fileMedia = get_field('file', $queriedObject);

		$playlistId = get_term_meta($term_id, 'playlist', true);

		$list = get_field('playlist_list', $playlistId);

		if (!isset($list)) return;

		$newList = [];

		foreach ($list as $l) {

			if (is_array($l['kamp_name']) && in_array($term_id, $l['kamp_name'])) {

				if (isset($l['period'])) {
					$periods = [];

					foreach ($l['period'] as $period) {


							if (isset($l['startdate'])) $l['startdate'] = $startDateCampaign;
							if (isset($l['enddate'])) $l['enddate'] = $endDateCampaign;
							if (isset($l['order_nr'])) $l['order_nr'] = $orderNr;
							if (isset($l['file'])) $l['file'] = $fileMedia;
						}

						array_push($periods, $period);


					$l['period'] = $periods;

					array_push($newList, $l);

					update_field('playlist_list', $newList, $playlistId);
				}
			}
		}
	}

    /**
     * @param $tag
     * @param $taxonomy
     *
     * @return void
     */
	public function addPlaylistsField($tag, $taxonomy)
    {
        $termId = $tag->term_id;
        $query = new WP_Query(array(
            'posts_per_page'    => -1,
            'post_type'         => 'playlist',
            'meta_query'        => [
                [
                    'key'       => 'playlist_list_$_kamp_name',
                    'value'     => $termId,
                    'compare'   => 'LIKE'
                ]
            ]
        ));

        $playlists = [];

        if (!$query->have_posts()) return;

        while ($query->have_posts()) {
            $query->the_post();

            $playlistId = get_the_ID();
            $playlistName = get_the_title();

            $playlistList = get_field('playlist_list', $playlistId);


            $field = get_field_object('playlist_list');
            $field_key = $field['key'];



            if (!$playlistList) continue;

            foreach ($playlistList as $list) {
                $playlist = [
                    'clients' => [],
                    'file' => [
                        'url'   => '',
                        'alt'   => '',
                        'type'  => ''
                    ],
                    'periods' => [],
                ];

                if (!is_array($list['kamp_name'])) continue;
                if (!in_array($termId, $list['kamp_name'])) continue;

                $playlist['playlist_name'] = $playlistName;
                $playlist['playlist_id'] = $playlistId;
                $playlist['field_key'] = $field_key;
                $playlist['unique_id'] = $list['row_number'];


                $playlist['playlist_edit_link'] = get_edit_post_link($playlistId);

                if ($list['kunden']) {
                    foreach ($list['kunden'] as $clientId) {
                        $client = get_term($clientId, 'kunden');

                        array_push($playlist['clients'], [
                            'name' => $client->name,
                            'edit_link' => get_edit_term_link($clientId, 'kunden')
                        ]);
                    }
                }

                if ($list['file']) {
                    $playlist['file'] = [
                        'url'       => $list['file']['url'],
                        'alt'       => $playlistName,
                        'type'      => $list['file']['type'],
                        'mime_type' => $list['file']['mime_type']
                    ];
                }

                $playlist['order_nr'] = $list['order_nr'];

                if ($list['period']) {
                    foreach ($list['period'] as $period) {
                        array_push($playlist['periods'], [
                            'start_date'    => $period['startdate'],
                            'end_date'      => $period['enddate']
                        ]);
                    }
                }

                array_push($playlists, $playlist);
            }
        }

        load_template(__DIR__ . '/../views/playlists-table.php', true, [
            'playlists' => $playlists
        ]);
    }
}





//add_action( 'edited_kampagnen', 'edited_kampagnen_playlist' );
/*
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
*/

/*
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

	global $post;

	// 1) Получить список всех репитеров в плейлисте
	// 2) Полуучить список всех медиа относящихся к данной кампании в плейлисте
	// 3) Дать возможность удалять/изменять медиа, для репитера
	// 4) Дать возможность удалять репитер целиком
	// 5)

	$queried_object = get_queried_object();
	$term_id = $queried_object->term_id;
	echo '<pre>';
	print_r($queriedObject);
	echo '</pre>';

	echo 'test';
	echo 'test';

	$term_id = get_queried_object()->term_id;
	/*





	$queriedObject = $taxonomy . '_' . $term_id;

	echo '<pre>';
	//print_r($queriedObject);
	echo '</pre>';

	$orderNr = get_field('order-nr', $queriedObject);
	$startDateCampaign = get_field('start_der_kampagne', $queriedObject);
	$endDateCampaign = get_field('ende_der_kampagne', $queriedObject);
	$fileMedia = get_field('file', $queriedObject);

	$playlistId = get_term_meta($term_id, 'playlist', true);

	$list = get_field('playlist_list', $playlistId);

	foreach ($list as $l) {



	}

		if (!isset($list)) return;

		$newList = [];

		foreach ($list as $l) {

			if (is_array($l['kamp_name']) && in_array($term_id, $l['kamp_name'])) {

				if (isset($l['period'])) {
					$periods = [];

					foreach ($l['period'] as $period) {


							if (isset($l['startdate'])) $l['startdate'] = $startDateCampaign;
							if (isset($l['enddate'])) $l['enddate'] = $endDateCampaign;
							if (isset($l['order_nr'])) $l['order_nr'] = $orderNr;
							if (isset($l['file'])) $l['file'] = $fileMedia;
						}

						array_push($periods, $period);


					$l['period'] = $periods;

					array_push($newList, $l);

					update_field('playlist_list', $newList, $playlistId);
				}
			}
		}


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
	*/



//}

/*
function hide_description_row() {
    echo "<style> .term-description-wrap { display:none; } </style>";
}

add_action( "measure_sectors_edit_form", 'hide_description_row');
add_action( "measure_sectors_add_form", 'hide_description_row');

add_filter('manage_edit-measure_sectors_columns', function ( $columns ) {
    if( isset( $columns['description'] ) )
        unset( $columns['description'] );
    return $columns;
}, 999);
*/
