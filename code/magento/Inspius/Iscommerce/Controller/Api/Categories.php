<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Controller\Api;

class Categories extends AbstractApi
{
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * Categories constructor.
     * 
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Inspius\Iscommerce\Helper\Data $helper
     * @param \Inspius\Iscommerce\Model\Client $client
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Event\Manager $manager,
        \Inspius\Iscommerce\Helper\Data $helper,
        \Inspius\Iscommerce\Model\Client $client
    )
    {
        $this->_categoryFactory = $categoryFactory;
        parent::__construct($context, $resultJsonFactory, $scopeConfig, $manager, $helper, $client);
    }

    /**
     * return category list
     * 
     * @return array
     */
    public function _getResponse()
    {
        $categoryCollection = $this->_categoryFactory->create()->getCollection()->addFieldToSelect('*');
        $categories = [];
        foreach ($categoryCollection as $category) {
            /* @var $category \Magento\Catalog\Model\Category */
            if ($this->_isParentActive($category, $categoryCollection)) {
                $categories[] = $this->_helper->formatCategory($category);
            }
        }
        return $categories;
    }

    /**
     * check whether a category and its parents are active
     * 
     * @param $category
     * @param $categoryCollection
     * @return bool
     */
    private function _isParentActive($category, $categoryCollection)
    {
        /* @var $category \Magento\Catalog\Model\Category */
        /* @var $categoryCollection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        if ($category->getIsActive()) {
            $parentId = $category->getParentId();
            // category id = 1 => root category
            if ($parentId == 1) {
                return true;
            }

            // check parent whether active or not
            $parent = $categoryCollection->getItemById($parentId);
            return $this->_isParentActive($parent, $categoryCollection);
        }
        return false;
    }
}