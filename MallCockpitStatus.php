<?php

$args = array(
    'numberposts' => -1,
    'post_type' => 'devices',
    'post_status' => 'publish',
    'ignore_sticky_posts' => true,
);

$vastList = [];
$pingList = [];
$versionList = [];
$centerGroup = [];
$playlistList = [];

$posts = get_posts($args);
foreach ($posts as $post) {

    if (!get_field('device_active', $post->ID)) {
        continue;
    }

    if (get_field('device_apptype', $post->ID) !== 'advertiser') {
        continue;
    }

    if (get_field('device_apptype', $post->ID) === 'advertiser' && !get_field('device_programmatic', $post->ID)) {
        continue;
    }

    $playlistId = get_field('device_playlist', $post->ID);
    if (empty($playlistId)) {
        $playlistId = get_field('center_global_playlist', get_field('device_center', $post->ID));
    }


    $centerId = get_field('device_center', $post->ID);
    $version = get_field('code_version', $post->ID);

    if (!$version || !get_field('center_name', $centerId)) {
        continue;
    }

    // Ping Info
    if ((get_field('device_apptype', $post->ID) === 'advertiser' && get_field('device_programmatic', $post->ID)) || get_field('device_apptype', $post->ID) !== 'advertiser') {
        $date = get_field('ping', $post->ID);
        $dateOutput = date('d.m - H:i', (strtotime($date) + 3600));
        $dateTime = time() - strtotime($date);
        if ($dateTime > 800 && $date) {
            if (!isset($centerGroup[$centerId])) {
                $centerGroup[$centerId] = [
                    'name' => get_field('center_name', $centerId),
                    'devices' => [],
                    'ping' => [],
                    'vast' => [],
                    'version' => [],
                    'playlist' => []
                ];
            }

            if (!isset($centerGroup[$centerId]['devices'][$post->ID])) {
                $centerGroup[$centerId]['devices'][$post->ID] = [
                    'post' => $post,
                    'ping' => false,
                    'vast' => false,
                    'version' => false,
                    'playlist' => false
                ];
            }

            $pingList[] = [
                'centerId' => $centerId,
                'deviceId' => $post->ID,
                'post' => $post,
                'dateTime' => $dateTime,
                'date' => $date,
                'output' => $dateOutput
            ];
        }
    }

    if (get_field('device_apptype', $post->ID) === 'advertiser' && get_field('device_programmatic', $post->ID)) {
        // Vast Request
        $date = get_field('vast_request', $post->ID);
        $dateOutput = date('d.m - H:i', (strtotime($date) + 3600));
        $dateTime = time() - strtotime($date);
        if ($dateTime > 800 && $date) {
            if (!isset($centerGroup[$centerId])) {
                $centerGroup[$centerId] = [
                    'devices' => [],
                    'ping' => [],
                    'vast' => [],
                    'version' => [],
                    'playlist' => []
                ];
            }

            if (!isset($centerGroup[$centerId]['devices'][$post->ID])) {
                $centerGroup[$centerId]['devices'][$post->ID] = [
                    'post' => $post,
                    'ping' => false,
                    'vast' => false,
                    'version' => false,
                    'playlist' => false
                ];
            }

            $vastList[] = [
                'centerId' => $centerId,
                'deviceId' => $post->ID,
                'post' => $post,
                'dateTime' => $dateTime,
                'output' => $dateOutput,
                'date' => $date,
            ];
        }
    }



    $versionStatus = true;
    if (get_field('device_apptype', $post->ID) === 'advertiser') {
        if ($version != MC_DEVICE_VERSION ) {
            $versionStatus = false;
        }
    } else {
        if ($version != MC_DEVICE_VERSION_AD) {
            $versionStatus = false;
        }
    }

    if (!$versionStatus) {
        if (!isset($centerGroup[$centerId])) {
            $centerGroup[$centerId] = [
                'devices' => [],
                'ping' => [],
                'vast' => [],
                'version' => [],
                'playlist' => []
            ];
        }

        if (!isset($centerGroup[$centerId]['devices'][$post->ID])) {
            $centerGroup[$centerId]['devices'][$post->ID] = [
                'post' => $post,
                'ping' => false,
                'vast' => false,
                'version' => false,
                'playlist' => false
            ];
        }

        $versionList[] = [
            'centerId' => $centerId,
            'deviceId' => $post->ID,
            'post' => $post,
            'version' => $version
        ];
    }

    if (empty($playlistId)) {
        if (!isset($centerGroup[$centerId])) {
            $centerGroup[$centerId] = [
                'devices' => [],
                'ping' => [],
                'vast' => [],
                'version' => [],
                'playlist' => []
            ];
        }

        if (!isset($centerGroup[$centerId]['devices'][$post->ID])) {
            $centerGroup[$centerId]['devices'][$post->ID] = [
                'post' => $post,
                'ping' => false,
                'vast' => false,
                'version' => false,
                'playlist' => false
            ];
        }

        $playlistList[] = [
            'centerId' => $centerId,
            'deviceId' => $post->ID,
            'playlist' => 'empty'
        ];
    } elseif (!isPlaylistOk($playlistId)) {
        if (!isset($centerGroup[$centerId])) {
            $centerGroup[$centerId] = [
                'devices' => [],
                'ping' => [],
                'vast' => [],
                'version' => [],
                'playlist' => []
            ];
        }

        if (!isset($centerGroup[$centerId]['devices'][$post->ID])) {
            $centerGroup[$centerId]['devices'][$post->ID] = [
                'post' => $post,
                'ping' => false,
                'vast' => false,
                'version' => false,
                'playlist' => false
            ];
        }

        $playlistList[] = [
            'centerId' => $centerId,
            'deviceId' => $post->ID,
            'playlist' => 'failed'
        ];
    } else {
        if (!isset($centerGroup[$centerId])) {
            $centerGroup[$centerId] = [
                'devices' => [],
                'ping' => [],
                'vast' => [],
                'version' => [],
                'playlist' => []
            ];
        }

        if (!isset($centerGroup[$centerId]['devices'][$post->ID])) {
            $centerGroup[$centerId]['devices'][$post->ID] = [
                'post' => $post,
                'ping' => false,
                'vast' => false,
                'version' => false,
                'playlist' => false
            ];
        }

        $playlistList[] = [
            'centerId' => $centerId,
            'deviceId' => $post->ID,
            'playlist' => $playlistId
        ];
    }
}


