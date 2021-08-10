<?php
/**
 * 2007-2021 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class Connection
{
    const TYPE_JSON = 'application/json';

    /**
     * @var string $base_url API base URL
     * @var $curl
     * @var array $headers HTTP request headers
     * @var mixed $response HTTP response
     */
    private $base_url, $token, $curl, $headers = array(), $response;

    /**
     * Connection constructor.
     *
     * @param string $base_url
     */
    public function __construct($base_url)
    {
        $this->base_url = $base_url;
        $this->curl     = curl_init();

        curl_setopt_array(
            $this->curl,
            array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1
            )
        );
    }

    /**
     * Add a custom header to the request.
     *
     * @param string $header HTTP request field name
     * @param string $value HTTP request field value
     */
    public function addHeader($header, $value)
    {
        $this->headers[$header] = "$header: $value";
    }

    /**
     * Authenticate API connection. Make an HTTP POST request to the
     * authorization endpoint  and obtain access token.
     *
     * @param string $url
     * @param string $client_id Payout client ID
     * @param string $client_secret Payout client secret
     *
     * @return mixed
     * @throws Exception
     */
    public function authenticate($url, $client_id, $client_secret)
    {
        $this->initializeRequest();

        $credentials = json_encode(
            array(
                'client_id'     => $client_id,
                'client_secret' => $client_secret
            )
        );

        curl_setopt($this->curl, CURLOPT_URL, $this->base_url . $url);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $credentials);

        $this->response = curl_exec($this->curl);

        return $this->handleResponse();
    }

    /**
     * Make an HTTP POST request to the specified endpoint.
     *
     * @param string $url URL to which we send the request
     * @param mixed $body Data payload (JSON string or raw data)
     *
     * @return mixed
     * @throws Exception
     */
    public function post($url, $body)
    {
        $this->addHeader('Authorization', 'Bearer ' . $this->token);
        $this->initializeRequest();

        if ( ! is_string($body)) {
            $body = json_encode($body);
        }

        curl_setopt($this->curl, CURLOPT_URL, $this->base_url . $url);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);

        $this->response = curl_exec($this->curl);

        return $this->handleResponse();
    }

    /**
     * Make an HTTP GET request to the specified endpoint.
     *
     * @param string $url URL to retrieve
     * @param array|bool $query Optional array of query string parameters
     *
     * @return mixed
     * @throws Exception
     */
    public function get($url, $query = false)
    {
        $this->addHeader('Authorization', 'Bearer ' . $this->token);
        $this->initializeRequest();

        if (is_array($query)) {
            $url .= '?' . http_build_query($query);
        }

        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($this->curl, CURLOPT_URL, $this->base_url . $url);
        curl_setopt($this->curl, CURLOPT_POST, false);
        curl_setopt($this->curl, CURLOPT_PUT, false);
        curl_setopt($this->curl, CURLOPT_HTTPGET, true);

        $this->response = curl_exec($this->curl);

        return $this->handleResponse();
    }

    /**
     * Clear previously cached request data and prepare for
     * making a fresh request.
     */
    private function initializeRequest()
    {
        $this->response = '';
        $this->addHeader('Content-Type', self::TYPE_JSON);
        $this->addHeader('Accept', self::TYPE_JSON);

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
    }

    /**
     * Check the response for possible errors and handle the response body returned.
     *
     * @return mixed the value encoded in json in appropriate PHP type.
     * @throws Exception
     */
    private function handleResponse()
    {
        if (curl_error($this->curl)) {
            throw new Exception('Payout error: ' . curl_error($this->curl));
        }

        $response = json_decode($this->response);

        if (isset($response->errors)) {
            throw new Exception('Payout error: ' . json_encode($response));
        }

        if (isset($response->token)) {
            $this->token = $response->token;
        }

        return $response;
    }
}
