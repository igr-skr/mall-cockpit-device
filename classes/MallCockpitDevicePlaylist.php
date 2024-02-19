<?php

/**
 * Class MallCockpitDevicePlaylist
 */
class MallCockpitDevicePlaylist
{
    /** @var WP_REST_Request */
    private $request;

    /**
     * @var bool
     */
    private $vastFallback = true;

    /** @var int */
    private $deviceId;

    /** @var array */
    private $deviceFields;

    /** @var array */
    private $centerFields;

    /** @var array */
    private $playlistItems;

    /**
     * Only for monitoring
     * @var bool
     */
    private $monitoring;

    /**
     * Only for monitoring
     * @var int
     */
    private $playlistId;

    /**
     * Only for monitoring
     * @var \DateTime
     */
    private $date;

    /**
     * Only for monitoring
     * @var int
     */
    private $h;

    /**
     * Only for monitoring
     * @var int
     */
    private $m;


    public function monitoring($playlistId, $date, $h, $m = 0)
    {
        $this->monitoring = true;
        $this->playlistId = $playlistId;
        $this->date = $date;
        $this->h = $h;
        $this->m = $m;

        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $playlist = get_post($playlistId);
        $playlistFields = get_fields($playlist);

        $this->preparePlaylistEntriesBeforeCreate($playlistFields['playlist_list']);

        return $this->createPlaylist();
    }

    /**
     * @return void
     */
    public function init()
    {
        add_action('edit_form_after_title', function() {
            global $post;
            if ($post->post_type === 'playlist') {
                echo '<div style="position:fixed;z-index:100;bottom:0;left:0;width:100%;background:#fff;font-size:18px;border-top:1px solid #ccc;font-weight:bold;text-align:center;padding:15px;">Playlist: ' . esc_attr( htmlspecialchars( $post->post_title ) ) . '</div>';
                ?>
                <script>
                    jQuery(document).ready(function() {
                        var today = new Date();
                        var dd = String(today.getDate()).padStart(2, '0');
                        var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
                        var yyyy = today.getFullYear();
                        var c = yyyy + '-' + mm + '-' + dd;
                        jQuery.each(jQuery('[data-key=field_59fc5819b02bf] .hasDatepicker'), function(index, item) {
                             var d = jQuery(item).val();

                             if (c < d) {
                                 jQuery(item).closest('.acf-row').find('.acf-row-handle:eq(0)').attr('style', 'background-color: blue');
                             }
                        });

                        jQuery.each(jQuery('[data-key=field_59fc5853b02c0] .hasDatepicker'), function(index, item) {
                            var d = jQuery(item).val();
                            if (c > d) {
                                jQuery(item).closest('.acf-row').find('.acf-row-handle:eq(0)').attr('style', 'background-color: red');
                            }
                        });
                    });
                </script>
                <?php
            }
        });

        add_action('rest_api_init', function () {
            register_rest_route('mall-cockpit-devices', '/playlist', array(
                'methods' => 'GET',
                'callback' => [$this, 'playlist'],
                'args' => array(
                    'device' => array(
                        'validate_callback' => function ($param) {
                            if (!is_numeric($param)) {
                                return false;
                            }

                            $device = get_post($param);
                            if (!$device || $device->post_type != 'devices') {
                                return false;
                            }

                            $this->deviceId = $device->ID;
                            $this->deviceFields = get_fields($device);
                            $this->centerFields = get_fields($this->deviceFields['device_center']);

                            if ($this->deviceFields['device_apptype'] != 'advertiser') {
                                return false;
                            }

                            $playlistId = $this->getPlaylistId();
                            if (empty($playlistId)) {
                                return false;
                            }

                            $playlist = get_post($playlistId);
                            if (!$playlist) {
                                return false;
                            }
                            return true;
                        }
                    ),
                    'h' => array(
                        'validate_callback' => function ($param) {
                            if (!is_numeric($param)) {
                                return false;
                            }
                            if ($param < 0 || $param > 23) {
                                return false;
                            }
                            return true;
                        }
                    ),
                    'm' => array(
                        'validate_callback' => function ($param) {
                            if (!is_numeric($param)) {
                                return false;
                            }
                            if ($param < 0 || $param > 59) {
                                return false;
                            }
                            return true;
                        }
                    )
                )
            ));
        });
    }

