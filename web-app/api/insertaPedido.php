<?php
    /**
    * Validar Usuario
    * Esteban Aquino 30/09/2019
    */
    require_once '../config/operacionesDB.php';
    require_once 'sharedFunctions.php';
    # Obtener headers
    $head = getallheaders();
    //print_r($head);
    $token = $head['token'];
    $ok = false;
    // Verificar autenticidad del token
    if ($token !== 'null'&&$token !== null) { 
        $ok = validarToken($token)['valid'];
    }
    
    if ($ok) { 
        # Capturar post JSON
        $json_str = file_get_contents('php://input');
        # Obtener como Array
        $pedido = json_decode(utf8_converter_sting($json_str), true);
        
        print_r($pedido);
        
    }
    
    // agregar cabecera
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: *');
    print json_encode($respuesta);
?>
