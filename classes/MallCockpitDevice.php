<?php

/**
 * Class MallCockpitDevice
 */
class MallCockpitDevice
{
    public static function abort()
    {

    }

    /**
     * @return void
     */
    public function init()
    {
        add_action('admin_head', function() {
            echo '<style>
.blink_text
{
    animation:1s blinker linear infinite;
    -webkit-animation:1s blinker linear infinite;
    -moz-animation:1s blinker linear infinite;
    color: red;
}

@-moz-keyframes blinker
{  
    0% { opacity: 1.0; }
    50% { opacity: 0.0; }
    100% { opacity: 1.0; }
}

@-webkit-keyframes blinker
{  
    0% { opacity: 1.0; }
    50% { opacity: 0.0; }
    100% { opacity: 1.0; }
}

@keyframes blinker
{  
    0% { opacity: 1.0; }
    50% { opacity: 0.0; }
    100% { opacity: 1.0; }
 }
          </style>';
        });

        // Change device posts columns
        add_filter('manage_devices_posts_columns', [$this, 'postsColumns']);
        add_action('manage_devices_posts_custom_column', [$this, 'postsCustomColumn'], 10, 2);

        // Add search query filter
        add_action('pre_get_posts', [$this, 'preGetPosts']);

        // Change label of acf field
        add_filter('acf/fields/post_object/result', [$this, 'changeAcfFieldResult'], 10, 4);

        // Add custom search filter
        add_action('restrict_manage_posts', [$this, 'restrictManagePosts']);

        // Set google maps api key
        add_filter('acf/fields/google_map/api', function ($api) {
            $api['key'] = 'AIzaSyBvLlnDfYWN1eU4DBBRDbeK9bj6SJvpDEc';
            return $api;
        });

        add_filter( 'manage_edit-devices_sortable_columns', function() {
            $columns['info_ping'] = 'info_ping';
            $columns['vast_request'] = 'vast_request';
            $columns['info_version'] = 'info_version';
            $columns['device_apptype'] = 'device_apptype';
            $columns['device_name'] = 'device_name';

            return $columns;
        });

        add_action( 'pre_get_posts', function($query) {
            if( ! is_admin() )
                return;

            $orderby = $query->get( 'orderby');

            if( 'info_ping' == $orderby ) {
                $query->set('meta_key','ping');
                $query->set('orderby','meta_value_num');
            } else if( 'vast_request' == $orderby ) {
                $query->set('meta_key','vast_request');
                $query->set('orderby','meta_value_num');
            } else if( 'info_version' == $orderby ) {
                $query->set('meta_key','code_version');
                $query->set('orderby','meta_value_num');
            } else if( 'device_apptype' == $orderby ) {
                $query->set('meta_key','device_apptype');
                $query->set('orderby','meta_value');
            } else if( 'device_name' == $orderby ) {
                $query->set('meta_key','device_name');
                $query->set('orderby','meta_value');
            }



        });
    }

    /**
     * @return void
     */
    public function restrictManagePosts()
    {
        if (!is_admin()) {
            return;
        }

        $screen = get_current_screen();
        if ('devices' != $screen->post_type) {
            return;
        }

        $centerItems = get_posts([
            'post_type' => 'center',
            'posts_per_page' => -1
        ]);
        $currentCenter = '';
        if (isset($_GET['center'])) {
            $currentCenter = (int)$_GET['center'];
        } ?>
        <select name="center" id="center">
            <option value="" <?php selected('', $currentCenter); ?>>Alle Center</option>
            <?php foreach ($centerItems as $key => $center) { ?>
                <option value="<?php echo $center->ID; ?>"
                    <?php selected($center->ID, $currentCenter); ?>><?php echo get_field('center_name', $center->ID); ?></option>
            <?php } ?>
        </select>
        <?php
    }

    /**
     * @param $title
     * @param $post
     * @param $field
     * @param $postId
     * @return mixed
     */
    public function changeAcfFieldResult($title, $post, $field, $postId)
    {
        if ($post->post_type === 'devices') {
            return get_field('device_name', $post);
        }
        return $title;
    }

