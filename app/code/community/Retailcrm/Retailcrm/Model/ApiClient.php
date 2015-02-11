<?php

/**
 *  retailCRM API client class
 */
class Retailcrm_Retailcrm_Model_ApiClient
{
    const VERSION = 'v3';

    protected $client;

    /**
     * Site code
     */
    protected $siteCode;

    /**
     * Client creating
     *
     * @param  array $parameters
     * @internal param string $siteCode
     */
    public function __construct($parameters)
    {
        $url = $parameters['url'];
        $apiKey = $parameters['key'];
        $site = $parameters['site'];

        if ('/' != substr($url, strlen($url) - 1, 1)) {
            $url .= '/';
        }

        $url = $url . 'api/' . self::VERSION;

        $this->client = new Retailcrm_Retailcrm_Model_Http_Client($url, array('apiKey' => $apiKey));
        $this->siteCode = $site;
    }

    /**
     * Create a order
     *
     * @param  array       $order
     * @param  string      $site (default: null)
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function ordersCreate(array $order, $site = null)
    {
        if (!sizeof($order)) {
            throw new InvalidArgumentException('Parameter `order` must contains a data');
        }

        return $this->client->makeRequest("/orders/create", Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST, $this->fillSite($site, array(
            'order' => json_encode($order)
        )));
    }

    /**
     * Edit a order
     *
     * @param  array $order
     * @param  string $by
     * @param  string $site (default: null)
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function ordersEdit(array $order, $by = 'externalId', $site = null)
    {
        if (!sizeof($order)) {
            throw new InvalidArgumentException('Parameter `order` must contains a data');
        }

        $this->checkIdParameter($by);

        if (!isset($order[$by])) {
            throw new InvalidArgumentException(sprintf('Order array must contain the "%s" parameter.', $by));
        }

        return $this->client->makeRequest(
            "/orders/" . $order[$by] . "/edit",
            Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST,
            $this->fillSite($site, array(
                'order' => json_encode($order),
                'by' => $by,
            ))
        );
    }

    /**
     * Upload array of the orders
     *
     * @param  array       $orders
     * @param  string      $site (default: null)
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function ordersUpload(array $orders, $site = null)
    {
        if (!sizeof($orders)) {
            throw new InvalidArgumentException('Parameter `orders` must contains array of the orders');
        }

        return $this->client->makeRequest("/orders/upload", Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST, $this->fillSite($site, array(
            'orders' => json_encode($orders),
        )));
    }

    /**
     * Get order by id or externalId
     *
     * @param  string      $id
     * @param  string      $by (default: 'externalId')
     * @param  string      $site (default: null)
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function ordersGet($id, $by = 'externalId', $site = null)
    {
        $this->checkIdParameter($by);

        return $this->client->makeRequest("/orders/$id", Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET, $this->fillSite($site, array(
            'by' => $by
        )));
    }

    /**
     * Returns a orders history
     *
     * @param  DateTime   $startDate (default: null)
     * @param  DateTime   $endDate (default: null)
     * @param  int         $limit (default: 100)
     * @param  int         $offset (default: 0)
     * @param  bool        $skipMyChanges (default: true)
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function ordersHistory(
        DateTime $startDate = null,
        DateTime $endDate = null,
        $limit = 100,
        $offset = 0,
        $skipMyChanges = true
    ) {
        $parameters = array();

        if ($startDate) {
            $parameters['startDate'] = $startDate->format('Y-m-d H:i:s');
        }
        if ($endDate) {
            $parameters['endDate'] = $endDate->format('Y-m-d H:i:s');
        }
        if ($limit) {
            $parameters['limit'] = (int) $limit;
        }
        if ($offset) {
            $parameters['offset'] = (int) $offset;
        }
        if ($skipMyChanges) {
            $parameters['skipMyChanges'] = (bool) $skipMyChanges;
        }

        return $this->client->makeRequest('/orders/history', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET, $parameters);
    }

    /**
     * Returns filtered orders list
     *
     * @param  array       $filter (default: array())
     * @param  int         $page (default: null)
     * @param  int         $limit (default: null)
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function ordersList(array $filter = array(), $page = null, $limit = null)
    {
        $parameters = array();

        if (sizeof($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest('/orders', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET, $parameters);
    }

    /**
     * Returns statuses of the orders
     *
     * @param  array       $ids (default: array())
     * @param  array       $externalIds (default: array())
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function ordersStatuses(array $ids = array(), array $externalIds = array())
    {
        $parameters = array();

        if (sizeof($ids)) {
            $parameters['ids'] = $ids;
        }
        if (sizeof($externalIds)) {
            $parameters['externalIds'] = $externalIds;
        }

        return $this->client->makeRequest('/orders/statuses', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET, $parameters);
    }

    /**
     * Save order IDs' (id and externalId) association in the CRM
     *
     * @param  array       $ids
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function ordersFixExternalIds(array $ids)
    {
        if (!sizeof($ids)) {
            throw new InvalidArgumentException('Method parameter must contains at least one IDs pair');
        }

        return $this->client->makeRequest("/orders/fix-external-ids", Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST, array(
            'orders' => json_encode($ids),
        ));
    }

    /**
     * Create a customer
     *
     * @param  array       $customer
     * @param  string      $site (default: null)
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function customersCreate(array $customer, $site = null)
    {
        if (!sizeof($customer)) {
            throw new InvalidArgumentException('Parameter `customer` must contains a data');
        }

        return $this->client->makeRequest("/customers/create", Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST, $this->fillSite($site, array(
            'customer' => json_encode($customer)
        )));
    }

    /**
     * Edit a customer
     *
     * @param  array       $customer
     * @param  string      $by (default: 'externalId')
     * @param  string      $site (default: null)
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function customersEdit(array $customer, $by = 'externalId', $site = null)
    {
        if (!sizeof($customer)) {
            throw new InvalidArgumentException('Parameter `customer` must contains a data');
        }

        $this->checkIdParameter($by);

        if (!isset($customer[$by])) {
            throw new InvalidArgumentException(sprintf('Customer array must contain the "%s" parameter.', $by));
        }

        return $this->client->makeRequest(
            "/customers/" . $customer[$by] . "/edit",
            Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST,
            $this->fillSite($site, array(
                'customer' => json_encode($customer),
                'by' => $by,
            )
        ));
    }

    /**
     * Upload array of the customers
     *
     * @param  array       $customers
     * @param  string      $site (default: null)
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function customersUpload(array $customers, $site = null)
    {
        if (!sizeof($customers)) {
            throw new InvalidArgumentException('Parameter `customers` must contains array of the customers');
        }

        return $this->client->makeRequest("/customers/upload", Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST, $this->fillSite($site, array(
            'customers' => json_encode($customers),
        )));
    }

    /**
     * Get customer by id or externalId
     *
     * @param  string      $id
     * @param  string      $by (default: 'externalId')
     * @param  string      $site (default: null)
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function customersGet($id, $by = 'externalId', $site = null)
    {
        $this->checkIdParameter($by);

        return $this->client->makeRequest("/customers/$id", Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET, $this->fillSite($site, array(
            'by' => $by
        )));
    }

    /**
     * Returns filtered customers list
     *
     * @param  array       $filter (default: array())
     * @param  int         $page (default: null)
     * @param  int         $limit (default: null)
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function customersList(array $filter = array(), $page = null, $limit = null)
    {
        $parameters = array();

        if (sizeof($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest('/customers', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET, $parameters);
    }

    /**
     * Save customer IDs' (id and externalId) association in the CRM
     *
     * @param  array       $ids
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function customersFixExternalIds(array $ids)
    {
        if (!sizeof($ids)) {
            throw new InvalidArgumentException('Method parameter must contains at least one IDs pair');
        }

        return $this->client->makeRequest("/customers/fix-external-ids", Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST, array(
            'customers' => json_encode($ids),
        ));
    }

    /**
     * Returns deliveryServices list
     *
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function deliveryServicesList()
    {
        return $this->client->makeRequest('/reference/delivery-services', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET);
    }

    /**
     * Returns deliveryTypes list
     *
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function deliveryTypesList()
    {
        return $this->client->makeRequest('/reference/delivery-types', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET);
    }

    /**
     * Returns orderMethods list
     *
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function orderMethodsList()
    {
        return $this->client->makeRequest('/reference/order-methods', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET);
    }

    /**
     * Returns orderTypes list
     *
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function orderTypesList()
    {
        return $this->client->makeRequest('/reference/order-types', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET);
    }

    /**
     * Returns paymentStatuses list
     *
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function paymentStatusesList()
    {
        return $this->client->makeRequest('/reference/payment-statuses', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET);
    }

    /**
     * Returns paymentTypes list
     *
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function paymentTypesList()
    {
        return $this->client->makeRequest('/reference/payment-types', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET);
    }

    /**
     * Returns productStatuses list
     *
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function productStatusesList()
    {
        return $this->client->makeRequest('/reference/product-statuses', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET);
    }

    /**
     * Returns statusGroups list
     *
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function statusGroupsList()
    {
        return $this->client->makeRequest('/reference/status-groups', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET);
    }

    /**
     * Returns statuses list
     *
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function statusesList()
    {
        return $this->client->makeRequest('/reference/statuses', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET);
    }

    /**
     * Returns sites list
     *
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function sitesList()
    {
        return $this->client->makeRequest('/reference/sites', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET);
    }

    /**
     * Edit deliveryService
     *
     * @param array $data delivery service data
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function deliveryServicesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/delivery-services/' . $data['code'] . '/edit',
            Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST,
            array(
                'deliveryService' => json_encode($data)
            )
        );
    }

    /**
     * Edit deliveryType
     *
     * @param array $data delivery type data
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function deliveryTypesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/delivery-types/' . $data['code'] . '/edit',
            Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST,
            array(
                'deliveryType' => json_encode($data)
            )
        );
    }

    /**
     * Edit orderMethod
     *
     * @param array $data order method data
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function orderMethodsEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/order-methods/' . $data['code'] . '/edit',
            Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST,
            array(
                'orderMethod' => json_encode($data)
            )
        );
    }

    /**
     * Edit orderType
     *
     * @param array $data order type data
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function orderTypesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/order-types/' . $data['code'] . '/edit',
            Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST,
            array(
                'orderType' => json_encode($data)
            )
        );
    }

    /**
     * Edit paymentStatus
     *
     * @param array $data payment status data
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function paymentStatusesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/payment-statuses/' . $data['code'] . '/edit',
            Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST,
            array(
                'paymentStatus' => json_encode($data)
            )
        );
    }

    /**
     * Edit paymentType
     *
     * @param array $data payment type data
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function paymentTypesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/payment-types/' . $data['code'] . '/edit',
            Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST,
            array(
                'paymentType' => json_encode($data)
            )
        );
    }

    /**
     * Edit productStatus
     *
     * @param array $data product status data
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function productStatusesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/product-statuses/' . $data['code'] . '/edit',
            Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST,
            array(
                'productStatus' => json_encode($data)
            )
        );
    }

    /**
     * Edit order status
     *
     * @param array $data status data
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function statusesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/statuses/' . $data['code'] . '/edit',
            Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST,
            array(
                'status' => json_encode($data)
            )
        );
    }

    /**
     * Edit site
     *
     * @param array $data site data
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function sitesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/sites/' . $data['code'] . '/edit',
            Retailcrm_Retailcrm_Model_Http_Client::METHOD_POST,
            array(
                'site' => json_encode($data)
            )
        );
    }

    /**
     * Update CRM basic statistic
     *
     * @return Retailcrm_Retailcrm_Model_Response_ApiResponse
     */
    public function statisticUpdate()
    {
        return $this->client->makeRequest('/statistic/update', Retailcrm_Retailcrm_Model_Http_Client::METHOD_GET);
    }

    /**
     * Return current site
     *
     * @return string
     */
    public function getSite()
    {
        return $this->siteCode;
    }

    /**
     * Set site
     *
     * @param  string $site
     * @return void
     */
    public function setSite($site)
    {
        $this->siteCode = $site;
    }

    /**
     * Check ID parameter
     *
     * @param  string $by
     * @return bool
     */
    protected function checkIdParameter($by)
    {
        $allowedForBy = array('externalId', 'id');
        if (!in_array($by, $allowedForBy)) {
            throw new InvalidArgumentException(sprintf(
                'Value "%s" for parameter "by" is not valid. Allowed values are %s.',
                $by,
                implode(', ', $allowedForBy)
            ));
        }

        return true;
    }

    /**
     * Fill params by site value
     *
     * @param  string $site
     * @param  array $params
     * @return array
     */
    protected function fillSite($site, array $params)
    {
        if ($site) {
            $params['site'] = $site;
        } elseif ($this->siteCode) {
            $params['site'] = $this->siteCode;
        }

        return $params;
    }
}