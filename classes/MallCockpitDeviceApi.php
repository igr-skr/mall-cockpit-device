<?php

/**
 * Class MallCockpitDeviceApi
 */
class MallCockpitDeviceApi
{
    /**
     * @return void
     */
    public function init()
    {
        add_action('rest_api_init', function () {
            register_rest_route('mall-cockpit-devices', '/devices', array(
                'methods' => 'GET',
                'callback' => [$this, 'devices'],
            ));
        });

        add_action('rest_api_init', function () {
            register_rest_route('mall-cockpit-devices', '/devices-extended', array(
                'methods' => 'GET',
                'callback' => [$this, 'devicesExtended'],
            ));
        });
    }

    /**
     * @return array
     */
    public function devicesExtended()
    {
        $devices = [];
        $devicesQuery = new WP_Query([
            'post_type' => 'devices',
            'posts_per_page' => -1
        ]);
        if ($devicesQuery->have_posts()) {
            while ($devicesQuery->have_posts()) {
                $devicesQuery->the_post();

                $deviceFields = get_fields(get_the_ID());

                // change times
                $times = [];
                foreach (get_field('center_opening_times', $deviceFields['device_center']) as $timesArray) {
                    $newTimesArray = [];
                    foreach ($timesArray as $timeArrayKey => $timeArrayKeyValue) {
                        $newTimesArray[str_replace('center_opening_time_', '', $timeArrayKey)] = $timeArrayKeyValue;
                    }
                    $times[] = $newTimesArray;
                }

                if ((int) $deviceFields['device_programmatic'] === 1) {
                    $devices[] = [
                        'player_id' => get_the_ID(),
                        'player_name' => $deviceFields['device_name'],
                        'number_of_screens' => (int) $deviceFields['device_number_of_screens'] ?: 1,
                        'programmatic' => (int) $deviceFields['device_programmatic'],
                        'active' => (int) $deviceFields['device_active'],
                        'status' => (int) $deviceFields['device_status'] ? 9 : 1,
                        'device_id' => $deviceFields['device_directoryAPI'],
                        'device_location' => $deviceFields['device_location'],
                        'location' => get_field('center_name', $deviceFields['device_center']),
                        'city' => get_field('center_city', $deviceFields['device_center']),
                        'address' => get_field('center_address', $deviceFields['device_center']),
                        'location_id' => get_field('center_place_id', $deviceFields['device_center']),
                        'address_number' => get_field('center_address_number', $deviceFields['device_center']),
                        'zipcode' => get_field('center_zipcode', $deviceFields['device_center']),
                        'country' => 'DE',
                        'geolocation' => get_field('center_coordinates', $deviceFields['device_center']) ?: [],
                        'times' => $times,
                        'width' => ($deviceFields['device_dimension'] == 'Landscape' ? 1920 : 1080),
                        'height' => ($deviceFields['device_dimension'] == 'Portrait' ? 1920 : 1080),
                        /*'height' => ($deviceFields['device_dimension'] == 'Landscape' ? 1080 : 1920),*/
                    ];
                }
            }
        }
        return $devices;
    }

    /**
     * @return array
     */
    public function devices()
    {
        $devices = [];
        $devicesQuery = new WP_Query([
            'post_type' => 'devices',
            'posts_per_page' => -1
        ]);
        if ($devicesQuery->have_posts()) {
            while ($devicesQuery->have_posts()) {
                $devicesQuery->the_post();

                $deviceFields = get_fields(get_the_ID());
                $centerFields = get_fields($deviceFields['device_center']);

                $kioskUrl = $deviceFields['device_kioskUrl'];
                if (empty($kioskUrl) && !empty($centerFields['center_global_kiosk_url'])) {
                    $kioskUrl = $centerFields['center_global_kiosk_url'];
                    $kioskUrl = preg_replace_callback('/{([^}]+)}/', function ($matches) use ($deviceFields) {
                        $match = $matches[1];
                        if (isset($deviceFields[$match]) && !is_array($deviceFields[$match])) {
                            return $deviceFields[$match];
                        }
                        return '';
                    }, $kioskUrl);
                }

                $playlist = $deviceFields['device_playlist'];
                if (empty($playlist) && !empty($centerFields['center_global_playlist'])) {
                    $playlist = $centerFields['center_global_playlist'];
                }

                $devices[] = [
                    'apptype' => $deviceFields['device_apptype'],
                    'name' => $deviceFields['device_name'],
                    'center' => get_field('center_shortname', $deviceFields['device_center']),
                    'directoryAPI' => $deviceFields['device_directoryAPI'],
                    'heartbeatEvery' => $deviceFields['device_heartbeatEvery'],
                    'offlinePing' => $deviceFields['device_heartbeatEvery'],
                    'location' => $deviceFields['device_location'],
                    'kioskUrl' => $kioskUrl,
                    'playlistID' => $playlist,
                    'locationID' => $deviceFields['device_locationID'],
                    'restartEvery' => $deviceFields['device_restartEvery'],
                    'rotation' => $deviceFields['device_rotation'],
                    'device_serialnumber' => $deviceFields['device_serialnumber'],
                    'version' => 'v2',
                ];
            }
        }
        return $devices;
    }
}