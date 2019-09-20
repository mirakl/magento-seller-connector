<?php

class MiraklSeller_Core_Model_Listing_Builder_Standard implements MiraklSeller_Core_Model_Listing_Builder_Interface
{
    /**
     * @return  MiraklSeller_Core_Model_Listing
     */
    public function getListing()
    {
        return Mage::registry('mirakl_seller_listing');
    }

    /**
     * {@inheritdoc}
     */
    public function build(MiraklSeller_Core_Model_Listing $listing)
    {
        $ids = array();
        $conds = $listing->getBuilderParams();

        if (!empty($conds)) {
            /** @var $rule MiraklSeller_Core_Model_Rule */
            $rule = Mage::getModel('mirakl_seller/rule');
            $rule->setWebsiteIds(array_keys(Mage::app()->getWebsites(true)));
            $rule->loadPost($conds);

            Varien_Profiler::start('MIRAKL SELLER MATCHING PRODUCTS');
            $productIds = $rule->getMatchingProductIds();
            Varien_Profiler::stop('MIRAKL SELLER MATCHING PRODUCTS');

            foreach ($productIds as $productId => $validationByWebsite) {
                if (false !== array_search(1, $validationByWebsite, true)) {
                    $ids[] = $productId;
                }
            }
        }

        return $ids;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareForm(Varien_Data_Form $form, &$data = array())
    {
        $builderParams = $this->getListing()->getBuilderParams();

        /* @var $model MiraklSeller_Core_Model_Rule */
        $model = Mage::getSingleton('mirakl_seller/rule');
        $model->loadPost($builderParams);
        $model->getConditions()->setJsFormObject('conditions_fieldset');

        // New child url
        $newChildUrl = Mage::getModel('adminhtml/url')->getUrl('*/promo_catalog/newConditionHtml/form/conditions_fieldset');

        // Fieldset renderer
        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('mirakl_seller/listing/rule/promo/fieldset.phtml')
            ->setNewChildUrl($newChildUrl);

        // Add new fieldset
        $fieldset = $form->addFieldset(
            'conditions_fieldset',
            array('legend' => Mage::helper('mirakl_seller')->__('Filter Products To Export'))
        )->setRenderer($renderer);

        // Add new field to the fieldset
        $fieldset->addField(
            'conditions', 'text', array(
                'name'  => 'mirakl_seller_conditions',
                'label' => Mage::helper('mirakl_seller')->__('Conditions'),
                'title' => Mage::helper('mirakl_seller')->__('Conditions'),
            )
        )->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/conditions'));

        return $this;
    }
}