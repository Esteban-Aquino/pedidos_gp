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
$dir_foto = '../articulos/';
$dir_foto_web = 'articulos/';
// Verificar autenticidad del token
if ($token !== 'null' && $token !== null) {
    //$ok = validarToken($token)['valid'];
    $ok = true;
}
if ($ok) {

    /*$cod_articulo = NVL($_POST["COD_ARTICULO"], "");
    $busqueda = NVL($_POST["buscar_texto"], "");
    $pag = NVL($_POST["pagina"], 1);*/
    $cod_articulo = NVL($_GET["COD_ARTICULO"], "");
    $busqueda = NVL($_GET["buscar_texto"], "");
    $pag = NVL($_GET["pagina"], 1);
    
    if ($cod_articulo === "" || $cod_articulo === null) {
        //print("asdasd");
        $datos = operacionesDB::ListarArticulos($busqueda, $pag);
        $longitud = count($datos);
        if ($longitud > 0) {
            for ($i = 0; $i < $longitud; $i++) {
                //print_r($dir_foto.$datos[$i]['COD_ARTICULO'].'JPG');
                $dir_completa = $dir_foto . $datos[$i]['COD_ARTICULO'] . '.JPG';
                $dir_completa_web = $dir_foto_web . $datos[$i]['COD_ARTICULO'] . '.JPG';
                //print_r($dir_completa);
                if (file_exists($dir_completa)) {
                    $datos[$i]['IMAGEN'] = $dir_completa_web;
                } else {
                    $dir_completa = $dir_foto . $datos[$i]['COD_ARTICULO'] . '.PNG';
                    if (file_exists($dir_completa)) {
                        $datos[$i]['IMAGEN'] = $dir_completa_web;
                    } else {
                        $datos[$i]['IMAGEN'] = $dir_foto_web .'SIN_IMAGEN.JPG';
                    }
                }
                //print_r($datos[$i]); 
            }
        }
    } else {
        //$datos = operacionesDB::DatosDireccion($cod_cliente, $cod_direccion);
    }

    $respuesta["acceso"] = true;
    $respuesta["datos"] = $datos;
    $respuesta["mensaje"] = '';
} else {
    $respuesta["acceso"] = false;
    $respuesta["mensaje"] = 'Token no valido';
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
print json_encode($respuesta);
