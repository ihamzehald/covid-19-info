<?php

require "vendor/autoload.php";
use PHPHtmlParser\Dom;

$dom = new Dom;

$fb = new \Facebook\Facebook([
    'app_id' => 'fb_app_id',
    'app_secret' => 'fb_app_secret',
    'default_graph_version' => 'v2.10',
]);

$pageAccessToken = "fb_page_access_token";
$pageId = 'fb_page_id';

// the actual length limit is (68727) but to allow post titles such as (Part 1) it's made 68650
$fbPostChunkLength = 63100;
$fbPostChunkSeparator = "~!@#$%^&*";
$data['picture'] = "";
$data['link'] = "";
$data['caption'] = "";
$data['description'] = "";
$data['access_token'] = $pageAccessToken;

$post_url = 'https://graph.facebook.com/'.$pageId.'/feed';

$dom->load(file_get_contents("https://www.worldometers.info/coronavirus/"));
$infoTable = $dom->find('table')[0];
$rows = $infoTable->find('tr');

$colsCount = 1;

$headers = [];

$fbPostString = "";

foreach($rows as $row){
    $dataObjType = $colsCount > 1 ? 'td' : 'th';
    $columns = $row->find($dataObjType);
    $fbPostString .= "\n";
    foreach($columns as $index => $column){
        $columnVal = strip_tags(html_entity_decode($column->innerHtml));
        if($dataObjType == 'th'){
            $headers[] = $columnVal;
        }else{
            $fbPostString .= "{$headers[$index]} ==> {$columnVal} \n";
        }
    }
    $fbPostString .= "\n ========== \n";
    $colsCount++;
}

$fbPostChunksString = chunk_split($fbPostString, $fbPostChunkLength, $fbPostChunkSeparator);

$fbPostChunks = explode($fbPostChunkSeparator, $fbPostChunksString);


if(count($fbPostChunks) > 1){
    foreach($fbPostChunks as $index => $fbPostChunk){
        if(!empty($fbPostChunk)){
            $partCount = $index + 1;
            $fbPostChunkTitle = "Part ({$partCount}) : \n";
            $fbPostStringFinal = $fbPostChunkTitle . $fbPostChunk;
            $data['message'] = $fbPostStringFinal;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $post_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $return = curl_exec($ch);
            curl_close($ch);

            echo $return . "\n";
        }
    }
}else{
        $fbPostStringFinal = $fbPostChunks[0];
        $data['message'] = $fbPostStringFinal;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $return = curl_exec($ch);
        curl_close($ch);

        echo $return . "\n";
}






