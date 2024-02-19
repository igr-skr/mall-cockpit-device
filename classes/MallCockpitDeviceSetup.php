<?php
/**
 * Class MallCockpitDeviceSetup
 */
class MallCockpitDeviceSetup
{
    private $requiredPlugins = [
        'Advanced Custom Fields PRO' => 'advanced-custom-fields-pro/acf.php',
        'Custom Post Type UI' => 'custom-post-type-ui/custom-post-type-ui.php',
    ];

    private $requiredPostTypes = [
        'devices',
        'center',
        'playlist'
    ];

    /**
     * @throws Exception
     * @return bool
     */
    public function init()
    {
        $missingPlugins = [];
        $activePlugins = apply_filters('active_plugins', get_option('active_plugins'));
        $customPostTypeUIExists = true;

        foreach ($this->requiredPlugins as $pluginName => $filePath) {
            if (!in_array($filePath, $activePlugins)) {
                $missingPlugins[] = $pluginName;

                if ($pluginName === 'Custom Post Type UI') {
                    $customPostTypeUIExists = false;
                }
            }
        }

        $missingPostTypes = [];
        if ($customPostTypeUIExists) {
            foreach ($this->requiredPostTypes as $postType) {
                if (post_type_exists($postType) === false) {
                    $missingPostTypes[] = $postType;
                }
            }
        }

        if (count($missingPostTypes) || count($missingPlugins)) {
            add_action('admin_notices', function () use ($missingPostTypes, $missingPlugins) {
                if (!current_user_can('activate_plugins')) {
                    return;
                }
                include dirname(__FILE__) . '/../views/setup-failed.php';
            });

            return false;
        }

        // Create reporting database table
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // @todo vast und media spalten hinzufügen (aktuell über phpmyadmin hinzugefügt)
        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}reportings` (
                  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                  device_id varchar(100) NOT NULL,
                  aid varchar(100) NOT NULL,
                  played_at datetime NOT NULL
                ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        $success = empty($wpdb->last_error);

        if (!$success) {
            $mysqlerror = $wpdb->last_error;
            include dirname(__FILE__) . '/../views/setup-failed.php';
        }

        return $success;
    }
}