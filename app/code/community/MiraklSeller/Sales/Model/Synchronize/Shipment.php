<?php

use Mage_Sales_Model_Order_Shipment as MagentoShipment;
use Mage_Sales_Model_Order_Shipment_Track as MagentoTracking;
use Mirakl\MMP\Common\Domain\Shipment\Shipment as MiraklShipment;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentTracking as MiraklTracking;

class MiraklSeller_Sales_Model_Synchronize_Shipment
{
    /**
     * Returns true if Magento shipment has been updated or false if not
     *
     * @param   MagentoShipment   $shipment
     * @param   MiraklShipment    $miraklShipment
     * @return  bool
     */
    public function synchronize(MagentoShipment $shipment, MiraklShipment $miraklShipment)
    {
        if (!$miraklShipment->getTracking()) {
            return false;
        }

        $updated = false; // Flag to mark Magento shipment as updated or not

        /** @var MiraklTracking $miraklTracking */
        $miraklTracking = $miraklShipment->getTracking();

        if (!$shipment->getTracksCollection()->count()) {
            if ($miraklTracking->getCarrierCode() || $miraklTracking->getCarrierName()) {
                // Create shipment tracking if not created yet
                $trackData = array(
                    'carrier_code' => $miraklTracking->getCarrierCode()
                        ? $miraklTracking->getCarrierCode()
                        : MagentoTracking::CUSTOM_CARRIER_CODE,
                    'title'        => $miraklTracking->getCarrierName(),
                    'number'       => $miraklTracking->getTrackingNumber(),
                );

                /** @var MagentoTracking $track */
                $track = Mage::getModel('sales/order_shipment_track');
                $track->addData($trackData);
                $shipment->addTrack($track);
                $shipment->save();
                $updated = true;
            }
        } else {
            // Update existing shipment tracking
            /** @var MagentoTracking $existingTrack */
            foreach ($shipment->getTracksCollection() as $existingTrack) {
                if ($shipment->getMiraklShipmentId() !== $miraklShipment->getId()) {
                    continue;
                }

                if ($this->synchronizeTracking($existingTrack, $miraklTracking)) {
                    $updated = true;
                }

                break; // exit loop
            }
        }

        return $updated;
    }

    /**
     * Returns true if the Magento shipment tracking has been modified and saved, false otherwise
     *
     * @param   MagentoTracking $magentoTracking
     * @param   MiraklTracking  $miraklTracking
     * @return  bool
     */
    public function synchronizeTracking(MagentoTracking $magentoTracking, MiraklTracking $miraklTracking)
    {
        $saveTrack = false;

        if ($magentoTracking->getCarrierCode() != $miraklTracking->getCarrierCode() && !empty($miraklTracking->getCarrierCode())) {
            $saveTrack = true;
            $magentoTracking->setCarrierCode($miraklTracking->getCarrierCode());
        }

        if ($magentoTracking->getNumber() != $miraklTracking->getTrackingNumber()) {
            $saveTrack = true;
            $magentoTracking->setNumber($miraklTracking->getTrackingNumber());
        }

        if ($magentoTracking->getTitle() != $miraklTracking->getCarrierName()) {
            $saveTrack = true;
            $magentoTracking->setTitle($miraklTracking->getCarrierName());
        }

        if ($saveTrack) {
            $magentoTracking->save();
        }

        return $saveTrack;
    }
}