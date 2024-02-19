<?php

class MallCockpitDeviceVastParser
{
    public $output = 'none';
    private $impression = false;
    private $trackingEvents = false;
    private $playerId = false;
    private $deviceId = false;

    /**
     * MallCockpitDeviceVastParser constructor.
     * @param $url
     * @param $deviceId
     */
    public function __construct($url, $deviceId = false)
    {
        global $wpdb;

        $this->deviceId = $deviceId;

        list($output, $httpCode) = $this->getContent($url);

        if (!empty($output)) {
            /*$handle = fopen(__DIR__ . "/../logs/vast-" . $deviceId . "-raw-".date('Y-m-d').".txt", "a");
                      fwrite($handle, date('Y-m-d H:i:s') . " " . $output . "\n\n");
                      fclose($handle);*/

            /*if (!empty($wpdb)) {
                try {
                    $dateTime = new \DateTime();
                    $dateTime->modify('+2 hour');

                    $table_name = 'dooh_ssp_requests';
                    $wpdb->insert($table_name, array('deviceId' => $deviceId, 'date' => $dateTime->format('Y-m-d'), 'time' => $dateTime->format('H:i:s')));
                } catch (\Exception $e) {

                }
            }*/

            $this->parseWrapper($output, $url);
        }
    }

    /**
     * @param $content
     * @param $url
     */
    public function parseWrapper($content, $url)
    {
        $xml = new SimpleXMLElement($content);

        // New VAST-URL
        if (isset($xml->Ad->Wrapper)) {

            // Get player id
            if (preg_match('/pid=([^&]+)/', $url, $matches)) {
                $this->playerId = $matches[1];

                // Get route url
                $url = $this->parseUrl((string) $xml->Ad->Wrapper->VASTAdTagURI);


                // Save impression
                $this->impression = $this->parseUrl((string) $xml->Ad->Wrapper->Impression);

                // Get tracking events
                foreach ($xml->Ad->Wrapper->Creatives->Creative->Linear->TrackingEvents->Tracking as $event)
                {
                    $this->trackingEvents[][(string) $event->attributes()['event']] =
                        $this->parseUrl((string) $event);
                }

                // Get vast content
                list($output, $httpCode) = $this->getContent($url);

                /*$handle = fopen(__DIR__ . "/../logs/vast-" . $this->deviceId . "-route-".date('Y-m-d').".txt", "a");
                          fwrite($handle, date('Y-m-d H:i:s') . " " . $output . "\n\n");
                          fclose($handle);*/

                if (!empty($output)) {
                    $xml = new SimpleXMLElement($output);

                    if (!isset($xml->Ad->Inline->AdSystem)) {

                        // Get tracking events
                        if (isset($xml->Ad->InLine->Creatives->Creative->Linear->TrackingEvents->Tracking)) {
                            foreach ($xml->Ad->InLine->Creatives->Creative->Linear->TrackingEvents->Tracking as $event)
                            {
                                $this->trackingEvents[][(string) $event->attributes()['event']] =
                                    $this->parseUrl((string) $event);
                            }
                        }

                        $this->createNewXml($xml);
                    } else {
                        $this->mergeXml($output);
                    }
                }
            }

        // Old VAST-URL
        } elseif (isset($xml->Ad)) {

            // Get tracking events
            if (isset($xml->Ad->InLine->Creatives->Creative->Linear->TrackingEvents->Tracking)) {
                foreach ($xml->Ad->InLine->Creatives->Creative->Linear->TrackingEvents->Tracking as $event)
                {
                    $this->trackingEvents[][(string) $event->attributes()['event']] =
                        $this->parseUrl((string) $event);
                }
            }

            $this->createNewXml($xml);
        }
    }

