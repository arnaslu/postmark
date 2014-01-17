<?php
/**
 * This file is part of the Postmark package.
 *
 * PHP version 5.4
 *
 * @category Library
 * @package  Postmark
 * @author   Arnas Lukosevicius <arnaslu@gmail.com>
 * @license  https://github.com/arnaslu/postmark/LICENSE.md MIT Licence
 * @link     https://github.com/arnaslu/postmark
 */

namespace Postmark;

use Postmark\Exception\PostmarkErrorException;
use Guzzle\Common\Event;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Request;

/**
 * Postmark API wrapper
 *
 * @category Library
 * @package  Postmark
 * @author   Arnas Lukosevicius <arnaslu@gmail.com>
 * @license  https://github.com/arnaslu/postmark/LICENSE.md MIT Licence
 * @link     https://github.com/arnaslu/postmark
 */
class Postmark
{
    private $_client;

    const SEND_EMAIL = '/email';
    const SEND_BATCH = '/email/batch';
    const GET_BOUNCES = '/bounces';
    const GET_BOUNCE = '/bounces/%id%';
    const GET_BOUNCE_DUMP = '/bounces/%id%/dump';
    const GET_BOUNCE_TAGS = '/bounces/tags';
    const ACTIVATE_BOUNCE = '/bounces/%id%/activate';
    const GET_DELIVERY_STATS = '/deliverystats';

    /**
     * Sets up Guzzle HTTP client to use Postmark auth headers, overrides default
     * error handler.
     *
     * @param string $apiKey Postmark API key
     * @param Client $client Guzzle HTTP client object
     */
    public function __construct($apiKey, Client $client)
    {
        $client->setBaseUrl('https://api.postmarkapp.com');
        $client->setDefaultOption(
            'headers',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Postmark-Server-Token' => $apiKey,
            ]
        );

        $this->_client = $client;

        $this->_addErrorHandler();
    }

    /**
     * Sends single email
     *
     * @param Message $message JSON serializable message object
     *
     * @return array
     */
    public function send(Message $message)
    {
        $request = $this->_client->post(
            self::SEND_EMAIL,
            null,
            json_encode($message)
        );
        return $this->_sendRequest($request);
    }

    /**
     * Sends array (batch) of messages
     *
     * @param Message[] $messages An array of JSON serializable message objects
     *
     * @return array
     */
    public function sendBatch(array $messages)
    {
        $request = $this->_client->post(
            self::SEND_BATCH,
            null,
            json_encode($messages)
        );
        return $this->_sendRequest($request);
    }

    /**
     * Retrieves bounced messages
     *
     * @param int   $count   Number of messages to return (pagination parameter)
     * @param int   $offset  Number of messages to offset (pagination parameter)
     * @param array $filters Array of filters to limit returned bounces
     *
     * @return array
     */
    public function getBounces($count, $offset, array $filters = [])
    {
        // Count and offset are actually regular filter params
        $params = array_merge(
            $filters,
            [
                'count' => $count,
                'offset' => $offset,
            ]
        );

        // Build GET request
        $request = $this->_client->get(self::GET_BOUNCES);

        // Build query string from parameters
        $query = $request->getQuery();
        foreach ($params as $key => $value) {
            $query->set($key, $value);
        }

        $result = $this->_sendRequest($request);
        return $result;
    }

    /**
     * Retrieves specific bounced message
     *
     * @param string $bounceID Bounced message ID
     *
     * @return array
     */
    public function getBounce($bounceID)
    {
        $request = $this->_client->get(
            str_replace('%id%', $bounceID, self::GET_BOUNCE)
        );
        $result = $this->_sendRequest($request);
        return $result;
    }

    /**
     * Retrieves body source of bounced message
     *
     * @param string $bounceID Bounced message ID
     *
     * @return string
     */
    public function getBounceDump($bounceID)
    {
        $request = $this->_client->get(
            str_replace('%id%', $bounceID, self::GET_BOUNCE_DUMP)
        );
        $result = $this->_sendRequest($request);
        return $result['Body'];
    }

    /**
     * Retrieve possible tags for bounced messages
     *
     * @return array
     */
    public function getBounceTags()
    {
        $request = $this->_client->get(self::GET_BOUNCE_TAGS);
        $result = $this->_sendRequest($request);
        return $result;
    }

    /**
     * Retrieve delivery statistics
     *
     * @return array
     */
    public function getDeliveryStats()
    {
        $request = $this->_client->get(self::GET_DELIVERY_STATS);
        $result = $this->_sendRequest($request);
        return $result;
    }

    /**
     * Sends request to Postmark API, returns de-serialized JSON response
     *
     * @param Request $request An instance of Guzzle HTTP client request object
     *
     * @return array
     */
    private function _sendRequest(Request $request)
    {
        $response = $request->send();
        return $response->json();
    }

    /**
     * Overrides default Guzzle error handler.
     *
     * By default, Guzzle will throw it's own exception whenever response HTTP status
     * code is not equal to 200. Postmark, however, is using 422 status code when
     * request has an error.
     *
     * @return void
     */
    private function _addErrorHandler()
    {
        $this->_client->getEventDispatcher()->addListener(
            'request.error',
            function (Event $event) {
                if ($event['response']->getStatusCode() == 422) {
                    $error = $event['response']->json();

                    throw new PostmarkErrorException(
                        $error['Message'],
                        $error['ErrorCode']
                    );
                }
            }
        );
    }
}
