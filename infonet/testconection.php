<?php
    require 'db.php';
   /* try {
        foreach(PDO::getAvailableDrivers() as $driver)
        echo $driver, '<br>';

        $mbd = new PDO(
                    'oci:dbname = '.TNS,
                    USERNAME,
                    PASSWORD);
        foreach($mbd->query('SELECT nombre from personas where cod_persona = 14598') as $fila) {
                print_r($fila);
        }
        $mbd = null;

    } catch (PDOException $e) {
        print "¡Error!: " . $e->getMessage() . "<br/>";
        die();
    }*/
try {
    foreach (PDO::getAvailableDrivers() as $driver) {
        echo $driver, '<br>';
    }
	echo "Conectando...";
    $conn = new PDO('odbc:Driver={Microsoft ODBC for Oracle};
                    Server='.DATABASE,
                    USERNAME, 
                    PASSWORD);
   $stmt = $conn->query("select * from personas WHERE ROWNUM = 1");
   $stmt->execute();
    while ($row = $stmt->fetch()) {
        print_r($row['COD_PERSONA']." ".$row['NOMBRE']." ".$row['APELLIDO']."<BR>");
    }
    IF ($conn === null){
        echo("Error al conectar");

    }else{
        echo "Conectado!!";
    }
} catch (PDOException $e) {
        print "¡Error!: " . $e->getMessage() . "<br/>";
        die();
}
 /*  try {
        $conn = oci_connect("inv", "masterinv","SOL");

        IF (!$conn){
            echo("Error al conectar");

        }else{
            echo "Conectado!!";
        }
    } catch (PDOException $e) {
        print "¡Error!: " . $e->getMessage() . "<br/>";
        die();
    }*/
?>

