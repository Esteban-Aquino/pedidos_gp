<?php

    /**
    * Trae datos de cliente 
    * Esteban Aquino 321-11-2019
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
        $documento = $_POST["DOCUMENTO"];
        $cod_cliente = $_POST["COD_CLIENTE"];
        //$cod_cliente = $_GET["COD_CLIENTE"];
        $completo = $_GET["COMPLETO"];
        $busqueda = NVL($_POST["buscar_texto"], "");
        $pag = NVL($_POST["pagina"], 1);
        if (($documento === null || $documento === "") && ($cod_cliente === "" || $cod_cliente === null)) {
            //print_r('asd');
            $datos = operacionesDB::ListarCliente($busqueda, $pag);
        } else {
            //print_r('qwe');
            // SI COMPLETO ESTA CARGADO
            if (nvl($completo,'N') == 'N') {
                $datos = operacionesDB::getDatosCliente($documento, $cod_cliente);
            } else {
                // datos de cliente
                $datosCliente = operacionesDB::DatosClienteCompleto($cod_cliente);
                $datos['CLIENTE'] = $datosCliente[0];
                // direcciones
                $datosDireccion = operacionesDB::DatosDireccionesCompleto($cod_cliente);
                $datos['DIRECCIONES'] = $datosDireccion;
                // telefomos
                $datosTelefonos = operacionesDB::DatosTelefonosCompleto($cod_cliente);
                $datos['TELEFONOS'] = $datosTelefonos;
                // documentos
                $datosDocumentos = operacionesDB::DatosDocumentosCompleto($cod_cliente);
                $datos['DOCUMENTOS'] = $datosDocumentos;
            }
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