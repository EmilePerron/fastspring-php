<?php

namespace Emileperron\FastSpring;

use Emileperron\FastSpring\Exception\NotFoundException;

class FastSpring {

    private $host = 'https://api.fastspring.com';
    private $apiUsername;
    private $apiPassword;

    private static $instance = null;

    private function __construct(string $apiUsername, string $apiPassword)
    {
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
    }

    public static function initialize(string $apiUsername, string $apiPassword)
    {
        static::$instance = new static($apiUsername, $apiPassword);
    }

    /**
     * @throws NotFoundException
     */
    private function request(string $method, string $endpoint, $payload = null)
    {
        $method = $this->standardizeMethod($method);
        $url = $this->buildUrl($method, $endpoint, $payload);
        $payload = $this->standardizePayload($payload);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiUsername . ':' . $this->apiPassword);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        } else if ($method != 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($info['http_code'] == 200) {
            return is_string($response) ? json_decode($response, true) : $response;
        }

        if ($this->isResponseNotFound($response)) {
            throw new NotFoundException(sprintf('Error %s reported by FastSpring\'s API for your %s request to the "%s" endpoint. Response: %s', $info['http_code'], $method, $endpoint, json_encode($response)));
        }

        throw new \Exception(sprintf('Error %s reported by FastSpring\'s API for your %s request to the "%s" endpoint. Response: %s', $info['http_code'], $method, $endpoint, json_encode($response)));
    }

    /**
     * Checks if the body of a response seem to indicate
     * that the desired entity could not be found.
     * 
     * FastSpring doesn't return a 404 HTTP code when they
     * cannot find an entity: they return a 400 Bad Request 
     * with a generic error message in the body. This method
     * attempts to detect if the provided response's body
     * contains said error message, indicating that it should
     * actually be a 404 Not Found.
     */
    protected function isResponseNotFound($response)
    {
        try {
            $decodedResponse = is_string($response) ? json_decode($response, true) : $response;
        } catch (\Exception $e) {
            return false;
        }

        $containsNotFoundString = false;
        $containsErrorResult = false;
        
        array_walk_recursive($decodedResponse, function ($value, $key) {
            if ($key == "result" && $value == "error") {
                $containsErrorResult = true;
            } else if ($value == "Not found") {
                $containsNotFoundString = true;
            }
        });

        return $containsErrorResult && $containsNotFoundString;
    }

    protected static function instance()
    {
        if (!static::$instance) {
            throw new \Exception("You must call Emileperron\FastSpring\FastSpring::initialize() with your API credentials before you can start making requests.");
        }

        return static::$instance;
    }

    /**
     * @throws NotFoundException
     */
    public static function get(string $endpoint, $payload = null)
    {
        return static::instance()->request('GET', $endpoint, $payload);
    }

    /**
     * @throws NotFoundException
     */
    public static function post(string $endpoint, $payload = null)
    {
        return static::instance()->request('POST', $endpoint, $payload);
    }

    /**
     * @throws NotFoundException
     */
    public static function put(string $endpoint, $payload = null)
    {
        return static::instance()->request('PUT', $endpoint, $payload);
    }

    /**
     * @throws NotFoundException
     */
    public static function delete(string $endpoint, $payload = null)
    {
        return static::instance()->request('DELETE', $endpoint, $payload);
    }

    private function standardizeMethod(string $method)
    {
        $uppercaseMethod = strtoupper($method);

        if (!in_array($uppercaseMethod, ['GET', 'POST', 'PUT', 'DELETE'])) {
            throw new \Exception(sprintf("Invalid method \"%s\" provided for request.", $method));
        }

        return $uppercaseMethod;
    }

    private function standardizePayload($payload)
    {
        if (!$payload) {
            return null;
        }

        if (is_array($payload) || is_object($payload)) {
            $payload = json_encode($payload);
        }

        try {
            json_decode($payload);
        } catch (\Exception $e) {
            throw new \Exception("Invalid body provided for request: an array, object or JSON string is expected.");
        }

        return $payload;
    }

    private function buildUrl(string $method, string $endpoint, $payload)
    {
        $url = $this->host . '/' . trim($endpoint, '/');

        if (in_array($method, ['GET', 'DELETE']) && is_array($payload)) {
            // Associative array = filters
            if (array_keys($payload) !== range(0, count($payload) - 1)) {
                $url .= '?' . http_build_query($payload);
            } else {
                // Regular array = list of ids
                $url .= '/' . implode(',', $payload);
            }
        }

        return $url;
    }
}
