<?php

/**
 * Class MallCockpitDevicePlayer2
 */
class MallCockpitDevicePlayer2 extends MallCockpitDevicePlayer
{
    /**
     * @return void
     */
    public function playerEndpoint()
    {
        $hook = current_filter();

        if ('template_redirect' === $hook && get_query_var('player2')) {
            require_once __DIR__ . '/../MallCockpitPlayer2.php';
            exit;
        }

        'init' === $hook && add_rewrite_endpoint('player2', EP_ROOT);
    }
}
