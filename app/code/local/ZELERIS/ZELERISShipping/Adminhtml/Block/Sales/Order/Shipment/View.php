<?php
class ZELERIS_ZELERISShipping_Adminhtml_Block_Sales_Order_Shipment_View extends Mage_Adminhtml_Block_Sales_Order_Shipment_View
/***********************************************************************************************************************************************************/
/******************************************************************* Versión 2.0.0 de marzo de 2013 ********************************************************/
/***********************************************************************************************************************************************************/
{
	public function __construct() {
		try {
       		parent::__construct();
        		$direccionUrl = Mage::helper('core/url')->getCurrentUrl();
        		$resant = split("/",$direccionUrl);
        		$limite = count($resant);
        		for($elemento=0;$elemento<$limite;$elemento++){
            			if($resant[$elemento] == 'etiquetazeleris'){
                			$this->obtenerEtiqueta();
            			}
    			}
       		if ($this->getShipment()->getId()) {
       			$this->_addButton('labelZELERIS', array('label'=>Mage::helper('sales')->__('Etiqueta Zeleris'),'onclick'=>'window.open(\''.$this->obtenerUrl().'\')'));
     			}
		} catch (Exception $e) {
  			Mage::getSingleton('core/session')->addError('ERROR: '.$e->getMessage()); 
		}
  	}


    	public function obtenerUrl() {
		try {
        		$direccionUrl='';
        		foreach ($this->getShipment()->getAllTracks() as $expedicion ) {
          			if ($expedicion->getCarrierCode()=='ZELERISShipping') {
             				$direccionUrl = Mage::helper('core/url')->getCurrentUrl()."etiquetazeleris/";
          			}
        		}
        		return $direccionUrl;
		} catch (Exception $e) {
  			Mage::getSingleton('core/session')->addError('ERROR: '.$e->getMessage()); 
		}
    	}


    	public function obtenerEtiqueta(){
		try {
			$envio = array();
			$envio["uidCliente"]=Mage::getStoreConfig('carriers/ZELERISShipping/GUID', $this->getStoreId());
			$envio["URL"]= Mage::getStoreConfig('carriers/ZELERISShipping/gateway_url', $this->getStoreId());
			$envio["numSeguimiento"]="";

			foreach ($this->getShipment()->getAllTracks() as $expedicion ) { //***** Nos quedamos con el primer número de tracking de Zeleris. Un envío puede tener varios números de tracking pero sólo uno nuestro. El resto, de existir, se consultarán manualmente si procede *****//
          			if ($expedicion->getCarrierCode()=='ZELERISShipping') {
             				$envio["numSeguimiento"]= $expedicion->getTrackNumber();
					break; //***** Una vez encontrado el primero, ya no seguimos buscando. *****//
          			}
        		}
			$baseurl=substr($envio["URL"] , 0, strlen($envio["URL"])-strlen(basename($envio["URL"])));
			$url=$baseurl."Etiqueta.aspx?uid=".$envio["uidCliente"]."&nseg=".$envio["numSeguimiento"];
      			echo "<SCRIPT>window.location='$url';</SCRIPT>"; 
		} catch (Exception $e) {
  			Mage::getSingleton('core/session')->addError('ERROR: '.$e->getMessage()); 
		}

    	}
}




