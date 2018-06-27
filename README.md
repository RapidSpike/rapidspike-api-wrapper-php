# RapidSpike API Binding (PHP)

PHP bindings for the [RapidSpike API](https://docs.rapidspike.com/system-api) (v1).

## Information

RapidSpike provides a RESTful API designed to make interfacing with their services cleaner and easier. The [RapidSpike Portal](https://my.rapidspike.com) is 100% API powered so anything you can do there is possible directly via the API.

To interact with the API you must first have a RapidSpike subscription that allows access to the API. Then you need to generate API keys in your [account settings area](https://my.rapidspike.com/#/account/my-account/account-settings?tab=api).

This bindings package is future proof as new end-points become available - it is mearly a wrapper that provides a standardised way to create end-points, package request data and authenticate in the API.

## Concepts

#### End-point Chaining
This bindings package is very simple, but provides you with a standard way to call our API. End-points are built using a method per path segment or directly in the `call()` method:
```
# GET /accounts/api (read account API keys)
$Client->accounts()->api()->via('get');
$Client->call('accounts/api')->via('get');
```

This makes use of magic methods so that we're future proofed against new end-points. If you need to add a value that doesn't fit into this method or is stored in a variable then the `call()` method is better and saves your from having to declare dynamic function names. Also, segments can be passed as parameters to segment methods:
```
# GET /websites/[uuid] (read one website)
$Client->websites('30031b9b-5df8-4b19-8dfe-17bf5bac7654')->via('get');
```

All requests *must* end by calling the `via()` method and passing an HTTP request verb (e.g. get, post, put, delete). Authentication is made using a pair of keys (public and private) that are required to generate a signature which is checked in the RapidSpike API.

#### Query & JSON Data
Adding query or JSON data can be done in or before the method chain. Either way it must be done before the `via()` method:
```
# Read page 1 of all websites with 10 displayed per page
$Client->addQueryData(['page' => 1, 'per_page' => 10]);
$Client->websites()->via('get');

# Alternative...
$Client->websites()->addQueryData(['page' => 1, 'per_page' => 10])->via('get');
```

## Installation



## Usage example

Usage begins with instantiating the `RapidSpike\API\Client` object and passing your public and private keys, which are required. From there you have a number of options on how to build the end-point (see Concepts above).

```
<?php
include 'vendor/autoload.php';

# Instantiate the Client with authentication keys
$Client = new RapidSpike\API\Client("rapidspike-********", "**********************************************");

# Add paging query data to be turned into the query string
$Client->addQueryData(['page' => 1, 'per_page' => 10]);

# Read all websites
$Websites = $Client->websites()->via('get');

# Read the first website's stats by its UUID
$uuid = $Websites->data->websites[0]->website->uuid;
$Website = $Client->websites($uuid)->addQueryData(['stats' => 'status,average_response,passing_monitors,failing_monitors,total_monitors'])->via('get');
```

Contact
-------
Twitter: [@rapidspike](https://twitter.com/rapidspike)
