<?php 
class ZELERIS_ZELERISShipping_Model_ZELERIS_ZELERISShipping extends Mage_Shipping_Model_Carrier_Abstract
/***********************************************************************************************************************************************************/
/******************************************************************* Versión 2.0.0 de marzo de 2013 ********************************************************/
/***********************************************************************************************************************************************************/	
{
	protected $_code = 'ZELERISShipping';

	public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
		if (!$this->getConfigFlag('active'))
		{
			return false; /*************************************************     Si el módulo está inactivo, no continuamos     ********************************/
		}
		else
		{
			$err = null;
			$envio = array();
			if (!$request->getOrig())
			{
				$request
					->setCountryId(Mage::getStoreConfig('shipping/origin/country_id', $request->getStore()))
					->setPostcode(Mage::getStoreConfig('shipping/origin/postcode', $request->getStore()));
			}
			/*************************************************     Datos de la compra     *********************************************/
			if ($request->getCountryId() <> 'ES')
			{
				$err = "AtenciÃ³n: SÃ³lo se admiten envÃ­os con origen en Espana.";
			}
			else
			{
				$envio["uidCliente"]= Mage::getStoreConfig('carriers/ZELERISShipping/GUID', $this->getStoreId());
				if ($this->limpia($envio["uidCliente"] == ""))
				{
					$err = "AtenciÃ³n: No se ha indicado el UID de cliente en el formulario de configuraciÃ³n.";
				}
				else
				{
					if (mb_strlen($this->limpia($envio["uidCliente"])) <> 32)
					{
						$err = "AtenciÃ³n: El UID de cliente ha de constar de 32 caracteres de longitud.";
					}
					else
					{
						$envio["URL"] = Mage::getStoreConfig('carriers/ZELERISShipping/gateway_url', $this->getStoreId());
						if ($envio["URL"] == "")
						{
							$err = "AtenciÃ³n: No se ha indicado una URL en el formulario de configuraciÃ³n.";
						}
						else
						{
							if (!filter_var($envio["URL"], FILTER_VALIDATE_URL)) {
								$err = "AtenciÃ³n: No se ha indicado una URL valida en el formulario de configuraciÃ³n.";
							}else{
								$envio["codPaisDst"] = $request->getDestCountryId();
								if (mb_strlen($this->limpia($envio["codPaisDst"])) <> 2)
								{
									$err = "AtenciÃ³n: El cÃ³digo del paÃ­s de destino no es correcto.";
								}
								else
								{
									if ($this->limpia($envio["codPaisDst"]) == "ES")
									{
										$envio["peso"] = ceil($request->getPackageWeight()); //Para envios nacionales, redondeamos hacia arriba y descartamos todos los decimales.
									}
									else
									{
										$envio["peso"] = round($request->getPackageWeight(),2); //Para envios internacionales, redondeamos y nos quedamos con 2 decimales.
							     	    //$envio["peso"] = str_replace (".", ",", $envio["peso"]); //Sustituimos el punto decimal por la coma decimal.
									}
									if (mb_strlen($this->limpia($envio["peso"])) > 6)
									{
										$err = "AtenciÃ³n: El peso supera los 6 caracteres de longitud.  Peso='".$envio["peso"]."'";
									}
									else
									{
										if (floatval($this->limpia($envio["peso"])) <= 0)
										{
											$envio["peso"] = 1;
										}
										
										$envio["cpDst"] = $request->getDestPostcode();
										if (mb_strlen($this->limpia($envio["cpDst"])) > 7)
										{
											$err = "AtenciÃ³n: El cÃ³digo postal de destino supera los 7 caracteres de longitud.";
										}
										else
										{
      										$envio["bultos"] = 1;
       										$envio["precioProducto"] = $request->getPackageValue();
       										$servicios = Mage::getModel('ZELERISShipping/ZELERIS_Source_Method')->toOptionArray();
       										$serviciosActivos = explode(',', $this->getConfigData('servicios'));
										}
									}
								}
							}
						}
					}
				}
			}

			$result = Mage::getModel('shipping/rate_result');

			if ($err)
			{
				$error = Mage::getModel('shipping/rate_result_error');
	            $error->setCarrier($this->_code);
       	     	$error->setCarrierTitle($this->getConfigData('title'));
            	$error->setErrorMessage($err);
	            $result->append($error);
        	}
			else
			{
				foreach ($serviciosActivos as $iservicio)
				{
					$servicio = $servicios[$iservicio];
					$envio["indice"] = $servicio ["value"];
					$envio["servicio"] = $servicio ["codigo"];	
					$modo = (int)Mage::getStoreConfig('carriers/ZELERISShipping/modo', $this->getStoreId());
					switch ($modo)
					{
						case 1:
							$envio["importe"] = $this->valora($envio); //***** Si el valor es 1, el precio del servicio es el devuelto por el web service de Zeleris *****
							break;
						case 2:
							$envio["importe"] = Mage::helper('tablerate')->collectRates($request); //***** Si el valor es 2, el precio del servicio es el devuelto por el Core de Magento para el Table Rates, pero poniendo ese precio como si el Web Service de Zeleris lo hubiese devuelto.
							break;
						case 3:
							$envio["importe"] = Mage::helper('flatrate')->collectRates($request); //***** Si el valor es 3, el precio del servicio es el devuelto por el Core de Magento para el Flat Rate, pero poniendo ese precio como si el Web Service de Zeleris lo hubiese devuelto.
							break;
						default:
							$envio["importe"] = -3;
					}
            		if ($envio["importe"] < 0)
					{
						$error = Mage::getModel('shipping/rate_result_error');
	                	$error->setCarrier($this->_code);
						$error->setCarrierTitle($this->getConfigData('title'));
						switch ($envio["importe"])
						{
							case -1: //***** -1 indica que Zeleris no tiene disponible el servicio indicado para el destino solicitado. Por ejemplo, un servicio Z10 en un país extranjero o un ZDS para Japón, o bien que se trata de un destino desconocido, como un código postal no existente en España.
								//***** No hacemos nada en este caso. Si $result->append($error), no nos mostraría ningún servicio en el caso de que el cliente no tuviese contratado alguno.
								break;
							case -2:  //***** -2 indica un error en la función de consulta al Web Service, por lo que mostramos el error establecido en el campo "Mensaje error" del formulario de configuración del módulo, si está habilitada la opción "Mostrar servicios en caso de error".
								$error->setErrorMessage($this->getConfigData('specificerrmsg'));
								$result->append($error);
								break;
							case -3: //***** -3 indica que el tipo de valoración es desconocido.
								$error->setErrorMessage("Se desconoce el tipo de valoracion que debe ser aplicado a este servicio.");
								$result->append($error);
								break;
						}
	            	}
					else
					{
						$envio["importeFinal"]=$this->precioFinal($envio);
						$rate = Mage::getModel('shipping/rate_result_method');
						$rate->setCarrier($this->_code);
						$rate->setCarrierTitle($this->getConfigData('title'));
						$rate->setMethod($servicio["label"]);


						//$rate->setMethodTitle('<img src="'.$servicio["img"].'" alt="'. $servicio["label"].'" title="'. $servicio["hint"] .'" />'.$servicio["hint"].'.');
						// Bug de Magento a partir de la versión 1.6.2 que hace que el < se cambie por &lt y el > por &gt, lo que impide que se vea bien el logotipo del los servicios.
						// Solucionar en Magento o cambiar la línea de arriba por esta de abajo.
						$rate->setMethodTitle($servicio["hint"].'.');


						if ($envio["importeFinal"] == -1)
						{
							$rate->setMethodTitle($rate->getMethodTitle().' (Servicio gratuito)');
	                    	$envio["importeFinal"]=0;
						}
						$rate->setCost($envio["importe"]);
						$rate->setPrice($envio["importeFinal"]);
						$result->append($rate);
					}
				}
			}
	       	return $result;
		}
	}

	
	/**************************************     Valoramos el envío llamando al Web Service correspondiente     ******************************/
	protected function valora($envio)
    {
		try
		{
			$xml = array("docIn" =>	"<?xml version=\"1.0\" encoding=\"UTF-8\" ?>" .
									"<Body>" 														.
										"<InfoCuenta>" 												.
											"<UIDCliente>" 	. 	$this->limpia($envio["uidCliente"]) 	. "</UIDCliente>" 	.
											"<Usuario>" 		.	""						. "</Usuario>" 	.
											"<Clave>" 		.	""						. "</Clave>" 		.
											"<CodRemitente>"	.	""						. "</CodRemitente>"	.
										"</InfoCuenta>" 												.

										"<DatosDestino>" 												.
											"<Pais>" 		. 	$this->limpia($envio["codPaisDst"]) 	. "</Pais>" 		. 
											"<Codpos>" 		. 	$this->limpia($envio["cpDst"]) 		. "</Codpos>" 	.
										"</DatosDestino>" 												.
				
										"<DatosServicio>" 												.
											"<Bultos>" 		. 	$this->limpia($envio["bultos"]) 		. "</Bultos>" 	.
											"<Kilos>" 		. 	$this->limpia($envio["peso"]) 		. "</Kilos>" 		. 
											"<Volumen>"		.	"0"						. "</Volumen>" 	.
											"<Servicio>" 		. 	$this->limpia($envio["servicio"]) 		. "</Servicio>" 	.
											"<Reembolso>"		.	"0"						. "</Reembolso>" 	.
											"<ValorSeguro>"	.	"0"						. "</ValorSeguro>" 	.
										"</DatosServicio>" 												.
									"</Body>");	


			$client = new SoapClient($envio["URL"]);
			$respuesta = $client->Valora($xml)->ValoraResult;
         	$posicioninicial = strpos($respuesta , '<Status>') + 8;
	 		$posicionfinal = strpos($respuesta , '</Status>');
	 		$resultado = substr($respuesta , $posicioninicial, $posicionfinal - $posicioninicial);
			if ($resultado == "OK")
			{
				$posicioninicial = strpos($respuesta , '<PresupuestoServicio>') + 21;
	 			$posicionfinal = strpos($respuesta , '</PresupuestoServicio>');
	 			$presupuestoservicio = substr($respuesta , $posicioninicial, $posicionfinal - $posicioninicial); 
			}
			else
			{	
				$presupuestoservicio = -1;
           	}
		}
		catch (Exception $e)
		{
   			$presupuestoservicio = -2;
		}

		return $presupuestoservicio;
	}


	/*********     Calculamos el precio total del envío, contemplando si hay costes de manipulado y/o envío gratuito     *********/
	protected function precioFinal($envio)
	{
		$importe=$envio["importe"];
//      if (($this->getConfigData('free_shipping_enable')))                                                                 // ***** Esta línea sólo funciona para el servicio 'Zeleris Ecommerce'. Si hay múltiples servicios, ha de ponerse la línea de abajo. *****
		if (($this->getConfigData('free_shipping_enable')) && ($this->getConfigData('free_method')==$envio["indice"]))      // ***** Esta línea sólo funciona para múltiples servicios. Si sólo se indica el servicio 'Zeleris Ecommerce', ha de ponerse la línea de arriba. *****
		{
			if ($envio["precioProducto"] >= (float)$this->getConfigData('free_shipping_subtotal'))
			{
				$importe='-1';
			}
		}

		if ($importe>0)
		{
			$tipoManipulado = $this->getConfigData('handling_type');
            $valorManipulado = $this->getConfigData('handling_fee');
            if ($tipoManipulado=='F')
			{
				$importe+= $valorManipulado;
			}
			if ($tipoManipulado=='P')
			{
				$importe+= ($importe*$valorManipulado/100);
			}
        }
        return $importe;
    }


	/*************************************************     Obtenemos la información sobre el tracking     **********************************/
	public function getTrackingInfo($tracking_number)
	{
  		$tracking_result = $this->getTracking($tracking_number);
		if ($tracking_result instanceof Mage_Shipping_Model_Tracking_Result)
		{
			$trackings = $tracking_result->getAllTrackings();
			if ($trackings)
			{
				return $trackings[0];
			}
		}
		else
		{
			if (is_string($tracking_result) && !empty($tracking_result))
			{
				return $tracking_result;
			}
			else
			{
				return false;
			}
		}
	}


   	/********************************    Invocamos la página web de Zeleris con la información del envío y la gestión de incidencias, si procede    ****************************/
	protected function getTracking($numeroSeguimiento)
	{
		$uidCliente = Mage::getStoreConfig('carriers/ZELERISShipping/GUID', $this->getStoreId());
		$cliente = new SoapClient(Mage::getStoreConfig('carriers/ZELERISShipping/gateway_url', $this->getStoreId()));
		header("Location: ".$cliente->TrackingURL()->TrackingURLResult."?id_seguimiento=".$numeroSeguimiento); //***** Esto muestra sólo el tracking reducido, tanto para el comprador como para el administrador.
		//header("Location: ".$cliente->TrackingURL()->TrackingURLResult."?uid=".$uidCliente."&id_seguimiento=".$numeroSeguimiento); //***** Esto muestra sólo el tracking ampliado, que sólo debe ser visible por un administrador.
		$tracking_status = Mage::getModel('shipping/tracking_result_status');
		$tracking_result->append($tracking_status);
	}


	//***** Limpia todos los caracteres que pueden plantear problemas al construir el XML.
	public function limpia($valor) 
	{
		$trans = array("&" => "y", "¿" => "", "?" => "", "<" => "", ">" => "");
		return utf8_encode(strtr(utf8_decode($valor), $trans));
	}

}