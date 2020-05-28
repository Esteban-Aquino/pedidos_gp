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
if ($token !== 'null' && $token !== null) {
    $ok = validarToken($token)['valid'];
}
if ($ok) {

    $NOMBRE = NVL($_POST["ALTA_NOMBRE"], "");
    $NOMBRE_FANTASIA = NVL($_POST["ALTA_NOMBRE_FANTASIA"], "");
    $DIRECCION = NVL($_POST["ALTA_DIRECCION"], "");
    
    $COD_CIUDAD = NVL($_POST["ALTA_COD_CIUDAD"], "");
    $CIUDAD = NVL($_POST["ALTA_CIUDAD"], "");
    $COD_BARRIO = NVL($_POST["ALTA_COD_BARRIO"], "");
    $BARRIO = NVL($_POST["ALTA_BARRIO"], "");
    
    
    $AREA1 = NVL($_POST["ALTA_AREA1"], "");
    $NUMERO1 = NVL($_POST["ALTA_NUMERO1"], "");
    $AREA2 = NVL($_POST["ALTA_AREA2"], "");
    $NUMERO2 = NVL($_POST["ALTA_NUMERO2"], "");
    $RUC = NVL($_POST["ALTA_RUC"], "");
    $CI = NVL($_POST["ALTA_CI"], "");
    $FEC_NACIMIENTO = NVL($_POST["ALTA_FEC_NACIMIENTO"], "");
    $SEXO = NVL($_POST["ALTA_SEXO"], "");
    
    /*$NOMBRE = NVL($_GET["ALTA_NOMBRE"], "");
    $NOMBRE_FANTASIA = NVL($_GET["ALTA_NOMBRE_FANTASIA"], "");
    $DIRECCION = NVL($_GET["ALTA_DIRECCION"], "");
    
    $COD_CIUDAD = NVL($_GET["ALTA_COD_CIUDAD"], "");
    $CIUDAD = NVL($_GET["ALTA_CIUDAD"], "");
    $COD_BARRIO = NVL($_GET["ALTA_COD_BARRIO"], "");
    $BARRIO = NVL($_GET["ALTA_BARRIO"], "");
    
    
    $AREA1 = NVL($_GET["ALTA_AREA1"], "");
    $NUMERO1 = NVL($_GET["ALTA_NUMERO1"], "");
    $AREA2 = NVL($_GET["ALTA_AREA2"], "");
    $NUMERO2 = NVL($_GET["ALTA_NUMERO2"], "");
    $RUC = NVL($_GET["ALTA_RUC"], "");
    $CI = NVL($_GET["ALTA_CI"], "");
    $FEC_NACIMIENTO = NVL($_GET["ALTA_FEC_NACIMIENTO"], "");
    $SEXO = NVL($_GET["ALTA_SEXO"], "");*/

    $persona['COD_EMPRESA'] = '1';
    $persona['NOMBRE'] = $NOMBRE;
    $persona['NOMBRE_FANTASIA'] = $NOMBRE_FANTASIA;
    $persona['DIRECCION'] = $DIRECCION;
    
    $persona['COD_CIUDAD'] = $COD_CIUDAD;
    $persona['CIUDAD'] = $CIUDAD;
    $persona['COD_BARRIO'] = $COD_BARRIO;
    $persona['BARRIO'] = $BARRIO;
    
    $persona['AREA1'] = $AREA1;
    $persona['NUMERO1'] = $NUMERO1;
    $persona['AREA2'] = $AREA2;
    $persona['NUMERO2'] = $NUMERO2;
    $persona['RUC'] = $RUC;
    $persona['CI'] = $CI;
    $persona['FEC_NACIMIENTO'] = NVL($FEC_NACIMIENTO,'');
    $persona['SEXO'] = $SEXO;

    //print_r($persona);
    $datos = operacionesDB::insertaCliente($persona);
    $dsc = formatea_respuesta($datos);
    //print_r($dsc);
    if ($dsc !== 'OK') {
        $mens = $dsc;
        $datos = 'ERROR';
    } ELSE {
        $mens = 'OK';
    }


    $respuesta["acceso"] = true;
    $respuesta["datos"] = $datos;
    $respuesta["mensaje"] = $mens;
} else {
    $respuesta["acceso"] = false;
    $respuesta["mensaje"] = 'Token no valido';
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
print json_encode($respuesta);