    /**
     * @param $source
     */
    private function mergeXml($source)
    {
        // Add Impression
        $impressionTag = '';
        if ($this->impression) {
            $impressionTag = '<Impression id="third'.microtime().'"><![CDATA[' . $this->impression . ']]></Impression>';
        }
        $source = str_replace('</Description>', '</Description>' . $impressionTag, $source);

        // Add TrackingEvents
        $trackingEvents = '';
        if ($this->trackingEvents) {
            foreach ($this->trackingEvents as $index => $trackingEventsArr) {
                foreach ($trackingEventsArr as $eventName => $eventUrl) {
                    $trackingEvents .= '<Tracking event="' . $eventName . '"><![CDATA[' . $eventUrl . ']]></Tracking>';
                }
            }
        }
        $source = str_replace('</TrackingEvents>', $trackingEvents . '</TrackingEvents>', $source);

        $this->output = $source;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return bool
     */
    public function createNewXml(SimpleXMLElement $xml)
    {
        $source = file_get_contents(__DIR__ . '/../xml-templates/vast.xml');

        // Add Impression
        $impressionTag = '';
        if ($this->impression) {
            $impressionTag = '<Impression id="third'.microtime().'"><![CDATA[' . $this->impression . ']]></Impression>';
        }

        if (!empty($xml->Ad->InLine->Impression)) {
            foreach ($xml->Ad->InLine->Impression as $impression) {
                $impressionTag .= '<Impression id="third'.microtime().'"><![CDATA[' . trim((string) $impression) . ']]></Impression>';
            }
        }

        $source = str_replace('[Impression]', $impressionTag, $source);



        // Add TrackingEvents
        $trackingEvents = '';
        if ($this->trackingEvents) {
            foreach ($this->trackingEvents as $index => $trackingEventsArr) {
                foreach ($trackingEventsArr as $eventName => $eventUrl) {
                    $trackingEvents .= '<Tracking event="' . $eventName . '"><![CDATA[' . $eventUrl . ']]></Tracking>';
                }
            }
        }
        $source = str_replace('[TrackingEvents]', $trackingEvents, $source);

        // Set Duration
        $source = str_replace('[Duration]', (string) $xml->Ad->InLine->Creatives->Creative->Linear->Duration, $source);

        // Set MediaFiles
        $mediaFileTag = '';
        if (!empty($xml->Ad->InLine->Creatives->Creative->Linear->MediaFiles->MediaFile)) {
            foreach ($xml->Ad->InLine->Creatives->Creative->Linear->MediaFiles->MediaFile as $mediaFileXml) {
                if (empty($mediaFileXml->attributes())) {
                    continue;
                }
                $mediaFileTag .= '<MediaFile type="' . (string) $mediaFileXml->attributes()['type'] . '"' .
                    ' width="'. (string) $mediaFileXml->attributes()['width'] . '"' .
                    ' height="' . (string) $mediaFileXml->attributes()['height'] .'" delivery="progressive" scalable="true">' .
                    '<![CDATA[' . trim((string) $mediaFileXml) . ']]></MediaFile>';
            }
        }

        if (emptY($mediaFileTag)) {
            return false;
        }

        $source = str_replace('[MediaFile]', $mediaFileTag, $source);

        if (trim((string) $mediaFileXml->attributes()['type']) == 'text/html') {
            return false;
        }

        $this->output = $source;
    }

    /**
     * @param $str
     * @return string|string[]
     */
    private function parseUrl($str)
    {
        return str_replace('[PLAYER_ID]', $this->playerId, $str);
    }

    /**
     * @param $url
     * @return array
     */
    private function getContent($url)
    {
        $ch = curl_init($url);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
              curl_setopt($ch, CURLOPT_TIMEOUT, 10);
              curl_setopt($ch, CURLOPT_HEADER, false);
              curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [$output, $httpCode];
    }

    /**
     * @param bool $deviceId
     */
    public function output($deviceId = false)
    {
        if ($this->output !== 'none') {
            header('Content-Type: application/xml; charset=utf-8');

            /*$handle = fopen(__DIR__ . "/../logs/vast-" . $deviceId . "-".date('Y-m-d').".txt", "a");
                      fwrite($handle, date('Y-m-d H:i:s') . " " . $this->output . "\n\n");
                      fclose($handle);*/
        }

        echo $this->output;
        exit;
    }
}

