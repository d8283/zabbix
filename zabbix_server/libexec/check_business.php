#!/usr/local/server/php/bin/php
<?php

$date = new DateTime();

// Zipkin记录的是UTC时间
$to = $date->sub(new DateInterval('PT480M'))->format('Y-m-d');

// 再往前推3分钟是查询的起始时间
$from = $date->sub(new DateInterval('PT3M'))->format('Y-m-d');

$esIndex = "zipkin-$from,zipkin-$to";
$queryUrl = "http://192.168.10.7:9200/$esIndex/span/_search";

if (count($argv) < 7) {
    exit('ERROR: params missing');
}

if (!preg_match('/^\w+$/', $argv[1])) {
    exit('ERROR: host error');
}
$host = $argv[1];

if (!is_numeric($argv[2])) {
    exit('ERROR: port error');
}
$port = $argv[2];

if (!preg_match('%^[\-\w._*/]+$%', $argv[3])) {
    exit('ERROR: url error');
}
$url = $argv[3];

// be base64ed
$keyword = base64_decode($argv[4]);
if (!preg_match('%^[-\w._*/]+$%', $keyword)) {
    exit('ERROR: keyword error');
}

if (!preg_match('/^[\w-]+$/', $argv[5])) {
    exit('ERROR: uuid error');
}
$uuid = $argv[5];
$expect = $argv[6];

$logfile = "/tmp/monitors/$uuid.log";

// zipkin query
$params = [
    'size' => 1,
    'query' => [
        "bool" => [
            "filter" => [
                [
                    "range" => [
                        "timestamp_millis" => [
                            "gte" => "now-3m",
                            "lte" => "now-2m",
                        ],
                    ],
                ],
                [
                    "wildcard" => [
                        "name" => $url,
                    ],
                ],
                [
                    'nested' => [
                        'path' => 'binaryAnnotations',
                        'query' => [
                            'bool' => [
                                'filter' => [
                                    [
                                        "term" => [
                                            "binaryAnnotations.endpoint.serviceName" => $host,
                                        ],
                                    ],
                                    [
                                        'wildcard' => [
                                            'binaryAnnotations.key' => $keyword,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $queryUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

if ($response = curl_exec($ch)) {
    $response = json_decode($response, true);

    // not json
    if (json_last_error() != JSON_ERROR_NONE) {
        exit(json_last_error());
    }

    // parse error
    if (!isset($response['timed_out']) || $response['timed_out'] !== false) {
        file_put_contents($logfile, json_encode($response), FILE_APPEND);
        exit('255');
    }

    // no error
    if (isset($response['hits']['total']) && $response['hits']['total'] == 0) {
        exit((string)$expect);
    }

    // normalize error
    $traces = [];
    foreach ($response['hits']['hits'] as $error) {
        $traceId = $error['_source']['traceId'];

        $annotations = [];
        foreach ($error['_source']['binaryAnnotations']  as $annotation) {
            $annotations[] = trim($annotation['key'] . ': ' . $annotation['value']) . "\n";
        }

        $traces[] = 'TraceID: ' . $traceId . "\n" . implode("", $annotations);
    }

    file_put_contents($logfile, implode("\n\n", $traces), FILE_APPEND);
}

exit('255');