    /**
     * @param WP_REST_Request $request
     * @return array
     */
    public function playlist(WP_REST_Request $request)
    {
        $this->request = $request;

        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $deviceId = $request->get_param('device');
        $device = get_post($deviceId);

        $this->deviceFields = get_fields($device);
        $this->centerFields = get_fields($this->deviceFields['device_center']);

        $playlistId = $this->getPlaylistId();
        $playlist = get_post($playlistId);

        $playlistFields = get_fields($playlist);

        $this->preparePlaylistEntriesBeforeCreate($playlistFields['playlist_list']);

        /*
        // Create cache dirs
        $cacheDir = __DIR__ . '/cache';
        $cacheDir .= '/' . date('Y');
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777);
        }

        $cacheDir .= '/' . date('m');
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777);
        }

        $cacheDir .= '/center-' . $this->deviceFields['device_center'];
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777);
        }

        $cacheDir .= '/playlist-' . $playlistId;
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777);
        }

        $fileName = $cacheDir . '/hour-' . $request->get_param('h') . '-' . $deviceId . '.json';
        */
        return $this->createPlaylist();

        if (!file_exists($fileName)) {
            $createdPlaylist = $this->createPlaylist();
            $createdPlaylist['create_time'] = date('Y-m-d H:i:s');

            $handle = fopen($fileName, "w+");
            fwrite($handle, json_encode($createdPlaylist));
            fclose($handle);

            return $createdPlaylist;
        } else {
            return json_decode(file_get_contents($fileName));
        }
    }

    /**
     * Get playlist id from device id
     *
     * @return int|null
     */
    public function getPlaylistId()
    {
        $playlistId = $this->deviceFields['device_playlist'];
        if (empty($playlistId) && !empty($this->centerFields['center_global_playlist'])) {
            $playlistId = $this->centerFields['center_global_playlist'];
        }

        return $playlistId;
    }

