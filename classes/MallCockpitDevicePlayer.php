<?php

/**
 * Class MallCockpitDevicePlayer
 */
class MallCockpitDevicePlayer
{

    /**
     * @param $url
     */
    public static function loadVastXml($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ((intval($httpCode) === 200 || intval($httpCode) === 204) && !empty($output)) {
            header("Content-type: text/xml");
            echo $output;

            /*$to      = 'birim.karaustaoglu@gmail.com';
            $subject = 'ssp adpack xml';
            $message = $output;
            $headers = 'From: no-reply@mall-cockpit.de' . "\r\n" .
                'Reply-To: no-reply@mall-cockpit.de' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

            @mail($to, $subject, $message, $headers);*/

        } else {
            echo "none";
        }
        exit;
    }

    /**
     * @param $deviceId
     * @return array|bool
     */
    public static function getDeviceFieldsOrDie($deviceId)
    {
        if (!is_numeric($deviceId)) {
            self::abort('invalid device id', 400);
        }

        $device = get_post($deviceId);
        if (!$device || $device->post_type != 'devices') {
            self::abort('device id not found', 400);
        }

        $deviceFields = get_fields($device);
        $centerFields = get_fields($deviceFields['device_center']);

        if ($deviceFields['device_apptype'] != 'advertiser') {
            self::abort('device app type is invalid', 400);
        }

        $playlistId = $deviceFields['device_playlist'];
        if (empty($playlistId) && !empty($centerFields['center_global_playlist'])) {
            $playlistId = $centerFields['center_global_playlist'];
        }

        if (empty($playlistId)) {
            self::abort('playlist not found', 400);
        }

        $playlist = get_post($playlistId);
        if (!$playlist) {
            self::abort('playlist not found', 400);
        }

        $deviceFields['splash_portrait'] =
            !empty($centerFields['center_placeholder_image_portrait']) ?
                $centerFields['center_placeholder_image_portrait'] : null;

        $deviceFields['splash_landscape'] =
            !empty($centerFields['center_placeholder_image_landscape']) ?
                $centerFields['center_placeholder_image_landscape'] : null;

        return $deviceFields;
    }

    /**
     * @param $message
     * @param $httpCode
     */
    public static function abort($message, $httpCode)
    {
        http_response_code($httpCode);
        echo $message;
        exit;
    }

    /**
     * @return void
     */
    public function init()
    {
        add_action('template_redirect', [$this, 'playerEndpoint']);

        $this->playerEndpoint();
    }

    /**
     * @return void
     */
    public function playerEndpoint()
    {
        $hook = current_filter();

        if ('template_redirect' === $hook && get_query_var('player')) {
            require_once __DIR__ . '/../MallCockpitPlayer.php';
            exit;
        }

        'init' === $hook && add_rewrite_endpoint('player', EP_ROOT);
    }
}
