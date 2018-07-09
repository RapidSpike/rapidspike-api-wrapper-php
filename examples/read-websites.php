<?php

/* =============================================================================
 *
 * This script is an example of how the Public/Private Key Authentication works.
 *
 * Usage:
 *      php read-websites.php url='https://api.rapidspike.com' \
 *          public_key='rapidspike-ertbwtb' \
 *          private_key='**********************************************'
 *
 * =============================================================================
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    include __DIR__ . '/../vendor/autoload.php';
} else if (file_exists(__DIR__ . '/../../../autoload.php')) {
    include __DIR__ . '/../../../autoload.php';
} else {
    exit('Fail - missing autoloader');
}

$params = array();
if ($argc > 0) {
    // All args written with equals in
    foreach ($argv as $arg) {
        if (strpos($arg, '=') !== false) {
            list($key, $value) = explode('=', $arg);
            $params[$key] = $value;
        }
    }
}

if (!isset($params['url'], $params['public_key'], $params['private_key'])) {
    exit('Missing required parts. Required params: url, public_key and private_key.');
}

try {
    $Client = new RapidSpike\API\Client($params['public_key'], $params['private_key'], $params['url']);

    /*
     * Test GET all websites
     */
    echo PHP_EOL, "Testing GET '/websites'", PHP_EOL;

    $Websites = $Client->websites()->addQueryData(['page' => 1, 'per_page' => 1])->via('get');

    echo "Message: {$Websites->message}", PHP_EOL;
    echo "Status code: {$Websites->status->code}", PHP_EOL;

    /*
     * Test GET one website
     */
    echo PHP_EOL, "Testing GET '/websites/[uuid]'", PHP_EOL;

    $uuid = $Websites->data->websites[0]->website->uuid;
    $Website = $Client->websites($uuid)->addQueryData(['stats' => 'status,average_response,passing_monitors,failing_monitors,total_monitors'])->via('get');

    echo "Message: {$Website->message}", PHP_EOL;
    echo "Status code: {$Website->status->code}", PHP_EOL;

    echo PHP_EOL, 'Pass', PHP_EOL;
} catch (\Exception $e) {
    echo 'Fail', PHP_EOL, $e->getMessage(), PHP_EOL, PHP_EOL;
    echo json_encode($e->getApiResponse(), JSON_PRETTY_PRINT), PHP_EOL;
}