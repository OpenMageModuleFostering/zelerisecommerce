<?php 
class ZELERIS_ZELERISShipping_Model_ZELERIS_Shipment extends Mage_Sales_Model_Order_Shipment
/***********************************************************************************************************************************************************/
/******************************************************************* Versión 2.0.0 de marzo de 2013 ********************************************************/
/***********************************************************************************************************************************************************/
{
	protected function _beforeSave()
	{
		$canSave = parent::_beforeSave();

		If (Mage::getStoreConfig('carriers/ZELERISShipping/active', $this->getStoreId()) == true) //***************** Sólo continuamos si el módulo de Zeleris está activado. ******************	
		{
			If ($this->getShipment()->getId() == 0) //***************** Sólo generamos la expedición si no existe previamente. ******************
			{
				$arrayMethod = explode("_", $this->getOrder()->getShippingMethod());
				
				If ($arrayMethod[0] == "ZELERISShipping")
				{
					$err = null;
					$envio = array();
					$envio["uidCliente"] = Mage::getStoreConfig('carriers/ZELERISShipping/GUID', $this->getStoreId());
					$envio["URL"] = Mage::getStoreConfig('carriers/ZELERISShipping/gateway_url', $this->getStoreId());
					$envio["nombreProducto"] = substr(Mage::getStoreConfig('carriers/ZELERISShipping/productname', $this->getStoreId()), 0, 20);

					$metodo = Mage::getModel('ZELERISShipping/ZELERIS_Source_Method')->getMethod($this->getOrder()->getShippingMethod());
					$envio["servicio"] = substr($metodo["codigo"], 0, 3);
					//No utilizado en la actualidad. ============>     $envio["horario"] = $metodo["horario"]; 

					$comentarios = $this->_comments;

					if (count($comentarios) > 0)
					{
						foreach ($comentarios as $comentario)
						{
							break;
						}
						$no_generar_envio = substr($comentario->getComment(), 0, 1);

						if (strlen($comentario->getComment()) > 1)
						{
							$comentario->setComment(substr($comentario->getComment(), 1));
						}
						else
						{
							$this->_comments = null;
						}
					}
					else
					{
						$no_generar_envio = "0";
					}

					if ($no_generar_envio == "1")
					{
						$envio["modoTransporte"] = "1";
					}
					else
					{
						$envio["modoTransporte"] = "";
					}

					if (count($comentarios) > 0)
					{
						if (strlen($comentario->getComment()) > 1)
						{
							$this->getShipment()->setCommentText($comentario->getComment());
						}
					}
					else
					{
						$this->_comments = null;
					}


					$pesoexpedicion = 0; //***************** Iteramos entre todos los productos de esta expedición de esta compra. ******************
					$envio["detalle"] = "";
					foreach ($this->getItemsCollection() as $item)
					{
						$pesounidad = floatval($item->getWeight());
						$unidades = $item->getQty();
						$pesoexpedicion += $pesounidad * $unidades;
					
						for ($i = 1; $i <= $unidades; $i++)
						{
							$envio["detalle"] .= "<InfoBulto";
							$envio["detalle"] .= " Referencia='". substr($this->limpia($item->getSku()), 0, 20);
							$envio["detalle"] .= "' Bulto='1";
							$envio["detalle"] .= "' Kilos='". number_format($pesounidad, 2, ',', '');
							$envio["detalle"] .= "' Volumen= '0'";
							$envio["detalle"] .= "/>";
						}
					}

					$envio["bultos"] = 1;
					$envio["RefC"] = $this->getOrder()->getIncrementId();//$envio["RefC"] = $this->getOrder()->getId();

					$orderaddress = $this->getShippingAddress();
					$envio["nombreDst"] = substr($this->limpia($orderaddress->getName()), 0, 40);
					$envio["direccionDst"] = substr($this->limpia($orderaddress->getStreetFull()), 0, 80);
					$envio["poblacionDst"] = substr($this->limpia($orderaddress->getCity()), 0, 40);
					$envio["telefonoDst"] = substr($this->limpia($orderaddress->getTelephone()), 0, 15);
					$envio["faxDst"] = substr($this->limpia($orderaddress->getFax()), 0, 15);

					$envio["emailDst"] = substr($this->limpia($orderaddress->getEmail()), 0, 60);
					if ($envio["emailDst"] == "") //**** Si el email obtenido de la dirección de envío está vacío, lo buscamos entonces en la dirección de facturación. Esto ocurre cuando dirección de envío y de facturación son diferentes.
					{
						$billingaddress = $this->getBillingAddress();
						$envio["emailDst"] = substr($this->limpia($billingaddress->getEmail()), 0, 60);
					}

					$envio["codPaisDst"] = $orderaddress->getCountry();
					$envio["cpDst"] = substr($this->limpia($orderaddress->getPostcode()), 0, 7);

					//$envio["provinciaDst"] = $orderaddress->getRegion();
					//$envio["nombreOrg"] = Mage::getStoreConfig('general/store_information/name');
					//$envio["direccionOrg"] = Mage::getStoreConfig('general/store_information/address');
					//$envio["poblacionOrg"] = Mage::getStoreConfig('shipping/origin/city');
					//$envio["codPaisOrg"] = Mage::getStoreConfig('shipping/origin/country_id');
					//$envio["cpOrg"] = Mage::getStoreConfig('shipping/origin/postcode');

					if ($this->limpia($envio["servicio"]) == 50)
					{
						$dato = $this->limpia($orderaddress->getCompany());
						$posicioninicial = strpos($dato, '#') + 1;
						$posicionfinal = strpos($dato, '#',$posicioninicial + 1);
						$envio["codPuntoEntrega"] = substr($dato, $posicioninicial, $posicionfinal - $posicioninicial);

						$envio["contactoDst"] = $envio["nombreDst"];
						$envio["nombreDst"] = substr($dato, $posicionfinal + 1, 40);
					}
					else
					{
						$envio["codPuntoEntrega"] = "";
						$envio["contactoDst"] = "";
					}

					if (floatval($pesoexpedicion) <= 0)
					{
						$pesoexpedicion = 1;
					}
						
					if ($this->limpia($envio["codPaisDst"]) == "ES")
					{
						$envio["peso"] = number_format(ceil(floatval($pesoexpedicion)), 0, ',', ''); //Para envios nacionales, redondeamos hacia arriba y descartamos todos los decimales.
					}
					else
					{
						$envio["peso"] = number_format(floatval($pesoexpedicion), 2, ',', ''); //Para envios internacionales, redondeamos y nos quedamos con 2 decimales.
					}

					$envio["importeReembolso"] = 0; //***** Por defecto, establecemos a cero el importe del reembolso.
					$habilitar_pago_contrareembolso = Mage::getStoreConfig('carriers/ZELERISShipping/habilitar_pago_contrareembolso', $this->getStoreId());
					if ($habilitar_pago_contrareembolso) //***** Si la forma de pago contra-reembolso está habilitada en el combo de configuración de este módulo de transportes de Zeleris para Magento. *****
					{
						$forma_pago_compra = $this->getOrder()->getPayment()->getMethod(); //***** Forma de pago de la compra a la que pertenece esta expedición. *****
						$forma_pago_contrareembolso = Mage::getStoreConfig('carriers/ZELERISShipping/forma_pago_contrareembolso', $this->getStoreId()); //***** Forma de pago seleccionada como contra-reembolso en el combo de configuración de este módulo de transportes de Zeleris para Magento. *****
						if($forma_pago_compra == $forma_pago_contrareembolso) //***** Comprobamos si la forma de pago de la compra a la que corresponde esta expedición, coincide con la seleccionada como contra-reembolso en el combo de configuración de este módulo de transportes de Zeleris para Magento. *****
						{
							if($this->getOrder()->hasShipments() == false)
							{ 
								$envio["importeReembolso"] = number_format(floatval($this->getOrder()->getTotalDue()), 2, ',', ''); //***** Sólo se cobra el importe del reembolso en la primera expedición de esta compra. 
							}
						}
					}

					$ret=$this->graba($envio);

					if ($ret=="")
					{ 
							Mage::throwException(Mage::helper('sales')->__('ERROR: No se ha podido grabar el pedido en Zeleris.'));
					}

					$track = Mage::getModel('sales/order_shipment_track')
							->setNumber($ret)
							->setCarrierCode('ZELERISShipping')
							->setTitle(substr(Mage::getStoreConfig('carriers/ZELERISShipping/title', $this->getStoreId()), 0, 20))
							->setDescription('');
					$this->addTrack($track);
				}
			}
		}
		else
		{
			Mage::throwException(Mage::helper('sales')->__('ATENCI&Oacute;N: M&oacute;dulo de transporte de Zeleris desactivado. Expedici&oacute;n no generada.'));
		}
       	return $canSave;
	}

	/*****************     Comunicamos a Zeleris el envío en firme, llamando al Web Service correspondiente     ******************/
	protected function graba($envio)
	{
		try {
			$xml = array("docIn" =>	"<?xml version=\"1.0\" encoding=\"UTF-8\" ?>" 										.
							"<Body>" 															.
								"<InfoCuenta>" 													.
									"<UIDCliente>"	. 	$this->limpia($envio["uidCliente"])	. "</UIDCliente>" 		.
									"<Usuario>"		. 	""						. "</Usuario>" 		.
									"<Clave>"		. 	""						. "</Clave>" 			.
									"<CodRemitente>"	. 	""						. "</CodRemitente>" 		.
								"</InfoCuenta>" 													.

								"<DatosDestinatario>" 												.
									"<NifCons>"		. 	"-"						. "</NifCons>" 		.
							 		"<Nombre>" 		. 	$this->limpia($envio["nombreDst"])		. "</Nombre>" 		.	
									"<Direccion>" 	. 	$this->limpia($envio["direccionDst"])	. "</Direccion>" 		.
									"<Pais>" 		. 	$this->limpia($envio["codPaisDst"])	. "</Pais>" 			.
									"<Codpos>" 		. 	$this->limpia($envio["cpDst"])		. "</Codpos>" 		.
									"<Poblacion>" 	. 	$this->limpia($envio["poblacionDst"])	. "</Poblacion>" 		.
									"<Contacto>"		. 	$this->limpia($envio["contactoDst"])	. "</Contacto>"		.
									"<Telefono1>" 	. 	$this->limpia($envio["telefonoDst"])	. "</Telefono1>" 		.
									"<Telefono2>" 	. 	$this->limpia($envio["faxDst"])		. "</Telefono2>" 		.
									"<Email>" 		. 	$this->limpia($envio["emailDst"])		. "</Email>" 			.
									"<CodPunto>" 		. 	$this->limpia($envio["codPuntoEntrega"])	. "</CodPunto>" 		.
								"</DatosDestinatario>" 												.

								"<DatosServicio>" 													.
									"<Referencia>"	. 	$this->limpia($envio["RefC"])		. "</Referencia>" 		.
									"<Bultos>" 		. 	$this->limpia($envio["bultos"])		. "</Bultos>" 		.
									"<Kilos>" 		. 	$this->limpia($envio["peso"])		. "</Kilos>" 			.
									"<Volumen>"		. 	"0" 						. "</Volumen>" 		.
									"<Servicio>" 		. 	$this->limpia($envio["servicio"])		. "</Servicio>" 		.
									"<Reembolso>" 	. 	$this->limpia($envio["importeReembolso"])	. "</Reembolso>" 		.
									"<ValorSeguro>"	. 	"0" 						. "</ValorSeguro>" 		.
									"<ValoraAduana>"	.	"0"						. "</ValoraAduana>" 		.
									"<Mercancia>" 	. 	$this->limpia($envio["nombreProducto"])	. "</Mercancia>" 		.
									"<TipoGastosAduana>"	.	"0"						. "</TipoGastosAduana>"	.
									"<TipoAvisoEntrega>"	. 	"0"						. "</TipoAvisoEntrega>"	.
									"<TipoPortes>"	. 	"P"						. "</TipoPortes>" 		.
									"<TipoReembolso>"	. 	"P" 						. "</TipoReembolso>"		.
									"<DAS>"		. 	""						. "</DAS>" 			.
									"<GS>"			. 	""						. "</GS>" 			.
									"<Identicket>"	. 	""						. "</Identicket>" 		.
									"<FechaEA>"		. 	""						. "</FechaEA>" 		.
									"<Observaciones>"	. 	""						. "</Observaciones>"		.
									"<ModoTransporte>"	. 	$envio["modoTransporte"]			. "</ModoTransporte>"	.
									"<InfoBultos>"	.	$envio["detalle"]				. "</InfoBultos>"		.
								"</DatosServicio>" 		.
							"</Body>");

			$client = new SoapClient($envio["URL"]);
			$respuesta = $client->GrabaServicios($xml)->GrabaServiciosResult;
			$posicioninicial = strpos($respuesta , '<resultado>') + 11;
	 		$posicionfinal = strpos($respuesta , '</resultado>');
	 		$resultado = substr($respuesta , $posicioninicial, $posicionfinal - $posicioninicial); 

			if ($resultado == "OK") {
              		$posicioninicial = strpos($respuesta , '<nseg>') + 6;
				$posicionfinal = strpos($respuesta , '</nseg>');
				$NumeroSeguimiento = substr($respuesta , $posicioninicial, $posicionfinal - $posicioninicial); 
            		} else {
				$NumeroSeguimiento = "";
				$posicioninicial = strpos($respuesta , '<mensaje>') + 9;
				$posicionfinal = strpos($respuesta , '</mensaje>');
				$MensajeError = substr($respuesta , $posicioninicial, $posicionfinal - $posicioninicial);
				$MensajeError = substr($MensajeError ,0, 150);
				Mage::throwException(Mage::helper('sales')->__('ATENCI&Oacute;N: Error al invocar el Web Service de Zeleris: ' . $MensajeError));
            		}

		} catch (Exception $e) {
			Mage::throwException(Mage::helper('sales')->__('ERROR:'. $e)); 
  			$NumeroSeguimiento = "";
		}
		return $NumeroSeguimiento;
	}

	//***** Limpia todos los caracteres que pueden plantear problemas al construir el XML.
	public function limpia($valor)
	{
		$trans = array("&" => "y", "¿" => "", "?" => "", "<" => "", ">" => "");
		return utf8_encode(strtr(utf8_decode($valor), $trans));
	}

	//***** Obtenemos el envío actual.
	protected function getShipment()
	{
         	return Mage::registry('current_shipment');
	}

}