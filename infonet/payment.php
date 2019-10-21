<?php

/**
 * Autor: Esteban Aqiono
 * Fecha: 16/07/2018
 * Descripcion: Registra un pago
 */
//require 'operacionesDB.php';
require 'mensajes.php';
//if ($_SERVER['REQUEST_METHOD'] == 'POST') {
# Capturar post JSON
$json_str = file_get_contents('php://input');
# Obtener como Array
$json_obj = json_decode(utf8_converter_sting($json_str), true);

$guarda = operacionesDB::guardaPeticion('PAGO', $json_obj['tid'], $json_obj['prd_id'], $json_obj['sub_id'][0], json_encode($json_obj['addl']), json_encode($json_obj['inv_id']), $json_obj['amt'], $json_obj['curr'], $json_obj['trn_dat'], $json_obj['cm_amt'], $json_obj['cm_cur'], $json_obj['barcode'], $json_obj['trn_hou']);
header('Content-Type: application/json; charset=utf-8');
// VERIFICAR CAMPOS
$tid = floatval($json_obj['tid']);
$SUB_ID = $json_obj['sub_id'][0];
$inv_id = $json_obj['inv_id'];
$amt = $json_obj['amt'];
$curr = $json_obj['curr'];
IF (IS_NULL($tid) || IS_NULL($SUB_ID) || empty($tid) || empty($SUB_ID) || empty($inv_id) || empty($amt) || empty($curr) || IS_NULL($json_str) || empty($json_str)) {
    http_response_code(mensaje(MSN_INVALID_PARAMETERS_PAGO, CONST_ESTADO_HTTP));
    $datos["status"] = mensaje(MSN_INVALID_PARAMETERS_PAGO, CONST_ESTADO);
    $datosPago["tid"] = $tid;
    $datos["messages"] = mensaje(MSN_INVALID_PARAMETERS_PAGO, CONST_MENSAJE);
    print json_encode($datos);
    operacionesDB::guardarRespuesta($datos, MSN_INVALID_PARAMETERS_PAGO);
} ELSE {
    // VER SI EL CLIENTE TIENE DEUDA
    $tieneDeuda = operacionesDB::tieneDeuda($SUB_ID);
    //PRINT $tieneDeuda.'<BR>';
    IF ($tieneDeuda == 'S') {
        $id_transaccion = operacionesDB::trae_id_transaccion();
        foreach ($json_obj['inv_id'] as $INVOICE) {
            $datosPago["id_transaccion"] = $id_transaccion;
            $datosPago["tid"] = $tid;
            $datosPago["id_saldo"] = $INVOICE;
            $datosPago["monto"] = $json_obj['amt'];
            $datosPago["siglas_iso"] = $json_obj['curr'];
            if (is_array($json_obj['sub_id'])) {
                $datosPago["doc_cliente"] = $json_obj['sub_id'][0];
            } else {
                $datosPago["doc_cliente"] = $_GET['sub_id'];
            }
            //$datosPago["doc_cliente"] = $json_obj['sub_id'][0];
            $datosPago["fecha_env"] = $json_obj['trn_dat'] . ' ' . $json_obj['trn_hou'];
            $datosPago["forma_cob"] = $json_obj['addl']['payment_method'];
            // GUARDAR PAGO
            operacionesDB::guardarPago($datosPago);
            // VER SI EL COMPROBANTE TIENE SALDO SUFICIENTE
            $saldo = operacionesDB::saldoCuota($INVOICE);
            if ($datosPago["monto"] != $saldo) {
                operacionesDB::anulaPago($id_transaccion);
                // SIN DEUDA DE COMPROBANTE
                http_response_code(mensaje(MSN_PAYMENT_UNAUTHORIZED_PAGO, CONST_ESTADO_HTTP));
                $datosAux["status"] = mensaje(MSN_PAYMENT_UNAUTHORIZED_PAGO, CONST_ESTADO);
                $datosAux["tid"] = $tid;
                $datosAux["messages"] = mensaje(MSN_PAYMENT_UNAUTHORIZED_PAGO, CONST_MENSAJE);
                $datosAux["messages"][0]["dsc"][0] = utf8_converter_sting("El monto para la deuda $INVOICE es dirente al saldo que posee");
                print json_encode($datosAux);
                operacionesDB::guardarRespuesta($datosAux, MSN_NO_DEBT_PAGO);
                $procesarPago = 'N';
            } ELSE IF (operacionesDB::verifica_tid($tid)) {
                // YA USO TID
                operacionesDB::anulaPago($id_transaccion);
                http_response_code(mensaje(MSN_PAYMENT_UNAUTHORIZED_PAGO, CONST_ESTADO_HTTP));
                $datosAux["status"] = mensaje(MSN_PAYMENT_UNAUTHORIZED_PAGO, CONST_ESTADO);
                $datosAux["tid"] = $tid;
                $datosAux["messages"] = mensaje(MSN_PAYMENT_UNAUTHORIZED_PAGO, CONST_MENSAJE);
                //var_dump($proceso);
                $dsc = "El numero de transaccion $tid ya fue utilizado en un cobro anterior.";
                $datosAux["messages"][0]["dsc"][0] = $dsc;
                print ltrim(json_encode($datosAux));
                operacionesDB::guardarRespuesta($datosAux, MSN_PAYMENT_UNAUTHORIZED_PAGO);
            } else {
                $procesarPago = 'S';
            }
        }
        if ($procesarPago == 'S') {
            // EJECUTAR PAGO
            $proceso = operacionesDB::procesaTransaccion($id_transaccion);
            // RESPONDER
            IF ($proceso == 'OK') {
                $nro_recibo = operacionesDB::traeNroRecibo($id_transaccion);
                http_response_code(mensaje(MSN_SUCCESS_PAGO, CONST_ESTADO_HTTP));
                $datos["status"] = mensaje(MSN_SUCCESS_PAGO, CONST_ESTADO);
                $datos["tid"] = $tid;
                $datos["tkt"] = intval($nro_recibo);
                $datos["aut_cod"] = intval($nro_recibo);
                $datos["messages"] = mensaje(MSN_SUCCESS_PAGO, CONST_MENSAJE);
                print json_encode($datos);
                operacionesDB::guardarRespuesta($datos, MSN_SUCCESS_PAGO);
            } ELSE {
                // ERROR
                http_response_code(mensaje(MSN_UNKNOWNERROR_PAGO, CONST_ESTADO_HTTP));
                $datosAux["status"] = mensaje(MSN_UNKNOWNERROR_PAGO, CONST_ESTADO);
                $datosAux["tid"] = $tid;
                $datosAux["messages"] = mensaje(MSN_UNKNOWNERROR_PAGO, CONST_MENSAJE);
                $dsc = str_replace(')', '', str_replace('(', '', str_replace(']', '', str_replace('[', '', str_replace('"', '', $proceso)
                                        )
                                )
                        )
                );
                $datosAux["messages"][0]["dsc"][0] = utf8_converter_sting($dsc);
                print json_encode($datosAux);
                operacionesDB::guardarRespuesta($datosAux, MSN_UNKNOWNERROR_PAGO);
            }
        }
    } ELSE {
        // SIN DEUDA
        http_response_code(mensaje(MSN_NO_DEBT_PAGO, CONST_ESTADO_HTTP));
        $datos["status"] = mensaje(MSN_NO_DEBT_PAGO, CONST_ESTADO);
        $datos["tid"] = $tid;
        $datos["messages"] = mensaje(MSN_NO_DEBT_PAGO, CONST_MENSAJE);
        print json_encode($datos);
        operacionesDB::guardarRespuesta($datos, MSN_NO_DEBT_PAGO);
    }
}

//}
?>