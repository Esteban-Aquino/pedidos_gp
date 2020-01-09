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
    $json_str = file_get_contents('php://input');
    $json_obj = json_decode(utf8_converter_sting($json_str), true);
    $cabecera = $json_obj["CABECERA"];
    $detalle = $json_obj["DETALLE"];
    $totales = $json_obj["TOTALES"];

    // GUARDAR CABECERA

    $datos = operacionesDB::insertaCabeceraPedido($cabecera);

    //print_r($datos);

    if (is_numeric($datos)) {
        $mens = 'OK';
    } ELSE {
        $mens = $datos;
        $datos = 'ERROR';
    }

    if ($datos !== 'ERROR') {
        // Si la cabecera inserto bien $datos va a tener el nro de pedido insertado 
        //$detalle['NRO_SOLICUTUD'] = $datos;
        // GUARDAR DETALLE
        // recorrer array
        //saco el numero de elementos
        $longitud = count($detalle);
        //Recorro todos los elementos
        for ($i = 0; $i < $longitud; $i++) {
            //saco el valor de cada elemento
            $detalle[$i]['NRO_SOLICUTUD'] = $datos;
            $datosDet = operacionesDB::insertaDetallePedido($detalle[$i]);
            if ($datosDet !== 'OK') {
                break;
            }
            //print_r($detalle[$i]);
        }
    }
    if ($datosDet !== 'OK') {
        $mens = $datosDet;
        $datos = 'ERROR';
    } ELSE {
        $mens = 'OK';
    }
    
    IF ($mens === 'OK') {
        $confirmado = operacionesDB::completaCarga($datos);
    }
    
    if ($confirmado !== 'OK') {
        $mens = $confirmado;
        $datos = 'ERROR';
    } ELSE {
        $mens = 'OK';
    }
    
    
    //print_r($mens);
    $respuesta["acceso"] = true;
    $respuesta["datos"] = $datos;
    $respuesta["mensaje"] = $mens;
    //print_r($respuesta);
} else {
    $respuesta["acceso"] = false;
    $respuesta["mensaje"] = 'Token no valido';
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
print json_encode($respuesta);
