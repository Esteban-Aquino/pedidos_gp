<?php

/**
 * Autor: Esteban Aquino
 * Fecha: 17/07/2018
 * Descripcion: Reversa un pago
 */
//require 'operacionesDB.php';
require 'mensajes.php';
//if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    # Capturar post JSON
    $json_str = file_get_contents('php://input');
    # Obtener como Array
    $json_obj = json_decode(utf8_converter_sting($json_str),true);
    
    $guarda = operacionesDB::guardaPeticion('REVER',
                                  $json_obj['tid'],
                                  $json_obj['prd_id'],
                                  $json_obj['sub_id'][0],
                                  json_encode($json_obj['addl']),
                                  json_encode($json_obj['inv_id']),
                                  $json_obj['amt'],
                                  $json_obj['curr'],
                                  $json_obj['trn_dat'],
                                  $json_obj['cm_amt'],
                                  $json_obj['cm_cur'],
                                  $json_obj['barcode'],
                                  $json_obj['trn_hou']);
    // VERIFICAR CAMPOS
    $tid = floatval($json_obj['tid']);
    $SUB_ID = $json_obj['sub_id'][0];
    $inv_id = $json_obj['inv_id'];
    $amt = $json_obj['amt'];
    $curr = $json_obj['curr'];
    header('Content-Type: application/json; charset=utf-8');
    IF(IS_NULL($tid)||empty($tid)||IS_NULL($json_str)||empty($json_str)){
        http_response_code(mensaje(MSN_INVALID_PARAMETERS_PAGO, CONST_ESTADO_HTTP));
        $datos["status"] = mensaje(MSN_INVALID_PARAMETERS_PAGO, CONST_ESTADO);
        $datos["tid"] = $tid;
        $datos["messages"] = mensaje(MSN_INVALID_PARAMETERS_PAGO, CONST_MENSAJE);
        print json_encode($datos);
        operacionesDB::guardarRespuesta($datos,MSN_INVALID_PARAMETERS_PAGO);
    }ELSE {
        // PROCESAR ANULACION
        $id_transaccion = operacionesDB::trae_id_transaccion();
        $datosPago["id_transaccion"] = $id_transaccion;
        $datosPago["tid"] = $tid;
        operacionesDB::guardaReverso($datosPago);
        
        $proceso = operacionesDB::procesaReverso($id_transaccion);
        //print $proceso;
        // RESPONDER
        IF ($proceso == 'OK'){
            http_response_code(mensaje(MSN_SUCCESS_REVERSE, CONST_ESTADO_HTTP));
            $datos["status"] = mensaje(MSN_SUCCESS_REVERSE, CONST_ESTADO);
            $datos["tid"] = $tid;
            $datos["messages"] = mensaje(MSN_SUCCESS_REVERSE, CONST_MENSAJE);
            print json_encode($datos);
            operacionesDB::guardarRespuesta($datos,MSN_SUCCESS_REVERSE);

        }ELSE IF($proceso == 'R'){
            // YA REVERSADO
            http_response_code(mensaje(MSN_ALREDY_REVERSED_REVERSE, CONST_ESTADO_HTTP));
            $datos["status"] = mensaje(MSN_ALREDY_REVERSED_REVERSE, CONST_ESTADO);
            $datos["tid"] = $tid;
            $datos["messages"] = mensaje(MSN_ALREDY_REVERSED_REVERSE, CONST_MENSAJE);
            print json_encode($datos);
            operacionesDB::guardarRespuesta($datos,MSN_ALREDY_REVERSED_REVERSE);
        }ELSE{
            // ERROR
            http_response_code(mensaje(MSN_NOT_REVERSED_REVERSE, CONST_ESTADO_HTTP));
            $datosAux["status"] = mensaje(MSN_NOT_REVERSED_REVERSE, CONST_ESTADO);
            $datosAux["tid"] = $tid;
            $datosAux["messages"] = mensaje(MSN_NOT_REVERSED_REVERSE, CONST_MENSAJE);
            //var_dump($proceso);
            $dsc = str_replace(')','',
                                    str_replace('(','',
                                                        str_replace(']','',
                                                                            str_replace('[','',str_replace('"','',$proceso)
                                                                                        )
                                                                    )
                                                )
                            );
            $datosAux["messages"][0]["dsc"][0] = $dsc;
            print ltrim(json_encode($datosAux));
            operacionesDB::guardarRespuesta($datosAux,MSN_NOT_REVERSED_REVERSE);
        }
        
    }
    
//}
?>