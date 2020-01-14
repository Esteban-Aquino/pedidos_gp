<?php

use Firebase\JWT\JWT;

require_once '../config/operacionesDB.php';
require_once '../config/php-jwt-master/src/JWT.php';

function validarToken($token) {
    $valid = true;
    $decoded = '';
    $message = '';
    $respuesta["valid"] = $valid;
    $respuesta["decoded"] = $decoded;
    $respuesta["message "] = $message;
    try {
        $decoded = JWT::decode($token, llave_super_secreta(), array('HS256'));
    } catch (Exception $e) {
        $valid = false;
        $message = $e->getMessage();
    }
    if ($valid) {
        $valid = operacionesDB::ValidaVencToken($token, $decoded[0]->USR_CALL);
        if (!$valid) {
            $message = 'Token expirado';
        }
    }
    $respuesta["valid"] = $valid;
    $respuesta["decoded"] = $decoded;
    $respuesta["message"] = $message;
    //print_r ($respuesta);
    //print_r($valido[0]['VALID']);
    //print_r ($decoded['decoded'][0]->USR_CALL);

    return $respuesta;
}

function generaToken($datos) {
    $time = time(); //Fecha y hora actual en segundos
    $key = llave_super_secreta();
    $usuario = $datos[0]['USR_CALL'];
    $token = JWT::encode($datos, $key); //CodificaR el Token
    $conn = operacionesDB::guardaToken($usuario, $token);
    return $token;
}

/**
 * Trae datos del token
 *
 * @param Token y dato requerido
 * @return Dato del token o el token completo decodificado
 */
function traeDatoToken($token) {
    $decoded = '';
    $respuesta["valid"] = $valid;
    $respuesta["decoded"] = $decoded;
    $respuesta["message "] = $message;
    try {
        $decoded = JWT::decode($token, llave_super_secreta(), array('HS256'));
    } catch (Exception $e) {
        $valid = false;
        $message = $e->getMessage();
    }
    $respuesta["valid"] = $valid;
    $respuesta["decoded"] = $decoded;
    $respuesta["message"] = $message;


    return $respuesta;
}

function formatea_respuesta($respuesta) {
    return str_replace(')', '', str_replace('(', '', str_replace(']', '', str_replace('[', '', str_replace('"', '', $respuesta)
                            )
                    )
            )
    );
}
