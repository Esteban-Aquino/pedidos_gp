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
                    U.FEC_VENC_HASH = SYSDATE + (1/24*12)
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

    public static function buscaParametro($cod_modulo, $cod_parametro) {
        try {

            $sql = "begin :res := bs_busca_parametro(:cod_modulo, :cod_parametro); end;";

            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(':res', $result, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
            $comando->bindParam(':cod_modulo', $cod_modulo, PDO::PARAM_STR);
            $comando->bindParam(':cod_parametro', $cod_parametro, PDO::PARAM_STR);
            $comando->execute();

            return $result;
        } catch (PDOException $e) {
            return $e->getMessage();
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
                                D.COD_CIUDAD,
                                CI.DESCRIPCION CIUDAD,
                                B.COD_BARRIO,
                                B.DESCRIPCION BARRIO,
                                nvl(T.codigo_area, ' ') || ' ' || T.num_telefono TEL_CLIENTE,
                                NVL((SELECT I.NUMERO
                                      FROM IDENT_PERSONAS I
                                     WHERE CC.COD_PERSONA = I.COD_PERSONA
                                       AND I.COD_IDENT = 'RUC'
                                       AND ROWNUM = 1),
                                    (SELECT I.NUMERO
                                       FROM IDENT_PERSONAS I
                                      WHERE CC.COD_PERSONA = I.COD_PERSONA
                                        AND I.COD_IDENT = 'CI'
                                        AND ROWNUM = 1)) RUC,
                                CC.COD_CONDICION_VENTA,
                                NVL(CC.COD_MONEDA_LIMITE, '1') COD_MONEDA,
                                NVL(CC.TIP_DOCUMENTO,
                                    NVL((SELECT C.COD_FORMA_PAGO
                                          FROM cc_plazo_documento C
                                          JOIN CC_FORMAS_PAGO F
                                            ON C.COD_FORMA_PAGO = F.COD_FORMA_PAGO
                                         WHERE C.COD_CONDICION_VENTA = CC.COD_CONDICION_VENTA
                                           AND ROWNUM = 1),
                                        bs_busca_parametro('CC', 'WEB_PLAZO_DOC'))) TIP_DOCUMENTO
                           from ident_personas a
                           join cc_clientes cc
                             on cc.cod_persona = a.cod_persona
                           join personas p
                             on cc.cod_persona = p.cod_persona
                           left join direc_personas d
                             on p.cod_persona = d.cod_persona
                           LEFT JOIN TELEF_PERSONAS T
                             ON P.COD_PERSONA = T.COD_PERSONA
                           LEFT JOIN CIUDADES CI
                           ON D.COD_CIUDAD = CI.COD_CIUDAD
                           LEFT JOIN BARRIOS B
                           ON D.COD_BARRIO = B.COD_BARRIO
                           AND D.COD_CIUDAD = B.COD_CIUDAD
                          where (a.numero = :DOCUMENTO OR
                                (CC.COD_CLIENTE = :COD_CLIENTE AND :DOCUMENTO1 IS NULL))
                            and rownum = 1
                            and nvl(cc.estado, 'X') <> 'I'
";
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
     * Valida Trae datos de cliente
     *
     * @param Documento y codigo de cliente
     * @return Datos de cliente
     */
    public static function ListarCliente($busqueda, $pag) {
        $cant = 10;
        try {
            $conn = null;
            $consulta = "select dsa.COD_CLIENTE,
                        dsa.COD_PERSONA,
                        dsa.DESC_CLIENTE,
                        dsa.DESC_DIRECCION,
                        dsa.TEL_CLIENTE,
                        dsa.RUC,
                        dsa.COD_CONDICION_VENTA,
                        dsa.COD_MONEDA,
                        dsa.TIP_DOCUMENTO
                   from (select asd.*, rownum r
                           from (select cc.COD_CLIENTE,
                                        CC.COD_PERSONA,
                                        initcap(p.nombre) DESC_CLIENTE,
                                        nvl(trim(bsf_direcciones(p.cod_persona)),
                                            'Sin Definir') DESC_DIRECCION,
                                        nvl(trim(bsf_telefonos(p.cod_persona)), 'Sin Definir') TEL_CLIENTE,
                                        NVL((SELECT I.NUMERO
                                              FROM IDENT_PERSONAS I
                                             WHERE CC.COD_PERSONA = I.COD_PERSONA
                                               AND I.COD_IDENT = 'RUC'
                                               AND ROWNUM = 1),
                                            (SELECT I.NUMERO
                                               FROM IDENT_PERSONAS I
                                              WHERE CC.COD_PERSONA = I.COD_PERSONA
                                                AND I.COD_IDENT = 'CI'
                                                AND ROWNUM = 1)) RUC,
                                        CC.COD_CONDICION_VENTA,
                                        NVL(CC.COD_MONEDA_LIMITE, '1') COD_MONEDA,
                                        NVL(CC.TIP_DOCUMENTO,
                                            NVL((SELECT C.COD_FORMA_PAGO
                                                  FROM cc_plazo_documento C
                                                  JOIN CC_FORMAS_PAGO F
                                                    ON C.COD_FORMA_PAGO = F.COD_FORMA_PAGO
                                                 WHERE C.COD_CONDICION_VENTA =
                                                       CC.COD_CONDICION_VENTA
                                                   AND ROWNUM = 1),
                                                bs_busca_parametro('CC', 'WEB_PLAZO_DOC'))) TIP_DOCUMENTO
                                   from cc_clientes cc
                                   join personas p
                                     on cc.cod_persona = p.cod_persona
                                  where (CC.COD_CLIENTE like
                                        upper('%' || trim(:busqueda1) || '%') OR
                                        P.NOMBRE like upper('%' || trim(:busqueda2) || '%') OR
                                        NVL((SELECT I.NUMERO
                                               FROM IDENT_PERSONAS I
                                              WHERE CC.COD_PERSONA = I.COD_PERSONA
                                                AND I.COD_IDENT = 'RUC'
                                                AND ROWNUM = 1),
                                             (SELECT I.NUMERO
                                                FROM IDENT_PERSONAS I
                                               WHERE CC.COD_PERSONA = I.COD_PERSONA
                                                 AND I.COD_IDENT = 'CI'
                                                 AND ROWNUM = 1)) like
                                        upper('%' || trim(:busqueda3) || '%'))
                                    and nvl(cc.estado, 'X') <> 'I'
                                    and rownum <= ({$pag} * {$cant})

                                 ) asd) dsa
                  where r >= ({$pag}  + ({$pag}  * {$cant}) - ({$cant} + {$pag} ) + 1)";
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':busqueda1' => $busqueda,
                ':busqueda2' => $busqueda,
                ':busqueda3' => $busqueda]);

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
     * Trae datos de monedas
     *
     * @param $pag Pagina actual, $busqueda Texto buscado
     * @return Lista de condiciones de venta
     */
    public static function DatosMonedas($cod_moneda) {
        try {
            $cant = 10;
            $consulta = "select descripcion, cod_moneda, round(tipo_cambio_dia) tip_cambio, nvl( decimales, 0 ) decimales, siglas
                         from monedas
                         where cod_moneda in ('1','2')
                         and cod_moneda = :cod_moneda";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':cod_moneda' => $cod_moneda]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Trae datos de monedas
     *
     * @param $pag Pagina actual, $busqueda Texto buscado
     * @return Lista de condiciones de venta
     */
    public static function ListarMonedas($cod_moneda, $busqueda, $pag) {
        try {
            $cant = 10;
            //print_r($pag);
            $consulta = "select dsa.descripcion,
                        dsa.cod_moneda,
                        round(dsa.tipo_cambio_dia) tip_cambio,
                        dsa.decimales,
                        dsa.siglas
                   from (select asd.*, rownum r
                           from (select descripcion,
                                        cod_moneda,
                                        tipo_cambio_dia,
                                        nvl(decimales, 0) decimales,
                                        siglas
                                   from monedas
                                  where (instr(upper(descripcion), upper(:busqueda)) > 0 or
                                        instr(upper(cod_moneda), upper(:busqueda1)) > 0
                                        or :busqueda2 is null)
                                    and cod_moneda in ('1', '2')) asd) dsa
                  where r between ({$pag} + ({$pag} * {$cant}) - ({$cant} + {$pag}) + 1) and
                        ({$pag} + ({$pag}  * {$cant}) - ({$cant} + {$pag} ) + {$cant})";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':busqueda' => $busqueda,
                ':busqueda1' => $busqueda,
                ':busqueda2' => $busqueda]);

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
    public static function DatosTiposDocumentos($COD_CONDICION_VENTA, $COD_FORMA_PAGO) {
        try {
            $cant = 10;
            $consulta = "SELECT C.COD_FORMA_PAGO, F.DESCRIPCION DESC_FORMA_PAGO
                         FROM cc_plazo_documento C
                         JOIN CC_FORMAS_PAGO F
                           ON C.COD_FORMA_PAGO = F.COD_FORMA_PAGO
                         WHERE C.COD_CONDICION_VENTA = :COD_CONDICION_VENTA
                         AND F.COD_FORMA_PAGO = :COD_FORMA_PAGO";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_CONDICION_VENTA' => $COD_CONDICION_VENTA,
                ':COD_FORMA_PAGO' => $COD_FORMA_PAGO]);

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
    public static function ListarTiposDocumentos($COD_CONDICION_VENTA, $busqueda, $pag) {
        try {
            $cant = 10;
            //print_r('ASD');
            //print_r($busqueda);
            $consulta = "select dsa.DESC_FORMA_PAGO, dsa.COD_FORMA_PAGO
                        from (select asd.*, rownum r
                              from (

                                    SELECT C.COD_FORMA_PAGO, F.DESCRIPCION DESC_FORMA_PAGO
                                      FROM cc_plazo_documento C
                                      JOIN CC_FORMAS_PAGO F
                                        ON C.COD_FORMA_PAGO = F.COD_FORMA_PAGO
                                     WHERE  C.COD_CONDICION_VENTA = :COD_CONDICION_VENTA
                                      AND (upper(F.DESCRIPCION) like upper('%'||'{$busqueda}'||'%') or
                                          upper(F.COD_FORMA_PAGO) like upper('%'||'{$busqueda}'||'%'))
                                    ) asd
                             ) dsa
                         where r between ({$pag} + ({$pag} * {$cant}) - ({$cant} + {$pag}) + 1) and
                               ({$pag} + ({$pag} * {$cant}) - ({$cant} + {$pag}) + {$cant})";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_CONDICION_VENTA' => $COD_CONDICION_VENTA,
            /* ':busqueda' => $busqueda,
              ':busqueda1' => $busqueda */            ]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //PRINT_R($result);
            return utf8_converter($result);
        } catch (PDOException $e) {
            print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Valida lista direcciones
     *
     * @param Documento y codigo de cliente
     * @return Datos de cliente
     */
    public static function DatosDireccion($COD_CLIENTE, $COD_DIRECCION) {
        $cant = 10;
        try {
            $conn = null;
            $consulta = "SELECT D.DETALLE DESC_DIRECCION, D.COD_DIRECCION, I.DESCRIPCION CIUDAD
                        FROM CC_CLIENTES C
                             JOIN DIREC_PERSONAS D
                             ON C.COD_PERSONA = D.COD_PERSONA
                             LEFT JOIN CIUDADES I
                             ON D.COD_CIUDAD = I.COD_CIUDAD
                        WHERE C.COD_EMPRESA = '1'
                        AND C.COD_CLIENTE = :COD_CLIENTE    
                        AND D.COD_DIRECCION = :COD_DIRECCION";
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_CLIENTE' => $COD_CLIENTE,
                ':COD_DIRECCION' => $COD_DIRECCION]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Valida lista direcciones
     *
     * @param Documento y codigo de cliente
     * @return Datos de cliente
     */
    public static function ListarDirecciones($COD_CLIENTE, $busqueda, $pag) {
        $cant = 10;
        try {
            $conn = null;
            $consulta = "select dsa.DESC_DIRECCION, 
                                DSA.COD_CIUDAD,
                                dsa.CIUDAD,
                                DSA.COD_BARRIO,
                                DSA.BARRIO,
                                dsa.COD_DIRECCION
                           from (select asd.*, rownum r
                                   from (SELECT D.DETALLE       DESC_DIRECCION,
                                                D.COD_DIRECCION,
                                                I.COD_CIUDAD,
                                                I.DESCRIPCION   CIUDAD,
                                                B.COD_BARRIO,
                                                B.DESCRIPCION BARRIO
                                   FROM CC_CLIENTES C
                                    JOIN DIREC_PERSONAS D
                                      ON C.COD_PERSONA = D.COD_PERSONA
                                    LEFT JOIN CIUDADES I
                                      ON D.COD_CIUDAD = I.COD_CIUDAD
                                    LEFT JOIN BARRIOS B
                                      ON D.COD_BARRIO = B.COD_BARRIO
                                      AND D.COD_CIUDAD = B.COD_CIUDAD
                                  WHERE C.COD_CLIENTE = :COD_CLIENTE
                                    AND (D.DETALLE LIKE UPPER('%'||:busqueda||'%')
                                        or I.DESCRIPCION LIKE UPPER('%'||:busqueda1||'%')
                                        or D.COD_DIRECCION LIKE UPPER('%'||:busqueda2||'%'))
                                    and rownum <= ({$pag} * {$cant})
                                    and cod_empresa = '1'
                                     
                                 ) asd) dsa
                  where r >= ({$pag}  + ({$pag} * {$cant}) - ({$cant} + {$pag} ) + 1)";
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_CLIENTE' => $COD_CLIENTE,
                ':busqueda' => $busqueda,
                ':busqueda1' => $busqueda,
                ':busqueda2' => $busqueda]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Listar articulo
     *
     * @param Documento y codigo de cliente
     * @return Datos de cliente
     */
    public static function ListarArticulos($busqueda, $pag) {
        $cant = 20;
        $cod_sucursal = operacionesDB::buscaParametro('VT', 'COD_SUCURSAL_WEB');
        $cod_lista = operacionesDB::buscaParametro('VT', 'LISTA_PRECIO_WEB');
        try {
            /*
              A.COD_RUBRO != '12'
              AND A.COD_ARTICULO NOT LIKE 'BOL0%'
              AND
             */
            $conn = null;
            $consulta = "select dsa.COD_ARTICULO, 
                                dsa.DESCRIPCION, 
                                dsa.COD_UNIDAD_MEDIDA,
                                dsa.CANTIDAD,
                                dsa.CANTIDAD_UB
                           from (select asd.*, rownum r
                                   from (SELECT A.COD_ARTICULO,
                                                A.DESCRIPCION,
                                                R.COD_UNIDAD_REL COD_UNIDAD_MEDIDA,
                                                (E.EXISTENCIA * R.DIV / R.MULT) CANTIDAD,
                                                (E.EXISTENCIA) CANTIDAD_UB
                                           FROM ST_ARTICULOS A
                                           JOIN (SELECT COD_EMPRESA,
                                                       COD_ARTICULO,
                                                       SUM((CANTIDAD - CASE
                                                             WHEN RESERVADO < 0 THEN
                                                              0
                                                             ELSE
                                                              RESERVADO
                                                           END)) EXISTENCIA
                                                  FROM ST_EXISTENCIA_RES
                                                 WHERE COD_SUCURSAL = {$cod_sucursal}
                                                 GROUP BY COD_ARTICULO, COD_EMPRESA
                                                HAVING SUM((CANTIDAD -CASE
                                                  WHEN RESERVADO < 0 THEN
                                                   0
                                                  ELSE
                                                   RESERVADO
                                                END)) > 0) E
                                             ON A.COD_ARTICULO = E.COD_ARTICULO
                                            AND A.COD_EMPRESA = E.COD_EMPRESA
                                           LEFT JOIN ST_RELACIONES R
                                             ON A.COD_RELACION_UM = R.COD_RELACION_UM
                                            AND r.por_defecto = 'S'
                                          WHERE (EXISTS (SELECT DISTINCT 'S'
                                                        FROM VT_PRECIOS_FIJOS F
                                                        WHERE A.COD_ARTICULO = F.COD_ARTICULO
                                                        AND F.COD_PRECIO_FIJO = {$cod_lista}
                                                        )
                                                 OR EXISTS (select DISTINCT 'S'
                                                            from vt_ofertas_cab c, vt_ofertas_det d
                                                           where c.cod_empresa = A.COD_EMPRESA
                                                             and c.cod_empresa = d.cod_empresa
                                                             and c.cod_oferta = d.cod_oferta
                                                             and SYSDATE between c.fec_vigencia_ini and
                                                                 nvl(prorroga, fec_vigencia_fin)
                                                             and d.cod_articulo = A.COD_ARTICULO
                                                             and nvl(c.autorizado, 'N') = 'S'))
                                            AND (A.COD_ARTICULO like '%' || upper(:busqueda) || '%' OR
                                                A.DESCRIPCION like '%' || upper(:busqueda1) || '%')

                                            and rownum <= ({$pag}  * {$cant})

                                            ) asd) dsa
                          where r >= ({$pag}  + ({$pag}  * {$cant}) - ({$cant} + {$pag} ) + 1)";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);
            //print_r($busqueda);
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
    
    public static function DatosArticulo($COD_ARTICULO) {
        $cod_sucursal = operacionesDB::buscaParametro('VT', 'COD_SUCURSAL_WEB');
        $cod_lista = operacionesDB::buscaParametro('VT', 'LISTA_PRECIO_WEB');
        try {
            $conn = null;
            $consulta = "SELECT A.COD_ARTICULO,
                                A.DESCRIPCION,
                                R.COD_UNIDAD_REL COD_UNIDAD_MEDIDA,
                                SUM((E.EXISTENCIA * R.DIV / R.MULT)) CANTIDAD,
                                SUM(E.EXISTENCIA) CANTIDAD_UB
                           FROM ST_ARTICULOS A
                           JOIN (SELECT COD_EMPRESA,
                                        COD_ARTICULO,
                                        COD_COLOR,
                                        SUM((CANTIDAD - CASE
                                              WHEN RESERVADO < 0 THEN
                                               0
                                              ELSE
                                               RESERVADO
                                            END)) EXISTENCIA
                                   FROM ST_EXISTENCIA_RES
                                  WHERE COD_SUCURSAL = {$cod_sucursal}
                                  GROUP BY COD_ARTICULO, COD_EMPRESA, COD_COLOR
                                 HAVING SUM((CANTIDAD -CASE
                                   WHEN RESERVADO < 0 THEN
                                    0
                                   ELSE
                                    RESERVADO
                                 END)) > 0) E
                             ON A.COD_ARTICULO = E.COD_ARTICULO
                            AND A.COD_EMPRESA = E.COD_EMPRESA
                           LEFT JOIN ST_RELACIONES R
                             ON A.COD_RELACION_UM = R.COD_RELACION_UM
                            AND r.por_defecto = 'S'
                          WHERE (EXISTS (SELECT DISTINCT 'S'
                                           FROM VT_PRECIOS_FIJOS F
                                          WHERE A.COD_ARTICULO = F.COD_ARTICULO
                                            AND F.COD_PRECIO_FIJO = {$cod_lista}
                                         ) OR EXISTS
                                 (select DISTINCT 'S'
                                    from vt_ofertas_cab c, vt_ofertas_det d
                                   where c.cod_empresa = A.COD_EMPRESA
                                     and c.cod_empresa = d.cod_empresa
                                     and c.cod_oferta = d.cod_oferta
                                     and SYSDATE between c.fec_vigencia_ini and
                                         nvl(prorroga, fec_vigencia_fin)
                                     and d.cod_articulo = A.COD_ARTICULO
                                     and nvl(c.autorizado, 'N') = 'S'
                                     and (nvl(d.cod_color, 'XX') = NVL(E.COD_COLOR, 'ZZ') OR
                                         D.COD_COLOR IS NULL)))
                            AND A.COD_ARTICULO = :COD_ARTICULO
                         GROUP BY A.COD_ARTICULO,
                                A.DESCRIPCION,
                                R.COD_UNIDAD_REL
                         ";
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_ARTICULO' => $COD_ARTICULO]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Listar articulo
     *
     * @param Documento y codigo de cliente
     * @return Datos de cliente
     */
    public static function ListarColoresTallas($busqueda, $pag, $cod_articulo) {
        $cant = 10;
        try {
            $conn = null;
            $consulta = "select DSA.COD_ARTICULO,
                                DSA.DESCRIPCION,
                                DSA.COD_COLOR,
                                DSA.DESC_COLOR,
                                DSA.COD_TALLA,
                                DSA.EXISTENCIA
                           from (select asd.*, rownum r
                                   from (SELECT A.COD_ARTICULO,
                                                A.DESCRIPCION,
                                                E.COD_COLOR,
                                                NVL(MC.DESC_INTERNO, C.DESCRIPCION) DESC_COLOR,
                                                E.COD_TALLA,
                                                (E.CANTIDAD - CASE
                                                  WHEN E.RESERVADO < 0 THEN
                                                   0
                                                  ELSE
                                                   E.RESERVADO
                                                END) EXISTENCIA
                                           FROM ST_ARTICULOS A
                                           JOIN ST_EXISTENCIA_RES E
                                             ON A.COD_ARTICULO = E.COD_ARTICULO
                                            AND A.COD_EMPRESA = E.COD_EMPRESA
                                            AND E.COD_SUCURSAL = bs_busca_parametro('VT','COD_SUCURSAL_WEB')
                                           LEFT JOIN ST_MARCA_COLOR MC
                                             ON A.COD_MARCA = MC.COD_MARCA
                                            AND A.COD_ARTICULO = MC.COD_ARTICULO
                                            AND E.COD_COLOR = MC.COD_ORIGEN
                                           LEFT JOIN COLORES C
                                             ON C.COD_EMPRESA = E.COD_EMPRESA
                                            AND C.COD_COLOR = E.COD_COLOR
                                          WHERE A.COD_ARTICULO = :COD_ARTICULO
                                            AND (E.COD_COLOR like upper('%' || trim(:busqueda) || '%') OR
                                                 NVL(MC.DESC_INTERNO, C.DESCRIPCION) like upper('%' || trim(:busqueda1) || '%') OR
                                                 E.COD_TALLA like upper('%' || trim(:busqueda2) || '%'))
                                            AND (E.CANTIDAD - CASE
                                                  WHEN E.RESERVADO < 0 THEN
                                                   0
                                                  ELSE
                                                   E.RESERVADO
                                                END) > 0

                                            and rownum <= ({$pag} * {$cant})) asd) dsa
                          where r >= ({$pag}+ ({$pag} * {$cant}) - ({$cant} + {$pag}) + 1)";
            //print_r($consulta);     
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_ARTICULO' => $cod_articulo,
                ':busqueda' => $busqueda,
                ':busqueda1' => $busqueda,
                ':busqueda2' => $busqueda]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Listar articulo
     *
     * @param Documento y codigo de cliente
     * @return Datos de cliente
     */
    public static function consultaExistencia($cod_articulo, $cod_color, $cod_talla) {
        $cod_sucursal = operacionesDB::buscaParametro('VT', 'COD_SUCURSAL_WEB');
        $cod_lista = operacionesDB::buscaParametro('VT', 'LISTA_PRECIO_WEB');
        //print_r( $cod_articulo.' '.$cod_color.' '.$cod_talla);
        try {
            $conn = null;
            $consulta = "SELECT E.COD_SUCURSAL,
                                REPLACE(S.DESCRIPCION,' ','&nbsp')    DESC_SUCURSAL,
                                A.COD_ARTICULO,
                                A.DESCRIPCION,
                                E.COD_COLOR,
                                NVL(MC.DESC_INTERNO,COL.DESCRIPCION) DESC_COLOR,
                                E.COD_TALLA,
                                R.COD_UNIDAD_REL COD_UNIDAD_MEDIDA,
                                SUM((E.EXISTENCIA * R.DIV / R.MULT)) CANTIDAD,
                                SUM(E.EXISTENCIA) CANTIDAD_UB
                           FROM ST_ARTICULOS A
                           JOIN (SELECT COD_EMPRESA,
                                        COD_SUCURSAL,
                                        COD_ARTICULO,
                                        COD_COLOR,
                                        COD_TALLA,
                                        SUM((CANTIDAD - CASE
                                              WHEN RESERVADO < 0 THEN
                                               0
                                              ELSE
                                               RESERVADO
                                            END)) EXISTENCIA
                                   FROM ST_EXISTENCIA_RES
                                  WHERE COD_SUCURSAL = '{$cod_sucursal}'
                                  GROUP BY COD_ARTICULO,
                                           COD_EMPRESA,
                                           COD_COLOR,
                                           COD_TALLA,
                                           COD_SUCURSAL
                                 HAVING SUM((CANTIDAD -CASE
                                   WHEN RESERVADO < 0 THEN
                                    0
                                   ELSE
                                    RESERVADO
                                 END)) > 0) E
                             ON A.COD_ARTICULO = E.COD_ARTICULO
                            AND A.COD_EMPRESA = E.COD_EMPRESA
                           LEFT JOIN ST_RELACIONES R
                             ON A.COD_RELACION_UM = R.COD_RELACION_UM
                            AND r.por_defecto = 'S'
                           JOIN SUCURSALES S
                             ON S.COD_SUCURSAL = E.COD_SUCURSAL
                           LEFT JOIN st_marca_color MC
                             ON MC.COD_EMPRESA = A.COD_EMPRESA
                             AND MC.COD_ARTICULO = A.COD_ARTICULO
                             AND MC.COD_ORIGEN = E.COD_COLOR
                           LEFT JOIN COLORES COL
                           ON COL.COD_EMPRESA = A.COD_EMPRESA
                           AND COL.COD_COLOR = E.COD_COLOR
                          WHERE (EXISTS (SELECT DISTINCT 'S'
                                           FROM VT_PRECIOS_FIJOS F
                                          WHERE A.COD_ARTICULO = F.COD_ARTICULO
                                            AND F.COD_PRECIO_FIJO = '{$cod_lista}') OR EXISTS
                                 (select DISTINCT 'S'
                                    from vt_ofertas_cab c, vt_ofertas_det d
                                   where c.cod_empresa = A.COD_EMPRESA
                                     and c.cod_empresa = d.cod_empresa
                                     and c.cod_oferta = d.cod_oferta
                                     and SYSDATE between c.fec_vigencia_ini and
                                         nvl(prorroga, fec_vigencia_fin)
                                     and d.cod_articulo = A.COD_ARTICULO
                                     and nvl(c.autorizado, 'N') = 'S'
                                     and (nvl(d.cod_color, 'XX') = NVL(E.COD_COLOR, 'ZZ') OR
                                         D.COD_COLOR IS NULL)))
                            AND A.COD_ARTICULO = :COD_ARTICULO
                            AND (E.COD_COLOR = :COD_COLOR or :COD_COLOR1 = 'N')
                            AND (E.COD_TALLA = :COD_TALLA OR :COD_TALLA1 = 'N')
                          GROUP BY A.COD_ARTICULO,
                                   A.DESCRIPCION,
                                   R.COD_UNIDAD_REL,
                                   E.COD_COLOR,
                                   E.COD_TALLA,
                                   E.COD_SUCURSAL,
                                   S.DESCRIPCION,
                                   NVL(MC.DESC_INTERNO,COL.DESCRIPCION)
                         ";
            //print_r($consulta);     
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_ARTICULO' => $cod_articulo,
                               ':COD_COLOR' => $cod_color,
                               ':COD_COLOR1' => $cod_color,
                               ':COD_TALLA' => $cod_talla,
                               ':COD_TALLA1' => $cod_talla]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            print_r($e->getMessage());
            return false;
        }
    }

    public static function consultaPrecio($cod_articulo,$cod_color ,$cod_unidad_medida,$omitir_oferta) {
        $cant = 10;
        try {
            $conn = null;
            $consulta = "SELECT TRAE_PRECIO('1',
                                :COD_ARTICULO,
                                bs_busca_parametro('VT', 'LISTA_PRECIO_WEB'),
                                bs_busca_parametro('VT', 'COD_SUCURSAL_WEB'),
                                :COD_COLOR,
                                :COD_UNIDAD_MEDIDA,
                                :OMITIR_OFERTA) PRECIO,
                    TIENE_OFERTA('1',
                                 :COD_ARTICULO1,
                                 bs_busca_parametro('VT', 'LISTA_PRECIO_WEB'),
                                 bs_busca_parametro('VT', 'COD_SUCURSAL_WEB'),
                                 :COD_COLOR1,
                                 NULL,
                                 :OMITIR_OFERTA1) OFERTA
                    FROM DUAL";
            //print_r($consulta);
            //print_r($cod_articulo);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_ARTICULO' => $cod_articulo,
                ':COD_COLOR' => $cod_color,
                ':COD_UNIDAD_MEDIDA' => $cod_unidad_medida,
                ':OMITIR_OFERTA' => $omitir_oferta,
                ':COD_ARTICULO1' => $cod_articulo,
                ':COD_COLOR1' => $cod_color,
                ':OMITIR_OFERTA1' => $omitir_oferta]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    public static function datosOferta($cod_oferta) {
        $cant = 10;
        try {
            $conn = null;
            $consulta = "SELECT C.FECHA_ALTA, 
                                C.FEC_VIGENCIA_INI, 
                                C.FEC_VIGENCIA_FIN,
                                C.DESCRIPCION DESC_OFERTA,
                                C.COD_SUCURSAL,
                                S.DESCRIPCION DESC_SUCURSAL
                         FROM VT_OFERTAS_CAB C
                              LEFT JOIN SUCURSALES S
                              ON C.COD_SUCURSAL = S.COD_SUCURSAL
                              AND C.COD_EMPRESA = S.COD_EMPRESA
                         WHERE C.COD_EMPRESA = '1'
                         AND C.COD_OFERTA = :COD_OFERTA";
            //print_r($consulta);   
            //print_r($cod_articulo);    
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_OFERTA' => $cod_oferta]);

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
    public static function DatosMetodosPagos($COD_METODO) {
        try {
            $cant = 10;
            $consulta = "SELECT E.COD_METODO,
                                E.DESCRIPCION DESC_METODO,
                                E.TIPO_PAGO
                         FROM WEB_APP_METODO_PAGO E
                         WHERE E.COD_METODO = :COD_METODO";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_METODO' => $COD_METODO]);

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
    public static function ListarMetodosPagos($TIPO_PAGO, $busqueda, $pag) {
        try {
            $cant = 10;
            //print_r('ASD');
            //print_r($busqueda);
            $consulta = "select DSA.COD_METODO,
                                DSA.DESC_METODO,
                                DSA.TIPO_PAGO
                           from (select asd.*, rownum r
                                   from (SELECT E.COD_METODO, E.DESCRIPCION DESC_METODO, E.TIPO_PAGO
                                           FROM WEB_APP_METODO_PAGO E
                                          WHERE E.TIPO_PAGO = :TIPO_PAGO
                                            AND (E.COD_METODO like
                                                upper('%' || trim(:busqueda) || '%') OR
                                                E.DESCRIPCION like
                                                upper('%' || trim(:busqueda1) || '%'))

                                            and rownum <= ({$pag} * {$cant} )) asd) dsa
                          where r >= ({$pag} + ({$pag} * {$cant} ) - ({$cant}  + {$pag} ) + 1)";

            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':TIPO_PAGO' => $TIPO_PAGO,
                ':busqueda' => $busqueda,
                ':busqueda1' => $busqueda]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //PRINT_R($result);
            return utf8_converter($result);
        } catch (PDOException $e) {
            print_r($e->getMessage());
            return false;
        }
    }

    /**
     * Trae datos de la condicion de venta
     *
     * @param $pag Pagina actual, $busqueda Texto buscado
     * @return Lista de condiciones de venta
     */
    public static function ListarDelivery($busqueda, $pag) {
        try {
            $cant = 10;
            //print_r('ASD');
            //print_r($busqueda);
            $consulta = "select DSA.COD_DELIVERY,
                                DSA.DESCRIPCION,
                                DSA.MONTO
                           from (select asd.*, rownum r
                                   from (SELECT E.Cod_Delivery, E.DESCRIPCION, E.MONTO
                                           FROM WEB_APP_DELIVERY E
                                          WHERE (E.COD_DELIVERY like
                                                upper('%' || trim(:busqueda) || '%') OR
                                                E.DESCRIPCION like
                                                upper('%' || trim(:busqueda1) || '%'))

                                            and rownum <= ({$pag} * {$cant})) asd) dsa
                          where r >= ({$pag}  + ({$pag} * {$cant}) - ({$cant} + {$pag} ) + 1)";

            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':busqueda' => $busqueda,
                ':busqueda1' => $busqueda]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //PRINT_R($result);
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
    public static function DatosDelivery($COD_DELIVERY) {
        try {
            $cant = 10;
            $consulta = "SELECT E.COD_DELIVERY,
                                E.DESCRIPCION ,
                                E.MONTO
                         FROM WEB_APP_DELIVERY E
                         WHERE E.COD_DELIVERY = :COD_DELIVERY";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_DELIVERY' => $COD_DELIVERY]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    public static function ListarPedidos($cod_call, $usr_call, $busqueda, $pag) {
        try {
            $cant = 10;
            //print_r('ASD');
            //print_r($busqueda);
            $consulta = "select COD_PEDIDO,
                                FEC_COMPROBANTE,
                                COD_CLIENTE,
                                NOM_CLIENTE,
                                MONTO_DELIVERY,
                                TOTAL_SOLICITUD,
                                TOTAL,
                                VOUCHER,
                                TIPO_PAGO,
                                COD_METODO,
                                METODO_PAGO,
                                COD_DELIVERY,
                                DELIVERY,
                                estado,
                                USA_VOUCHER,
                                CONTADO
           from (select asd.*, rownum r
                   from (SELECT W.COD_PEDIDO,
                                TO_CHAR(TRUNC(W.FEC_COMPROBANTE),'DD/MM/YYYY') FEC_COMPROBANTE,
                                W.COD_CLIENTE,
                                W.NOM_CLIENTE,
                                W.MONTO_DELIVERY,
                                SUM(D.MONTO_TOTAL) TOTAL_SOLICITUD,
                                NVL(W.MONTO_DELIVERY, 0) + SUM(D.MONTO_TOTAL) TOTAL,
                                NVL(W.VOUCHER,' ') VOUCHER,
                                DECODE(w.tipo_pago, 'CRE', 'CREDITO', 'CONTADO') TIPO_PAGO,
                                M.COD_METODO,
                                M.DESCRIPCION METODO_PAGO,
                                L.COD_DELIVERY,
                                L.DESCRIPCION DELIVERY,
                                DECODE(C.AUTORIZA_STOCK,
                                       'S',
                                       'Confirmado',
                                       'A',
                                       'Anulado',
                                       'Pendiente') estado,
                                NVL(M.USA_VOUCHER,'N') USA_VOUCHER,
                                NVL(M.CONTADO,'N') CONTADO
                           FROM WEB_APP_PED_CAB W
                           JOIN VT_PED_CAB C
                             ON W.COD_EMPRESA = C.COD_EMPRESA
                            AND W.COD_PEDIDO = C.COD_PEDIDO_WEB
                           JOIN VT_PED_DET D
                             ON C.COD_EMPRESA = D.COD_EMPRESA
                            AND C.TIP_COMPROBANTE = D.TIP_COMPROBANTE
                            AND C.SER_COMPROBANTE = D.SER_COMPROBANTE
                            AND C.NRO_COMPROBANTE = D.NRO_COMPROBANTE
                            AND C.SECUENCIA = D.SECUENCIA
                           LEFT JOIN WEB_APP_METODO_PAGO M
                             ON W.COD_METODO = M.COD_METODO
                           LEFT JOIN WEB_APP_DELIVERY L
                             ON W.COD_DELIVERY = L.COD_DELIVERY
                          WHERE W.COD_EMPRESA = '1'
                            AND W.COD_CALL = :COD_CALL
                            AND W.USR_CALL = :USR_CALL
                            AND (TO_CHAR(W.COD_PEDIDO) like
                                upper('%' || trim('{$busqueda}') || '%') OR
                                upper(W.NOM_CLIENTE) like
                                upper('%' || trim('{$busqueda}') || '%'))
                            and (NVL(M.USA_VOUCHER,'N') = 'S'
                                 OR NVL(M.CONTADO,'N') != 'S')

                            and rownum <= ({$pag} * {$cant})
                            and DECODE(C.AUTORIZA_STOCK,
                                       'S',
                                       'Confirmado',
                                       'A',
                                       'Anulado',
                                       'Pendiente') = 'Pendiente'
                            
                          GROUP BY W.COD_PEDIDO,
                                   W.FEC_COMPROBANTE,
                                   W.COD_CLIENTE,
                                   W.NOM_CLIENTE,
                                   W.MONTO_DELIVERY,
                                   W.VOUCHER,
                                   DECODE(w.tipo_pago, 'CRE', 'CREDITO', 'CONTADO'),
                                   M.COD_METODO,
                                   M.DESCRIPCION,
                                   L.DESCRIPCION,
                                   L.COD_DELIVERY,
                                   DECODE(C.AUTORIZA_STOCK,
                                          'S',
                                          'Confirmado',
                                          'Pendiente'),
                                   DECODE(C.AUTORIZA_STOCK,
                                          'S',
                                          'Confirmado',
                                          'A',
                                          'Anulado',
                                          'Pendiente'),
                                   M.USA_VOUCHER,
                                   M.CONTADO
                          order by W.FEC_COMPROBANTE desc) asd) dsa
                     where r >= ({$pag} + ({$pag}* {$cant}) - ({$cant} + {$pag}) + 1)";

            //print_r($consulta);
            // Preparar sentencia
            //print_r($busqueda);
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            /* $comando->bindParam(':COD_CALL', $cod_call, PDO::PARAM_STR);
              $comando->bindParam(':USR_CALL', $usr_call, PDO::PARAM_STR);
              $comando->bindParam(':busqueda', $busqueda, PDO::PARAM_STR);
              $comando->bindParam(':busqueda1', $busqueda, PDO::PARAM_STR); */
            $comando->execute([':COD_CALL' => $cod_call,
                ':USR_CALL' => $usr_call/* ,
                      ':busqueda' => $busqueda,
                      ':busqueda1' => $busqueda */]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //PRINT_R($result);
            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    public static function ListarPedidosCompleto($cod_call, $fec_des, $fec_has) {
        try {
            $cant = 10;
            //print_r('ASD');
            //print_r($busqueda);
            $consulta = "SELECT V.TIP_COMPROBANTE,
                                V.SER_COMPROBANTE,
                                V.NRO_COMPROBANTE,
                                V.FEC_COMPROBANTE,
                                V.COD_CLIENTE,
                                V.NOM_CLIENTE,
                                V.TEL_CLIENTE,
                                V.DIRECCION,
                                V.COMENTARIO,
                                V.COD_ARTICULO,
                                V.DESCRIPCION,
                                V.COD_COLOR,
                                V.COD_TALLA,
                                V.CANTIDAD,
                                V.MONTO_TOTAL,
                                V.TOTAL_CON_IVA,
                                V.ESTADO,
                                V.COD_USUARIO,
                                V.FECHA_DE_CARGA,
                                V.COD_PEDIDO_WEB,
                                V.COD_CALL,
                                V.USR_CALL,
                                V.DESC_CALL
                           FROM WEB_APPV_PEDIDOS_EMITIDOS V
                          WHERE TO_DATE(V.FEC_COMPROBANTE,'DD/MM/YYYY')  >= TO_DATE('{$fec_des}','DD/MM/YYYY') 
                            AND TO_DATE(V.FEC_COMPROBANTE,'DD/MM/YYYY')  <= TO_DATE('{$fec_has}','DD/MM/YYYY')
                            AND V.COD_CALL = TO_NUMBER(:COD_CALL)";

            //print_r($fec_des);
            // Preparar sentencia
            //print_r($consulta);
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_CALL'  => $cod_call]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //PRINT_R($result);
            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }
    
    
    public static function insertaCliente($persona) {
        try {


            $sql = "begin :res := fcrea_cliente(:pcod_empresa,
                                                :pnombre,
                                                :pnomb_fantasia,
                                                :pdireccion,
                                                :parea1,
                                                :ptelefono1,
                                                :parea2,
                                                :ptelefono2,
                                                :pruc,
                                                :pci,
                                                :pfec_nacimiento,
                                                :psexo); end;";
            //print_r($sql);
            // Preparar sentencia
            //print_r($persona);
            //print $persona['NOMBRE'];
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(':res', $result, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
            $comando->bindParam(':pcod_empresa', $persona['COD_EMPRESA'], PDO::PARAM_STR);
            $comando->bindParam(':pnombre', $persona['NOMBRE'], PDO::PARAM_STR);
            $comando->bindParam(':pnomb_fantasia', $persona['NOMBRE_FANTASIA'], PDO::PARAM_STR);
            $comando->bindParam(':pdireccion', $persona['DIRECCION'], PDO::PARAM_STR);
            $comando->bindParam(':parea1', $persona['AREA1'], PDO::PARAM_STR);
            $comando->bindParam(':ptelefono1', $persona['NUMERO1'], PDO::PARAM_STR);
            $comando->bindParam(':parea2', $persona['AREA2'], PDO::PARAM_STR);
            $comando->bindParam(':ptelefono2', $persona['NUMERO2'], PDO::PARAM_STR);
            $comando->bindParam(':pruc', $persona['RUC'], PDO::PARAM_STR);
            $comando->bindParam(':pci', $persona['CI'], PDO::PARAM_STR);
            $comando->bindParam(':pfec_nacimiento', $persona['FEC_NACIMIENTO'], PDO::PARAM_STR);
            $comando->bindParam(':psexo', $persona['SEXO'], PDO::PARAM_STR);

            $comando->execute();
            //var_dump($result);

            return $result;
        } catch (PDOException $e) {
            print_r($e->getMessage());
            //return false;
            return $e->getMessage();
        }
    }

    public static function insertaCabeceraPedido($pedidoCab) {
        try {

            $sql = "begin :res := FWEB_APP_INSERTA_CAB_PEDIDO(:PCOD_CLIENTE,
                                                              :PNOM_CLIENTE,
                                                              :PTEL_CLIENTE,
                                                              :PRUC,
                                                              :PCAMBIAR_NOMBRE,
                                                              :PTIPO_PAGO,
                                                              :PCOD_METODO,
                                                              :PUSR_CALL,
                                                              :PCOD_CALL,
                                                              :PCOD_DIRECCION,
                                                              :PDESC_DIRECCION,
                                                              :PCOD_DELIVERY,
                                                              :PMONTO_DELIVERY,
                                                              :PNRO_SOLICUTUD,
                                                              :POBSERVACION,
                                                              :PFEC_ENTREGA,
                                                              :PHOR_ENTREGA) ; end;";
            //print_r($sql);
            // Preparar sentencia
            //print_r($pedidoCab);
            //print $persona['NOMBRE'];
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(':res', $result, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
            $comando->bindParam(':PCOD_CLIENTE', $pedidoCab['COD_CLIENTE'], PDO::PARAM_STR);
            $comando->bindParam(':PNOM_CLIENTE', $pedidoCab['NOM_CLIENTE'], PDO::PARAM_STR);
            $comando->bindParam(':PTEL_CLIENTE', $pedidoCab['TEL_CLIENTE'], PDO::PARAM_STR);
            $comando->bindParam(':PRUC', $pedidoCab['RUC'], PDO::PARAM_STR);
            $comando->bindParam(':PCAMBIAR_NOMBRE', $pedidoCab['CAMBIAR_NOMBRE'], PDO::PARAM_STR);
            $comando->bindParam(':PTIPO_PAGO', $pedidoCab['TIPO_PAGO'], PDO::PARAM_STR);
            $comando->bindParam(':PCOD_METODO', $pedidoCab['COD_METODO'], PDO::PARAM_STR);
            $comando->bindParam(':PUSR_CALL', $pedidoCab['USR_CALL'], PDO::PARAM_STR);
            $comando->bindParam(':PCOD_CALL', $pedidoCab['COD_CALL'], PDO::PARAM_INT);
            $comando->bindParam(':PCOD_DIRECCION', $pedidoCab['COD_DIRECCION'], PDO::PARAM_STR);
            $comando->bindParam(':PDESC_DIRECCION', $pedidoCab['DESC_DIRECCION'], PDO::PARAM_STR);
            $comando->bindParam(':PCOD_DELIVERY', $pedidoCab['COD_DELIVERY'], PDO::PARAM_STR);
            $comando->bindParam(':PMONTO_DELIVERY', $pedidoCab['MONTO_DELIVERY'], PDO::PARAM_INT);
            $comando->bindParam(':PNRO_SOLICUTUD', $pedidoCab['NRO_SOLICUTUD'], PDO::PARAM_STR);
            $comando->bindParam(':POBSERVACION', $pedidoCab['OBSERVACION'], PDO::PARAM_STR);
            $comando->bindParam(':PFEC_ENTREGA', $pedidoCab['FEC_ENTREGA'], PDO::PARAM_STR);
            $comando->bindParam(':PHOR_ENTREGA', $pedidoCab['HOR_ENTREGA'], PDO::PARAM_STR);

            $comando->execute();
            //var_dump($result);
            //$result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //print_r($result);
            return utf8_converter_sting($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return utf8_converter_sting($e->getMessage());
        }
    }

    public static function insertaDetallePedido($pedidoDet) {
        try {

            $sql = "begin :res := FWEB_APP_INSERTA_DET_PEDIDO(:PCOD_PEDIDO,
                                                              :PNRO_ORDEN,
                                                              :PCOD_ARTICULO,
                                                              :PCANTIDAD,
                                                              :PPRECIO_UNITARIO,
                                                              :PMONTO_TOTAL,
                                                              :PTOTAL_IVA,
                                                              :PPRECIO_LISTA,
                                                              :PDESCUENTO,
                                                              :PPORC_DESCUENTO,
                                                              :PCOD_UNIDAD_MEDIDA,
                                                              :PDESC_ARTICULO,
                                                              :PCOD_COLOR,
                                                              :PCOD_TALLA,
                                                              :PGRAVADAS,
                                                              :PEXENTAS,
                                                              :POMITIR_OFERTA) ; end;";
            //print_r($sql);
            // Preparar sentencia
            //print_r($pedidoDet);
            //print $persona['NOMBRE'];
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(':res', $result, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
            $comando->bindParam(':PCOD_PEDIDO', $pedidoDet['NRO_SOLICUTUD'], PDO::PARAM_STR);
            $comando->bindParam(':PNRO_ORDEN', $pedidoDet['ORDEN'], PDO::PARAM_STR);
            $comando->bindParam(':PCOD_ARTICULO', $pedidoDet['COD_ARTICULO'], PDO::PARAM_STR);
            $comando->bindParam(':PCANTIDAD', $pedidoDet['CANTIDAD'], PDO::PARAM_STR);
            $comando->bindParam(':PPRECIO_UNITARIO', $pedidoDet['PRECIO_UNITARIO'], PDO::PARAM_STR);
            $comando->bindParam(':PMONTO_TOTAL', $pedidoDet['MONTO_TOTAL'], PDO::PARAM_STR);
            $comando->bindParam(':PTOTAL_IVA', $pedidoDet['TOTAL_IVA'], PDO::PARAM_STR);
            $comando->bindParam(':PPRECIO_LISTA', $pedidoDet['PRECIO_LISTA'], PDO::PARAM_STR);
            $comando->bindParam(':PDESCUENTO', $pedidoDet['DESCUENTO'], PDO::PARAM_STR);
            $comando->bindParam(':PPORC_DESCUENTO', $pedidoDet['PORC_DESCUENTO'], PDO::PARAM_STR);
            $comando->bindParam(':PCOD_UNIDAD_MEDIDA', $pedidoDet['COD_UNIDAD_MEDIDA'], PDO::PARAM_STR);
            $comando->bindParam(':PDESC_ARTICULO', $pedidoDet['DESC_ARTICULO'], PDO::PARAM_STR);
            $comando->bindParam(':PCOD_COLOR', $pedidoDet['COD_COLOR'], PDO::PARAM_STR);
            $comando->bindParam(':PCOD_TALLA', $pedidoDet['COD_TALLA'], PDO::PARAM_STR);
            $comando->bindParam(':PGRAVADAS', $pedidoDet['GRAVADAS'], PDO::PARAM_STR);
            $comando->bindParam(':PEXENTAS', $pedidoDet['EXENTAS'], PDO::PARAM_STR);
            $comando->bindParam(':POMITIR_OFERTA', $pedidoDet['OMITIR_OFERTA'], PDO::PARAM_STR);

            $comando->execute();
            //var_dump($result);
            //$result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter_sting($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return utf8_converter_sting($e->getMessage());
        }
    }

    public static function completaCarga($cod_pedido) {
        try {

            $sql = "begin :res := FWEB_COMPLETA_CARGA(:cod_pedido); end;";

            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(':res', $result, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
            $comando->bindParam(':cod_pedido', $cod_pedido, PDO::PARAM_STR);

            $comando->execute();

            return $result;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public static function confirmaPedido($cod_pedido, $voucher) {
        try {

            $sql = "begin :res := FWEB_APP_CONFIRMA_PEDIDO(:COD_PEDIDO, :VOUCHER); end;";

            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(':res', $result, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
            $comando->bindParam(':COD_PEDIDO', $cod_pedido, PDO::PARAM_STR);
            $comando->bindParam(':VOUCHER', $voucher, PDO::PARAM_STR);
            $comando->execute();

            return $result;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public static function DatosClienteCompleto($COD_CLIENTE) {
        try {
            $cant = 10;
            $consulta = "select cc.COD_CLIENTE,
                                CC.COD_PERSONA,
                                initcap(p.nombre) NOMBRE,
                                initcap(p.nomb_fantasia) NOMBRE_FANTASIA,
                                p.fec_nacimiento,
                                p.sexo,
                                decode(p.sexo,'F', 'FEMENINO', 'M','MASCULINO','') DESC_SEXO
                           from cc_clientes cc
                           join personas p
                             on cc.cod_persona = p.cod_persona
                          where CC.COD_EMPRESA = '1'
                            AND cc.cod_cliente = :COD_CLIENTE
                            and nvl(cc.estado, 'X') <> 'I'";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_CLIENTE' => $COD_CLIENTE]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    public static function DatosDireccionesCompleto($COD_CLIENTE) {
        try {
            $cant = 10;
            $consulta = "SELECT P.COD_DIRECCION,
                                P.DETALLE DESC_DIRECCION,
                                P.COD_CIUDAD,
                                I.DESCRIPCION DESC_CIUDAD,
                                B.COD_BARRIO,
                                B.DESCRIPCION DESC_BARRIO
                         FROM CC_CLIENTES C
                              JOIN DIREC_PERSONAS P
                              ON C.COD_PERSONA = P.COD_PERSONA
                              LEFT JOIN CIUDADES I
                              ON P.COD_CIUDAD = I.COD_CIUDAD
                              LEFT JOIN BARRIOS B
                              ON I.COD_CIUDAD = B.COD_CIUDAD
                              AND P.COD_BARRIO = B.COD_BARRIO
                         WHERE C.COD_EMPRESA = '1'
                         AND C.COD_CLIENTE = :COD_CLIENTE";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_CLIENTE' => $COD_CLIENTE]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    public static function DatosTelefonosCompleto($COD_CLIENTE) {
        try {
            $cant = 10;
            $consulta = "SELECT P.CODIGO_AREA,
                                P.NUM_TELEFONO
                         FROM CC_CLIENTES C
                              JOIN TELEF_PERSONAS P
                              ON C.COD_PERSONA = P.COD_PERSONA
                         WHERE C.COD_EMPRESA = '1'
                         AND C.COD_CLIENTE = :COD_CLIENTE";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_CLIENTE' => $COD_CLIENTE]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    public static function DatosDocumentosCompleto($COD_CLIENTE) {
        try {
            $cant = 10;
            $consulta = "SELECT P.COD_IDENT,
                                P.NUMERO
                         FROM CC_CLIENTES C
                              JOIN IDENT_PERSONAS P
                              ON C.COD_PERSONA = P.COD_PERSONA
                         WHERE C.COD_EMPRESA = '1'
                         AND C.COD_CLIENTE = :COD_CLIENTE";
            //print_r($consulta);
            // Preparar sentencia
            $comando = oraconnect::getInstance()->getDb()->prepare($consulta);

            // Ejecutar sentencia preparada
            $comando->execute([':COD_CLIENTE' => $COD_CLIENTE]);

            $result = $comando->fetchAll(PDO::FETCH_ASSOC);

            return utf8_converter($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return false;
        }
    }

    public static function ListarCiudadesBarrios($busqueda, $pag) {
        $cant = 10;
        try {
            $conn = null;
            $consulta = "select DSA.COD_CIUDAD, DSA.DESC_CIUDAD, DSA.COD_BARRIO, DSA.DESC_BARRIO
                                from (select asd.*, rownum r
                                        from (
                                        
                                              SELECT C.COD_CIUDAD,
                                                      C.DESCRIPCION DESC_CIUDAD,
                                                      NVL(B.COD_BARRIO, ' ') COD_BARRIO,
                                                      NVL(B.DESCRIPCION, ' ') DESC_BARRIO
                                                FROM CIUDADES C
                                                LEFT JOIN BARRIOS B
                                                  ON C.COD_CIUDAD = B.COD_CIUDAD
                                                 AND C.COD_PAIS = B.COD_PAIS
                                               WHERE (UPPER(C.DESCRIPCION) like
                                                     upper('%' || trim(:busqueda) || '%') OR
                                                     UPPER(B.DESCRIPCION) like
                                                     upper('%' || trim(:busqueda1) || '%'))

                                                 and rownum <= ({$pag} * {$cant})) asd) dsa
                               where r >= ({$pag} + ({$pag}  * {$cant}) - ({$cant} + {$pag} ) + 1)
                              ";
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

    public static function actualizaClienteCabecera($clienteCab) {
        try {

            $sql = "begin :res := FACTUALIZA_CLIENTE('1',
                                                     :PCOD_CLIENTE,
                                                     :PNOMBRE,
                                                     :PNOM_FANTASIA,
                                                     :PFEC_NACIMIENTO,
                                                     :PSEXO) ; end;";
            //print_r($sql);
            // Preparar sentencia
            //print_r($pedidoCab);
            //print $persona['NOMBRE'];
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(':res', $result, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
            $comando->bindParam(':PCOD_CLIENTE', $clienteCab['COD_CLIENTE'], PDO::PARAM_STR);
            $comando->bindParam(':PNOMBRE', $clienteCab['NOMBRE'], PDO::PARAM_STR);
            $comando->bindParam(':PNOM_FANTASIA', $clienteCab['NOM_FANTASIA'], PDO::PARAM_STR);
            $comando->bindParam(':PFEC_NACIMIENTO', $clienteCab['FEC_NACIMIENTO'], PDO::PARAM_STR);
            $comando->bindParam(':PSEXO', $clienteCab['SEXO'], PDO::PARAM_STR);


            $comando->execute();
            //var_dump($result);
            //$result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //print_r($result);
            return utf8_converter_sting($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return utf8_converter_sting($e->getMessage());
        }
    }

    public static function actualizaClienteDireccion($cod_cliente, $clienteDir) {
        try {

            $sql = "begin :res := FACTUALIZA_DIRECCION('1',
                                                       :PCOD_CLIENTE,
                                                       :PCOD_DIRECCION,
                                                       :PDIRECCION ,
                                                       :PCOD_CIUDAD,
                                                       :PCIUDAD,
                                                       :PCOD_BARRIO,
                                                       :PBARRIO); end;";
            //print_r($sql);
            // Preparar sentencia
            //print_r($clienteCab);
            //print_r($clienteDir);
            //print $persona['NOMBRE'];
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(':res', $result, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
            $comando->bindParam(':PCOD_CLIENTE', $cod_cliente, PDO::PARAM_STR);
            $comando->bindParam(':PCOD_DIRECCION', $clienteDir['COD_DIRECCION'], PDO::PARAM_STR);
            $comando->bindParam(':PDIRECCION', $clienteDir['DIRECCION'], PDO::PARAM_STR);
            $comando->bindParam(':PCOD_CIUDAD', $clienteDir['COD_CIUDAD'], PDO::PARAM_STR);
            $comando->bindParam(':PCIUDAD', $clienteDir['CIUDAD'], PDO::PARAM_STR);
            $comando->bindParam(':PCOD_BARRIO', $clienteDir['COD_BARRIO'], PDO::PARAM_STR);
            $comando->bindParam(':PBARRIO', $clienteDir['BARRIO'], PDO::PARAM_STR);


            $comando->execute();
            //var_dump($result);
            //$result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //print_r($result);
            return utf8_converter_sting($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return utf8_converter_sting($e->getMessage());
        }
    }

    public static function actualizaClienteTelefono($clienteCab, $clienteTel) {
        try {
            
            $sql = "begin :res := FACTUALIZA_TELEFONO('1',
                                                      :PCOD_CLIENTE,
                                                      :PCOD_AREA,
                                                      :PNUMERO); end;";
            //print_r($sql);
            // Preparar sentencia
            //print_r($pedidoCab);
            //print $persona['NOMBRE'];
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(':res', $result, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
            $comando->bindParam(':PCOD_CLIENTE', $clienteCab['COD_CLIENTE'], PDO::PARAM_STR);
            $comando->bindParam(':PCOD_AREA', $clienteTel['COD_AREA'], PDO::PARAM_STR);
            $comando->bindParam(':PNUMERO', $clienteTel['NUMERO'], PDO::PARAM_STR);


            $comando->execute();
            //var_dump($result);
            //$result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //print_r($result);
            return utf8_converter_sting($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return utf8_converter_sting($e->getMessage());
        }
    }

    public static function actualizaClienteDocumento($clienteCab, $clienteDoc) {
        try {

            $sql = "begin :res := FACTUALIZA_DOCUMENTO('1',
                                                       :PCOD_CLIENTE,
                                                       :PCOD_IDENT,
                                                       :PNUMERO); end;";
            //print_r($sql);
            // Preparar sentencia
            //print_r($pedidoCab);
            //print $persona['NOMBRE'];
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(':res', $result, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
            $comando->bindParam(':PCOD_CLIENTE', $clienteCab['COD_CLIENTE'], PDO::PARAM_STR);
            $comando->bindParam(':PCOD_IDENT', $clienteDoc['COD_IDENT'], PDO::PARAM_STR);
            $comando->bindParam(':PNUMERO', $clienteDoc['NUMERO'], PDO::PARAM_STR);


            $comando->execute();
            //var_dump($result);
            //$result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //print_r($result);
            return utf8_converter_sting($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return utf8_converter_sting($e->getMessage());
        }
    }

    public static function consultaLimiteDescuento($cod_articulo, $porc) {
        try {
            $cod_sucursal = operacionesDB::buscaParametro('VT', 'COD_SUCURSAL_WEB');
            
            $sql = "begin :res := vtf_busca_limite_desc_fact_p('1',
                                                               SYSDATE,
                                                               :pcod_sucursal,
                                                               :pcod_articulo,
                                                               null,
                                                               null,
                                                               null,
                                                               :pporc); end;";
            //print $cod_sucursal;
            $comando = oraconnect::getInstance()->getDb()->prepare($sql);
            $comando->bindParam(':res', $result, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT);
            $comando->bindParam(':pcod_sucursal', $cod_sucursal, PDO::PARAM_STR);
            $comando->bindParam(':pcod_articulo', $cod_articulo, PDO::PARAM_STR);
            $comando->bindParam(':pporc', $porc, PDO::PARAM_STR);


            $comando->execute();
            //var_dump($result);
            //$result = $comando->fetchAll(PDO::FETCH_ASSOC);
            //print_r($cod_articulo);
            return utf8_converter_sting($result);
        } catch (PDOException $e) {
            //print_r($e->getMessage());
            return utf8_converter_sting($e->getMessage());
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