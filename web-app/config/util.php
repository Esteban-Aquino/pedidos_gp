<?php
/**
 * Autor: Esteban Aqiono
 * Fecha: 01/03/2017
 * Descripcion: Confersor a utf8 para parsear a JSON
 */

function utf8_converter($array)
    {
        array_walk_recursive($array, function(&$item, $key){
            if(!mb_detect_encoding($item, 'utf-8', true)){
                    $item = utf8_encode($item);
            }
        });

        return $array;
    }
function utf8_converter_sting($string)
{
    if(!mb_detect_encoding($string, 'utf-8', true)){
            $string = utf8_encode($string);
    }
    return $string;
}


function llave_super_secreta(){
        $key = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0eHQiOiJwdXRvX2VsX3F1ZV9kZWNvZGlmaWNhIn0.P1rjOy9uniubE2Xs3MGZ-Qo39yU3HtU7PBvRNBaJXwM';
    return $key;
}

function nvl($var, $default = "")
{   
    if (!isset($var)) {
        $valor = $default;
    } else if ($var === "") {
        $valor = $default;
    } else if ($var === null) {
        $valor = $default;
    } else {
        $valor = $var;
    }
    return $valor;
}
