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
    $dsc = formatea_respuesta($datos);
    //print_r($datos);

    if (is_numeric($datos)) {
        $mens = 'OK';
    } ELSE {
        $mens = $dsc;
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
        $dsc = formatea_respuesta($datosDet);
    }
    //print_r($datosDet);
    
    //print_r($dsc);
    if ($datosDet !== 'OK') {
        $mens = $dsc;
        $datos = 'ERROR';
    } ELSE {
        $mens = 'OK';
    }
    //print_r($mens);
    IF ($mens === 'OK') {
        $confirmado = operacionesDB::completaCarga($datos);
        $dsc = formatea_respuesta($confirmado);
    }
    
    if ($confirmado !== 'OK') {
        $mens = $dsc;
        $datos = 'ERROR';
    } ELSE {
        $mens = 'OK';
    }
    
    
    //print_r($mens);
    $respuesta["acceso"] = true;
    $respuesta["datos"] = utf8_converter_sting($datos);
    $respuesta["mensaje"] = $mens;
    //print_r($respuesta);
} else {
    $respuesta["acceso"] = false;
    $respuesta["mensaje"] = 'Token no valido';
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
print json_encode($respuesta);
