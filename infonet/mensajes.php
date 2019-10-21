<?php
    /**
    * Autor: Esteban Aqiono
    * Fecha: 13/07/2018
    * Descripcion: Retorna mensajes estandares para infonet
    */
    require 'operacionesDB.php';
    /**************** CONSUTAR *******************/
    define("MSN_INVALID_PARAMETERS", 2);
    define("MSN_MISS_PARAMETERS", 3);
    define("MSN_NO_CLIENT", 4);
    define("MSN_NO_DEBT", 8);
    define("MSN_SUCCESS", 9);
    
    /**************** PAGO *******************/
    define("MSN_NO_DEBT_PAGO", 10);
    define("MSN_INVALID_PARAMETERS_PAGO", 11);
    define("MSN_UNKNOWNERROR_PAGO", 14);
    define("MSN_PAYMENT_UNAUTHORIZED_PAGO", 16);
    define("MSN_SUCCESS_PAGO", 17);
    
    /**************** REVERSO *******************/
    define("MSN_ALREDY_REVERSED_REVERSE", 18);
    define("MSN_INVALID_PARAMETERS_REVERSE", 19);
    define("MSN_UNKNOWNERROR_REVERSE", 21);
    define("MSN_NOT_REVERSED_REVERSE", 23);
    define("MSN_SUCCESS_REVERSE", 24);
    
    /**************** TIPO DE RETORNO *******************/
    define("CONST_MENSAJE", 1);
    define("CONST_ESTADO", 2);
    define("CONST_ESTADO_HTTP", 3);
    
    function mensaje($id_mensaje,$tipo){
        $query = operacionesDB::getMensaje($id_mensaje);
        IF ($tipo == CONST_MENSAJE){
            IF ($query) {
                $mensajes[0]["level"] = $query[0]["NIVEL"];
                $mensajes[0]["key"] = $query[0]["CODIGO"];
                $mensajes[0]["dsc"][0] = $query[0]["DESCRIPCION"];
            }
        } else if($tipo == CONST_ESTADO) {
                $mensajes = $query[0]["ESTADO"];
        }else if($tipo == CONST_ESTADO_HTTP) {
                $mensajes = $query[0]["HTTP_STATUS_DESC"];
        }
        RETURN $mensajes;
    }

