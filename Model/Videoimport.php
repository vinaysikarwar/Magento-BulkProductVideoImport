<?php
namespace WebTechnologyCodes\VideoImport\Model;

class Videoimport extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('WebTechnologyCodesWebTechnologyCodes\VideoImport\Model\ResourceModel\Videoimport');
    }
}
?>