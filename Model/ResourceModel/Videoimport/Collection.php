<?php

namespace WebTechnologyCodes\VideoImport\Model\ResourceModel\Videoimport;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('WebTechnologyCodes\VideoImport\Model\Videoimport', 'WebTechnologyCodes\VideoImport\Model\ResourceModel\Videoimport');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }

}
?>