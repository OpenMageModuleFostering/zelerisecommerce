<?php
class ZELERIS_ZELERISShipping_Function_FlatRate_Data extends Mage_Shipping_Model_Carrier_Abstract
/***********************************************************************************************************************************************************/
/******************************************************************* Versión 2.0.0 de marzo de 2013 ********************************************************/
/***********************************************************************************************************************************************************/
{
	protected $_code = 'flatrate';
	protected $_isFixed = true;

    	public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    	{
        	$freeBoxes = 0;
        	if ($request->getAllItems()) {
            		foreach ($request->getAllItems() as $item) {
                		if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    			continue;
                		}
                		if ($item->getHasChildren() && $item->isShipSeparately()) {
                    			foreach ($item->getChildren() as $child) {
                        			if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            			$freeBoxes += $item->getQty() * $child->getQty();
                        			}
                    			}
                		} elseif ($item->getFreeShipping()) {
                    			$freeBoxes += $item->getQty();
                		}
            		}
        	}
        	$this->setFreeBoxes($freeBoxes);
        	$result = Mage::getModel('shipping/rate_result');
        	if ($this->getConfigData('type') == 'O') { //***** Por pedido *****
            		$shippingPrice = $this->getConfigData('price');
        	} elseif ($this->getConfigData('type') == 'I') { //***** Por unidad *****
            		$shippingPrice = ($request->getPackageQty() * $this->getConfigData('price')) - ($this->getFreeBoxes() * $this->getConfigData('price'));
        	} else { //***** No podemos determinar la forma de calcular el precio *****
            		$shippingPrice = false;
        	}
        	$shippingPrice = $this->getFinalPriceWithHandlingFee($shippingPrice);
        	if ($shippingPrice !== false) {
              	if ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes()) {
                		$shippingPrice = '0.00';
            		}
            	}
		return $shippingPrice;
    	}
}