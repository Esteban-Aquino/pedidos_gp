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

        $TIPO_PAGO = NVL($_GET["TIPO_PAGO"], "");
        $COD_METODO = NVL($_POST["COD_METODO"], "");
        $busqueda = NVL($_POST["buscar_texto"], "");
        $pag = NVL($_POST["pagina"], 1);
        if ($COD_METODO === "" || $COD_METODO=== NULL){
            //print_r('asd');
            $datos = operacionesDB::ListarMetodosPagos($TIPO_PAGO ,$busqueda, $pag);
        } else {
            //print_r('QWE');
            $datos = operacionesDB::DatosMetodosPagos($COD_METODO);
            
        }
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