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
        
        $cod_articulo = NVL($_GET["COD_ARTICULO"], "");
        $cod_color = NVL($_POST["COD_COLOR"], "");
        $cod_talla = NVL($_POST["COD_TALLA"], "");
        $busqueda = NVL($_POST["buscar_texto"], "");
        $pag = NVL($_POST["pagina"], 1);
        //print($cod_articulo);
        if (($cod_color==="" || $cod_color===null) || ($cod_talla==="" || $cod_talla===null)) {
           //print("asdasd");
           $datos = operacionesDB::ListarColoresTallas($busqueda, $pag, $cod_articulo); 
        }else{
            //print("DSADSA");
           //$datos = operacionesDB::DatosDireccion($cod_cliente, $cod_direccion);
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