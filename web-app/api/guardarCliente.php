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
    if ($token !== 'null'&&$token !== null) { 
        $ok = validarToken($token)['valid'];
    }     
    if ($ok) {

        $NOMBRE = NVL($_POST["ALTA_NOMBRE"], "");
        $NOMBRE_FANTASIA = NVL($_POST["ALTA_NOMBRE_FANTASIA"], "");
        $DIRECCION= NVL($_POST["ALTA_DIRECCION"], "");
        $AREA1= NVL($_POST["ALTA_AREA1"], "");
        $NUMERO1 = NVL($_POST["ALTA_NUMERO1"], "");
        $AREA2= NVL($_POST["ALTA_AREA2"], "");
        $NUMERO2 = NVL($_POST["ALTA_NUMERO2"], "");
        $RUC= NVL($_POST["ALTA_RUC"], "");
        $CI = NVL($_POST["ALTA_CI"], "");
        $FEC_NACIMIENTO= NVL($_POST["ALTA_FEC_NACIMIENTO"], "");
        $SEXO= NVL($_POST["ALTA_SEXO"], "");
        
        $persona['COD_EMPRESA'] = '1';
        $persona['NOMBRE'] = $NOMBRE;
        $persona['NOMBRE_FANTASIA'] = $NOMBRE_FANTASIA;
        $persona['DIRECCION'] = $DIRECCION;
        $persona['AREA1'] = $AREA1;
        $persona['NUMERO1'] = $NUMERO1;
        $persona['AREA2'] = $AREA2;
        $persona['NUMERO2'] = $NUMERO2;
        $persona['RUC'] = $RUC;
        $persona['CI'] = $CI;
        $persona['FEC_NACIMIENTO'] = $FEC_NACIMIENTO;
        $persona['SEXO'] = $SEXO;
        
        //print_r($persona);
        $datos = operacionesDB::insertaCliente($persona);
        
        if ($datos != 'OK') {
          $mens = $datos;
        }
        
        $respuesta["acceso"] = true;
        $respuesta["datos"] = $datos;
        $respuesta["mensaje"] = $mens;
    }else{
        $respuesta["acceso"] = false;
        $respuesta["mensaje"] = 'Token no valido';
    }

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: *');
    print json_encode($respuesta);