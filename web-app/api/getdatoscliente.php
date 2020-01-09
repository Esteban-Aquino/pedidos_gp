<?php

    /**
    * Trae datos de cliente 
    * Esteban Aquino 321-11-2019
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
        $documento = $_POST["DOCUMENTO"];
        $cod_cliente = $_POST["COD_CLIENTE"];
        $busqueda = NVL($_POST["buscar_texto"], "");
        $pag = NVL($_POST["pagina"], 1);
        if (($documento === null || $documento === "") && ($cod_cliente === "" || $cod_cliente === null)) {
            //print_r('asd');
            $datos = operacionesDB::ListarCliente($busqueda, $pag);
        } else {
            //print_r('qwe');
            $datos = operacionesDB::getDatosCliente($documento, $cod_cliente);
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