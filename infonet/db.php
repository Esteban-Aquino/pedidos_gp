<?php
/**
 * Provee las constantes para conectarse a la base de datos
 * 
 */
define("TNS", "(DESCRIPTION =
    (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.2.200)(PORT = 1521))
    (CONNECT_DATA =
      (SERVER = DEDICATED)
      (SERVICE_NAME = pytil)
    )
  )"); // tns
DEFINE("TNSALT","(DESCRIPTION = 
      (ADDRESS_LIST =
        (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.2.200)(PORT = 1521))
      )
    (CONNECT_DATA =
      (SERVICE_NAME = PYTIL)
    )
   )");
define("DATABASE", "GUATA"); // Nombre del db
define("USERNAME", "INV"); // Nombre del usuario
define("PASSWORD", "INVGUATA"); // Nombre de la constraseña
?>