    /**
     * Validate weekday
     *
     * @param array $weekdays
     * @return bool
     */
    public function isWeekdayValid(array $weekdays)
    {
        $currentWeekday = null;
        switch (date('N')) {
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

    /**
     * Get vast url
     *
     * @return null
     */
    public function getVastUrl()
    {
	    if ($this->deviceId == 1679 || $this->deviceId == 2015) {
		    if ($_GET['h'] >= 20 || $_GET['h']< 8) {
			    return null;
		    }
	    }

	    if ($this->vastFallback === false) {
	        return null;
        }
	    
        $vast = $this->deviceFields['device_vast'];
        if (empty($vast) && !empty($this->centerFields['center_global_vast_url'])) {
            $vast = $this->centerFields['center_global_vast_url'];
            $deviceFields = $this->deviceFields;
            $vast = preg_replace_callback('/{([^}]+)}/', function ($matches) use ($deviceFields) {
                $match = $matches[1];
                if (isset($deviceFields[$match]) && !is_array($deviceFields[$match])) {
                    return $deviceFields[$match];
                }
                return '';
            }, $vast);

            $vast .= $this->deviceId;
        }

        return $vast ?: null;
    }

    /**
     * Exclude items by date and hour
     *
     * @param array $playlistItemsRaw
     */
    public function preparePlaylistEntriesBeforeCreate(array $playlistItemsRaw)
    {
        if ($this->monitoring) {
            $startTime = $this->h;
            $currentDate = $this->date;
        } else {
            $startTime = (int)$this->request->get_param('h');
            $currentDate = date('Y-m-d');
        }

        $playlistItems = [];
        foreach ($playlistItemsRaw as $playlistItem) {
            $item = new stdClass();
            $item->type = $playlistItem['acf_fc_layout'] == 'list_item_bild' ? 'image' : 'video';
            $item->advertiser_id = $playlistItem['advertiser_id'];
            $item->center = 0;
            $item->file = $playlistItem['file']['url'];

            // Set item duration
            if ($item->type == 'video') {
                $video = wp_read_video_metadata(get_attached_file($playlistItem['file']['id']));
                $item->duration = $video['length'];
            } else {
                $item->duration = $playlistItem['duration'];
            }


            // Validate date and time
            $playOuts = [];
            foreach ($playlistItem['period'] as $period) {
                if (date('Y-m-d', strtotime($period['startdate'])) <= $currentDate &&
                    date('Y-m-d', strtotime($period['enddate'])) >= $currentDate) {
                    $itemStatus = false;
                    foreach ($period['daytimes'] as $daytime) {
                        if ($this->isWeekdayValid($daytime['weekdays']) &&
                            (int)$startTime >= (int)$daytime['starthour'] &&
                            (int)$startTime <= (int)$daytime['endhour']) {
                            if (!empty($period['repeats_per_hour'])) {
                                $percentage = round(100 - (3600 - $this->getAvailableSeconds()) / (3600 / 100));
                                $playOuts[] = [
                                    'repeats_per_hour' => round(($period['repeats_per_hour'] / 100) * $percentage),
                                ];
                                $itemStatus = true;
                            } else {
                                $item->center = 1;
                                $itemStatus = true;
                            }
                        }
                    }
                    if (!$itemStatus) {
                        continue 2;
                    }
                } else {
                    continue 2;
                }
            }

            if (!empty($playOuts)) {
                $item->playOuts = $playOuts;
            }

            $playlistItems[] = $item;
        }

        $this->playlistItems = $playlistItems;
    }

    /**
     * Get only center items
     *
     * @return array
     */
    public function getCenterPlaylistItems()
    {
        $items = [];
        foreach ($this->playlistItems as $playlistItem) {
            if ($playlistItem->center) {
                $items[] = $playlistItem;
            }
        }
        return $items;
    }

    /**
     * Sum of center time
     *
     * @return float|int|mixed
     */
    public function getCenterPlaylistTime()
    {
        $availableSeconds = $this->getAvailableSeconds();
        return ($availableSeconds - $this->getAdPlaylistTime() - $this->getAdVastTime());
    }

    /**
     * Get sum of center playlist play outs
     *
     * @return int|mixed
     */
    public function getSumOfCenterPlaylistPlayOuts()
    {
        $targetTime = $this->getCenterPlaylistTime();
        $usedTime = 0;
        $items = $this->getCenterPlaylistItems();
        $playOuts = 0;
        $status = false;
        while ($status != true) {
            $item = $items[0];
            $usedTime += (int) $item->duration;

            // Change position of center items
            unset($items[0]);
            array_push($items, $item);
            $items = array_values($items);

            $playOuts++;

            if ($usedTime > $targetTime) {
                $status = true;
            }
        }

        return $playOuts;
    }

    /**
     * Get only ad (ads) items
     *
     * @return array
     */
    public function getAdPlaylistItems()
    {
        $items = [];
        foreach ($this->playlistItems as $playlistItem) {
            if (!$playlistItem->center) {
                $items[] = $playlistItem;
            }
        }
        return $items;
    }

    /**
     * Sum time of ad time
     *
     * @return float|int
     */
    public function getAdPlaylistTime()
    {
        $time = 0;
        $items = $this->getAdPlaylistItems();
        foreach ($items as $item) {
            foreach ($item->playOuts as $playOut) {
                $time += ceil($playOut['repeats_per_hour'] * (int) $item->duration);
            }
        }
        return $time;
    }

    /**
     * Get sum of ad playlist play outs
     *
     * @return int|mixed
     */
    public function getSumOfAdPlaylistPlayOuts()
    {
        $playOuts = 0;
        $items = $this->getAdPlaylistItems();
        foreach ($items as $item) {
            foreach ($item->playOuts as $playOut) {
                $playOuts += $playOut['repeats_per_hour'];
            }
        }
        return $playOuts;
    }

    /**
     * Calc ad time in seconds for vast url
     *
     * @return int|mixed
     */
    public function getAdVastTime()
    {
        $vastTime = 0;
        if ($this->getVastUrl() || $this->monitoring) {
            $adTime = $this->getAdPlaylistTime();
            $availableSeconds = $this->getAvailableSeconds();

            $vastTime = floor((($availableSeconds * 0.75) - $adTime) / 30) * 10;
            if ($vastTime > 600) {
                $vastTime = 600;
            }
        }
        return $vastTime;
    }

    /**
     * Calc available seconds left for current hour
     *
     * @return float|int
     */
    public function getAvailableSeconds()
    {
        $availableSeconds = 3600;

        if ($this->monitoring) {
            $currentMinute = $this->m;
        } else {
            $currentMinute = $this->request->get_param('m');
        }
        if ($currentMinute != 0) {
            $minutesLeft = 60 - $currentMinute;
            $availableSeconds = $minutesLeft * 60;
        }
        return $availableSeconds;
    }

    /**
     * Creates playlist by hour and minute
     *
     * @return array|WP_Error
     */
    public function createPlaylist()
    {
        if (empty($this->getCenterPlaylistItems())) {
            return new WP_Error('invalid_playlist', 'Center playlist item not found', array('status' => 404));
        }

        // Default vast item
        $vastItem = new stdClass();
        $vastItem->type = 'vast';
        $vastItem->url = $this->getVastUrl();

        $playlist = get_post($this->getPlaylistId());
        $playlistFields = get_fields($playlist);
        $vastItem->fallback = $playlistFields['playlist_vast_fallback'];

        if (!$vastItem->fallback || empty($vastItem->fallback['id'])) {
            $this->vastFallback = false;
        }

        if ($this->getVastUrl()) {
            if (!$vastItem->fallback || empty($vastItem->fallback['id'])) {
                return new WP_Error('invalid_playlist', 'Vast fallback item not found', array('status' => 404));
            }
        }

        $adPlayOuts = $this->getSumOfAdPlaylistPlayOuts();
        $centerPlayOuts = $this->getSumOfCenterPlaylistPlayOuts();
        $vastPlayOuts = $this->getAdVastTime() / 10;

        if ($adPlayOuts > 0) {
            $playOutsBetweenAdAndCenter = floor($centerPlayOuts / $adPlayOuts);
            $playOutsBetweenAdAndCenterFirstCycle = $centerPlayOuts % $adPlayOuts;
            $playOutsBetweenAdAndCenterFirstCycleCounter = 0;
        } else {
            $playOutsBetweenAdAndCenter = $centerPlayOuts;
            $playOutsBetweenAdAndCenterFirstCycle = 0;
            $playOutsBetweenAdAndCenterFirstCycleCounter = 0;
        }

        if ($vastPlayOuts > 0) {
            if ($adPlayOuts > 0) {
                $playOutsBetweenPlaylistAndVast = floor(($adPlayOuts + $centerPlayOuts) / $vastPlayOuts);
                $playOutsBetweenPlaylistAndVastFirstCycle = ($adPlayOuts + $centerPlayOuts) % $vastPlayOuts;
                $playOutsBetweenPlaylistAndVastFirstCycleCounter = 0;
            } else {
                $playOutsBetweenPlaylistAndVast = floor($centerPlayOuts / $vastPlayOuts);
                $playOutsBetweenPlaylistAndVastFirstCycle = ($centerPlayOuts % $vastPlayOuts);
                $playOutsBetweenPlaylistAndVastFirstCycleCounter = 0;
            }
        }

        $adItems = $this->getAdPlaylistItems();
        $centerItems = $this->getCenterPlaylistItems();

        $counterAdPlayOuts = 0;
        $counterCenterPlayOuts = 0;
        $counterVastPlayOuts = 0;

        $items = [];
        $status = false;

        $itemCounter = 0;

        while ($status != true) {
            if ($adPlayOuts > 0) {
                // Push ad playlist item with aid
                $adItem = $adItems[0];
                $items[] = $adItem;

                // Change position of ad items
                unset($adItems[0]);
                array_push($adItems, $adItem);
                $adItems = array_values($adItems);

                // Set item counter
                $itemCounter++;
            }

            // Push default vast item
            if ($counterVastPlayOuts < $vastPlayOuts) {
                $nthIteration = $playOutsBetweenPlaylistAndVast;
                if ($playOutsBetweenPlaylistAndVastFirstCycleCounter < $playOutsBetweenPlaylistAndVastFirstCycle) {
                    $nthIteration++;
                }
                if ($itemCounter % $nthIteration == 0) {
                    $items[] = $vastItem;
                    $counterVastPlayOuts++;
                    $playOutsBetweenPlaylistAndVastFirstCycleCounter++;
                }
            }

            // Push center content and vast
            $playOutIteration = $playOutsBetweenAdAndCenter;
            if ($playOutsBetweenAdAndCenterFirstCycleCounter < $playOutsBetweenAdAndCenterFirstCycle) {
                $playOutIteration++;
                $playOutsBetweenAdAndCenterFirstCycleCounter++;
            }

            for ($i = 1; $i <= $playOutIteration; $i++) {
                if ($counterCenterPlayOuts != $centerPlayOuts) {
                    // Push center playlist item with aid
                    $centerItem = $centerItems[0];
                    $items[] = $centerItem;

                    // Set item counter
                    $itemCounter++;

                    // Change position of center items
                    unset($centerItems[0]);
                    array_push($centerItems, $centerItem);
                    $centerItems = array_values($centerItems);

                    // Push default vast item
                    if ($counterVastPlayOuts < $vastPlayOuts) {
                        $nthIteration = $playOutsBetweenPlaylistAndVast;
                        if ($playOutsBetweenPlaylistAndVastFirstCycleCounter < $playOutsBetweenPlaylistAndVastFirstCycle) {
                            $nthIteration++;
                        }
                        if ($itemCounter % $nthIteration == 0) {
                            $items[] = $vastItem;
                            $counterVastPlayOuts++;
                            $playOutsBetweenPlaylistAndVastFirstCycleCounter++;
                        }
                    }

                    $counterCenterPlayOuts++;
                }
            }

            $counterAdPlayOuts++;

            if ($counterAdPlayOuts >= $adPlayOuts) {
                $status = true;
            }
        }

        // Calc total used time
        $usedTimeBeforePrepare = $this->getTotalTimeOfItems($items);
        if ($usedTimeBeforePrepare > $this->getAvailableSeconds()) {
            $diff = $usedTimeBeforePrepare - $this->getAvailableSeconds();
            $numberToReduce = 1;
            if ($diff > $centerPlayOuts) {
                $numberToReduce = ceil($diff / $centerPlayOuts);
            }

            // Prepare center duration
            $newItems = [];
            foreach ($items as $item) {
                if ($item->center && $diff > 0) {
                    $reduce = $numberToReduce;
                    if ($diff - $numberToReduce < 0) {
                        $reduce = 1;
                    }

                    $clonedItem = clone $item;
                    $clonedItem->duration -= $reduce;
                    $newItems[] = $clonedItem;
                    $diff -= $reduce;
                } else {
                    $newItems[] = $item;
                }
            }
            $items = $newItems;
        }

        // Set play time
        $finalItems = $items;
        /*$finalItems = [];
        $dateTime = new \DateTime();
        //$dateTime->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $dateTime->setTime($this->request->get_param('h'), $this->request->get_param('m'));
        $timestamp = $dateTime->getTimestamp();

        foreach ($items as $item) {
            $item->timestamp = $timestamp;
            if ($item->type == 'vast') {
                $timestamp += 10;
            } else {
                $timestamp += $item->duration;
            }
            $finalItems[] = $item;
        }*/

        // Add info on end of the array
        if ($this->monitoring || $this->request->get_param('info')) {
            array_push($finalItems, [
                'used_time_before_prepare' => $usedTimeBeforePrepare,
                'used_time_after_prepare' => $this->getTotalTimeOfItems($finalItems),
                'available_seconds' => $this->getAvailableSeconds(),
                'calculated_ad_time' => $this->getAdPlaylistTime(),
                'ad_time' => $this->getTotalTimeOfAdTime($finalItems),
                'calculated_ad_play_outs' => $adPlayOuts,
                'ad_play_outs' => $this->getTotalTimeOfAdPlayOuts($finalItems),
                'calculated_center_time' => $this->getCenterPlaylistTime(),
                'center_time' => $this->getTotalTimeOfCenterTime($finalItems),
                'calculated_center_play_outs' => $centerPlayOuts,
                'center_play_outs' => $this->getTotalTimeOfCenterPlayOuts($finalItems),
                'calculated_vast_play_outs' => $vastPlayOuts,
                'vast_play_outs' => $this->getTotalTimeOfVastPlayOuts($finalItems),
                'calculated_vast_time' => $this->getAdVastTime(),
                'vast_time' => $this->getTotalTimeOfVastTime($finalItems),
            ]);
        }

        return $finalItems;
    }

    /**
     * Calc total number of vast play outs
     *
     * @param array $items
     * @return int
     */
    public function getTotalTimeOfVastPlayOuts(array $items)
    {
        $playOuts = 0;
        foreach ($items as $item) {
            if ($item->type == 'vast') {
                $playOuts++;
            }
        }
        return $playOuts;
    }

    /**
     * Calc total time of vast play outs
     *
     * @param array $items
     * @return int
     */
    public function getTotalTimeOfVastTime(array $items)
    {
        $time = 0;
        foreach ($items as $item) {
            if ($item->type == 'vast') {
                $time += 10;
            }
        }
        return $time;
    }

    /**
     * Calc total number of center play outs
     *
     * @param array $items
     * @return int
     */
    public function getTotalTimeOfCenterPlayOuts(array $items)
    {
        $playOuts = 0;
        foreach ($items as $item) {
            if ($item->center && $item->type != 'vast') {
                $playOuts++;
            }
        }
        return $playOuts;
    }

    /**
     * Calc total time of center play outs
     *
     * @param array $items
     * @return int
     */
    public function getTotalTimeOfCenterTime(array $items)
    {
        $time = 0;
        foreach ($items as $item) {
            if ($item->center && $item->type != 'vast') {
                $time += $item->duration;
            }
        }
        return $time;
    }

    /**
     * Calc total number of ad play outs
     *
     * @param array $items
     * @return int
     */
    public function getTotalTimeOfAdPlayOuts(array $items)
    {
        $playOuts = 0;
        foreach ($items as $item) {
            if (!$item->center && $item->type != 'vast') {
                $playOuts++;
            }
        }
        return $playOuts;
    }

    /**
     * Calc total time of ad play outs
     *
     * @param array $items
     * @return int
     */
    public function getTotalTimeOfAdTime(array $items)
    {
        $time = 0;
        foreach ($items as $item) {
            if (!$item->center && $item->type != 'vast') {
                $time += $item->duration;
            }
        }
        return $time;
    }

    /**
     * Calc total time
     *
     * @param array $items
     * @return int
     */
    public function getTotalTimeOfItems(array $items)
    {
        $time = 0;
        foreach ($items as $item) {
            if ($item->type == 'vast') {
                $time += 10;
            } else {
                $time += (int) $item->duration;
            }
        }
        return $time;
    }
}
