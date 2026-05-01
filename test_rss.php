<?php
$urls = [
    'https://www.nature.com/eye.rss',
    'https://iovs.arvojournals.org/rss/site_163/rss.xml',
    'https://www.ajo.com/current.rss',
    'https://bjo.bmj.com/rss/current.xml'
];
$ctx = stream_context_create(['http'=>['user_agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64)']]);
foreach($urls as $url) {
    echo "Testing $url\n";
    $res = @file_get_contents($url, false, $ctx);
    if($res) {
        echo "SUCCESS: " . strlen($res) . " bytes\n";
    } else {
        echo "FAILED\n";
    }
}