usort($pingList, function ($item1, $item2) {
    return $item1['dateTime'] <=> $item2['dateTime'];
});

usort($vastList, function ($item1, $item2) {
    return $item1['dateTime'] <=> $item2['dateTime'];
});

foreach ($pingList as $item) {
    $centerGroup[$item['centerId']]['devices'][$item['deviceId']]['ping'] = $item;
    $centerGroup[$item['centerId']]['ping'][] = $item;
}

foreach ($vastList as $item) {
    $centerGroup[$item['centerId']]['devices'][$item['deviceId']]['vast'] = $item;
    $centerGroup[$item['centerId']]['vast'][] = $item;
}

foreach ($versionList as $item) {
    $centerGroup[$item['centerId']]['devices'][$item['deviceId']]['version'] = $item;
    $centerGroup[$item['centerId']]['version'][] = $item;
}

foreach ($playlistList as $item) {
    $centerGroup[$item['centerId']]['devices'][$item['deviceId']]['playlist'] = $item;
    $centerGroup[$item['centerId']]['playlist'][] = $item;
}

?>
<style>
    .p-table tbody td {
        font-size: 13px;
        border-bottom: 1px solid #ccc;
        padding: 5px 0;
    }
    .device {
        background: #fff;
        padding: 15px;
        margin: 0 25px 0 0;
    }
    .table-device:hover {
        background-color: #efefef;
    }
