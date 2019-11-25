<?php

/**
 * Todas las interacciones con la base de datos se coloca aqui
 * Esteban Aquino 30/09/2019
 */
require 'oraconnect.php';
require 'util.php';

class operacionesDB {

    function __construct() {
        
    }

    /**
     * Valida credenciales del usuario
     *
     * @param Usuario, Clave
     * @return true o false
     */
    public static function ValidarUsuario($usuario, $clave) {
        $autorizado = false;
        try {
            $conn = null;
            $consulta = "SELECT U.USR_CALL,
                        INITCAP(P.NOMBRE) NOMBRE,
                        C.COD_CALL,
                        PC.NOMBRE NOM_CALL,
                        V.COD_VENDEDOR
                 FROM WEB_APP_USUARIOS U
                      JOIN WEB_APP_CALL C
                      ON U.COD_CALL = C.COD_CALL
                      JOIN FV_VENDEDORES V
                      ON U.COD_VENDEDOR = V.COD_VENDEDOR
                      AND U.COD_EMPRESA = V.COD_EMPRESA
                      JOIN PERSONAS P
                      ON V.COD_PERSONA = P.COD_PERSONA
                      JOIN PERSONAS PC
                      ON C.COD_PERSONA = PC.COD_PERSONA
                 WHERE UPPER(U.USR_CALL) = UPPER(:usuario)
                 AND UPPER(U.CLAVE) = UPPER(:clave)
                 AND NVL(U.ACTIVO,'N') = 'S'
                 AND NVL(C.ACTIVO,'N') = 'S'";
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':usuario' => $usuario, ':clave' => $clave]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            //print_r($result);
            IF ($result !== null) {
                $autorizado = true;
            }
            if ($autorizado) {
                return utf8_converter($result);
            }
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Guarda el token para corroborar validez luego
     *
     * @param usuario y token
     * @return nada
     */
    public static function guardaToken($usr, $token) {

        $sql = "UPDATE WEB_APP_USUARIOS U
                SET U.COD_HASH = :token,
                    U.FEC_EMISION_HASH = SYSDATE,
                    U.FEC_VENC_HASH = SYSDATE + (1/24*8)
                WHERE UPPER(U.USR_CALL) = UPPER(:usr)";
        //print $sql;
        try {
            $ERR = 'preparar sentencia';
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $ERR = 'ejecuntar sentencia';
            $comando->execute([':token' => $token, ':usr' => $usr]);
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

    /**
     * Valida la autenticidad del token
     *
     * @param Token y Usuario
     * @return true o false
     */
    public static function ValidaVencToken($token, $usuario) {
        $autorizado = false;
        //print_r($token);
        //print_r($usuario);
        try {
            $conn = null;
            $consulta = "SELECT 'S' VALID
                        FROM WEB_APP_USUARIOS U
                             JOIN WEB_APP_CALL C
                             ON U.COD_CALL = C.COD_CALL
                             JOIN FV_VENDEDORES V
                             ON U.COD_VENDEDOR = V.COD_VENDEDOR
                             AND U.COD_EMPRESA = V.COD_EMPRESA
                             JOIN PERSONAS P
                             ON V.COD_PERSONA = P.COD_PERSONA
                             JOIN PERSONAS PC
                             ON C.COD_PERSONA = PC.COD_PERSONA
                        WHERE U.USR_CALL = :usuario
                        AND U.COD_HASH = :token
                        AND NVL(U.ACTIVO,'N') = 'S'
                        AND NVL(C.ACTIVO,'N') = 'S'
                        AND SYSDATE BETWEEN U.FEC_EMISION_HASH AND U.FEC_VENC_HASH";
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':usuario' => $usuario, ':token' => $token]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            //print_r($result[0]['VALID']);
            IF ($result[0] !== null) {
                return true;
            } ELSE {
                return false;
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Guarda peido
     *
     * @param Array de pedido
     * @return Id del pedido o error
     */
    public static function guardaPedido($pedido, $token) {
        $datosToken = traeDatoToken($token);
        $USR_CALL = $datosToken['USR_CALL'];
        $COD_CALL = $datosToken['COD_CALL'];
        $COD_VENDEDOR = $datosToken['COD_VENDEDOR'];

        // Sql cabecera del pedido
        $sql_cab = "INSERT INTO WEB_APP_PED_CAB
                (cod_empresa, 
                cod_pedido, 
                cod_sucursal, 
                fec_comprobante, 
                cod_cliente, 
                nom_cliente, 
                cod_vendedor, 
                cod_condicion_venta, 
                cod_lista_precio,
                cod_moneda,
                tip_cambio, 
                estado, 
                tel_cliente, 
                ruc, 
                cambiar_nombre, 
                comentario, 
                usr_call, 
                cod_call, 
                cod_promocion)
         VALUES (:cod_empresa, 
                 :cod_pedido, 
                 :cod_sucursal, 
                 :fec_comprobante, 
                 :cod_cliente, 
                 :nom_cliente, 
                 :cod_vendedor, 
                 :cod_condicion_venta, 
                 :cod_lista_precio,
                 :cod_moneda,
                 :tip_cambio, 
                 :estado, 
                 :tel_cliente, 
                 :ruc, 
                 :cambiar_nombre, 
                 :comentario, 
                 :usr_call, 
                 :cod_call, 
                 :cod_promocion)";
        // Sql detalle del pedido
        $sql_det = "INSERT INTO WEB_APP_PED_CAB 
                (COD_EMPRESA,
                 COD_PEDIDO,
                 COD_SUCURSAL,
                 FEC_COMPROBANTE,
                 COD_CLIENTE,
                 NOM_CLIENTE,
                 COD_VENDEDOR,
                 COD_CONDICION_VENTA,
                 COD_LISTA_PRECIO,
                 COD_MONEDA,
                 TIP_CAMBIO,
                 ESTADO,
                 TEL_CLIENTE,
                 RUC,
                 CAMBIAR_NOMBRE,
                 COMENTARIO,
                 FECHA_DE_CARGA,
                 TIP_DOCUMENTO,
                 USR_CALL,
                 COD_CALL,
                 COD_PROMOCION)
         VALUES (:COD_EMPRESA,
                 :COD_PEDIDO,
                 :COD_SUCURSAL,
                 :FEC_COMPROBANTE,
                 :COD_CLIENTE,
                 :NOM_CLIENTE,
                 :COD_VENDEDOR,
                 :COD_CONDICION_VENTA,
                 :COD_LISTA_PRECIO,
                 :COD_MONEDA,
                 :TIP_CAMBIO,
                 :ESTADO,
                 :TEL_CLIENTE,
                 :RUC,
                 :CAMBIAR_NOMBRE,
                 :COMENTARIO,
                 :FECHA_DE_CARGA,
                 :TIP_DOCUMENTO,
                 :USR_CALL,
                 :COD_CALL,
                 :COD_PROMOCION)";
        // Insertar
        try {
            // CABECERA
            print_r($pedido);
            /* $dbh = oraconnect::getInstance()->getDb();
              $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
              $dbh->beginTransaction();

              $comando = $dbh->prepare($sql_cab);

              // insertar cabecera
              $dbh->execute([':token'=>$token, ':usr'=>$usr]);

              $dbh->exec("insert into salarychange (id, amount, changedate)
              values (23, 50000, NOW())");
              $dbh->commit(); */
        } catch (Exception $e) {
            $dbh->rollBack();
            echo "Failed: " . $e->getMessage();
        }
    }

    /**
     * Valida Trae datos de cliente
     *
     * @param Documento y codigo de cliente
     * @return Datos de cliente
     */
    public static function getDatosCliente($documento, $cod_cliente) {
        $autorizado = false;
        try {
            $conn = null;
            $consulta = "select cc.COD_CLIENTE, 
                                CC.COD_PERSONA,
                                initcap(p.nombre) DESC_CLIENTE,
                                d.COD_DIRECCION,
                                initcap(d.detalle) DESC_DIRECCION,
                                nvl(T.codigo_area,' ')||' '||T.num_telefono TEL_CLIENTE,
                                NVL((SELECT I.NUMERO
                                FROM IDENT_PERSONAS I
                                WHERE CC.COD_PERSONA = I.COD_PERSONA
                                AND I.COD_IDENT = 'RUC'
                                AND ROWNUM=1),
                                (SELECT I.NUMERO
                                FROM IDENT_PERSONAS I
                                WHERE CC.COD_PERSONA = I.COD_PERSONA
                                AND I.COD_IDENT = 'CI'
                                AND ROWNUM=1)) RUC,
                                CC.COD_CONDICION_VENTA,
                                NVL(CC.COD_MONEDA_LIMITE,'1') COD_MONEDA,
                                NVL(CC.TIP_DOCUMENTO,bs_busca_parametro('CC','WEB_PLAZO_DOC')) TIP_DOCUMENTO
                        from ident_personas a
                             join cc_clientes cc
                             on cc.cod_persona = a.cod_persona
                             join personas p
                             on cc.cod_persona = p.cod_persona
                             left join direc_personas d
                             on p.cod_persona = d.cod_persona
                             LEFT JOIN TELEF_PERSONAS T
                             ON P.COD_PERSONA = T.COD_PERSONA
                        where (a.numero = :DOCUMENTO OR (CC.COD_CLIENTE = :COD_CLIENTE AND :DOCUMENTO1 IS NULL))
                        and rownum = 1
                        and nvl(cc.estado, 'X') <> 'I'";
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':DOCUMENTO' => $documento,
                ':COD_CLIENTE' => $cod_cliente,
                ':DOCUMENTO1' => $documento]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Trae datos de la condicion de venta
     *
     * @param Cod_condicion
     * @return datos de la condicion de venta
     */
    public static function getCondicionVenta($cod_ondicion_venta) {
        try {
            $consulta = "select descripcion DESC_CONDICION, 
                                cod_condicion_venta,
                                c.nro_cuotas,
                                c.dias_inicial,
                                c.per_cuotas
                         from cc_condiciones_ventas c
                         where cod_empresa = '1'
                         AND cod_condicion_venta = :COD_CONDICION_VENTA
                         order by 1";
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_CONDICION_VENTA' => $cod_ondicion_venta]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Trae datos de la condicion de venta
     *
     * @param $pag Pagina actual, $busqueda Texto buscado
     * @return Lista de condiciones de venta
     */
    public static function ListarCondicionesVentas($busqueda, $pag) {
        try {
            $cant = 10;
            $consulta = "select dsa.DESC_CONDICION, dsa.cod_condicion_venta
                        from (select asd.*, rownum r
                                from (select descripcion DESC_CONDICION,
                                             cod_condicion_venta
                                        from cc_condiciones_ventas c
                                       where cod_empresa = '1'
                                         AND (UPPER(descripcion) LIKE
                                             '%' || UPPER(:busqueda) || '%' or
                                             UPPER(cod_condicion_venta) LIKE
                                             '%' || UPPER(:busqueda1) || '%')
                                         AND NVL(C.ESTADO, 'A') = 'A'
                                       order by 1) asd
                              ) dsa
                       where r between ({$pag}+ ({$pag} * {$cant}) - ({$cant}+ {$pag}) + 1) and
                             ({$pag} + ({$pag}* {$cant}) - ({$cant} + {$pag}) + {$cant})";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':busqueda' => $busqueda,
                               ':busqueda1' => $busqueda]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Trae datos de la condicion de venta
     *
     * @param $pag Pagina actual, $busqueda Texto buscado
     * @return Lista de condiciones de venta
     */
    public static function ListarMonedas($cod_moneda, $busqueda, $pag) {
        try {
            $cant = 10;
            $consulta = "select dsa.descripcion, 
                        dsa.cod_moneda, 
                        round(dsa.tipo_cambio_dia) tip_cambio, 
                        dsa.decimales, 
                        dsa.siglas
                   from (select asd.*, rownum r
                           from (
                           select descripcion, cod_moneda, tipo_cambio_dia, nvl( decimales, 0 ) decimales, siglas
                           from monedas
                           where (cod_moneda = :cod_moneda)
                           or (instr(upper(descripcion), upper(:busqueda)) > 0
                           or instr(upper(cod_moneda), upper(:busqueda1)) > 0
                           and  :cod_moneda1 is null)
                           or (:cod_moneda2 is null and :busqueda2 is null)
                           ) asd
                         ) dsa
                  where r between ({$pag}+ ({$pag} * {$cant}) - ({$cant}+ {$pag}) + 1) and
                             ({$pag} + ({$pag}* {$cant}) - ({$cant} + {$pag}) + {$cant})";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':cod_moneda' => $cod_moneda,
                               ':busqueda' => $busqueda,
                               ':busqueda1' => $busqueda,
                               ':cod_moneda1' => $cod_moneda,
                               ':cod_moneda2' => $cod_moneda,
                               ':busqueda2' => $busqueda]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }
    
    /**
     * Retorna datos de venta del dia ANTERIOR
     *
     * @param Nada
     * @return ventas del dia
     */
    public static function getVentasAyer() {
        $consulta = "SELECT * FROM V_INF_VENTAS_AYER";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //print_r($result);
            //print utf8_converter($result);
            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retorna datos de venta del dia
     *
     * @param Nada
     * @return ventas del dia
     */
    public static function getVentasDia() {
        $consulta = "SELECT *"
                . "FROM V_INF_VENTAS_DIA";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retorna datos de venta del mes actual
     *
     * @param Nada
     * @return ventas del dia
     */
    public static function getVentasMes() {
        $consulta = "SELECT *"
                . "FROM V_INF_VENTAS_MES";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retorna renta del año en compraraion al pasado
     *
     * @param Nada
     * @return renta año
     */
    public static function getRentaAnio() {
        $consulta = "SELECT *"
                . "FROM V_INF_RENTA_ANIO";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retorna renta del año en compraraion al pasado
     *
     * @param Nada
     * @return renta año
     */
    public static function getUnidadesMes() {
        $consulta = "SELECT *"
                . "FROM V_INF_UNIDADES_MES";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retorna renta del año en compraraion al pasado
     *
     * @param Nada
     * @return renta año
     */
    public static function getTicketsMes() {
        $consulta = "SELECT *"
                . "FROM V_INF_TICKETS_MES";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retorna renta del año en compraraion al pasado
     *
     * @param Nada
     * @return renta año
     */
    public static function getMontoPromedioTicketsMes() {
        $consulta = "SELECT *"
                . "FROM V_INF_MON_TICK_PROM_MES";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retorna datos de venta del dia detallado
     *
     * @param Nada
     * @return ventas del dia
     */
    public static function getDetalleVentasDia() {
        $consulta = "SELECT *"
                . "FROM V_INF_VENTAS_DIA_DET";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //print $comando;
            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retorna datos de venta de ayer
     *
     * @param Nada
     * @return ventas del dia
     */
    public static function getDetalleVentasAyer() {
        $consulta = "SELECT *"
                . "FROM V_INF_VENTAS_AYER_DET";
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retorna datos de venta de ayer
     *
     * @param Nada
     * @return ventas del dia
     */
    public static function getVentasMarca($p_fec_des, $p_fec_has) {
        $consulta = "SELECT COD_MARCA, MARCA,
                    SUM(VENTAS) VENTAS,
                    SUM(VENTAS_ANIO_PAST) VENTAS_ANIO_PAST,
                    ROUND(DECODE(NVL(SUM(VENTAS_ANIO_PAST), 0),
                                 0,
                                 DECODE(NVL(SUM(VENTAS), 0), 0, 0, 999),
                                 ((NVL(SUM(VENTAS), 0) - NVL(SUM(VENTAS_ANIO_PAST), 0)) /
                                 NVL(SUM(VENTAS_ANIO_PAST), 0)) * 100),
                          2) PORC
               FROM (
                     /*OPTIMA*/
                     SELECT  M.COD_MARCA,
                             M.DESCRIPCION MARCA,
                             ROUND(SUM(CASE
                                         WHEN V.FEC_ALTA BETWEEN
                                              TRUNC(TO_DATE('" . $p_fec_des . "', 'DD/MM/YYYY')) AND
                                              TRUNC(TO_DATE('" . $p_fec_has . "', 'DD/MM/YYYY')) THEN
                                          V.VENTA_NETA_GS
                                         ELSE
                                          0
                                       END)) VENTAS,
                             ROUND(SUM(CASE
                                         WHEN V.FEC_ALTA BETWEEN
                                              ADD_MONTHS((TRUNC(TO_DATE('" . $p_fec_des . "', 'DD/MM/YYYY'))),
                                                         -12) AND
                                              ADD_MONTHS(TRUNC(TO_DATE('" . $p_fec_has . "', 'DD/MM/YYYY')), -12) THEN
                                          V.VENTA_NETA_GS
                                         ELSE
                                          0
                                       END)) VENTAS_ANIO_PAST
                       FROM REL_MARCA_OP_GP M
                       JOIN VMATRIZ_VENTA_C_MAT V
                         ON M.COD_MARCA_OP = V.COD_MARCA
                      WHERE V.COD_VENDEDOR IN (SELECT DISTINCT VA.COD_VENDEDOR
                                               FROM EDS_VENDEDORES@GUATA VA
                                               WHERE VA.COD_EMPRESA = '2')
                      GROUP BY M.DESCRIPCION, M.COD_MARCA
                     UNION ALL
                     /*GP*/
                     SELECT M.COD_MARCA,
                            M.DESCRIPCION MARCA,
                            ROUND(SUM(CASE
                                        WHEN C.FEC_COMPROBANTE BETWEEN
                                             TRUNC(TO_DATE('" . $p_fec_des . "', 'DD/MM/YYYY')) AND
                                             TRUNC(TO_DATE('" . $p_fec_has . "', 'DD/MM/YYYY')) THEN
                                         C.TOTAL_GS
                                        ELSE
                                         0
                                      END)) VENTAS,
                            ROUND(SUM(CASE
                                        WHEN C.FEC_COMPROBANTE BETWEEN
                                             ADD_MONTHS((TRUNC(TO_DATE('" . $p_fec_des . "', 'DD/MM/YYYY'))),
                                                        -12) AND
                                             ADD_MONTHS(TRUNC(TO_DATE('" . $p_fec_has . "', 'DD/MM/YYYY')), -12) THEN
                                         C.TOTAL_GS
                                        ELSE
                                         0
                                      END)) VENTAS_ANIO_PAST
                       FROM REL_MARCA_OP_GP M
                       JOIN VVT_VTA_TOTAL_MAT@GUATA C
                         ON C.COD_MARCA = M.COD_MARCA_OP
                       WHERE C.COD_VENDEDOR IN (SELECT DISTINCT VA.COD_VENDEDOR
                                                FROM EDS_VENDEDORES@GUATA VA
                                                WHERE VA.COD_EMPRESA = '1')
                      GROUP BY M.DESCRIPCION, M.COD_MARCA)
              GROUP BY MARCA, COD_MARCA
              HAVING SUM(VENTAS) != 0 OR  SUM(VENTAS_ANIO_PAST) != 0
             ORDER BY 3 DESC
             ";
        //print $consulta;
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function getDetalleVentasRubro($p_fec_des, $p_fec_has, $cod_marca) {
        $consulta = "SELECT COD_RUBRO,
                    RUBRO,
                    SUM(VENTAS) VENTAS,
                    SUM(VENTAS_ANIO_PAST) VENTAS_ANIO_PAST,
                    (SUM(VENTAS) - SUM(VENTAS_ANIO_PAST)) DIFERENCIA
               FROM (
                     /*OPTIMA*/
                     SELECT R.COD_RUBRO,
                             R.DESCRIPCION RUBRO,
                             ROUND(SUM(CASE
                                         WHEN V.FEC_ALTA BETWEEN
                                              TRUNC(TO_DATE('" . $p_fec_des . "', 'DD/MM/YYYY')) AND
                                              TRUNC(TO_DATE('" . $p_fec_has . "', 'DD/MM/YYYY')) THEN
                                          V.VENTA_NETA_GS
                                         ELSE
                                          0
                                       END)) VENTAS,
                             ROUND(SUM(CASE
                                         WHEN V.FEC_ALTA BETWEEN
                                              ADD_MONTHS((TRUNC(TO_DATE('" . $p_fec_des . "', 'DD/MM/YYYY'))),
                                                         -12) AND
                                              ADD_MONTHS(TRUNC(TO_DATE('" . $p_fec_has . "', 'DD/MM/YYYY')), -12) THEN
                                          V.VENTA_NETA_GS
                                         ELSE
                                          0
                                       END)) VENTAS_ANIO_PAST
                       FROM VMATRIZ_VENTA_C_MAT V
                       JOIN REL_MARCA_OP_GP M
                         ON V.COD_MARCA = M.COD_MARCA_OP
                       JOIN REL_RUBRO_OP_GP R
                         ON V.COD_RUBRO = R.COD_RUBRO_OP
                      WHERE M.COD_MARCA = " . trim($cod_marca) . "
                        AND V.COD_VENDEDOR IN (SELECT DISTINCT VA.COD_VENDEDOR
                                               FROM EDS_VENDEDORES@GUATA VA
                                               WHERE VA.COD_EMPRESA = '2')
                      GROUP BY R.COD_RUBRO, R.DESCRIPCION
                     UNION ALL
                     /*GP*/
                     SELECT R.COD_RUBRO,
                            R.DESCRIPCION RUBRO,
                            ROUND(SUM(CASE
                                        WHEN C.FEC_COMPROBANTE BETWEEN
                                             TRUNC(TO_DATE('" . $p_fec_des . "', 'DD/MM/YYYY')) AND
                                             TRUNC(TO_DATE('" . $p_fec_has . "', 'DD/MM/YYYY')) THEN
                                         C.TOTAL_GS
                                        ELSE
                                         0
                                      END)) VENTAS,
                            ROUND(SUM(CASE
                                        WHEN C.FEC_COMPROBANTE BETWEEN
                                             ADD_MONTHS((TRUNC(TO_DATE('" . $p_fec_des . "', 'DD/MM/YYYY'))),
                                                        -12) AND
                                             ADD_MONTHS(TRUNC(TO_DATE('" . $p_fec_has . "', 'DD/MM/YYYY')), -12) THEN
                                         C.TOTAL_GS
                                        ELSE
                                         0
                                      END)) VENTAS_ANIO_PAST
                       FROM REL_MARCA_OP_GP M
                       JOIN VVT_VTA_TOTAL_MAT@GUATA C
                         ON C.COD_MARCA = M.COD_MARCA_GP
                       JOIN REL_RUBRO_OP_GP R
                         ON C.COD_RUBRO = R.COD_RUBRO_GP
                      WHERE M.COD_MARCA = " . trim($cod_marca) . "
                        AND C.COD_VENDEDOR IN (SELECT DISTINCT VA.COD_VENDEDOR
                                               FROM EDS_VENDEDORES@GUATA VA
                                               WHERE VA.COD_EMPRESA = '1')
                      GROUP BY R.COD_RUBRO, R.DESCRIPCION)
              GROUP BY RUBRO, COD_RUBRO
             HAVING SUM(VENTAS) != 0 OR SUM(VENTAS_ANIO_PAST) != 0
             ORDER BY 3 DESC";
        //print $consulta;
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function getDetalleVentasVendedor($p_fec_des, $p_fec_has, $cod_marca, $cod_rubro) {
        $consulta = "SELECT COD_VENDEDOR,
                    NOM_VENDEDOR,
                    RUBRO,
                    COD_ZONA,
                    SUM(VENTAS_NETA) VENTAS_NETA,
                    SUM(CANTIDAD) CANTIDAD,
                    SUM(DEVOLUCION) DEVOLUCION,
                    SUM(VENTAS_BRUTA) VENTAS_BRUTA
               FROM (
                     /*OPTIMA*/
                     SELECT VA.COD_VENDEDOR,
                             VA.NOM_PERSONA NOM_VENDEDOR,
                             R.DESCRIPCION RUBRO,
                             V.COD_ZONA,
                             ROUND(SUM(DECODE(V.TIPO_COMPROBANTE, 'NCR', 0, V.VENTA_NETA_GS))) +
                             ROUND(SUM(DECODE(V.TIPO_COMPROBANTE, 'NCR', V.VENTA_NETA_GS, 0))) VENTAS_NETA,
                             ROUND(SUM(V.CANTIDAD)) CANTIDAD,
                             ROUND(SUM(DECODE(V.TIPO_COMPROBANTE, 'NCR', V.VENTA_NETA_GS, 0))) DEVOLUCION,
                             ROUND(SUM(DECODE(V.TIPO_COMPROBANTE, 'NCR', 0, V.VENTA_NETA_GS))) VENTAS_BRUTA
                       FROM VMATRIZ_VENTA_C_MAT V
                       JOIN REL_MARCA_OP_GP M
                         ON V.COD_MARCA = M.COD_MARCA_OP
                       JOIN REL_RUBRO_OP_GP R
                         ON V.COD_RUBRO = R.COD_RUBRO_OP
                       JOIN EDS_VENDEDORES@GUATA VA
                         ON V.COD_VENDEDOR = VA.COD_VENDEDOR
                        AND VA.COD_EMPRESA = '2'
                      WHERE M.COD_MARCA = '" . trim($cod_marca) . "'
                        AND R.COD_RUBRO = '" . trim($cod_rubro) . "'
                        AND V.FEC_ALTA BETWEEN TRUNC(TO_DATE('" . $p_fec_des . "', 'DD/MM/YYYY')) AND
                            TRUNC(TO_DATE('" . $p_fec_has . "', 'DD/MM/YYYY'))
                      GROUP BY VA.COD_VENDEDOR, VA.NOM_PERSONA, R.DESCRIPCION, V.COD_ZONA

                     UNION ALL 
                     /*GUATA*/
                     SELECT VA.COD_VENDEDOR,
                            VA.NOM_PERSONA NOM_VENDEDOR,
                            R.DESCRIPCION RUBRO,
                            C.ZONA,
                            ROUND(SUM(DECODE(S.SALDOS, 'R', 0, C.TOTAL_GS))) +
                            ROUND(SUM(DECODE(S.SALDOS, 'R', C.TOTAL_GS, 0))) VENTAS_NETA,
                            ROUND(SUM(C.CANTIDAD)) CANTIDAD,
                            ROUND(SUM(DECODE(S.SALDOS, 'R', C.TOTAL_GS, 0))) DEVOLUCION,
                            ROUND(SUM(DECODE(S.SALDOS, 'R', 0, C.TOTAL_GS))) VENTAS_BRUTA
                       FROM REL_MARCA_OP_GP M
                       JOIN VVT_VTA_TOTAL_MAT@GUATA C
                         ON C.COD_MARCA = M.COD_MARCA_GP
                       JOIN REL_RUBRO_OP_GP R
                         ON C.COD_RUBRO = R.COD_RUBRO_GP
                       JOIN TIPOS_COMPROBANTES@GUATA S
                         ON C.TIP_COMPROBANTE = S.TIP_COMPROBANTE
                        AND S.COD_MODULO = 'VT'
                        AND S.COD_EMPRESA = '1'
                       JOIN EDS_VENDEDORES@GUATA VA
                         ON C.COD_VENDEDOR = VA.COD_VENDEDOR
                        AND VA.COD_EMPRESA = '1'

                      WHERE M.COD_MARCA = '" . trim($cod_marca) . "'
                        AND R.COD_RUBRO = '" . trim($cod_rubro) . "'
                        AND C.FEC_COMPROBANTE BETWEEN
                            TRUNC(TO_DATE('" . $p_fec_des . "', 'DD/MM/YYYY')) AND
                            TRUNC(TO_DATE('" . $p_fec_has . "', 'DD/MM/YYYY'))

                      GROUP BY VA.COD_VENDEDOR, VA.NOM_PERSONA, R.DESCRIPCION, C.ZONA)
              GROUP BY COD_VENDEDOR,
                       NOM_VENDEDOR,
                       RUBRO,
                       COD_ZONA
              ORDER BY 3 DESC";
        //print $consulta;
        try {
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->query($consulta);
            // Ejecutar sentencia preparada
            //$comando->execute(array($nombre));
            $comando->execute();
            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            return false;
        }
    }

}

?>