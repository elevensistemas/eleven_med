<?php
$urls = [
    'https://news.google.com/rss/search?q=oftalmología+argentina+cataratas+OR+miopía+OR+retina+OR+glaucoma&hl=es-419&gl=AR&ceid=AR:es-419',
    'https://sao.org.ar/feed/'
];
$ctx = stream_context_create(['http'=>['user_agent'=>'Mozilla/5.0']]);
foreach($urls as $url) {
    echo "Testing $url\n";
    $res = @file_get_contents($url, false, $ctx);
    if($res) {
        $xml = simplexml_load_string($res);
        if ($xml) {
            $items = isset($xml->channel->item) ? $xml->channel->item : (isset($xml->item) ? $xml->item : []);
            echo "XML OK. Items: " . count($items) . "\n";
            if (count($items) > 0) {
                $item = $items[0];
                echo "Title: " . $item->title . "\n";
            }
        } else {
            echo "XML PARSE FAILED\n";
        }
    } else {
        echo "FAILED\n";
    }
}
