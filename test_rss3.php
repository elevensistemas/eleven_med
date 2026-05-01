<?php
$urls = [
    'https://www.aaojournal.org/current.rss',
    'https://bmjopenophthalmology.bmj.com/rss/current.xml'
];
$ctx = stream_context_create(['http'=>['user_agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)']]);
foreach($urls as $url) {
    echo "Testing $url\n";
    $res = @file_get_contents($url, false, $ctx);
    if($res) {
        $xml = simplexml_load_string($res);
        if ($xml) {
            $items = isset($xml->channel->item) ? $xml->channel->item : (isset($xml->item) ? $xml->item : null);
            echo "XML OK. Items: " . count($items) . "\n";
        } else {
            echo "XML PARSE FAILED\n";
        }
    } else {
        echo "FAILED\n";
    }
}
