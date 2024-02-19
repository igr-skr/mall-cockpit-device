<?php
require_once(__DIR__ . "/../../../wp-load.php");
require_once __DIR__ . '/classes/MallCockpitDeviceReport.php';

if (isset($_POST['report_download'])) {
    $dateFrom = isset($_POST['report']['to']) ? $_POST['report']['to'] : date('Y-m-d');
    $dateTo = isset($_POST['report']['from']) ? $_POST['report']['from'] : date('Y-m-d');

    $csv = "Device-ID;Advertiser-ID;Uhrzeit";
    $reports = MallCockpitDeviceReport::getReports($dateFrom, $dateTo);
    foreach ($reports as $report) {
        $csv .= "\r\n";
        $csv .= $report->device_id . ';' . $report->aid . ';' . date('d.m.Y H:i:s', strtotime($report->played_at));
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=report_'.time().'.csv');
    echo $csv;
    exit;
}