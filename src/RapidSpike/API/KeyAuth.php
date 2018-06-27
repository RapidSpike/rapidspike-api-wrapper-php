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
 * This class takes a public and private key and creates a signature with them
 * to use when authenticating against the RapidSpike API. We would recommend
 * instantiating the object then leaving it until the last minute to generate
 * the auth signature so as to keep the timestamp as new as possible.
 */
class KeyAuth
{

    /**
     * This settings allows us to pick the Unix timestamp in
     * the same timezone as the main RapidSpike API's timezone.
     */
    const API_TIMEZONE = 'UTC';

    /**
     * Current hashing algorithm to use when creating the signature.
     */
    const SIGNATURE_ALGO = 'sha1';

    /**
     * @var string
     */
    private $public_key;

    /**
     * @var string
     */
    private $private_key;

    /**
     * Set the public and private keys to the object
     *
     * @param string $public_key
     * @param string $private_key
     */
    public function __construct(string $public_key, string $private_key)
    {
        $this->public_key = $public_key;
        $this->private_key = $private_key;
    }

    /**
     * Builds the authentication signature to use in other requests
     *
     * @param bool $string Whether to return a query string or array
     *
     * @return string|array
     */
    public function getSignature(bool $string = false)
    {
        // Capture the time and package up with the public key
        $time = (new \DateTime('now', new \DateTimeZone(self::API_TIMEZONE)))->format('U');

        $package = $this->public_key . PHP_EOL . $time;

        // SHA1 hash using the private key and then URL encode the signature
        $bin_signature = hash_hmac(self::SIGNATURE_ALGO, $package, $this->private_key, true);
        $uri_signature = urlencode(base64_encode($bin_signature));

        // Build an array of things the auth signature needs
        $arrSignature = array(
            'time' => $time,
            'public_key' => $this->public_key,
            'signature' => $uri_signature
        );

        return $string === true ? http_build_query($arrSignature) : $arrSignature;
    }

}
