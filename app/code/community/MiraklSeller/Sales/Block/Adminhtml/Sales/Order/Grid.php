<?php

class MiraklSeller_Sales_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
    /**
     * @var MiraklSeller_Api_Model_Resource_Connection_Collection
     */
    protected $_connections;

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumnAfter(
            'mirakl_connection_id', array(
                'header'                    => $this->__('Source'),
                'type'                      => 'options',
                'frame_callback'            => array($this, 'decorateSource'),
                'filter_condition_callback' => array($this, 'filterSource'),
                'options'                   => $this->_getOptions(),
                'sortable'                  => false,
            ), 'real_order_id'
        );

        return parent::_prepareColumns();
    }

    /**
     * Handles decoration of the "Source" column
     *
     * @param   string                  $value
     * @param   Mage_Sales_Model_Order  $order
     * @return  string
     */
    public function decorateSource($value, $order)
    {
        $class = 'magento';
        $label = $this->__('Magento');

        if ($connectionId = $order->getMiraklConnectionId()) {
            $class = 'marketplace';
            $connection = $this->_getConnections()->getItemById($connectionId);
            $label = $connection ? $connection->getName() : $this->__('Unknown Connection');
        }

        return sprintf('<span class="%s">%s</span>', $class, $label);
    }

    /**
     * Handles order filtering by the "Source" column
     *
     * @param   Mage_Sales_Model_Resource_Order_Collection  $collection
     * @param   Mage_Adminhtml_Block_Widget_Grid_Column     $column
     */
    public function filterSource($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $cond = $value === '0' ? array('null' => true) : array('eq' => $value);
        $collection->addFieldToFilter('main_table.mirakl_connection_id', $cond);
    }

    /**
     * @return  MiraklSeller_Api_Model_Resource_Connection_Collection
     */
    protected function _getConnections()
    {
        if (null === $this->_connections) {
            $this->_connections = Mage::getModel('mirakl_seller_api/connection')->getCollection();
        }

        return $this->_connections;
    }

    /**
     * @return  array
     */
    protected function _getOptions()
    {
        $options = array($this->__('Magento'));

        /** @var MiraklSeller_Api_Model_Connection $connection */
        foreach ($this->_getConnections() as $connection) {
            $options[$connection->getId()] = $connection->getName();
        }

        return $options;
    }
}