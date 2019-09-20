<?php

class MiraklSeller_Sales_Block_Adminhtml_Order_Item_Column_Renderer_Massaction
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Massaction
{
    /**
     * {@inheritdoc}
     */
    public function render(Varien_Object $row)
    {
        return $row->getProductId() ? parent::render($row) : ''; // Do not display if no product is associated with the order line
    }
}