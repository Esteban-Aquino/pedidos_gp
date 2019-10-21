<?php

/**
 * Autor: Esteban Aqiono
 * Fecha: 13/07/2018
 * Descripcion: Obtiene todas las facturas del abonado
 */

//require 'operacionesDB.php';
require 'mensajes.php';

//if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    ini_set('max_execution_time', 480);
    // INSERTAR PETICION
    //operacionesDB::guardarAux(substr(file_get_contents('php://input'),1,4000)); 
    $guarda = operacionesDB::guardaPeticion('CONS',
                                            $_GET['tid'],
                                            $_GET['prd_id'],
                                            json_encode($_GET['sub_id']),
                                            $_GET['addl'],
                                            $_GET['inv_id'],
                                            $_GET['amt'],
                                            $_GET['cur'],
                                            $_GET['trn_dat'],
                                            $_GET['cm_amt'],
                                            $_GET['cm_cur'],
                                            $_GET['barcode'],
                                            $_GET['trn_hou']);
    header('Content-Type: application/json; charset=utf-8');
    //print floatval("202632111111");
    //PRINT 'GUARDO: '.$guarda;
    if ( $guarda == 'OK'){
        IF (!isset ($_GET['tid'])){
            // RESPUESTA
            http_response_code(mensaje(MSN_INVALID_PARAMETERS, CONST_ESTADO_HTTP));
            $datos["status"] = mensaje(MSN_INVALID_PARAMETERS, CONST_ESTADO);
            $datos["messages"] = mensaje(MSN_INVALID_PARAMETERS, CONST_MENSAJE);
            print json_encode($datos);
            operacionesDB::guardarRespuesta($datos,MSN_SUCCESS);
        }else if ( !isset ($_GET['sub_id'])){
            // RESPUESTA
            $tid = floatval($_GET['tid']);
            http_response_code(mensaje(MSN_INVALID_PARAMETERS, CONST_ESTADO_HTTP));
            $datos["status"] = mensaje(MSN_INVALID_PARAMETERS, CONST_ESTADO);
            $datos["messages"] = mensaje(MSN_INVALID_PARAMETERS, CONST_MENSAJE);
            print json_encode($datos);
            operacionesDB::guardarRespuesta($datos,MSN_SUCCESS);
        } else { 
            // RESPUESTA
            $tid = floatval($_GET['tid']);
            if (is_array($_GET['sub_id'])){
                $SUB_ID = json_decode(json_encode($_GET['sub_id']),true)[0];
            }else{
                $SUB_ID = $_GET['sub_id'];
            }
            
            IF(IS_NULL($tid)|| IS_NULL($SUB_ID)||empty($tid)|| empty($SUB_ID)){
                http_response_code(mensaje(MSN_INVALID_PARAMETERS, CONST_ESTADO_HTTP));
                $datos["status"] = mensaje(MSN_INVALID_PARAMETERS, CONST_ESTADO);
                $datos["messages"] = mensaje(MSN_INVALID_PARAMETERS, CONST_MENSAJE);
                print json_encode($datos);
                operacionesDB::guardarRespuesta($datos,MSN_SUCCESS);
            }ELSE {
                // BUSCAR FACTURAS
                $existencia= operacionesDB::getFacturas($SUB_ID);
                if ($existencia) {
                    // SE ENCONTRO FACTURAS
                    // RESPUESTA
                    http_response_code(mensaje(MSN_SUCCESS, CONST_ESTADO_HTTP));
                    $datos["status"] = mensaje(MSN_SUCCESS, CONST_ESTADO);
                    $datos["tid"] = $tid;
                    $datos["messages"] = mensaje(MSN_SUCCESS, CONST_MENSAJE);
                    $i = 0;
                    foreach ($existencia as $value) {
                        $invoices[$i]["due"] = $value["DUE"];
                        $invoices[$i]["amt"] = intval($value["AMT"]);
                        $invoices[$i]["min_amt"] = intval($value["MIN_AMT"]);
                        $inv_id[0]=$value["INV_ID"];
                        $invoices[$i]["inv_id"] = $inv_id;
                        $invoices[$i]["curr"] = $value["CURR"];
                        $addl[0]=$value["NRO_FACTURA"];
                        $addl[1]=$value["NOMBRE"];
                        $invoices[$i]["addl"] = $addl;

                        //Buscar next_dues
                        // Le saco por que no se que rayos hace bancard, asi que mejor le dejo nulo. mef 21-08-2018
                        /*$next_dues= operacionesDB::getProxVenc($value["COD_CLIENTE"], $value["DUE"]);
                        if ($next_dues) {
                            $f = 0;
                            foreach ($next_dues as $valor) {
                                $NEXT[$f]["amt"] = intval($valor["AMT"]);  
                                $NEXT[$f]["date"] = $valor["VENC"];    
                                $f++;
                            }
                            $invoices[$i]["next_dues"] = $NEXT;
                            $NEXT= "";
                       }else{*/
                            //$AUX[0]=;
                            $invoices[$i]["next_dues"] = array();
                        //}
                        $invoices[$i]["cm_amt"] = 0;
                        $invoices[$i]["cm_curr"] = "PYG";
                        $invoices[$i]["dsc"] = $value["DSC"];
                        $i++;
                    }

                    $datos["invoices"] = $invoices;
                    print json_encode($datos);
                    //echo json_last_error();
                    operacionesDB::guardarRespuesta($datos,MSN_SUCCESS);
                } else {
                    // NO SE ENCONTRO FACTURAS
                    $datos["tid"] = $tid;
                    IF (operacionesDB::existe_cliente($SUB_ID)){
                        http_response_code(mensaje(MSN_NO_DEBT, CONST_ESTADO_HTTP));
                        //header('HTTP/1.0 401 Unauthorized');
                        $datos["status"] = mensaje(MSN_NO_DEBT, CONST_ESTADO);
                        $datos["messages"] = mensaje(MSN_NO_DEBT, CONST_MENSAJE);
                    }ELSE{
                        http_response_code(mensaje(MSN_NO_CLIENT, CONST_ESTADO_HTTP));
                        $datos["status"] = mensaje(MSN_NO_CLIENT, CONST_ESTADO);
                        $datos["messages"] = mensaje(MSN_NO_CLIENT, CONST_MENSAJE);
                    }
                    // RESPUESTA
                    print json_encode($datos);
                    operacionesDB::guardarRespuesta($datos,MSN_SUCCESS);
                }
            }
        }
        
    }else{
        // ERROR AL GUARDAR PETICION
        http_response_code(mensaje(MSN_INVALID_PARAMETERS, CONST_ESTADO_HTTP));
        $datos["status"] = mensaje(MSN_INVALID_PARAMETERS, CONST_ESTADO);
        $datos["messages"] = mensaje(MSN_INVALID_PARAMETERS, CONST_MENSAJE);
        $dsc = str_replace(')','',
                                    str_replace('(','',
                                                        str_replace(']','',
                                                                            str_replace('[','',str_replace('"','',$guarda)
                                                                                        )
                                                                    )
                                                )
                            );
        $datos["messages"][0]["dsc"][0] = utf8_converter_sting($dsc);
        // RESPUESTA
        print json_encode($datos);
        operacionesDB::guardarRespuesta($datos,MSN_SUCCESS);
    }
//}

?>