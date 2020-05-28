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

        $COD_CALL = NVL($_GET["COD_CALL"], "");
        $USR_CALL = NVL($_GET["USR_CALL"], "");
        $FEC_DESDE = NVL($_GET["FEC_DESDE"], "");
        $FEC_HASTA = NVL($_GET["FEC_HASTA"], "");

        //print_r('asd');
        $datos = operacionesDB::ListarPedidosCompleto($COD_CALL, $FEC_DESDE, $FEC_HASTA);
        //print_r($datos);

        $respuesta["acceso"] = true;
        $respuesta["datos"] = $datos;
        $respuesta["mensaje"] = '';
    }else{
        $respuesta["acceso"] = false;
        $respuesta["mensaje"] = 'Token no valido';
    }

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: *');
    print json_encode($respuesta);