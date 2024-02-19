<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * @var string[] $missingPostTypes
 * @var string[] $missingPlugins
 */
?>

<div class="error notice">
    <p>
        <strong>Error:</strong>
        <em>Mall-Cockpit Devices</em> Plugin kann nicht installiert werden.<br />
        <?php if (count($missingPostTypes)) { ?>
            Folgende Post Types Fehlen: <?php echo esc_html( implode( ', ', $missingPostTypes ) ) ?>.<br />
        <?php } ?>
        <?php if (count($missingPlugins)) { ?>
            Folgende Plugins fehlen: <?php echo esc_html( implode( ', ', $missingPlugins ) ) ?>.<br />
        <?php } ?>
        <?php if (!empty($mysqlerror)) { ?>
            Fehler: <?=$mysqlerror ?>
        <?php } ?>
    </p>
</div>