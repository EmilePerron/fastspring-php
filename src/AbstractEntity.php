<?php

namespace Emileperron\FastSpring;

abstract class AbstractEntity implements \ArrayAccess {

    protected $record = [];

    protected static $endpoint = null;

    public function __construct(array $record = [])
    {
        $this->record = $record;
    }

    public static function find($ids)
    {
        $idsList = (array) $ids;
        $response = FastSpring::get(static::$endpoint, $idsList);
        static::checkResponse($response);
        $entities = static::getEntityArrayFromResponse($response);

        if (!is_array($ids)) {
            return array_pop($entities);
        }

        return $entities;
    }

    public static function findBy(array $filters)
    {
        $response = FastSpring::get(static::$endpoint, $filters);
        static::checkResponse($response);
        $entities = static::getEntityArrayFromResponse($response);

        if (!is_array($ids)) {
            return array_pop($entities);
        }

        // Handle pagination automatically
        while (isset($response['nextPage']) && $response['nextPage'] != null) {
            try {
                $nextPage = intval($response['page']) + 1;
                $response = FastSpring::get(static::$endpoint, ['page' => $nextPage]);
                static::checkResponse($response);
                $pageEntities = static::getEntityArrayFromResponse($response);

                if (is_array($pageEntities) && is_array($entities)) {
                    $entities = array_merge($entities, $pageEntities);
                }
            } catch (\Exeception $e) {}
        }

        return $entities;
    }

    public static function findAll()
    {
        $response = FastSpring::get(static::$endpoint, ['limit' => 2]);
        static::checkResponse($response);

        if (!$response) {
            return [];
        }

        $idsList = static::getResponseBody($response);

        // Handle pagination automatically
        while (isset($response['nextPage']) && $response['nextPage'] != null) {
            try {
                $nextPage = intval($response['page']) + 1;
                $response = FastSpring::get(static::$endpoint, ['page' => $nextPage]);
                $pageIdsList = static::getResponseBody($response);

                if (is_array($pageIdsList) && is_array($idsList)) {
                    $idsList = array_merge($idsList, $pageIdsList);
                }
            } catch (\Exeception $e) {}
        }

        return static::find($idsList);
    }

    public function delete()
    {
        $response = FastSpring::delete(static::$endpoint, [$this->getId()]);
        static::checkResponse($response);
        return true;
    }

    public function save()
    {
        throw new \Exception('The save() method has not yet been implemented.');

        # @TODO: Implement the save() method, which currently results in a 400 Bad Request response from FastSpring.
        /*
        $endpoint = static::getCleanEndpoint();
        $payload = [
            $endpoint => [
                $this->record
            ]
        ];
        $response = FastSpring::post(static::$endpoint, $payload);
        static::checkResponse($response);
        */
    }

    public function getId()
    {
        return static::getIdFromRecord($this->record);
    }

    private static function checkResponse($response)
    {
        $responseBody = static::getResponseBody($response);
        $errors = [];

        if (!$responseBody || !is_array($responseBody)) {
            $errors[] = 'FastSpring\'s API returned an empty response.';
        }

        if (!$errors) {
            foreach ($responseBody as $record) {
                if (isset($record['result']) && $record['result'] == 'error') {
                    $errors[] = json_encode($record);
                }
            }
        }

        if ($errors) {
            throw new \Exception(sprintf("The following errors occured in the call to FastSpring's API: %s", implode("\n", $errors)));
        }
    }

    private static function getResponseBody($response)
    {
        $endpoint = static::getCleanEndpoint();

        if (array_key_exists($endpoint, $response)) {
            return $response[$endpoint];
        }

        if (array_key_exists('result', $response)) {
            return [$response];
        }

        return null;
    }

    private static function getEntityArrayFromResponse($response)
    {
        $entities = [];
        $responseBody = static::getResponseBody($response);

        foreach ($responseBody as $record) {
            $recordId = static::getIdFromRecord($record);
            unset($record['action'], $record['result'], $record['error']);
            $entities[$recordId] = new static($record);
        }

        return $entities;
    }

    private static function getIdFromRecord(array $record)
    {
        // This will break if new endpoints where the singular version ends with S, but it's good enough for now.
        return $record[rtrim(static::getCleanEndpoint(), 's')] ?? null;
    }

    private static function getCleanEndpoint()
    {
        return trim(static::$endpoint, '/');
    }

    public function offsetSet($offset, $value) {
        if (!is_null($offset)) {
            $this->record[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->record[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->record[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->record[$offset]) ? $this->record[$offset] : null;
    }
}
