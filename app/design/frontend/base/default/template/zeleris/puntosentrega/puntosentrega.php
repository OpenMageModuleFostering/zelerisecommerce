<?php 

/***********************************************************************************************************************************************************/
/******************************************************************* Versión 2.0.0 de marzo de 2013 ******************************************************/
/***********************************************************************************************************************************************************/

echo "<body onLoad=\"javascript:devolver_idpunto();\">";


echo "<script language=\"JavaScript\">";
echo "function devolver_idpunto()";
echo "{ ";

echo "	var idPunto = \"". $_GET['shortkpid']. "\";";

echo "	if (idPunto != \"\")";
echo "  	idPunto = \"#\" + idPunto + \"#\";";

echo "	if (window.opener.document.getElementById(\"shipping-address-select\") != null)";
echo "		window.opener.document.getElementById(\"shipping-address-select\").value = \"\";";

echo "	window.opener.document.getElementById(\"shipping:company\").value = idPunto + \"". $_GET['kpname']. "\";";
echo "	window.opener.document.getElementById(\"shipping:street1\").value = \"". $_GET['street']. "\";";
echo "	window.opener.document.getElementById(\"shipping:street2\").value = \"\";";
echo "	window.opener.document.getElementById(\"shipping:city\").value = \"". $_GET['city']. "\";";
echo "	window.opener.document.getElementById(\"shipping:postcode\").value = \"". $_GET['zip']. "\";";
echo "	window.opener.document.getElementById(\"shipping:same_as_billing\").checked = false;";

echo "	window.opener.document.getElementById(\"idpunto\").value = \"". $_GET['shortkpid']. "\";";
echo "	window.opener.document.getElementById(\"nombre_id_punto\").innerHTML =  \"#". $_GET['shortkpid']. "#". $_GET['kpname']. "\";";
echo "	window.opener.document.getElementById(\"dir_punto\").innerHTML =  \"". $_GET['street']. "\";";
echo "	window.opener.document.getElementById(\"cp_pob_punto\").innerHTML =  \"". $_GET['zip']. " ". $_GET['city']. "\";";
echo "	window.opener.document.getElementById(\"info_punto\").style.display = \"inline\";";

echo "	window.opener.shipping.save();";

echo "	var inicio = (new Date()).getTime();";
echo "	while ((new Date()).getTime() - inicio <= 10000) ";
echo "	{";
echo "		var methods = window.opener.document.getElementsByName(\"shipping_method\");";
echo "		var seleccionado = false;";

echo "		for (var i = 0; i < methods.length; i++)";
echo "			if (methods[i].checked)";
echo "			{";
echo "				seleccionado = true;";
echo "				break;";
echo "			}";

echo "		if (!seleccionado)";
echo "		{";
echo "			window.opener.document.getElementById(\"s_method_ZELERISShipping_Puntos de entrega\").checked = true;";
echo "			break;";
echo "		}";
echo "	}";

echo "	window.opener.document.getElementById(\"s_method_ZELERISShipping_Puntos de entrega\").checked = true;";

echo "	window.close();";
echo "	return false;";
echo "} ";
echo "</script>";
echo "</body>";
?>


