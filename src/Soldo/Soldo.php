<?php

namespace Soldo;

use Soldo\Authentication\OAuthCredential;
use Soldo\Exceptions\SoldoSDKException;
use Soldo\Resources\Card;
use Soldo\Resources\Company;
use Soldo\Resources\Employee;
use Soldo\Resources\ExpenseCentre;
use Soldo\Resources\Wallet;

/**
 * Class Soldo
 * @package Soldo
 */
class Soldo
{

    /**
     * @var SoldoClient
     */
    private $client;


    /**
     * Soldo constructor.
     * @param array $config
     * @throws SoldoSDKException
     */
    public function __construct(array $config = [])
    {
        $config = array_merge(
            [
                'environment' => 'demo', //live
                'log.enabled' => false,
                'log.file' => null,
                'log.level' => 'WARNING',
            ],
            $config
        );

        if (!array_key_exists('client_id', $config)) {
            throw new SoldoSDKException('Required "client_id" key is missing in config');
        }

        if (!array_key_exists('client_secret', $config)) {
            throw new SoldoSDKException('Required "client_secret" key is missing in config');
        }

        $this->client = new SoldoClient(
            new OAuthCredential($config['client_id'], $config['client_secret']),
            $config['environment']
        );

    }

    /**
     * Return an array containing a list of Wallet
     *
     * @return array
     */
    public function getWallets($searchFields = [])
    {
        $collection = $this->client->getCollection('\Soldo\Resources\Wallets', $searchFields);
        return $collection->get();
    }

    /**
     * Return a single Wallet resource
     *
     * @param $id
     * @return Wallet
     */
    public function getWallet($id)
    {
        $wallet = $this->client->getItem('\Soldo\Resources\Wallet', $id);
        return $wallet;
    }


    /**
     * Return an array containing a list of ExpenseCentre
     *
     * @return array
     */
    public function getExpenseCentres($searchFields = [])
    {
        $collection = $this->client->getCollection('\Soldo\Resources\ExpenseCentres', $searchFields);
        return $collection->get();
    }

    /**
     * Return a single ExpenseCentre resource
     *
     * @param $id
     * @return ExpenseCentre
     */
    public function getExpenseCentre($id)
    {
        $expense_center = $this->client->getItem('\Soldo\Resources\ExpenseCentre', $id);
        return $expense_center;
    }

    /**
     * Update the ExpenseCentre by id and return the resource up to date
     *
     * @param $id
     * @param $data
     * @return ExpenseCentre
     */
    public function updateExpenseCentre($id, $data)
    {
        $expense_center = $this->client->updateItem('\Soldo\Resources\ExpenseCentre', $id, $data);
        return $expense_center;
    }


    /**
     * Return an array containing a list of Employee
     *
     * @param array $search_fields
     * @return array
     */
    public function getEmployees($searchFields = [])
    {
        $collection = $this->client->getCollection('\Soldo\Resources\Employees', $searchFields);
        return $collection->get();
    }

    /**
     * Return a single Employee resource
     *
     * @param $id
     * @return Employee
     */
    public function getEmployee($id)
    {
        $employee = $this->client->getItem('\Soldo\Resources\Employee', $id);
        return $employee;
    }


    /**
     * Return an array containing a list of Card
     *
     * @param array $search_fields
     * @return array
     */
    public function getCards($searchFields = [])
    {
        $collection = $this->client->getCollection('\Soldo\Resources\Cards', $searchFields);
        return $collection->get();
    }

    /**
     * Return a single Card resource
     *
     * @param $id
     * @return Card
     */
    public function getCard($id)
    {
        $card = $this->client->getItem('\Soldo\Resources\Card', $id);
        return $card;
    }

    /**
     * Update the Employee by id and return the resource up to date
     *
     * @param $id
     * @param $data
     * @return Employee
     */
    public function updateEmployee($id, $data)
    {
        $employee = $this->client->updateItem('\Soldo\Resources\Employee', $id, $data);
        return $employee;
    }


    /**
     * Return a single Company resource
     *
     * @return Company
     */
    public function getCompany()
    {
        $company = $this->client->getItem('\Soldo\Resources\Company');
        return $company;
    }



}
