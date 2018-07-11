<?php

/*
  The MIT License (MIT)

  Copyright (c) 2018 RapidSpike Ltd

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in all
  copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  SOFTWARE.
 */

/*
 * RapidSpike API Wrapper (PHP)
 *
 * @package  rapidspike/rapidspike-api-wrapper-php
 * @author   James Tyler <james.tyler@rapidspike.com>
 * @license  MIT
 */

namespace RapidSpike\API;

/**
 * A very simple Client class to handle setting up the end-point and request
 * data. This enables the logic for building the end-point and request data to
 * be decoupled from the actual HTTP logic.
 */
class Client
{

    /**
     * @var string
     */
    public $version = '1.0.0';

    /**
     * @var int
     */
    public $timeout = 10;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $path;

    /**
     * @var bool
     */
    public $raw = false;

    /**
     * @var KeyAuth
     */
    public $KeyAuth = false;

    /**
     * @var array
     */
    public $arrQueryData = [];

    /**
     * @var array
     */
    public $arrJsonBody = [];

    /**
     * Sets the API base URL to the class
     *
     * @param string $url
     */
    public function __construct(string $public_key = null, string $private_key = null, string $url = 'https://api.rapidspike.com')
    {
        if (!empty($public_key) && !empty($private_key)) {
            $this->KeyAuth = new KeyAuth($public_key, $private_key);
        }

        $this->url = rtrim($url, '/') . '/v1/';
    }

    /**
     * Set a KeyAuth object to the class
     *
     * @param \RapidSpike\API\KeyAuth $KeyAuth
     *
     * @return $this
     */
    public function setKeyAuth(KeyAuth $KeyAuth)
    {
        $this->KeyAuth = $KeyAuth;

        return $this;
    }

    /**
     * Add items to the query data
     *
     * @param array $arrQueryData
     *
     * @return $this
     */
    public function addQueryData(array $arrQueryData)
    {
        $this->arrQueryData = array_merge($this->arrQueryData, $arrQueryData);

        return $this;
    }

    /**
     * Add items to the array intended for the JSON body
     *
     * @param array $arrJsonBody
     *
     * @return $this
     */
    public function addJsonBody(array $arrJsonBody)
    {
        $this->arrJsonBody = array_merge($this->arrJsonBody, $arrJsonBody);

        return $this;
    }

    /**
     * Magic method to allow API calls to be constructed via
     * method chaining. ie: $call->server()->properties() will
     * result in a endpoint location of BASE_URL/server/properties/.
     *
     * Magic method arguments will also be parsed as part of the call.
     * ie: $call->make('server', 'properties') will result in a
     * endpoint location of BASE_URL/server/properties/
     *
     * @param string $path The API endpoint to call
     * @param array $arrSlug Any arguments to parse as part of the location
     *
     * @return $this
     */
    public function __call(string $path, array $arrSlug)
    {
        // Ensure the location is lowercase
        $this->path .= ltrim(strtolower($path), '/') . '/';

        if (!empty($arrSlug)) {
            foreach ($arrSlug as $slug_value) {
                $this->path .= $slug_value . '/';
            }
        }

        return $this;
    }

    /**
     * Set the API call path (alternative to end-point method chaining
     *
     * @param string $path The API end-point to call.
     *
     * @return $this
     */
    public function callPath(string $path)
    {
        // Remove the first slash if its there
        $this->path = $this::standardisePath($path);
        return $this;
    }

    /**
     * Actually make a request using the Request class
     *
     * @param string $method
     *
     * @return null|object[]|object|string NULL if empty response body was empty
     *
     * @throws Exception\InvalidMethod
     * @throws \Exception
     */
    public function via(string $method)
    {
        try {
            $_method = strtolower($method);

            // Validate the request type
            $arrValidRequests = ['get' => true, 'post' => true, 'put' => true, 'delete' => true];
            if (!isset($arrValidRequests[$_method])) {
                throw new Exception\InvalidMethod("Invalid HTTP method '{$_method}' specified.");
            }

            // Get the request signature here so that the timestamp
            // used is as close as possible to the actual request
            if (!empty($this->KeyAuth)) {
                $this->addQueryData($this->KeyAuth->getSignature());
            }

            // Declare a new request object and make the call
            $Call = new Request($this);
            $Response = $Call->call($_method);

            // Reset the request data
            $this->_resetRequest();
            return $Response;
        } catch (\Exception $error) {

            // Re-throw this exception to allow us to reset the request
            // so that the client object can continue to be used
            $this->resetRequest();
            throw $error;
        }
    }

    /**
     * Method resets the outgoing request so
     * that future requests are not appended
     *
     * @return void
     */
    private function _resetRequest()
    {
        // Clear call path, query and JSON data
        $this->path = null;
        $this->arrQueryData = [];
        $this->arrJsonBody = [];
    }

}