    /**
     * @param $query
     * @return void
     */
    public function preGetPosts($query)
    {
        if (!is_admin()) {
            return;
        }

        $screen = get_current_screen();
        $postType = $query->get('post_type');
        if ((isset($screen->post_type) && 'devices' != $screen->post_type) || 'devices' != $postType) {
            return;
        }

        if (empty($_GET['center']) && empty($_GET['s'])) {
            return;
        }

        unset($query->query['s']);
        unset($query->query['name']);
        unset($query->query['center']);

        $query->set('s', '');
        $query->set('name', '');
        $query->set('center', '');


        if (!empty($_GET['s'])) {
            $metaQuery = ['relation' => 'AND'];
            array_push($metaQuery, [
                'key' => 'device_name',
                'value' => $_GET['s'],
                'compare' => 'LIKE'
            ]);
        } else {
            $metaQuery = ['relation' => 'OR'];
        }

        if (!empty($_GET['center'])) {
            array_push($metaQuery, [
                'key' => 'device_center',
                'value' => $_GET['center'],
                'compare' => '='
            ]);
        }

        $query->set('meta_query', $metaQuery);
    }

    /**
     * @param $column
     * @param $postId
     * @return void
     */
    public function postsCustomColumn($column, $postId)
    {
        if ($column === 'device_name') {
            echo '<a class="row-title" href="' . get_edit_post_link($postId) . '">' . get_field($column, $postId) . '</a>';

            if (get_field('device_apptype', $postId) === 'advertiser') {
                $playlistId = get_field('device_playlist', $postId);
                if (empty($playlistId)) {
                    $playlistId = get_field('center_global_playlist', get_field('device_center', $postId));
                }
                if (!isPlaylistOk($playlistId)) {
                    echo ' <span class="blink_text" style="padding-left:5px;">!!! Playlist ist fehlerhaft !!!</span>';
                }
            }

        } elseif ($column === 'info_version') {
            echo '<div>';
            $version = get_field('code_version', $postId);
            if (get_field('device_apptype', $postId) === 'advertiser') {
                if ($version != MC_DEVICE_VERSION) {
                    echo '<span style="color:red">' . ($version ?: 'n/a') . '</span>';
                } else {
                    echo $version;
                }
            } else {
                if ($version != MC_DEVICE_VERSION_AD) {
                    echo '<span style="color:red">' . ($version ?: 'n/a') . '</span>';
                } else {
                    echo $version;
                }
            }
            echo "</div>";
        } elseif ($column === 'info_ping') {
            echo '<div>';
            $date = get_field('ping', $postId);
            $dateOutput = date('d.m - H:i', (strtotime($date) + 3600));
            $dateTime = time() - strtotime($date);
            if ($dateTime > 600) {
                echo '<span style="color:red">Ping: ' . ($date ? $dateOutput : 'n/a') . '</span>';
            } else {
                echo '<span style="color:green">Ping: ' . $dateOutput . '</span>';
            }

            if (get_field('mbps', $postId)) {
                echo '<br /><span>Speed: ' . get_field('mbps', $postId) . 'Mbps</span>';
            }

            /*if (get_field('connectionType', $postId)) {
                echo '<br /><span>Connection: ' . get_field('connectionType', $postId) . '</span>';
            }*/

            echo "</div>";
        } elseif ($column === 'last_page') {
            if (get_field('last_page', $postId)) {
                echo get_field('last_page', $postId);
            } else {
                echo "-";
            }
        } elseif ($column === 'track_route') {
            if (get_field('track_route', $postId)) {
                echo get_field('track_route', $postId);
            } else {
                echo "-";
            }
        } elseif ($column === 'vast_request') {

            $date = get_field('vast_request', $postId);
            if ($date) {
                echo '<div>';
                $dateOutput = date('d.m - H:i', (strtotime($date) + 3600));
                $dateTime = time() - strtotime($date);
                if ($dateTime > 300) {
                    echo '<span style="color:red">Request: ' . ($date ? $dateOutput : 'n/a') . '</span>';
                } else {
                    echo '<span style="color:green">Request: ' . $dateOutput . '</span>';
                }
                echo "</div>";
            }

            $date = get_field('vast_request_success', $postId);
            if ($date) {
                echo '<div>';
                $dateOutput = date('d.m - H:i', (strtotime($date) + 3600));
                $dateTime = time() - strtotime($date);
                if ($dateTime > 300) {
                    echo '<span>Response: ' . ($date ? $dateOutput : 'n/a') . '</span>';
                } else {
                    echo '<span>Response: ' . $dateOutput . '</span>';
                }
                echo "</div>";
            }
        } elseif ($column === 'device_center') {
            echo get_field('center_name', get_field($column, $postId));
        } elseif ($column === 'device_apptype') {
            echo ucfirst(get_field($column, $postId));
        } elseif ($column === 'device_playlist') {
            if (get_field('device_apptype', $postId) === 'advertiser') {
                $playlistId = get_field('device_playlist', $postId);
                if (empty($playlistId)) {
                    $playlistId = get_field('center_global_playlist', get_field('device_center', $postId));
                }
                echo get_the_title($playlistId) ?: '-';
            } else {
                echo '-';
            }
        } elseif ($column === 'device_vast') {
            if (get_field('device_apptype', $postId) === 'advertiser') {
                $vastUrl = get_field('device_vast', $postId);
                if (empty($vastUrl)) {
                    $vastUrl = get_field('center_global_vast_url', get_field('device_center', $postId));
                }
                echo !empty($vastUrl) ? 'Ja' : 'Nein';
            } else {
                echo '-';
            }
        } elseif ($column === 'device_player_url') {
            if (get_field('device_apptype', $postId) === 'advertiser') {
                $type = get_field('device_player_type', $postId);
                if ($type === 'player2') {
                    echo '<a target="_blank" href="https://dooh.mall-cockpit.de/?player2=' . $postId . '">Unigon Player ('.$postId.') öffnen</a>';
                } else {
                    echo '<a target="_blank" href="https://dooh.mall-cockpit.de/?player=' . $postId . '">AdPack Player ('.$postId.') öffnen</a>';
                }
            } else {
                echo '-';
            }
        } elseif ($column === 'device_player_css') {
            if (!empty(get_field('device_player_css', $postId))) {
              echo 'Ja';
            }
        } elseif ($column === 'device_report') {
            if (get_field('device_apptype', $postId) === 'advertiser') {
                echo '<a target="_blank" href="https://dooh.mall-cockpit.de/wp-content/plugins/mall-cockpit-device/player/v4/player.php?deviceId=' . $postId . '">Player öffnen</a>';
                echo '<br /><a target="_blank" href="https://dooh.mall-cockpit.de/wp-content/plugins/mall-cockpit-device/player/report/index.php?date=' . date('Y-m-d') . '&media_id=&device_id=' . $postId . '&center_id=&s=">Report</a>';
            }
        }
    }

