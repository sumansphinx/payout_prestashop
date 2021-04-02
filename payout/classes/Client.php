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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
 
class Client
{
    const LIB_VER = '1.0.1';
    const API_URL = 'https://app.payout.one/api/v1/';
    const API_URL_SANDBOX = 'https://sandbox.payout.one/api/v1/';

    /**
     * @var array $config API client configuration
     * @var string $token Obtained API access token
     * @var Connection $connection Connection instance
     */
    private $config, $token, $connection;

    /**
     * Construct the Payout API Client.
     *
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config = array())
    {
        if (!function_exists('curl_init')) {
            throw new Exception('Payout needs the CURL PHP extension.');
        }
        if (!function_exists('json_decode')) {
            throw new Exception('Payout needs the JSON PHP extension.');
        }

        $this->config = array_merge(
            [
                'client_id' => '',
                'client_secret' => '',
                'sandbox' => false
            ],
            $config
        );
    }

    /**
     * Get a string containing the version of the library.
     *
     * @return string
     */
    public function getLibraryVersion()
    {
        return self::LIB_VER;
    }

    /**
     * Get an instance of the HTTP connection object. Initializes
     * the connection if it is not already active.
     * Authorize connection and obtain access token.
     *
     * @return Connection
     * @throws Exception
     */
    private function connection()
    {
        if (!$this->connection) {
            $api_url = ($this->config['sandbox']) ? self::API_URL_SANDBOX : self::API_URL;
            $this->connection = new Connection($api_url);
            $this->token = $this->connection->authenticate('authorize', $this->config['client_id'], $this->config['client_secret']);
        }

        return $this->connection;
    }

    /**
     * Create signature as SHA256 hash of message.
     *
     * @param $message
     * @return string
     */
    private function getSignature($message)
    {
        $message = implode('|', $message);
        return hash('sha256', pack('A*', $message));
    }

    /**
     * Verify signature obtained in API response.
     *
     * @param array $message to be signed
     * @param string $signature from response
     * @return bool
     */
    public function verifySignature($message, $signature)
    {
        $message[] = $this->config['client_secret'];

        if (strcmp($this->getSignature($message), $signature) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Generate nonce string. In cryptography, a nonce is an arbitrary number
     * that can be used just once in a cryptographic communication.
     * https://en.wikipedia.org/wiki/Cryptographic_nonce
     *
     * @return string
     */
    private function generateNonce()
    {
        // TODO use more secure nonce https://secure.php.net/manual/en/function.random-bytes.php
        $bytes = openssl_random_pseudo_bytes(32);
        $hash = base64_encode($bytes);
        return $hash;
    }

    /**
     * Verify input data and create checkout and post signed data to API.
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function createCheckout($data)
    {
        $checkout = new Checkout();

        $prepared_checkout = $checkout->create($data);

        $nonce = $this->generateNonce();
        $prepared_checkout['nonce'] = $nonce;

        $message = array($prepared_checkout['amount'], $prepared_checkout['currency'], $prepared_checkout['external_id'], $nonce, $this->config['client_secret']);
        $signature = $this->getSignature($message);
        $prepared_checkout['signature'] = $signature;

        $prepared_checkout = json_encode($prepared_checkout);

        $response = $this->connection()->post('checkouts', $prepared_checkout);

        if (!$this->verifySignature(array($response->amount, $response->currency, $response->external_id, $response->nonce), $response->signature)) {
            throw new Exception('Payout error: Invalid signature in API response.');
        }

        return $response;
    }

    /**
     * Get checkout details from API.
     *
     * @param integer $checkout_id
     * @return mixed
     * @throws Exception
     */
    public function getCheckout($checkout_id)
    {
        $url = 'checkouts/' . $checkout_id;
        $response = $this->connection()->get($url);

        if (!$this->verifySignature(array($response->amount, $response->currency, $response->external_id, $response->nonce), $response->signature)) {
            throw new Exception('Payout error: Invalid signature in API response.');
        }

        return $response;
    }
}
