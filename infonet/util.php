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
