<?php

namespace Retailcrm\Retailcrm\Model\Icml;

class Icml
{
    private $dd;
    private $eCategories;
    private $eOffers;
    private $shop;
    private $manager;
    private $category;
    private $storeManager;
    private $StockState;
    private $configurable;
    private $config;
    private $dirList;
    private $ddFactory;
    private $resourceModelProduct;
    private $searchCriteriaBuilder;
    private $productRepository;
    private $dimensionFields = ['height', 'length', 'width'];

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $manager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogInventory\Api\StockStateInterface $StockState,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\DomDocument\DomDocumentFactory $ddFactory,
        \Magento\Framework\Filesystem\DirectoryList $dirList,
        \Magento\Catalog\Model\ResourceModel\Product $resourceModelProduct,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->configurable = $configurable;
        $this->StockState = $StockState;
        $this->storeManager = $storeManager;
        $this->category = $categoryCollectionFactory;
        $this->manager = $manager;
        $this->config = $config;
        $this->ddFactory = $ddFactory;
        $this->dirList = $dirList;
        $this->resourceModelProduct = $resourceModelProduct;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
    }

    /**
     * Generate icml catelog
     *
     * @param \Magento\Store\Model\Website $website
     *
     * @return void
     */
    public function generate($website)
    {
        $this->shop = $website;

        $string = '<?xml version="1.0" encoding="UTF-8"?>
            <yml_catalog date="' . date('Y-m-d H:i:s') . '">
                <shop>
                    <name>' . $website->getName() . '</name>
                    <categories/>
                    <offers/>
                </shop>
            </yml_catalog>
        ';

        $xml = simplexml_load_string(
            $string,
            '\Magento\Framework\Simplexml\Element',
            LIBXML_NOENT | LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE
        );

        $this->dd = $this->ddFactory->create();
        $this->dd->preserveWhiteSpace = false;
        $this->dd->formatOutput = true;
        $this->dd->loadXML($xml->asXML());

        $this->eCategories = $this->dd
            ->getElementsByTagName('categories')->item(0);
        $this->eOffers = $this->dd
            ->getElementsByTagName('offers')->item(0);

        $this->addCategories();
        $this->addOffers();

        $this->dd->saveXML();
        $this->dd->save($this->dirList->getRoot() . '/retailcrm_' . $website->getCode() . '.xml');
    }

    /**
     * Add product categories in icml catalog
     *
     * @return void
     */
    private function addCategories()
    {
        $collection = $this->category->create();
        $collection->addAttributeToSelect('*');

        foreach ($collection as $category) {
            if ($category->getId() > 1) {
                $e = $this->eCategories->appendChild(
                    $this->dd->createElement('category')
                );

                $e->appendChild($this->dd->createElement('name', \htmlspecialchars($category->getName())));

                if ($category->getImageUrl()) {
                    $e->appendChild($this->dd->createElement('picture', \htmlspecialchars($category->getImageUrl())));
                }

                $e->setAttribute('id', $category->getId());

                if ($category->getParentId() > 1) {
                    $e->setAttribute('parentId', $category->getParentId());
                }
            }
        }
    }

    /**
     * Write products in icml catalog
     *
     * @return void
     */
    private function addOffers()
    {
        $offers = $this->buildOffers();

        foreach ($offers as $offer) {
            $this->addOffer($offer);
        }
    }

    /**
     * Write product in icml catalog
     *
     * @param array $offer
     *
     * @return void
     */
    private function addOffer($offer)
    {
        $e = $this->eOffers->appendChild(
            $this->dd->createElement('offer')
        );

        $e->setAttribute('id', $offer['id']);
        $e->setAttribute('productId', $offer['productId']);

        if (!empty($offer['quantity'])) {
            $e->setAttribute('quantity', (int) $offer['quantity']);
        } else {
            $e->setAttribute('quantity', 0);
        }

        if (!empty($offer['categoryId'])) {
            foreach ($offer['categoryId'] as $categoryId) {
                $e->appendChild(
                    $this->dd->createElement('categoryId')
                )->appendChild(
                    $this->dd->createTextNode($categoryId)
                );
            }
        } else {
            $e->appendChild($this->dd->createElement('categoryId', 1));
        }

        $e->appendChild($this->dd->createElement('productActivity'))
        ->appendChild(
            $this->dd->createTextNode($offer['productActivity'])
        );

        $e->appendChild($this->dd->createElement('name'))
        ->appendChild(
            $this->dd->createTextNode($offer['name'])
        );

        $e->appendChild($this->dd->createElement('productName'))
        ->appendChild(
            $this->dd->createTextNode($offer['productName'])
        );

        $e->appendChild($this->dd->createElement('price'))
        ->appendChild(
            $this->dd->createTextNode($offer['initialPrice'])
        );

        if (!empty($offer['purchasePrice'])) {
            $e->appendChild($this->dd->createElement('purchasePrice'))
            ->appendChild(
                $this->dd->createTextNode($offer['purchasePrice'])
            );
        }

        if (!empty($offer['picture'])) {
            $e->appendChild($this->dd->createElement('picture'))
            ->appendChild(
                $this->dd->createTextNode($offer['picture'])
            );
        }

        if (!empty($offer['url'])) {
            $e->appendChild($this->dd->createElement('url'))
            ->appendChild(
                $this->dd->createTextNode($offer['url'])
            );
        }

        if (!empty($offer['vendor'])) {
            $e->appendChild($this->dd->createElement('vendor'))
            ->appendChild(
                $this->dd->createTextNode($offer['vendor'])
            );
        }

        if (!empty($offer['dimensions'])) {
            $e->appendChild($this->dd->createElement('dimensions'))
            ->appendChild(
                $this->dd->createTextNode($offer['dimensions'])
            );
        }

        if (!empty($offer['weight'])) {
            $e->appendChild($this->dd->createElement('weight'))
            ->appendChild(
                $this->dd->createTextNode($offer['weight'])
            );
        }

        if (!empty($offer['params'])) {
            foreach ($offer['params'] as $param) {
                $paramNode = $this->dd->createElement('param');
                $paramNode->setAttribute('name', $param['name']);
                $paramNode->setAttribute('code', $param['code']);
                $paramNode->appendChild(
                    $this->dd->createTextNode($param['value'])
                );
                $e->appendChild($paramNode);
            }
        }
    }

    /**
     * Build offers array
     *
     * @return array $offers
     */
    private function buildOffers()
    {
        $offers = [];

        $criteria = $this->searchCriteriaBuilder
            ->addFilter('website_id', $this->shop->getId(), 'eq')
            ->create();
        $collection = $this->productRepository->getList($criteria);
        $customAdditionalAttributes = $this->config->getValue('retailcrm/catalog/attributes_to_export_into_icml');

        foreach ($collection->getItems() as $product) {
            if ($product->getTypeId() == 'simple') {
                $offers[] = $this->buildOffer($product, $customAdditionalAttributes);
            }

            if ($product->getTypeId() == 'configurable') {
                $associated_products = $this->getAssociatedProducts($product);

                foreach ($associated_products as $associatedProduct) {
                    $offers[] = $this->buildOffer($product, $customAdditionalAttributes, $associatedProduct);
                }
            }
        }

        return $offers;
    }

    /**
     * Build offer array
     *
     * @param object $product
     * @param $customAdditionalAttributes
     * @param object $associatedProduct
     *
     * @return array $offer
     */
    private function buildOffer($product, $customAdditionalAttributes, $associatedProduct = null)
    {
        $offer = [];

        $store = $this->shop->getDefaultStore() ? $this->shop->getDefaultStore() : $this->storeManager->getStore();
        $picUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        $offer['id'] = $associatedProduct === null ? $product->getId() : $associatedProduct->getId();
        $offer['productId'] = $product->getId();

        if ($associatedProduct === null) {
            $offer['productActivity'] = $product->isAvailable() ? 'Y' : 'N';
        } else {
            $offer['productActivity'] = $associatedProduct->isAvailable() ? 'Y' : 'N';
        }

        $offer['name'] = $associatedProduct === null ? $product->getName() : $associatedProduct->getName();
        $offer['productName'] = $product->getName();
        $offer['initialPrice'] = $associatedProduct === null
            ? $product->getFinalPrice()
            : $associatedProduct->getFinalPrice();
        $offer['url'] = $product->getUrlInStore();

        if ($associatedProduct === null) {
            $offer['picture'] = $picUrl . 'catalog/product' . $product->getImage();
        } else {
            $offer['picture'] = $picUrl . 'catalog/product' . $associatedProduct->getImage();
        }

        $offer['quantity'] = $associatedProduct === null
            ? $this->getStockQuantity($product)
            : $this->getStockQuantity($associatedProduct);
        $offer['categoryId'] = $associatedProduct === null
            ? $product->getCategoryIds()
            : $associatedProduct->getCategoryIds();
        $offer['vendor'] = $associatedProduct === null
            ? $product->getAttributeText('manufacturer')
            : $associatedProduct->getAttributeText('manufacturer');
        $offer['weight'] = $associatedProduct === null
            ? $product->getWeight()
            : $associatedProduct->getWeight();

        $params = $this->getOfferParams($product, $customAdditionalAttributes, $associatedProduct);

        $offer['params'] = $params['params'];
        $offer['dimensions'] = $params['dimensions'];

        unset($params);

        return $offer;
    }

    /**
     * Get parameters offers
     *
     * @param object $product
     * @param $customAdditionalAttributes
     * @param object $associatedProduct
     *
     * @return array $params
     */
    private function getOfferParams($product, $customAdditionalAttributes, $associatedProduct = null)
    {
        $params = [];

        if (!$customAdditionalAttributes) {
            return $params;
        }

        $attributes = explode(',', $customAdditionalAttributes);
        $dimensionsAttrs = [];
        $dimensions = '';

        foreach ($attributes as $attributeCode) {
            if ($this->checkDimension($attributeCode) !== false) {
                $dimensionsAttrs += $this->checkDimension($attributeCode);

                continue;
            }

            $attribute = $this->resourceModelProduct->getAttribute($attributeCode);
            $attributeValue = $associatedProduct
                ? $associatedProduct->getData($attributeCode)
                : $product->getData($attributeCode);
            $attributeText = $attribute->getSource()->getOptionText($attributeValue);

            if ($attribute && $attributeValue) {
                $params[] = [
                    'name' => $attribute->getDefaultFrontendLabel(),
                    'code' => $attributeCode,
                    'value' => $attributeText ? $attributeText : $attributeValue
                ];
            }
        }

        if ($dimensionsAttrs && count($dimensionsAttrs) == 3) {
            $length = $associatedProduct
                ? $associatedProduct->getData($dimensionsAttrs['length'])
                : $product->getData($dimensionsAttrs['length']);
            $width = $associatedProduct
                ? $associatedProduct->getData($dimensionsAttrs['width'])
                : $product->getData($dimensionsAttrs['width']);
            $height = $associatedProduct
                ? $associatedProduct->getData($dimensionsAttrs['height'])
                : $product->getData($dimensionsAttrs['height']);

            if ($length && $width && $height) {
                $dimensions = sprintf(
                    '%s/%s/%s',
                    $length,
                    $width,
                    $height
                );
            }
        }

        return ['params' => $params, 'dimensions' => $dimensions];
    }

    /**
     * Get associated products
     *
     * @param object $product
     *
     * @return object
     */
    private function getAssociatedProducts($product)
    {
        return $this->configurable
            ->getUsedProductCollection($product)
            ->addAttributeToSelect('*')
            ->addFilterByRequiredOptions();
    }

    /**
     * Get product stock quantity
     *
     * @param object $offer
     * @return int $quantity
     */
    private function getStockQuantity($offer)
    {
        $quantity = $this->StockState->getStockQty(
            $offer->getId(),
            $offer->getStore()->getWebsiteId()
        );

        return $quantity;
    }

    /**
     * @param string $attrCode
     *
     * @return mixed
     */
    private function checkDimension($attrCode)
    {
        foreach ($this->dimensionFields as $dimensionField) {
            if (mb_strpos($attrCode, $dimensionField) !== false) {
                return [$dimensionField => $attrCode];
            }
        }

        return false;
    }
}
