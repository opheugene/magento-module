<?php

namespace Retailcrm\Retailcrm\Test\Unit\Model\Service;

use Retailcrm\Retailcrm\Test\TestCase;

class InventoriesUploadTest extends TestCase
{
    private $mockApi;
    private $mockProductRepository;
    private $mockResponse;
    private $mockProduct;

    public function setUp()
    {
        $this->mockApi = $this->getMockBuilder(\Retailcrm\Retailcrm\Helper\Proxy::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'storeInventories',
                'isConfigured'
            ])
            ->getMock();

        $this->mockProductRepository = $this->getMockBuilder(\Magento\Catalog\Api\ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->mockResponse = $this->getMockBuilder(\RetailCrm\Response\ApiResponse::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSuccessful'])
            ->getMock();

        $this->mockProduct = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setStockData',
                'save'
                ])
            ->getMock();
    }

    /**
     * @param $response
     *
     * @dataProvider dataProviderLoadStocks
     */
    public function testInventoriesUpdload($response)
    {
        if ($response != false) {
            $responseInventories = new \RetailCrm\Response\ApiResponse(200, json_encode($response));
            $responseInventories->asJsonResponse($response);

            $this->mockResponse->expects($this->any())
                ->method('isSuccessful')
                ->willReturn(true);

            $this->mockApi->expects($this->any())
                ->method('isConfigured')
                ->willReturn(true);

            $this->mockApi->expects($this->any())
                ->method('storeInventories')
                ->willReturn($responseInventories);

            $this->mockProductRepository->expects($this->any())
                ->method('getById')
                ->willReturn($this->mockProduct);
        } else {
            $this->mockResponse->expects($this->any())
                ->method('isSuccessful')
                ->willReturn($response);
        }

        $inventoriesUpload = new \Retailcrm\Retailcrm\Model\Service\InventoriesUpload($this->mockProductRepository, $this->mockApi);
        $result = $inventoriesUpload->uploadInventory();

        if (!$response['success']) {
            $this->assertEquals(false, $result);
        } else {
            $this->assertEquals(true, $result);
        }
    }

    private function getResponseData()
    {
        return [
            'true' => $this->getApiInventories(),
            'false' => false
        ];
    }

    public function dataProviderLoadStocks()
    {
        $response = $this->getResponseData();

        return [
            [
                'response' => $response['true']
            ],
            [
                'response' => $response['false']
            ]
        ];
    }

    private function getApiInventories()
    {
        return [
            'success' => true,
            'pagination' => [
                'limit' => 250,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
            ],
            'offers' => [
                [
                    'externalId' => 1,
                    'xmlId' => 'xmlId',
                    'quantity' => 10
                ]
            ]
        ];
    }
}
