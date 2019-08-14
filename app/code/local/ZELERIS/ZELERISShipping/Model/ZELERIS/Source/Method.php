<?php
class ZELERIS_ZELERISShipping_Model_ZELERIS_Source_Method
/***********************************************************************************************************************************************************/
/******************************************************************* Versión 2.0.0 de marzo de 2013 ********************************************************/
/***********************************************************************************************************************************************************/
{
	public function toOptionArray()
    	{
		$arr = array();
		$rutaactual = dirname(__FILE__);
		$xml = simplexml_load_file($rutaactual . '/servicios.xml');

		$indice = 0;
		foreach($xml->servicio as $item){
			$arr[] = array('value'=>$indice, 'label'=>$item['descripcion'], 'img'=>$item['imagen'], 'hint'=>$item['ayuda'], 'horario'=>'1', 'codigo'=>$item['codigo']);
			$indice = $indice + 1;
		}
		return $arr;
    	}

     	public function getMethod($shippingMethod)
    	{
   		$metodo= explode("_",$shippingMethod);
      		if ($metodo[0]=="ZELERISShipping") {
       		$label=$metodo[1];
        		foreach($this->toOptionArray() as $metodoenvio) {
     				if ($metodoenvio["label"]==$label)
					$servicio=$metodoenvio;
       		}
      		}
      		return $servicio;
   	}
}
?>
