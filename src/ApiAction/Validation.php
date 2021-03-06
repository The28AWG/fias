<?php

namespace ApiAction;

use AddressStorage;
use PlaceStorage;
use Grace\DBAL\ConnectionAbstract\ConnectionInterface;

class Validation implements ApiActionInterface
{
    /** @var ConnectionInterface */
    private $db;
    private $address;

    public function __construct(ConnectionInterface $db, $address)
    {
        $this->address = $address;
        $this->db      = $db;
    }

    public function run()
    {
        $result = array();

        $addressData = $this->lookUpInFias();
        if ($addressData) {
            $addressData['tags'] = array('address');
            $result[]            = $addressData;
        }

        $placeData = $this->lookUpInPlaces();
        if ($placeData) {
            $result[] = array(
                'is_valid'    => $placeData['is_valid'],
                'is_complete' => $placeData['is_complete'],
                'tags'        => array('place', $placeData['type_system_name'])
            );
        }

        return $result;
    }

    private function lookUpInFias()
    {
        $storage = new AddressStorage($this->db);

        $completeAddress = $storage->findHouse($this->address);
        if ($completeAddress) {
            return array('is_complete' => true, 'is_valid' => true);
        }

        $incompleteAddress = $storage->findAddress($this->address);
        if ($incompleteAddress) {
            return array('is_complete' => false, 'is_valid' => true);
        }

        // ничего не нашлось
        return null;
    }

    private function lookUpInPlaces()
    {
        $storage = new PlaceStorage($this->db);
        $place   = $storage->findPlace($this->address);

        if ($place) {
            return array(
                'type_system_name' => $place['type_system_name'],
                'is_valid'         => true,
                'is_complete'      => true
            );
        }

        return null;
    }
}
