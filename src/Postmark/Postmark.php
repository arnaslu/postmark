<?php

namespace Postmark;

use Postmark\Exception\PostmarkErrorException;
use Guzzle\Common\Event;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Request;

class Postmark
{
	private $client;

	const SEND_EMAIL = '/email';
	const SEND_BATCH = '/email/batch';
	const GET_BOUNCES = '/bounces';
	const GET_BOUNCE = '/bounces/%id%';
	const GET_BOUNCE_DUMP = '/bounces/%id%/dump';
	const GET_BOUNCE_TAGS = '/bounces/tags';
	const ACTIVATE_BOUNCE = '/bounces/%id%/activate';
	const GET_DELIVERY_STATS = '/deliverystats';

	public function __construct($apiKey, Client $client)
	{
		$client->setBaseUrl('https://api.postmarkapp.com');
		$client->setDefaultOption('headers', [
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'X-Postmark-Server-Token' => $apiKey,
		]);

		$this->client = $client;

		$this->_addErrorHandler();
	}

	public function send(Message $message)
	{
		$request = $this->client->post(self::SEND_EMAIL, null, json_encode($message));
		return $this->_sendRequest($request);
	}

	public function sendBatch(array $messages)
	{
		$request = $this->client->post(self::SEND_BATCH, null, json_encode($messages));
		return $this->_sendRequest($request);
	}

	public function getBounces($count, $offset, array $filters = [])
	{
		// Count and offset are actually regular filter params
		$params = array_merge($filters, [
			'count' => $count,
			'offset' => $offset,
		]);

		// Build GET request
		$request = $this->client->get(self::GET_BOUNCES);

		// Build query string from parameters
		$query = $request->getQuery();
		foreach ($params as $key => $value)
		{
			$query->set($key, $value);
		}

		$result = $this->_sendRequest($request);
		return $result;
	}

	public function getBounce($bounceID)
	{
		$request = $this->client->get(str_replace('%id%', $bounceID, self::GET_BOUNCE));
		$result = $this->_sendRequest($request);
		return $result;
	}

	public function getBounceDump($bounceID)
	{
		$request = $this->client->get(str_replace('%id%', $bounceID, self::GET_BOUNCE_DUMP));
		$result = $this->_sendRequest($request);
		return $result['Body'];
	}

	public function getBounceTags()
	{
		$request = $this->client->get(self::GET_BOUNCE_TAGS);
		$result = $this->_sendRequest($request);
		return $result;
	}

	public function getDeliveryStats()
	{
		$request = $this->client->get(self::GET_DELIVERY_STATS);
		$result = $this->_sendRequest($request);
		return $result;
	}

	private function _sendRequest(Request $request)
	{
		$response = $request->send();
		return $response->json();
	}

	private function _addErrorHandler()
	{
		$this->client->getEventDispatcher()->addListener('request.error', function(Event $event) {
			if ($event['response']->getStatusCode() == 422)
			{
				$error = $event['response']->json();

				throw new PostmarkErrorException($error['Message'], $error['ErrorCode']);
			}
		});
	}
}
