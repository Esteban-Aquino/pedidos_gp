<?php

/**
 * Autor: Esteban Aqiono
 * Fecha: 01/03/2017
 * Descripcion: Todas las interacciones con la base de datos se coloca aqui
 */
require 'oraconnect.php';
require 'util.php';

class operacionesDB {

    function __construct() {
        
    }

    /*************** WEB API BANCARD *******************/

    public static function getFacturas($SUB_ID) {
        $consulta = "SELECT TO_CHAR(S.FEC_VENCIMIENTO,'yyyy-MM-dd') DUE,
                    ROUND(S.SALDO_CUOTA,M.DECIMALES) AMT,
                    ROUND(S.SALDO_CUOTA,M.DECIMALES) MIN_AMT,
                    S.ID_SALDO INV_ID,
                    M.SIGLAS_ISO CURR,
                    CASE 
                       WHEN S.TIPO_COMPROBANTE IN ('FCR','MCO','MCR') THEN
                         S.SER_COMPROBANTE||'-'||LPAD(S.NRO_COMPROBANTE,8,'0')
                       WHEN S.TIPO_COMPROBANTE IN ('PPT','PAG') THEN
                         (SELECT DISTINCT SA.SER_COMPROBANTE||'-'||LPAD(S.NRO_COMPROBANTE,8,'0')
                          FROM CC_PAGARES PG JOIN 
                               CC_SALDOS SA
                               ON PG.COD_EMPRESA = SA.COD_EMPRESA
                               AND PG.TIP_COMPROBANTE_REF = SA.TIPO_COMPROBANTE
                               AND PG.SER_COMPROBANTE_REF = SA.SER_COMPROBANTE
                               AND PG.NRO_COMPROBANTE_REF = SA.NRO_COMPROBANTE
                               AND PG.SER_TIMBRADO_REF = SA.SER_TIMBRADO
                               AND PG.TIMBRADO_REF = SA.TIMBRADO
                          WHERE S.TIPO_COMPROBANTE = PG.TIP_COMPROBANTE
                          AND S.SER_COMPROBANTE = PG.SER_COMPROBANTE
                          AND S.NRO_COMPROBANTE = PG.NRO_COMPROBANTE)
                    END NRO_FACTURA,
                    PR.NOMBRE,
                    CASE 
                       WHEN S.TIPO_COMPROBANTE IN ('FCR','MCO','MCR') THEN
                         'Factura:'||S.SER_COMPROBANTE||'-'||LPAD(S.NRO_COMPROBANTE,8,'0')||' - Cliente: '||PR.NOMBRE
                       WHEN S.TIPO_COMPROBANTE IN ('PPT','PAG') THEN
                         (SELECT DISTINCT 'Factura: '||SA.SER_COMPROBANTE||'-'||LPAD(S.NRO_COMPROBANTE,8,'0')
                          FROM CC_PAGARES PG JOIN 
                               CC_SALDOS SA
                               ON PG.COD_EMPRESA = SA.COD_EMPRESA
                               AND PG.TIP_COMPROBANTE_REF = SA.TIPO_COMPROBANTE
                               AND PG.SER_COMPROBANTE_REF = SA.SER_COMPROBANTE
                               AND PG.NRO_COMPROBANTE_REF = SA.NRO_COMPROBANTE
                               AND PG.SER_TIMBRADO_REF = SA.SER_TIMBRADO
                               AND PG.TIMBRADO_REF = SA.TIMBRADO
                          WHERE S.TIPO_COMPROBANTE = PG.TIP_COMPROBANTE
                          AND S.SER_COMPROBANTE = PG.SER_COMPROBANTE
                          AND S.NRO_COMPROBANTE = PG.NRO_COMPROBANTE)||' - Cliente: '||PR.NOMBRE
                    END||' - '|| 'CUOTA '||TRIM(TO_CHAR(S.FEC_VENCIMIENTO,'MONTH'))||' '||TRIM(TO_CHAR(S.FEC_VENCIMIENTO,'YYYY')) DSC,
                    S.COD_CLIENTE,
                    S.TIPO_COMPROBANTE,
                    S.SER_COMPROBANTE,
                    S.NRO_COMPROBANTE,
                    S.SER_TIMBRADO,
                    S.TIMBRADO
             FROM CC_SALDOS S JOIN
                  CC_CLIENTES C 
                  ON S.COD_EMPRESA = C.COD_EMPRESA
                  AND S.COD_CLIENTE = C.COD_CLIENTE
                  JOIN MONEDAS M
                  ON S.COD_MONEDA_CUOTA = M.COD_MONEDA
                  JOIN PERSONAS PR
                  ON C.COD_PERSONA = PR.COD_PERSONA
             WHERE S.SALDO_CUOTA > 0
             AND S.FEC_VENCIMIENTO <= ADD_MONTHS(TRUNC(SYSDATE),2)
             AND TO_CHAR(S.FEC_VENCIMIENTO,'MM/YYYY') = TO_CHAR((SELECT MIN(F.FEC_VENCIMIENTO)
                                                                 FROM CC_SALDOS F
                                                                 WHERE F.COD_CLIENTE = C.COD_CLIENTE
                                                                 AND F.SALDO_CUOTA > 0),'MM/YYYY')
             AND EXISTS (SELECT 1
                           FROM PERSONAS P JOIN
                                IDENT_PERSONAS I
                                ON P.COD_PERSONA = I.COD_PERSONA
                           WHERE C.COD_PERSONA = P.COD_PERSONA
                           AND (I.NUMERO = '$SUB_ID'))
             AND (INSTR(bs_busca_parametro('BS','TIENDAS_INFONET'),'|'||S.COD_SUCURSAL||'|') > 0
                  OR bs_busca_parametro('BS','TIENDAS_INFONET') = 'TODAS')
             order by s.fec_vencimiento";
        //print $consulta.'</BR>';
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function getProxVenc($cod_cliente, $pFEC_COMPROBANTE) {
        // RETORNA EN MAYUSCULAS LOS ALIAS. CORREGIR ESO. MEF 06-07-2018
        $sql = "SELECT ROUND(S.SALDO_CUOTA,M.DECIMALES) AMT, TO_CHAR(S.FEC_VENCIMIENTO,'YYYY-MM-DD') VENC
                FROM CC_SALDOS S 
                     JOIN MONEDAS M
                     ON S.COD_MONEDA_CUOTA = M.COD_MONEDA
                WHERE S.COD_CLIENTE = " . $cod_cliente . "
                AND (S.FEC_VENCIMIENTO > TO_DATE('" . $pFEC_COMPROBANTE . "','YYYY-MM-DD') OR '" . $pFEC_COMPROBANTE . " 'IS NULL)
                AND (S.FEC_VENCIMIENTO <= ADD_MONTHS(TRUNC(SYSDATE),2))
                AND S.SALDO_CUOTA > 0
                AND S.COD_EMPRESA = '1'
                order by S.FEC_VENCIMIENTO";
        //print $sql."<br>";
        try {
            $comando = oraconnect::getInstance()->getDb()->query($sql);
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);
            // recorrer result
            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function getMensaje($id_mensaje) {
        $sql = "SELECT D.ESTADO,
                       D.NIVEL,
                       D.CODIGO,
                       D.DESCRIPCION,
                       D.HTTP_STATUS,
                       D.HTTP_STATUS_DESC
                 FROM WEB_API_MENSAJES_DSC D
                 WHERE D.ID_MENSAJE = '" . $id_mensaje . "'";
        //print $sql."<br>";
        try {
            $comando = oraconnect::getInstance()->getDb()->query($sql);
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function existe_cliente($SUB_ID) {
        $sql = "SELECT DISTINCT 'S'
                FROM PERSONAS P JOIN
                     IDENT_PERSONAS I
                     ON P.COD_PERSONA = I.COD_PERSONA
                AND I.NUMERO = '$SUB_ID'";
        //print $sql."<br>";
        try {
            $comando = oraconnect::getInstance()->getDb()->query($sql);
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            if ($result) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function guardaPeticion($cod_tipo, $tid, $prd_id = null, $sub_id = null, $addl = null, $inv_id = null, $amt = null, $cur = null, $trn_dat = null, $cm_amt = null, $cm_cur = null, $barcode = null, $trn_hou = null) {
        /* cod_tipo VARCHAR2(30),
          tid      VARCHAR2(30),
          prd_id   VARCHAR2(30),
          sub_id   VARCHAR2(30),
          addl     VARCHAR2(2000),
          inv_id   VARCHAR2(2000),
          amt      VARCHAR2(30),
          cur      VARCHAR2(10),
          trn_dat  VARCHAR2(30),
          cm_amt   NUMBER(18,4),
          cm_cur   VARCHAR2(10),
          barcode  VARCHAR2(80),
          trn_hou  VARCHAR2(30),
          fecha    DATE */
        $sql = "INSERT INTO WEB_API_PETICIONES (cod_tipo,
                                tid,
                                prd_id,
                                sub_id,
                                addl,
                                inv_id,
                                amt,
                                cur,
                                trn_dat,
                                cm_amt,
                                cm_cur,
                                barcode,
                                trn_hou)
                    VALUES ('$cod_tipo',
                            '$tid',
                            '$prd_id',
                            '$sub_id',
                            '$addl',
                            '$inv_id',
                            '$amt',
                            '$cur',
                            '$trn_dat',
                            '$cm_amt',
                            '$cm_cur',
                            '$barcode',
                            '$trn_hou')";
        //print $sql."<br>";
        try {
            $ERR = 'preparar sentencia';
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $ERR = 'ejecuntar sentencia';
            $comando->execute();
            /* $ERR = 'buscar error';
              $correcto = $comando->errorInfo(); */
            $ERR = 'OK';
            return $ERR;
        } catch (PDOException $e) {
            return 'Error al ' . $ERR /*. ' - ' . $comando->errorInfo()[2]*/;
        }
    }

    public static function guardaMensaje($array, $tid, $id_mensaje) {
        /*
          tid          VARCHAR2(30),
          descripcion  VARCHAR2(200),
          id_mensaje   NUMBER,
          mensaje_json VARCHAR2(2000)
         */
        //print "<br><br> ".json_encode($array)." ".$array[0]["dsc"]." <br>";
        $sql = "INSERT INTO WEB_API_MENSAJES (tid,
                                              descripcion,
                                              id_mensaje,
                                              mensaje_json)
                    VALUES ('" . $tid . "',
                            '" . $array[0]["dsc"][0] . "',
                            '$id_mensaje',
                            '" . json_encode($array) . "')";
        //print "<br><br>".$sql."<br>";
        try {
            $ERR = 'preparar sentencia';
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $ERR = 'ejecuntar sentencia';
            $comando->execute();
            $ERR = 'OK';
            return $ERR;
        } catch (PDOException $e) {
            //print 'Error al ' . $ERR . "<br>";
            if (!is_null($comando)) {
                $msn = $comando->errorInfo()[2];
            }
            return 'Error al ' . $ERR . ' - ' . $msn;
        }
    }

    public static function guardarRespuesta($array, $id_mensaje = null) {
        /*
          status   VARCHAR2(80),
          tid      VARCHAR2(30),
          messages VARCHAR2(4000),
          tkt      VARCHAR2(80),
          aut_cod  VARCHAR2(80),
          prnt_msn VARCHAR2(4000),
          invoices CLOB
         */

        $sql = "INSERT INTO WEB_API_RESPUESTAS (status,
                                              tid,
                                              tkt,
                                              aut_cod,
                                              prnt_msn)
                    VALUES ('" . $array["status"] . "',
                            '" . $array["tid"] . "',
                            '" . $array["tkt"] . "',
                            '" . $array["aut_cod"] . "',
                            '" . $array["prnt_msn"] . "')";
        //print "<br><br>".$sql."<br>";
        try {
            $ERR = 'preparar sentencia';
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $ERR = 'ejecuntar sentencia';
            $comando->execute();
            //print "<br><br>AQUI1<br>";
            operacionesDB::guardaMensaje($array["messages"], $array["tid"], $id_mensaje);
            $ERR = 'OK';
            return $ERR;
        } catch (PDOException $e) {
            //print 'Error al ' . $ERR . "<br>";
            if (!is_null($comando)) {
                $msn = $comando->errorInfo()[2];
            }
            return 'Error al ' . $ERR . ' - ' . $msn;
        }
    }

    public static function tieneDeuda($SUB_ID){
        $consulta = "SELECT DISTINCT 'S' S  
                    FROM CC_SALDOS S JOIN
                         CC_CLIENTES C 
                         ON S.COD_EMPRESA = C.COD_EMPRESA
                         AND S.COD_CLIENTE = C.COD_CLIENTE
                         JOIN MONEDAS M
                         ON S.COD_MONEDA_CUOTA = M.COD_MONEDA
                         JOIN PERSONAS PR
                         ON C.COD_PERSONA = PR.COD_PERSONA
                    WHERE S.SALDO_CUOTA > 0
                    AND EXISTS (SELECT 1
                                  FROM PERSONAS P JOIN
                                       IDENT_PERSONAS I
                                       ON P.COD_PERSONA = I.COD_PERSONA
                                  WHERE C.COD_PERSONA = P.COD_PERSONA
                                  AND (I.NUMERO LIKE '%'||'$SUB_ID'||'%'
                                       OR P.NOMBRE LIKE '%'||'$SUB_ID'||'%'))";
        //PRINT $consulta;
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC)[0]["S"];
            //var_dump($result);
            //PRINT EMPTY($result);
            IF (EMPTY($result)){
                return 'N';
            }ELSE{
                return 'S';
            }
        } catch (PDOException $e) {
            return false;
        }

        
    }

    public static function saldoCuota($id_saldo){
        $consulta = "SELECT S.SALDO_CUOTA S
                    FROM CC_SALDOS S
                    WHERE S.ID_SALDO = '$id_saldo'  
                    AND NVL(S.SALDO_CUOTA,0) > 0";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC)[0]["S"];
            //var_dump($result);
            //PRINT EMPTY($result);
            IF (EMPTY($result)){
                return 0;
            }ELSE{
                return $result;
            }
        } catch (PDOException $e) {
            return false;
        }

        
    }
    
