<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

$defaultState = MiraklSeller_Core_Model_Offer_State::DEFAULT_STATE;

// Add offer state column in order to use it during listing offer exports
$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller/listing'),
    'offer_state',
    "SMALLINT(5) UNSIGNED NOT NULL DEFAULT '$defaultState' COMMENT 'Offer State'"
);

$this->endSetup();
