<?php

    /**
    * Lista Metodos de Pago
    * Esteban Aquino 25-11-2019
    */
    require_once '../config/operacionesDB.php';
    require_once 'sharedFunctions.php';
    # Obtener headers
    $head = getallheaders();
    $token = $head['token'];
    $ok = false;
    // Verificar autenticidad del token
    if ($token !== 'null'&&$token !== null) { 
        $ok = validarToken($token)['valid'];
    }     
    if ($ok) {

        $NRO_SOLICITUD = NVL($_GET["NRO_SOLICITUD"], "");
        $VOUCHER = NVL($_GET["VOUCHER"], "");
        
        
        $datos = operacionesDB::confirmaPedido($NRO_SOLICITUD, $VOUCHER);
        //print_r($datos);
        if ($datos != 'OK') {
          $mens = utf8_converter_sting($datos);
        }
        //print_r($mens);
        
        $respuesta["acceso"] = true;
        $respuesta["datos"] = $datos;
        $respuesta["mensaje"] = $mens;
    }else{
        $respuesta["acceso"] = false;
        $respuesta["mensaje"] = 'Token no valido';
    }

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: *');
    print json_encode($respuesta);