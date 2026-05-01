<?php
$urls = [
    'https://www.nature.com/eye.rss',
    'https://www.who.int/rss-feeds/news-english.xml'
];
$ctx = stream_context_create(['http'=>['user_agent'=>'Mozilla/5.0']]);
foreach($urls as $url) {
    echo "Testing $url\n";
    $res = @file_get_contents($url, false, $ctx);
    if($res) {
        $xml = simplexml_load_string($res);
        if ($xml) {
            $items = isset($xml->channel->item) ? count($xml->channel->item) : (isset($xml->item) ? count($xml->item) : 0);
            echo "XML OK. Items: " . $items . "\n";
        } else {
            echo "XML PARSE FAILED\n";
        }
    } else {
        echo "FAILED\n";
    }
}
