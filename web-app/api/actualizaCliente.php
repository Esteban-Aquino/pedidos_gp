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
    $cabecera = $json_obj["CLIENTE"];
    $direcciones = $json_obj["DIRECCIONES"];
    $telefonos = $json_obj["TELEFONOS"];
    $documentos = $json_obj["DOCUMENTOS"];

    // GUARDAR CABECERA
    //print_r($cabecera);
    $datos = operacionesDB::actualizaClienteCabecera($cabecera);
    $dsc = formatea_respuesta($datos);
    //print_r($datos);

    if ($datos === 'OK') {
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
        $longitud = count($direcciones);
        //Recorro todos los elementos
        for ($i = 0; $i < $longitud; $i++) {
            $datosDir = operacionesDB::actualizaClienteDireccion($cabecera['COD_CLIENTE'], $direcciones[$i]);
            if ($datosDir !== 'OK') {
                break;
            }
            //print_r($detalle[$i]);
        }
        $dsc = formatea_respuesta($datosDir);
    }
    //print_r($datosDet);
    
    
    //print_r($dsc);
    if ($datosDir !== 'OK') {
        $mens = $dsc;
        $datos = 'ERROR';
    } ELSE {
        $mens = 'OK';
    }
    
    // TELEFONOS
    if ($datos !== 'ERROR') {
        //saco el numero de elementos
        $longitud = count($telefonos);
        //Recorro todos los elementos
        for ($i = 0; $i < $longitud; $i++) {
            $datosTelef = operacionesDB::actualizaClienteTelefono($cabecera, $telefonos[$i]);
            if ($datosTelef !== 'OK') {
                break;
            }
            //print_r($detalle[$i]);
        }
        $dsc = formatea_respuesta($datosTelef);
    }
    //print_r($datosDet);
    
    
    //print_r($dsc);
    if ($datosTelef !== 'OK') {
        $mens = $dsc;
        $datos = 'ERROR';
    } ELSE {
        $mens = 'OK';
    }
    // DOCUMENTOS
    if ($datos !== 'ERROR') {
        //saco el numero de elementos
        $longitud = count($documentos);
        //Recorro todos los elementos
        for ($i = 0; $i < $longitud; $i++) {
            $datosDoc = operacionesDB::actualizaClienteDocumento($cabecera, $documentos[$i]);
            if ($datosDoc !== 'OK') {
                break;
            }
            //print_r($detalle[$i]);
        }
        $dsc = formatea_respuesta($datosDoc);
    }
    //print_r($datosDet);
    
    
    //print_r($dsc);
    if ($datosDoc !== 'OK') {
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
