<?php

namespace PaymentStripe\Model;

use Core\Model\Base;

/**
 * Class PaymentIntent
 * @package PaymentStripe\Model
 *
 * @method Db\Table\PaymentIntent getTable()
 * @method integer getId()
 * @method string getToken()
 * @method string getStatus()
 */
class PaymentIntent extends Base
{
    /**
     * PaymentIntent constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'PaymentStripe\Model\Db\Table\PaymentIntent';
    }

    /**
     * @param $reason
     * @param null $cron
     */
    public function cancel($reason, $cron = null)
    {

    }

    /**
     * @return array|string
     */
    public function toJson()
    {
        $payload = [
            'id' => (integer) $this->getId(),
            'token' => (string) $this->getToken(),
            'status' => (string) $this->getStatus(),
        ];

        return $payload;
    }
}