    /**
     * @param array $columns
     * @return array
     */
    public function postsColumns($columns)
    {
        return [
            'device_name' => 'Name',
            'info_version' => 'App-Version',
            'info_ping' => 'Status',
            'vast_request' => 'SSP',
            //'last_page' => 'Seite',
            //'track_route' => 'Route',
            'device_center' => 'Center',
            'device_apptype' => 'Typ',
            //'device_playlist' => 'Playlist',
            //'device_player_url' => 'Player-URL',
            //'device_vast' => 'VAST',
			'device_player_css' => 'CSS',
            'device_report' => 'Report/Player'
        ];
    }
}

function isPlaylistOk($playlistId = null)
{
    $fields = get_fields($playlistId);
    if (!$fields) {
        return false;
    }

    $playlistItems = $fields['playlist_list'];
    if (empty($playlistItems)) {
        return false;
    }

    $dt = new DateTime();

    $contentStatus = false;
    $adStatus = false;
    foreach ($playlistItems as $playlistItem) {
        foreach ($playlistItem['period'] as $period) {
            if (date('Y-m-d', strtotime($period['startdate'])) <= $dt->format('Y-m-d') &&
                date('Y-m-d', strtotime($period['enddate'])) >= $dt->format('Y-m-d')) {

                foreach ($period['daytimes'] as $daytime) {
                    if (isPlaylistOkIsWeekValid($dt, $daytime['weekdays']) &&
                        $dt->format('H') >= (int)$daytime['starthour'] &&
                        $dt->format('H') <= (int)$daytime['endhour']) {
                        if (!empty($period['repeats_per_hour'])) {
                            $adStatus = true;
                        } else {
                            $contentStatus = true;
                        }
                        break;
                    }
                }
                if ($contentStatus && $adStatus) {
                    break;
                }
            }
        }
        if ($contentStatus && $adStatus) {
            break;
        }
    }

    return ($contentStatus && $adStatus);
}

function isPlaylistOkIsWeekValid($dt, array $weekdays)
{
    $currentWeekday = null;
    switch ($dt->format('N')) {
        case 1:
            $currentWeekday = 'montag';
            break;
        case 2:
            $currentWeekday = 'dienstag';
            break;
        case 3:
            $currentWeekday = 'mittwoch';
            break;
        case 4:
            $currentWeekday = 'donnerstag';
            break;
        case 5:
            $currentWeekday = 'freitag';
            break;
        case 6:
            $currentWeekday = 'samstag';
            break;
        case 7:
            $currentWeekday = 'sonntag';
            break;
    }

    return in_array($currentWeekday, $weekdays);
}