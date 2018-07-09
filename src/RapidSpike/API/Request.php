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

use GuzzleHttp\Client as HttpClient,
    GuzzleHttp\RequestOptions as RequestOptions,
    GuzzleHttp\Exception\BadResponseException,
    GuzzleHttp\Exception\ClientException,
    GuzzleHttp\Exception\ConnectException,
    GuzzleHttp\Exception\ServerException;

/**
 * Request class to actually issue to request to the API. Requires the Scope
 * which is required to be the Client class. This enables the actual HTTP logic
 * to be decoupled from the logic for building the end-point and request data.
 */
class Request
{

    /**
     * @var \RapidSpike\API\Client
     */
    private $Scope;

    /**
     * Sets the calling Client object to the class
     *
     * @param \RapidSpike\API\Client $Scope
     */
    public function __construct(Client $Scope)
    {
        $this->Scope = $Scope;
    }

    /**
     * Put together the call using the Scope class then calls the
     * request function to handle the actual request and response.
     *
     * @param string $method The method that should be used in the HTTP request
     *
     * @return null|object[]|object|string
     */
    public function call(string $method)
    {
        // Prepare the configuration for a new Guzzle client
        $arrConfig = [
            'base_uri' => $this->Scope->url,
            RequestOptions::TIMEOUT => $this->Scope->timeout,
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'User-Agent' => "RapidSpike-API-Wrapper-v{$this->Scope->version}",
            ],
        ];

        // Prepare the path with any query string
        $path = rtrim($this->Scope->path, '/');
        $path .= !empty($this->Scope->arrQueryData) ? '?' . http_build_query($this->Scope->arrQueryData) : null;

        // Put the JSON body together
        $arrOptions = [];
        if (!empty($this->Scope->arrJsonBody)) {
            $arrOptions[RequestOptions::JSON] = $this->Scope->arrJsonBody;
        }

        $HttpClient = new HttpClient($arrConfig);
        return $this->_request($HttpClient, $method, $path, $arrOptions);
    }

    /**
     * Makes an API call to a RapidSpike Scanner using the Scope object to piece
     * everything together. Quite a complicated series of Exception catching.
     *
     * @param HttpClient $Client The HttpClient that should be used to make the request
     * @param string $method The method that should be used in the HTTP request
     * @param string $path The path to request (including query string)
     * @param array $arrOptions A set of options to send in the request
     *
     * @return null|object[]|object|string
     *
     * @throws Exception\FailedConnection
     */
    private function _request(HttpClient $Client, string $method, string $path, array $arrOptions = [])
    {
        try {
            // Attempt the actual response that has been built thus far
            $response = $Client->request(strtoupper($method), $path, $arrOptions);

            // If the response is requested in raw format, return it. We need
            // to be careful to not return raw to a token request too.
            if ($this->Scope->raw) {
                return (string) $response->getBody();
            }

            // Check that the response is not empty
            if (is_null($response->getBody()) || trim($response->getBody()) == 'null') {
                return null;
            }

            // We assume that RapidSpike can return empty bodies and that RapidSpike will
            // use HTTP status codes to inform us whether the request failed.
            if (trim($response->getBody()) == '') {
                return null;
            }

            // Attempt to convert the response to a JSON Object
            return \GuzzleHttp\json_decode($response->getBody());
        } catch (ClientException $ClientException) {
            // If a endpoint is called that does not exist, give a slightly easier to understand error.
            if ($ClientException->getResponse()->getStatusCode() == 404) {
                throw Exception\FailedRequest::exceptionFactory(
                        "RapidSpike responded with a 404 for {$this->Scope->url}{$path} via {$method}.", $ClientException->getRequest(), $ClientException->getResponse()
                );
            }

            throw Exception\FailedRequest::exceptionFactory(
                    $ClientException->getMessage(), $ClientException->getRequest(), $ClientException->getResponse()
            );
        } catch (ServerException $serverException) {
            throw Exception\FailedRequest::exceptionFactory(
                    $serverException->getMessage(), $serverException->getRequest(), $serverException->getResponse()
            );
        } catch (BadResponseException $badResponseException) {
            throw Exception\FailedRequest::exceptionFactory(
                    "Unsuccessful Request to [{$method}] {$path}", $badResponseException->getRequest(), $badResponseException->getResponse()
            );
        } catch (ConnectException $connectException) {
            throw new Exception\FailedConnection($connectException->getMessage(), $connectException->getCode());
        }
    }

}
