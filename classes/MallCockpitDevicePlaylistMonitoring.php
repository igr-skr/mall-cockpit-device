<?php

/**
 * Class MallCockpitDevicePlaylist
 */
class MallCockpitDevicePlaylistMonitoring
{
    /**
     * @return void
     */
    public function init()
    {
        if (!empty($_GET['post'])) {
        /*add_action('add_meta_boxes', function($postType) {
           if ($postType === 'playlist') {
                add_meta_box(
                    'dooh_playlists_meta',
                    'Playlist Status',
                    [$this, 'showPlaylistStatus'],
                    $postType,
                    'normal',
                    'high'
                );
            }
        });*/
        }
    }

    public function showPlaylistStatus()
    {
        $currentDate = new \DateTime();
        ?>
        <style>
            #dooh_playlists_meta .inside {
                overflow-x: scroll;
                overflow-y: scroll;
                max-height: 560px;
            }
            #playlist-status {
                width: 100%;
                min-width: 1400px;
            }
            #playlist-status ul {
                list-style-type: none;
                margin: 15px 0 0 0;
                padding: 0;
                display: block;
                width: 100%;
            }
            #playlist-status ul li {
                float: left;
                width: 13%;
            }
            #playlist-status ul li:first-child {
                width: 9%;
            }
            #playlist-status .playlist-status-date {
                width: 100%;
                height: 50px;
                font-size: 14px;
                text-align: center;
                line-height: 50px;
                color: #808080;
            }
            #playlist-status .playlist-time {
                line-height: 167px;
                text-align: right;
                padding-right: 5px;
                color: #808080;
                height: 167px;
            }
            .playlist-time-container {
                height: 157px;
                width: 100%;
            }
            .playlist-time-result {
                height: calc(100% - 20px);
                width: calc(100% - 20px);
                border-radius: 5px;
                box-shadow: 0 3px 6px #0000001F;
                margin: 10px;
            }
            .playlist-time-result.green {
                background:rgba(72,244,118,0.1);
            }
            .playlist-time-result.orange {
                background:rgba(244,178,72,0.1);
            }
            .playlist-time-result.red {
                background:rgba(244,72,72,0.1);
            }
            .playlist-time-result div {
                line-height: 20px;
            }

            .playlist-time-result span {
                font-size: 9px;
                display: inline-block;
                width: 48px;
                text-align: left;
                border-left: transparent 5px solid;
            }
            .playlist-time-result-item {
                height: 27px;
                line-height: 27px !important;
                margin: 0px 11px 0 11px;
                text-align:right;
                display: block;
                padding: 5px;
                font-weight: bold;
                font-size: 11px;
                text-indent: 5px;
            }
            .playlist-time-result-item small {
                float: left;
                font-size: 8px;
                font-weight: normal !important;
            }
            .playlist-time-result-item.green span {
                border-left: #48F476 5px solid;
                color: #333 !important;
            }
            .playlist-time-result-item.orange span {
                border-left: background: #F4B248 5px solid;
                color: #333 !important;
            }
            .playlist-time-result-item.red span {
                border-left: #F44848 5px solid;
                color: #333 !important;
            }
            .playlist-time-result-sum span,
            .playlist-time-result-item span {
                float: left;
            }
            .playlist-time-result-sum {
                height: 17px;
                line-height: 17px !important;
                text-align:right;
                display: block;
                font-weight: bold;
                font-size: 14px;
                padding: 2px 15px 5px 25px;
            }
            .playlist-time-result-sum.green {
                color: #48F476;
            }
            .playlist-time-result-sum.orange {
                color: #F4B248;
            }
            .playlist-time-result-sum.red {
                color: #F44848;
            }
            .playlist-time-result-sum span {
                color: black;
                font-weight: bold !important;
            }
        </style>
        <div id="playlist-status">
            <ul>
                <li>
                    <div class="playlist-status-date">
                        &nbsp;
                    </div>
                    <?php for ($i = 6; $i <= 22; $i++) { ?>
                        <div class="playlist-time">
                            <?php if ((date('H')+2) == $i) {
                                echo '<span style="color:black;font-weight:bold;">'.$i.' (Jetzt)</span>';
                            } else {
                                echo $i . ' Uhr';
                            } ?>
                        </div>
                    <?php } ?>
                </li>
                <?php
                    $playlist = new MallCockpitDevicePlaylist();
                ?>
                <?php for ($i = 0; $i < 7; $i++) { ?>
                    <li>
                        <div class="playlist-status-date">
                            <?php if ($currentDate->format('d.m') == date('d.m')) {
                                echo '<span style="color:black;font-weight:bold;">'.$currentDate->format('d.m.').' (Heute)</span>';
                            } else {
                                echo $currentDate->format('d.m.');
                            } ?>
                        </div>
                        <?php for ($idx = 6; $idx <= 22; $idx++) {
                            $result = $playlist->monitoring($_GET['post'], $currentDate->format('Y-m-d'), $idx, 0);
                            $result = end($result);
                        ?>
                            <div class="playlist-time-container">
                                <?php
                                $timeSum = $result['calculated_vast_time'] + $result['calculated_ad_time'] + 900;
                                $percentage = $timeSum / (3600 / 100);
                                $color = '';
                                if ($percentage < 75) {
                                    $color = 'orange';
                                }
                                if ($percentage < 50) {
                                    $color = 'green';
                                }
                                if ($percentage > 100) {
                                    $color = 'red';
                                }
                                ?>
                                <div class="playlist-time-result <?= $color ?>">
                                    <?php
                                    $percentage = $result['calculated_vast_time'] / (600 / 100);
                                    $color = '';
                                    if ($percentage < 75) {
                                        $color = 'orange';
                                    }
                                    if ($percentage < 50) {
                                        $color = 'green';
                                    }
                                    if ($percentage > 100) {
                                        $color = 'red';
                                    }
                                    ?>
                                    <div class="playlist-time-result-item <?=$color ?>">
                                        <span>VAST</span>
                                        <?php if (round($percentage) != 100) { ?>
                                            <small><?php if (round($percentage)>100) {
                                                echo '+' . ($result['calculated_vast_time'] - 600); } else {
                                                echo '-' . (600 - $result['calculated_vast_time']);
                                            } ?>s</small>
                                        <?php } ?>
                                        <?= round($percentage); ?>%
                                    </div>

                                    <?php
                                    $percentage = $result['calculated_ad_time'] / (2100 / 100);
                                    $color = '';
                                    if ($percentage < 75) {
                                        $color = 'orange';
                                    }
                                    if ($percentage < 50) {
                                        $color = 'green';
                                    }
                                    if ($percentage > 100) {
                                        $color = 'red';
                                    }
                                    ?>
                                    <div class="playlist-time-result-item <?=$color ?>"><span>CLASSIC</span>
                                        <?php if (round($percentage) != 100) { ?>
                                            <small><?php if (round($percentage)>100) {
                                                    echo '+' . ($result['calculated_ad_time'] - 2100); } else {
                                                    echo '-' . (2100 - $result['calculated_ad_time']);
                                                } ?>s</small>
                                        <?php } ?>
                                        <?= round($percentage); ?>%
                                    </div>

                                    <?php
                                    $percentage = $result['calculated_center_time'] / (900 / 100);
                                    $color = 'red';
                                    if ($percentage < 75) {
                                        $color = 'orange';
                                    }
                                    if ($percentage < 50) {
                                        $color = 'green';
                                    }
                                    ?>
                                    <div class="playlist-time-result-item <?=$color ?>"><span>CENTER</span>
                                        <?php if (round($percentage) != 100) { ?>
                                            <small><?php if (round($percentage)>100) {
                                                    echo '+' . ($result['calculated_center_time'] - 900); } else {
                                                    echo '-' . (900 - $result['calculated_center_time']);
                                                } ?>s</small>
                                        <?php } ?>
                                        <?= round($percentage); ?>%
                                    </div>

                                    <?php
                                    $timeSum = $result['calculated_vast_time'] + $result['calculated_ad_time'] + 900;
                                    $percentage = $timeSum / (3600 / 100);
                                    $color = 'red';
                                    if ($percentage < 75) {
                                        $color = 'orange';
                                    }
                                    if ($percentage < 50) {
                                        $color = 'green';
                                    }
                                    ?>
                                    <div class="playlist-time-result-sum <?= $color ?>">
                                        <span>GESAMT</span> <?= round($percentage) ?>%
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </li>
                <?php $currentDate->modify('+1 day'); } ?>
            </ul>
            <div style="clear: both"></div>
        </div>
        <?php
    }
}