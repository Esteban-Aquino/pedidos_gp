<?php

    $nombre_fichero = '../articulos/11016_001.jpg';

    if (file_exists($nombre_fichero)) {
        echo "El fichero $nombre_fichero existe";
    } else {
        echo "El fichero $nombre_fichero no existe";
    }