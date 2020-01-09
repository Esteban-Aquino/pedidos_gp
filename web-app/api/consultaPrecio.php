<?php

    /**
    * Lista colores
    * Esteban Aquino 04-12-2019
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
        
        $cod_articulo = NVL($_GET["COD_ARTICULO_AGREGAR"], "");
        $cod_unidad_medida= NVL($_GET["COD_UNIDAD_MEDIDA"], "");
        
        $datos = operacionesDB::consultaPrecio($cod_articulo, $cod_unidad_medida);
        

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