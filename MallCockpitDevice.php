<?php
/*
Plugin Name: Mall-Cockpit Devices Plugin
Description: Manage Devices
Version: 1.1
Author: SawatzkiMühlenbruch GmbH
Author URI: https://www.sawatzki-muehlenbruch.de/
*/
define('MC_DEVICE_PLUGIN_DIR', __FILE__);
define('MC_DEVICE_VERSION', '3.9.0');
define('MC_DEVICE_VERSION_AD', '1.4.0');

function device_ping_control()
{
    $devices = [];
    $header = 'From: norepy@dooh.mall-cockpit.de' . "\r\n" .
        'Reply-To: norepy@dooh.mall-cockpit.de' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    $devicesQuery = new WP_Query([
        'post_type' => 'devices',
        'posts_per_page' => -1
    ]);
    if ($devicesQuery->have_posts()) {
        while ($devicesQuery->have_posts()) {
            $devicesQuery->the_post();
            $date = get_field('ping', get_the_ID());
            if (!empty($date)) {
                $dateTime = time() - strtotime($date);
                if ($dateTime > 300 && get_the_ID() == 1679) {
                    $dateOutput = date('d.m - H:i', (strtotime($date) + 7200));
                    $name = get_field('device_name', get_the_ID());
                    $devices[] = $name . ' - ' . $dateOutput;
                }
            }
        }
    }
    if (count($devices)) {
        $mailSubject = 'DOOH '.(count($devices)).' Geräte reagieren nicht mehr (' . $dateOutput = date('d.m - H:i', (time() + 7200)) . ')';
        $mailText = "Folgende Geräte reagieren nicht mehr:\n\n" . implode("\n", $devices);
        //mail('birim.karaustaoglu@gmail.com', $mailSubject, $mailText, $header);
        mail('sb@schickma.de', $mailSubject, $mailText, $header);
        mail('656a1235.schickma.de@emea.teams.ms', $mailSubject, $mailText, $header);
    }
}
add_action('device_ping_control', 'device_ping_control');

add_action('admin_menu', function () {
    add_menu_page('Status', 'Status ', 'manage_options', 'dooh-status', function () {
        include __DIR__ . '/MallCockpitStatus.php';
    }, 'dashicons-image-filter', 3);
});

add_action('init', function () {
    require_once dirname(__FILE__) . '/classes/MallCockpitDeviceSetup.php';

    $mallCockpitDeviceSetup = new MallCockpitDeviceSetup();
    if ($mallCockpitDeviceSetup->init()) {
        // Device post type
        require_once dirname(__FILE__) . '/classes/MallCockpitDevice.php';
        $mallCockpitDevice = new MallCockpitDevice();
        $mallCockpitDevice->init();

        // Center post type
        require_once dirname(__FILE__) . '/classes/MallCockpitDeviceCenter.php';
        $mallCockpitDeviceCenter = new MallCockpitDeviceCenter();
        $mallCockpitDeviceCenter->init();

        // Device API
        require_once dirname(__FILE__) . '/classes/MallCockpitDeviceApi.php';
        $mallCockpitDeviceApi = new MallCockpitDeviceApi();
        $mallCockpitDeviceApi->init();

        // Playlist
        require_once dirname(__FILE__) . '/classes/MallCockpitDevicePlaylist.php';
        $mallCockpitDevicePlaylist = new MallCockpitDevicePlaylist();
        $mallCockpitDevicePlaylist->init();

        // Player
        require_once dirname(__FILE__) . '/classes/MallCockpitDevicePlayer.php';
        $mallCockpitDevicePlayer = new MallCockpitDevicePlayer();
        $mallCockpitDevicePlayer->init();

        require_once dirname(__FILE__) . '/classes/MallCockpitDevicePlayer2.php';
        $mallCockpitDevicePlayer2 = new MallCockpitDevicePlayer2();
        $mallCockpitDevicePlayer2->init();

        // Report
        require_once dirname(__FILE__) . '/classes/MallCockpitDeviceReport.php';
        $mallCockpitDeviceReport = new MallCockpitDeviceReport();
        $mallCockpitDeviceReport->init();

        // Monitoring
        require_once dirname(__FILE__) . '/classes/MallCockpitDevicePlaylistMonitoring.php';
        $mallCockpitDeviceReport = new MallCockpitDevicePlaylistMonitoring();
        $mallCockpitDeviceReport->init();

        // Wizard
        require_once dirname(__FILE__) . '/classes/MallCockpitDeviceWizard.php';
        $mallCockpitDeviceWizard = new MallCockpitDeviceWizard();
        $mallCockpitDeviceWizard->init();

        // Taxonomy
        require_once dirname(__FILE__) . '/classes/MallCockpitDeviceTaxonomy.php';
        $mallCockpitDeviceTaxonomy = new MallCockpitDeviceTaxonomy();
        $mallCockpitDeviceTaxonomy->init();

    } else {
        // Deactivate plugin
        deactivate_plugins(plugin_basename(__FILE__));
    }
});
