<?php
$deviceId = $_GET['player'];
if (strpos($deviceId, '?') !== false) {
    $deviceId = substr($deviceId, 0, strpos($deviceId, '?'));
}
$deviceFields = MallCockpitDevicePlayer::getDeviceFieldsOrDie($deviceId);

if (isset($_GET['vast'])) {
    MallCockpitDevicePlayer::loadVastXml($_GET['vast']);
} elseif (!empty($_GET['report'])) {
    //MallCockpitDeviceReport::addReport($deviceId, $_GET['report']);
    exit("success");
} elseif (!empty($_GET['setVersion'])) {
    MallCockpitDeviceReport::setVersion($deviceId, $_GET['setVersion']);
    exit("success");
} elseif (!empty($_GET['ping'])) {
    MallCockpitDeviceReport::setPing($deviceId);
    exit("success");
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <script type="text/javascript" src="<?= $pluginUrl ?>/assets/js/html5vast.js"></script>
    <script type="text/javascript" src="<?= $pluginUrl ?>/assets/js/mall-cockpit.player.js"></script>
    <script type="text/javascript" src="<?= $pluginUrl ?>/assets/js/mall-cockpit.playlist.js"></script>
    <script type="text/javascript" src="<?= $pluginUrl ?>/assets/js/mall-cockpit.preloader.js"></script>
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
</body>
</html>