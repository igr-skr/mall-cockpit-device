<?php

/**
 * Class MallCockpitDeviceReport
 */
class MallCockpitDeviceReport
{
    /**
     * @return void
     */
    public function init()
    {
        add_menu_page(
            'Reports',
            'Reports',
            'manage_options',
            'report',
            function () {
                require_once __DIR__ . '/../views/reports.php';
            },
            'dashicons-media-spreadsheet',
            30
        );
    }

    public static function addLog($message, $deviceId)
    {
        $messages = get_field('error_logs', $deviceId);
        $messages = date('d.m.Y H:i:s') . ' - ' . $message . "\n" . $messages;

        update_field('error_logs', $messages, $deviceId);
    }

    public static function setPing($deviceId)
    {
        update_field('ping', date('Y-m-d H:i:s'), $deviceId);

        update_field('mbps', (!empty($_GET['mbps']) ? $_GET['mbps'] : false), $deviceId);
        update_field('connectionType', (!empty($_GET['ct']) ? $_GET['ct'] : false), $deviceId);
    }

    public static function setTrackRoute($deviceId, $from, $to)
    {
        update_field('track_route', $from . " to " . $to, $deviceId);
    }

    public static function setLastPage($deviceId, $page)
    {
        update_field('last_page', $page, $deviceId);
    }

    public static function setVersion($deviceId, $version)
    {
        update_field('code_version', $version, $deviceId);
    }

    public static function addReportMediaFile($deviceId, $media)
    {
        global $wpdb;

        $wpdb->insert("{$wpdb->base_prefix}reportings", [
            'device_id' => $deviceId,
            'media' => $media,
            'played_at' => gmdate('Y-m-d H:i:s')
        ]);
    }

    public static function addReportVast($deviceId)
    {
        global $wpdb;

        $wpdb->insert("{$wpdb->base_prefix}reportings", [
            'device_id' => $deviceId,
            'vast' => 1,
            'played_at' => gmdate('Y-m-d H:i:s')
        ]);
    }

    /**
     * @param $deviceId
     * @param $advertiser_id
     */
    public static function addReport($deviceId, $advertiser_id)
    {
        global $wpdb;

        $wpdb->insert("{$wpdb->base_prefix}reportings", [
            'device_id' => $deviceId,
            'aid' => $advertiser_id,
            'played_at' => gmdate('Y-m-d H:i:s')
        ]);
    }

    /**
     * @param $from
     * @param $to
     * @return array|object|null
     */
    public static function getReports($from, $to)
    {
        global $wpdb;
        $result = $wpdb->get_results("SELECT * FROM {$wpdb->base_prefix}reportings
            WHERE date_format(played_at, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($from)) . "' AND 
            date_format(played_at, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($to)) . "'");

        return $result;
    }
}
