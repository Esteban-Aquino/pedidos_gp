<?php
    /**
    * Validar Usuario
    * Esteban Aquino 30/09/2019
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
    
    if (!$ok) { 
        # Capturar post JSON
        $json_str = file_get_contents('php://input');
        # Obtener como Array
        $json_obj = json_decode(utf8_converter_sting($json_str), true);
        $usuario = $json_obj['usuario'];
        $clave = $json_obj['clave'];
        
        if ($usuario != null || $clave != null){
            $datos = operacionesDB::ValidarUsuario($usuario, $clave);
            //print $conn;
            if ($datos != null){
                $ok = true;
                $token = generaToken($datos);
                $respuesta["token"] = $token;
                $respuesta["acceso"] = true;
            }
        }
    }
    if (!$ok){
        //$respuesta["datos_usuario"] = null;
        $respuesta["token"] = '';
        $respuesta["acceso"] = false;
        //http_response_code(401);
    }else{
        //http_response_code(200);
        $respuesta["token"] = $token;
         $respuesta["acceso"] = true;
    }
    // agregar cabecera
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: *');
    print json_encode($respuesta);
?>
