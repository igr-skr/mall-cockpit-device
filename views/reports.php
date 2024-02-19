<?php
$dateFrom = isset($_POST['report']['to']) ? $_POST['report']['to'] : date('Y-m-d');
$dateTo = isset($_POST['report']['from']) ? $_POST['report']['from'] : date('Y-m-d');
?><div class="wrap cptui-new">
    <h1>Reports</h1>
    <form method="post" action="/wp-content/plugins/mall-cockpit-device/MallCockpitDownloadReport.php">
        <div class="postbox-container">
            <div id="poststuff">
                <div class="cptui-section postbox">
                    <h2 class="hndle">
                        <span>Filter</span>
                    </h2>
                    <div class="inside">
                        <div class="main">
                            <table class="form-table cptui-table">
                                <tbody>
                                    <tr valign="top">
                                        <th scope="row">
                                            <label for="from">Datum von</label>
                                            <span class="required">*</span>
                                        </th>
                                        <td>
                                            <input type="date" id="from" name="report[from]" value="<?=$dateTo ?>"
                                                   maxlength="20" aria-required="true" required="true"
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row">
                                            <label for="from">Datum bis</label>
                                            <span class="required">*</span>
                                        </th>
                                        <td>
                                            <input type="date" id="from" name="report[to]" value="<?= $dateFrom ?>"
                                                   maxlength="20" aria-required="true" required="true"
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <p>
                    <input type="submit" class="button-primary" name="report_download" value="Report als .CSV downloaden">
                </p>
            </div>
        </div>
    </form>
    <div class="clear"></div>
</div>