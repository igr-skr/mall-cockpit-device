<?php
$deviceId = $_GET['player3'];
if (strpos($deviceId, '?') !== false) {
    $deviceId = substr($deviceId, 0, strpos($deviceId, '?'));
}
$deviceFields = MallCockpitDevicePlayer2::getDeviceFieldsOrDie($deviceId);

if (isset($_GET['vast'])) {
    require_once __DIR__ . '/classes/MallCockpitDeviceVastParser.php';

    $parser = new MallCockpitDeviceVastParser($_GET['vast'], $deviceId);
    $parser->output($deviceId);
} elseif (!empty($_GET['report'])) {
    //MallCockpitDeviceReport::addReport($deviceId, $_GET['report']);
    exit("success");
} elseif (!empty($_GET['setVersion'])) {
    MallCockpitDeviceReport::setVersion($deviceId, $_GET['setVersion']);
    exit("success");
} elseif (!empty($_GET['ping'])) {
    MallCockpitDeviceReport::setPing($deviceId);
    exit("success");
} elseif (!empty($_GET['errorName'])) {
    $handle = fopen(__DIR__ . "/logs/js-" . $deviceId . "-".date('Y-m-d').".txt", "a");
    fwrite($handle,
        date('Y-m-d H:i:s') . " " .
        $_GET['desc'] . "\n" . $_GET['errorName'] . "\n" . $_GET['errorMessage'] . "\n" . $_GET['errorStack'] . "\n\n");
    fclose($handle);

    exit("success_");
}

$dimension = [1080, 1920];
if ($deviceFields['device_dimension'] == 'Landscape') {
    $dimension = [1920, 1080];
}


$pluginUrl = plugin_dir_url( __FILE__ );

$splashImage = '';
if ($deviceFields['device_dimension'] == 'Landscape') {
    $splashImage = $pluginUrl . '/mall-cockpit-dooh-landscape-splash.jpg';
    if (!empty($deviceFields['splash_landscape'])) {
        $splashImage = $deviceFields['splash_landscape'];
    }
} else {
    $splashImage = $pluginUrl . '/mall-cockpit-dooh-portrait-splash.jpg';
    if (!empty($deviceFields['splash_portrait'])) {
        $splashImage = $deviceFields['splash_portrait'];
    }
}
?>
<html>
<head>
    <meta http-equiv="Cache-control" content="public">
    <link rel="stylesheet" type="text/css" href="<?= $pluginUrl ?>/assets/css/player.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/openplayerjs@latest/dist/openplayer.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <script type="text/javascript" src="<?= $pluginUrl ?>/assets/js/html5vast.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/openplayerjs@latest/dist/openplayer.min.js"></script>
    <script type="text/javascript" src="<?= $pluginUrl ?>/assets/js/mall-cockpit.player.js"></script>
    <script type="text/javascript" src="<?= $pluginUrl ?>/assets/js/mall-cockpit.playlist.js"></script>
    <script type="text/javascript" src="<?= $pluginUrl ?>/assets/js/mall-cockpit.preloader2.js"></script>
    <script type="text/javascript" src="<?= $pluginUrl ?>/assets/js/mall-cockpit.vast.js"></script>
    <style type="text/css">
        body, html {
            width: <?= $dimension[0]; ?>px;
            height: <?= $dimension[1]; ?>px;
            background: url('<?= $splashImage ?>') top left no-repeat;
        }

        video, img {
            width: <?= $dimension[0]; ?>px;
            height: <?= $dimension[1]; ?>px;
        <?php /*if ($deviceId == 878) { ?>
            transform: rotate(-90deg);
            position: absolute;
            margin-top: -<?= ($dimension[0]/2); ?>px;
            margin-left: -<?= ($dimension[0]/2); ?>px;
            top: 50%;
            left: 50%;
            transform-origin: 50% 50%;
        <?php }*/ ?>
        }

        <?php
            if (!empty($deviceFields['device_player_css'])) {
                echo $deviceFields['device_player_css'];
            }
        ?>
    </style>
</head>
<body>
<script>
    window.onload = function() {
        window.player = new $.MallCockpitPlayer(
            <?=$deviceId ?>,
            '<?=strtolower($deviceFields['device_dimension']) ?>',
            '<?=$pluginUrl ?>',
            <?php if (isset($_GET['debug'])) { echo 'true'; } else { echo 'false'; }; ?>,
            <?php if (isset($_GET['memory'])) { echo 'true'; } else { echo 'false'; }; ?>
        );
    }
</script>
<div id="banner"></div>
<div id="blackscreen" style="z-index:999999;background:#000;top:0;left:0;position:fixed;width:100%;height:100%;display:none;"></div>
</body>
</html>