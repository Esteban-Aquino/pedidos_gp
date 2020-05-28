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
        
        $cod_cliente = NVL($_GET["COD_CLIENTE"], "");
        $cod_direccion = NVL($_POST["COD_DIRECCION"], "");
        $busqueda = NVL($_POST["buscar_texto"], "");
        $pag = NVL($_POST["pagina"], 1);
        if ($cod_direccion==="" || $cod_direccion===null) {
           //print("asdasd");
           $datos = operacionesDB::ListarDirecciones($cod_cliente, $busqueda, $pag); 
        }else{
           $datos = operacionesDB::DatosDireccion($cod_cliente, $cod_direccion);
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