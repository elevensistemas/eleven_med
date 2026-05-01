<?php
$urls = [
    'https://www.medscape.com/cx/rssfeeds/2700.xml',
    'https://www.nature.com/eye.rss',
];
$ctx = stream_context_create(['http'=>['user_agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36']]);
foreach($urls as $url) {
    echo "Testing $url\n";
    $res = @file_get_contents($url, false, $ctx);
    if($res) {
        $xml = simplexml_load_string($res);
        if ($xml) {
            echo "XML OK. Items: " . count($xml->channel->item) . "\n";
        } else {
            echo "XML PARSE FAILED\n";
        }
    } else {
        echo "FAILED\n";
    }
}