</style>
<h1>Status - Advertiser Devices</h1>
<p>Die Liste beinhaltet alle Advertiser Devices mit "active" und "programmatic"</p>
<?php foreach ($centerGroup as $centerId => $center) { ?>
<div class="device">
    <h1 style="margin-bottom: 5px; text-indent: 3px;font-size:16px"><?= get_field('center_name', $centerId) ?></h1>
    <a target="_blank" href="/wp-content/plugins/mall-cockpit-device/player/auslastung/?category=ad_and_content&location-device=center_<?= $centerId ?>">Auslastungsreporting</a> |
    <a target="_blank" href="/wp-content/plugins/mall-cockpit-device/player/report/index.php?date=<?= date('Y-m-d') ?>>&media_id=&device_id=&center_id=<?= $centerId ?>&s=">Playoutsreporting</a>
    <hr />
    <?php foreach ($center['devices'] as $deviceId => $device) { ?>
        <table class="table-device">
            <tr>
                <td style="width:120px;">
                    <a href="/wp-admin/post.php?post=<?= $deviceId ?>&action=edit"><strong><?= get_field('device_name', $deviceId) ?></strong></a>
                </td>
                <td style="width:100px;">
                    <?php if ($device['ping']) { ?>
                        <span style="margin-left: 10px; background: white; display: inline-block; border: 1px solid red; border-radius:5px; width: 100%; text-align: center; font-size: inherit;">
                            <span style="color: red;">Offline</span>
                        </span>
                    <?php } else { ?>
                        <span style="margin-left: 10px; background: white; display: inline-block; border: 1px solid green; border-radius:5px; width: 100%; text-align: center; font-size: inherit;">
                            <span style="color: green;">Online</span>
                        </span>
                    <?php } ?>
                </td>
                <td style="width:20px;"></td>
                <td style="width:130px;">
                    <?php if ($device['vast'] || $device['ping']) {

                        if (get_field('device_apptype', $deviceId) === 'advertiser') {
                            $vastUrl = get_field('device_vast', $deviceId);
                            if (empty($vastUrl)) {
                                $vastUrl = get_field('center_global_vast_url', get_field('device_center', $deviceId));
                            }
                        }
                        if (empty($vastUrl)) {
                        ?>
                        <span style="margin: 0 10px; background: white; display: inline-block; border: 1px solid #f59342; border-radius:5px; width: 100%; text-align: center; font-size: inherit;">
                            <span style="color: #f59342;">keine VAST-URL</span>
                        </span>
                        <?php
                        } else {
                        ?>
                        <?php if (!$device['ping']) { ?>
                            <span style="margin: 0 10px; background: white; display: inline-block; border: 1px solid red; border-radius:5px; width: 100%; text-align: center; font-size: inherit;">
                                <span style="color: red;">Keine SSP-Requests</span>
                            </span>
                        <?php } else { ?>
                            <span style="margin: 0 10px; background: white; opacity: .5; display: inline-block; border: 1px solid gray; border-radius:5px; width: 100%; text-align: center; font-size: inherit;">
                                <span style="color: gray;">SSP - Device offline</span>
                            </span>
                        <?php } ?>
                    <?php } } else { ?>
                        <span style="margin: 0 10px; background: white; display: inline-block; border: 1px solid green; border-radius:5px; width: 100%; text-align: center; font-size: inherit;">
                            <span style="color: green;">SSP - OK</span>
                        </span>
                    <?php } ?>
                </td>
                <td style="width:20px;"></td>
                <td style="width:130px;">
                    <?php if ($device['playlist'] !== false && $device['playlist']['playlist'] == 'empty') { ?>
                        <span style="margin-left: 10px; background: white; display: inline-block; border: 1px solid #f59342; border-radius:5px; width: 100%; padding: 0 5px; text-align: center; font-size: inherit;">
                            <span style="color: #f59342;">Playlist nicht vorhanden</span>
                        </span>
                    <?php } elseif ($device['playlist'] !== false && $device['playlist']['playlist'] == 'failed') { ?>
                        <span style="margin-left: 10px; background: white; display: inline-block; border: 1px solid red; border-radius:5px; width: 100%; padding: 0 5px; text-align: center; font-size: inherit;">
                        <span style="color: red;">Playlist fehlerhaft
                        </span>
                    <?php } else { ?>
                        <span style="margin-left: 10px; background: white; display: inline-block; border: 1px solid green; border-radius:5px; width: 100%; padding: 0 5px; text-align: center; font-size: inherit;">
                            <span style="color: green;">Playlist - OK
                        </span>
                    <?php } ?>
                </td>
                <td style="width:20px;"></td>
                <td style="width:130px;">
                    <?php if ($device['version']) { ?>
                        <span style="margin-left: 10px; background: white; display: inline-block; border: 1px solid #f59342; border-radius:5px; width: 100%; padding: 0 5px; text-align: center; font-size: inherit;">
                            <span style="color: #f59342;">Version
                            <?php echo $device['version']['version']; ?>
                        </span>
                    <?php } else { ?>
                        <span style="margin-left: 10px; background: white; display: inline-block; border: 1px solid green; border-radius:5px; width: 100%; padding: 0 5px; text-align: center; font-size: inherit;">
                            <span style="color: green;">Version - OK
                        </span>
                    <?php } ?>
                </td>
                <td style="width:20px;"></td>
                <td>
                    <a target="_blank" href="/wp-content/plugins/mall-cockpit-device/player/v4/?deviceId=<?= $deviceId ?>">Player öffnen</a>
                </td>
            </tr>
        </table>
    <?php } ?>

    <?php /*
    <?php if (count($center['ping'])) { ?>
    <div style="color:#777; text-indent: 3px; margin-top:15px;">Offline Devices</div>
    <?php foreach ($center['ping'] as $pingItem) { ?>
        <table>
            <tr>
                <td style="width:150px;">
                    <a href="/wp-admin/post.php?post=<?= $pingItem['post']->ID ?>&action=edit"><strong><?= get_field('device_name', $pingItem['post']->ID) ?></strong></a>
                    <span style="color:#777; text-indent:5px; display:inline-block;"><?= ucfirst(get_field('device_apptype', $pingItem['post']->ID)) ?></span>
                </td>
                <td style="width: 100px">
                    <?php
                    if ($pingItem['dateTime'] <= 60) {
                        echo "seit 1 Minute";
                    } else if ($pingItem['dateTime'] <= 1800) {
                        echo "seit 30 Minute";
                    } else if ($pingItem['dateTime'] <= 3600) {
                        echo "seit 1 Stunde";
                    } else if ($pingItem['dateTime'] <= 86400) {
                        echo "über 1 Stunde";
                    } else {
                        echo "über 1 Tag";
                    }
                    ?>
                </td>
                <td style="width: 100px">
                    v<?= get_field('code_version', $pingItem['post']->ID) ?>
                </td>
            </tr>
        </table>
    <?php }
    }?>

    <?php if (count($center['vast'])) { ?>
        <div style="color:#777; text-indent: 3px; margin-top: 15px;">Devices without SSP Requests</div>
        <?php foreach ($center['vast'] as $vastItem) { ?>
            <table>
                <tr>
                    <td style="width:150px;">
                        <a href="/wp-admin/post.php?post=<?= $pingItem['post']->ID ?>&action=edit"><strong><?= get_field('device_name', $vastItem['post']->ID) ?></strong></a>
                        <span style="color:#777; text-indent:5px; display:inline-block;"><?= ucfirst(get_field('device_apptype', $vastItem['post']->ID)) ?></span>
                    </td>
                    <td style="width: 100px">
                        <?php
                        if ($vastItem['dateTime'] <= 60) {
                            echo "seit 1 Minute";
                        } else if ($vastItem['dateTime'] <= 1800) {
                            echo "seit 30 Minute";
                        } else if ($vastItem['dateTime'] <= 3600) {
                            echo "seit 1 Stunde";
                        } else if ($vastItem['dateTime'] <= 86400) {
                            echo "über 1 Stunde";
                        } else {
                            echo "über 1 Tag";
                        }
                        ?>
                    </td>
                    <td style="width: 100px">
                        v<?= get_field('code_version', $vastItem['post']->ID) ?>
                    </td>
                </tr>
            </table>
        <?php }
    }?>*/ ?>
</div>
<?php } ?>