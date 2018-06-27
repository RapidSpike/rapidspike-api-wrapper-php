<?php

/* =============================================================================
 *
 * This scripts gives an example of how to call basic test end-points available
 * in the RapidSpike API. Nothing fancy, just showing basic functionality.
 *
 * Usage:
 *      php basic-test.php url='https://api.rapidspike.com' \
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

if (!isset($params['url'])) {
    exit('Missing required parts. Required params: url.');
}

try {
    $obj = new RapidSpike\API\Client($params['public_key'], $params['private_key'], $params['url']);

    /*
     * Test GET
     */
    echo PHP_EOL, "Testing GET '/test'", PHP_EOL;
    $get = $obj->test()->via('get');

    echo "Message: {$get->message}", PHP_EOL;
    echo "Status code: {$get->status->code}", PHP_EOL;

    if ($get->status->code !== 200) {
        throw new Exception('GET call failed! Response: ' . PHP_EOL . json_encode($get, JSON_PRETTY_PRINT));
    }

    /*
     * Test POST
     */
    echo PHP_EOL, "Testing POST '/test'", PHP_EOL;
    $post = $obj->test()->via('post');

    echo "Message: {$post->message}", PHP_EOL;
    echo "Status code: {$post->status->code}", PHP_EOL;

    if ($post->status->code !== 200) {
        throw new Exception('POST call failed! Response: ' . PHP_EOL . json_encode($post, JSON_PRETTY_PRINT));
    }

    echo PHP_EOL, 'Pass', PHP_EOL;
} catch (\Exception $e) {
    echo 'Fail', PHP_EOL, $e->getMessage(), PHP_EOL;
}