    public static function trae_id_transaccion(){
        $consulta = "select WEB_API_TRAE_ID_TRANCACCION() id"
                . "  from dual";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC)[0]["ID"];
            //var_dump($result);
            return $result;
        } catch (PDOException $e) {
            return false;
        }

        
    }
    
    public static function verifica_tid($tid) {
        $sql = "select distinct 'S'
                from WEB_API_PAGOS p
                WHERE P.TID = $tid
                AND NVL(P.ESTADO,'N') NOT IN ('P','A')";
        //print $sql."<br>";
        try {
            $comando = oraconnect::getInstance()->getDb()->query($sql);
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            if ($result) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public static function guardarPago($array) {
        /*
          id_transaccion NUMBER,
          tid            VARCHAR2(30),
          id_saldo       NUMBER,
          monto          NUMBER(18,4),
          siglas_iso     VARCHAR2(10),
          doc_cliente    VARCHAR2(80),
          fecha_env      VARCHAR2(200),
          forma_cob      VARCHAR2(30)
         */

        $sql = "INSERT INTO WEB_API_PAGOS (id_transaccion, 
                           tid,
                           id_saldo, 
                           monto, 
                           siglas_iso, 
                           doc_cliente, 
                           fecha_env, 
                           forma_cob,
                           estado)
                VALUES ('".$array['id_transaccion']."', 
                        '".$array['tid']."',
                        '".$array['id_saldo']."', 
                        ".$array['monto'].", 
                        '".$array['siglas_iso']."', 
                        '".$array['doc_cliente']."', 
                        '".$array['fecha_env']."', 
                        '".$array['forma_cob']."',
                        'P')";
        //print $sql;
        try {
            $ERR = 'preparar sentencia';
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $ERR = 'ejecuntar sentencia';
            $comando->execute();
            $ERR = 'OK';
            return $ERR;
        } catch (PDOException $e) {
            if (!is_null($comando)) {
                $msn = $comando->errorInfo()[2];
            }
            return 'Error al ' . $ERR . ' - ' . $msn;
        }
    }
    
    public static function anulaPago($id_transaccion) {
        /*
          id_transaccion NUMBER,
          tid            VARCHAR2(30),
          id_saldo       NUMBER,
          monto          NUMBER(18,4),
          siglas_iso     VARCHAR2(10),
          doc_cliente    VARCHAR2(80),
          fecha_env      VARCHAR2(200),
          forma_cob      VARCHAR2(30)
         */

        $sql = "UPDATE WEB_API_PAGOS
                SET ESTADO = 'A'
                WHERE ID_TRANSACCION = '$id_transaccion'";
        //print $sql.'<br>';
        try {
            $ERR = 'preparar sentencia';
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $ERR = 'ejecuntar sentencia';
            $comando->execute();
            $ERR = 'OK';
            return $ERR;
        } catch (PDOException $e) {
            if (!is_null($comando)) {
                $msn = $comando->errorInfo()[2];
            }
            return 'Error al ' . $ERR . ' - ' . $msn;
        }
    }
    
    public static function procesaTransaccion($id_transaccion){
        $sql = "begin ? := WEP_API_PROCESA_PAGOS($id_transaccion); end;";
        //print $sql.'<br>';
        try {
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(1, $result, PDO::PARAM_INT|PDO::PARAM_INPUT_OUTPUT, 32);
            $comando->execute();
            //var_dump($result);
            return $result;
        } catch (PDOException $e) {
            return false;
        }
        
    }
    
    public static function traeNroRecibo($id_transaccion){
        $consulta = "SELECT R.Nro_Recibo NRO
                    FROM CC_RECIBOS R
                    WHERE R.ID_TRANSACCION = '".$id_transaccion."'";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC)[0]["NRO"];
            return $result;
        } catch (PDOException $e) {
            return false;
        }

        
    }
    
    public static function guardaReverso($array) {
        /*
          id_transaccion NUMBER,
          tid            VARCHAR2(30),
          id_saldo       NUMBER,
          monto          NUMBER(18,4),
          siglas_iso     VARCHAR2(10),
          doc_cliente    VARCHAR2(80),
          fecha_env      VARCHAR2(200),
          forma_cob      VARCHAR2(30)
         */

        $sql = "INSERT INTO WEB_API_REVERSOS (id_transaccion, 
                           tid,
                           estado)
                VALUES ('".$array['id_transaccion']."', 
                        '".$array['tid']."',
                        'P')";
        try {
            $ERR = 'preparar sentencia';
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $ERR = 'ejecuntar sentencia';
            $comando->execute();
            $ERR = 'OK';
            return $ERR;
        } catch (PDOException $e) {
            if (!is_null($comando)) {
                $msn = $comando->errorInfo()[2];
            }
            return 'Error al ' . $ERR . ' - ' . $msn;
        }
    }
    
    public static function procesaReverso($id_transaccion){
        $sql = "begin ? := WEP_API_PROCESA_REVERSOS($id_transaccion); end;";
        //print $consulta.'<br>';
        try {
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(1, $result, PDO::PARAM_INT|PDO::PARAM_INPUT_OUTPUT, 300);
            $comando->execute();
            //var_dump($result);
            return $result;
        } catch (PDOException $e) {
            return false;
        }
        
    }
    
    public static function guardarAux($aux) {
        /*
          id_transaccion NUMBER,
          tid            VARCHAR2(30),
          id_saldo       NUMBER,
          monto          NUMBER(18,4),
          siglas_iso     VARCHAR2(10),
          doc_cliente    VARCHAR2(80),
          fecha_env      VARCHAR2(200),
          forma_cob      VARCHAR2(30)
         */

        $sql = "INSERT INTO WEB_API_AUX (DATO)
                VALUES ('".$aux."')";
        try {
            $ERR = 'preparar sentencia';
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $ERR = 'ejecuntar sentencia';
            $comando->execute();
            $ERR = 'OK';
            return $ERR;
        } catch (PDOException $e) {
            if (!is_null($comando)) {
                $msn = $comando->errorInfo()[2];
            }
            return 'Error al ' . $ERR . ' - ' . $msn;
        }
    }
    
    /*************** WEB API BANCARD *******************/
    /*FINAL*/}

?>