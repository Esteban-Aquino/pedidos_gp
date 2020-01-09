<?php

    /**
    * Lista condiciones de ventas
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

        $COD_CONDICION_VENTA = NVL($_GET["COD_CONDICION_VENTA"], "");
        $TIP_DOCUMENTO= NVL($_POST["TIP_DOCUMENTO"], "");
        $busqueda = NVL($_POST["buscar_texto"], "");
        $pag = NVL($_POST["pagina"], 1);
        if ($TIP_DOCUMENTO === "" || $TIP_DOCUMENTO=== NULL){
            //print_r('asd');
            $datos = operacionesDB::ListarTiposDocumentos($COD_CONDICION_VENTA,$busqueda, $pag);
        } else {
            //print_r('QWE');
            $datos = operacionesDB::DatosTiposDocumentos($COD_CONDICION_VENTA,$TIP_DOCUMENTO);
            
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