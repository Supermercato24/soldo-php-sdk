<?php

namespace Soldo;

use Soldo\Exceptions\SoldoInvalidEvent;
use Soldo\Resources\Resource;
use Soldo\Validators\ValidatorTrait;

/**
 * Class SoldoWebhook
 * @package Soldo
 */
class SoldoEvent
{
    use ValidatorTrait;

    /**
     * Define webhook supported data types
     */
    const EVENT_TYPE_CARD = 'Card';
    const EVENT_TYPE_TRANSACTION = 'Transaction';
    const EVENT_TYPE_EMPLOYEE = 'Employee';

    /**
     * An identifier for the event
     *
     * @var string
     */
    private $type;

    /**
     * The resource that triggered the event
     *
     * @var \Soldo\Resources\Resource
     */
    private $resource;

    /**
     * SoldoWebhook constructor.
     * @param array $data
     * @param string $fingerprint
     * @param string $fingerprintOrder
     * @param string $internalToken
     * @throws SoldoInvalidEvent
     */
    public function __construct($data, $fingerprint, $fingerprintOrder, $internalToken)
    {
        $rules = [
            'event_type' => 'required',
            'event_name' => 'required',
            'data' => 'array',
        ];

        if (!$this->validateRawData($data, $rules)) {
            throw new SoldoInvalidEvent(
                'Invalid webhook data'
            );
        }

        if (!in_array($data['event_type'], self::types())) {
            throw new SoldoInvalidEvent(
                'Event type not supported'
            );
        }

        // build resource
        $className = '\Soldo\Resources\\' . $data['event_type'];
        /** @var \Soldo\Resources\Resource $resource */
        $resource = new $className($data['data']);

        $fingerprintOrder = explode(',', $fingerprintOrder);
        $resourceFingerprint = $resource->buildFingerprint($fingerprintOrder, $internalToken);
        if ($fingerprint !== $resourceFingerprint) {
            throw new SoldoInvalidEvent(
                'Cannot verify the given fingerprint'
            );
        }

        $this->resource = $resource;
        $this->type = $this->resource->getEventType();
    }

    /**
     * Return the event type
     *
     * @return null|string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Return the resource that triggered the event
     *
     * @throws SoldoInvalidEvent
     * @return Resources\Resource
     */
    public function get()
    {
        return $this->resource;
    }

    /**
     * Get supported data types
     *
     * @return array
     */
    public static function types()
    {
        return [
            self::EVENT_TYPE_CARD,
            self::EVENT_TYPE_TRANSACTION,
            self::EVENT_TYPE_EMPLOYEE,
        ];
    }
}
