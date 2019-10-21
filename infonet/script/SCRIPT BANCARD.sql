/****** SCRIPT COBRANZAS BANCARD ******/
/**** PARAMETROS INICIALES DE CARGA ***/
-- PARAMETROS QUE SE DEBEN PROVEER
   -- CAJA Y SUCURSAL DONDE SERA CARGADO
   -- TIENDAS QUE INCLUYE LAS COBNRANZAS
   -- CUENTA CONTABLE DE LA TRANSACCION DE COBRO
   -- SERIE DEL RECIBO
   -- TAMBIEN SE DEBE CARGAR EL TALONARIO DE RECIBO

INSERT INTO CC_CAJAS VALUES ('1','01',(SELECT MAX(TO_NUMBER(COD_CAJA))+1 FROM CC_CAJAS),TRUNC(SYSDATE),USER,'A','001001'); 

INSERT INTO PARAMETROS_GENERALES VALUES ('CAJA_INFONET','BS','Caja para cobranzas infonet',(SELECT MAX(TO_NUMBER(COD_CAJA)) FROM CC_CAJAS));
INSERT INTO PARAMETROS_GENERALES VALUES ('COBRADOR_INFONET','BS','Cobrador para cobranzas infonet','6');
INSERT INTO PARAMETROS_GENERALES VALUES ('CUSTODIO_INFONET','BS','Custodio cobranzas infonet','01');
-- INSERT INTO PARAMETROS_GENERALES VALUES ('TIENDAS_INFONET','BS','Saldo de tiendas que se pueden cobrar por infonet','|71|74|75|68|60|61|62|63|');
INSERT INTO PARAMETROS_GENERALES VALUES ('TIENDAS_INFONET','BS','Saldo de tiendas que se pueden cobrar por infonet','TODAS');
INSERT INTO PARAMETROS_GENERALES VALUES ('SUB_TIPO_INFONET','BS','Sub tipo trans de cobranza infonet',(SELECT subtipo_trans  FROM SUBTIPOS_TRANS WHERE TIPO_TRANS = '101' AND tip_documento = 'CIN')); 
INSERT INTO PARAMETROS_GENERALES VALUES ('SER_RECIBO_INFONET','BS','Serie de recibo infonet','W');

INSERT INTO TALONARIOS VALUES ('1','REC',(SELECT MAX(NRO_TALONARIO)+1 FROM TALONARIOS),'01','W',1,99999,'S','','1','T','0','W','','','','');

alter table CC_RECIBOS add tid varchar2(30);
alter table CC_RECIBOS add id_transaccion number;
alter table CC_DETALLE_RECIBOS add id_saldo number;

alter table CC_MOVIMIENTOS_CAJAS add tid varchar2(30);
alter table CC_MOVIMIENTOS_CAJAS add id_transaccion number;

INSERT INTO SUBTIPOS_TRANS
  (cod_empresa,
   cod_modulo,
   tipo_trans,
   subtipo_trans,
   descripcion,
   usa_dinero,
   concepto,
   carga_valores,
   verifica_valores,
   tip_documento,
   cod_cuenta,
   abreviatura)
VALUES ('1',
       'CC',
       '101',
       (SELECT MAX(TO_NUMBER(subtipo_trans))+1 FROM SUBTIPOS_TRANS WHERE TIPO_TRANS = '101'),
       'Cobranza Infonet',
       'S',
       'S',
       'N',
       'N',
       'CIN',
       '1.01.01.01.06',
       'CIN');
       
       
/****** INICIO SCRIPT ********/
alter table MONEDAS add SIGLAS_ISO varchar2(10);

UPDATE MONEDAS SET SIGLAS_ISO = 'PYG' WHERE COD_MONEDA = '1'; -- Guaranies
UPDATE MONEDAS SET SIGLAS_ISO = 'USD' WHERE COD_MONEDA = '2'; -- Dolares americanos
UPDATE MONEDAS SET SIGLAS_ISO = 'BRL' WHERE COD_MONEDA = '3'; -- REAL
UPDATE MONEDAS SET SIGLAS_ISO = 'EUR' WHERE COD_MONEDA = '4'; -- EURO
UPDATE MONEDAS SET SIGLAS_ISO = 'ARS' WHERE COD_MONEDA = '5'; -- PESO ARGENTINO

alter table CC_SALDOS add id_saldo number(20);

-- SECUENCIA
CREATE SEQUENCE SQ_SALDOS
START WITH 100 INCREMENT BY 1;

SELECT SQ_SALDOS.NEXTVAL
FROM DUAL

-- TRIGGER QUE ASIGNA SECUENCIA
CREATE OR REPLACE TRIGGER ASIGNAR_ID_SALDO
BEFORE INSERT ON CC_SALDOS
REFERENCING NEW AS NEW OLD AS OLD
FOR EACH ROW
DECLARE
    VID NUMBER;
BEGIN
  SELECT SQ_SALDOS.NEXTVAL
  INTO VID
  FROM DUAL;
  :NEW.ID_SALDO := VID;
END;

create or replace trigger cc_control_bor_saldo
       before delete or update
       on INV.CC_SALDOS
       referencing new as new old as old
       for each row

BEGIN
  if deleting then
    if (nvl(:old.saldo_cuota, 0) <> nvl(:old.monto_cuota, 0)) or
       (nvl(:old.rec_pendientes, 0) <> 0) then
      raise_application_error(-20003,
                              'No se puede elminar el saldo porque esta parcialmente cobrado');
    end if;
  end if;

  if updating then
    IF :new.saldo_cuota != :old.saldo_cuota then
      if :new.monto_cuota < 0 then
        if :new.saldo_cuota > 0 then
          raise_application_error(-20003,
                                  'Este comprobante fue aplicado demas');
        end if;
      else
        if :new.saldo_cuota < 0 then
          raise_application_error(-20003,
                                  'Este comprobante fue aplicado demas');
        end if;
      end if;
    end if;
  end if;
END;


-- ASIGNAR ID_SALDO A TODOS LOS REGISTROS
DECLARE 
     CURSOR SALDOS IS
            SELECT S.*, S.ROWID
            FROM CC_SALDOS S
            WHERE S.ID_SALDO IS NULL;
BEGIN
  FOR REG IN SALDOS LOOP
      UPDATE CC_SALDOS S
      SET S.id_saldo = SQ_SALDOS.NEXTVAL
      WHERE S.ROWID = REG.ROWID;
  END LOOP;
END;



-- TABLA MENSAJES
create table WEB_API_MENSAJES_DSC
(
  estado           VARCHAR2(80),
  nivel            VARCHAR2(80),
  codigo           VARCHAR2(80),
  descripcion      VARCHAR2(800),
  http_status      VARCHAR2(400),
  id_mensaje       NUMBER not null,
  http_status_desc VARCHAR2(400)
);
-- Create/Recreate primary, unique and foreign key constraints 
alter table WEB_API_MENSAJES_DSC
  add constraint PK_MENSAJES primary key (ID_MENSAJE);

-- INSERTAR TIPOS DE MENSAJES
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'InvalidParameters', 'Error en los parámetros', '422', '19', '422 Unprocessable Entity 26');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'MissingParameters', 'Parámetro requerido no recibido', '403', '20', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'UnknownError', 'Error desconocido', '403', '21', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'HostTransactionError', 'Error en el Host/Autorizador', '403', '22', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'TransactionNotReversed', 'La transacción no fue reversada.', '403', '23', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('success', 'success', 'TransactionReversed', 'La reversa fue procesada con éxito.', '200', '24', '200 Success');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('success', 'info', 'QueryNotAllowed', 'La operacion de consulta no est permitida', '403', '1', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('success', 'info', 'InvalidParameters', 'Error en los parametros', '422', '2', '422 Unprocessable Entity');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('success', 'info', 'MissingParameters', 'Parametro requerido no recibido', '403', '3', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('success', 'info', 'SubscriberNotFound', 'No se ha encontrado un abonado con el codigo enviado', '404', '4', '404 Not Found');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('success', 'info', 'QueryNotProcessed', 'La consulta no fue procesada', '403', '5', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('success', 'info', 'HostTransactionError', 'Error en el Host/Autorizador', '403', '6', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('success', 'info', 'UnknownError', 'Error desconocido', '403', '7', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('success', 'info', 'SubscriberWithoutDebt', 'El abonado no tiene deuda pendiente', '403', '8', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('success', 'success', 'QueryProcessed', 'La consulta fue procesada con exito', '200', '9', '200 Success');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'SubscriberWithoutDebt', 'El pago no fue aprobado porque el abonado no tiene deuda pendiente', '403', '10', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'InvalidParameters', 'Error en los parámetros', '422', '11', '422 Unprocessable Entity ');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'MissingParameter', 'Parámetro requerido no recibido', '403', '12', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'OverdueInvoice', 'El pago no fue aprobado, factura ya esta vencida', '403', '13', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'UnknownError', 'Error desconocido', '403', '14', '403 Forbidden ');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'HostTransactionError', 'Error en el Host/Autorizador', '403', '15', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'PaymentNotAuthorized', 'El pago no fue autorizado', '403', '16', '403 Forbidden');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('success', 'success', 'PaymentProcessed', 'El pago fue procesado con exito', '200', '17', '200 Success');
INSERT INTO WEB_API_MENSAJES_DSC VALUES ('error', 'error', 'AlreadyReversed', 'La transacción ya fue reversada', '403', '18', '403 Forbidden');

-- Create table
create table WEB_API_TIPOS_PETICIONES
(
  cod_tipo    VARCHAR2(30),
  descripcion VARCHAR2(200)
)
tablespace BS_DATA
  pctfree 10
  initrans 1
  maxtrans 255
  storage
  (
    initial 64K
    next 1M
    minextents 1
    maxextents unlimited
  );


INSERT INTO WEB_API_TIPOS_PETICIONES (SELECT 'CONS','CONSULTA' FROM DUAL);
INSERT INTO WEB_API_TIPOS_PETICIONES (SELECT 'PAGO','PAGO' FROM DUAL);
INSERT INTO WEB_API_TIPOS_PETICIONES (SELECT 'REVER','REVERSO' FROM DUAL);
INSERT INTO WEB_API_TIPOS_PETICIONES (SELECT 'CREATE','CREAR ID CLIENTE' FROM DUAL);

-- Create table
create table WEB_API_PETICIONES
(
  cod_tipo VARCHAR2(30),
  tid      VARCHAR2(30),
  prd_id   VARCHAR2(30),
  sub_id   VARCHAR2(2000),
  addl     VARCHAR2(2000),
  inv_id   VARCHAR2(2000),
  amt      NUMBER(18,4),
  cur      VARCHAR2(10),
  trn_dat  VARCHAR2(30),
  cm_amt   NUMBER(18,4),
  cm_cur   VARCHAR2(10),
  barcode  VARCHAR2(80),
  trn_hou  VARCHAR2(30),
  fecha    DATE
)
tablespace BS_DATA
  pctfree 10
  initrans 1
  maxtrans 255
  storage
  (
    initial 64K
    next 1M
    minextents 1
    maxextents unlimited
  );



create or replace trigger WEB_API_CARGA_FEC_PETICION
before insert ON WEB_API_PETICIONES
REFERENCING NEW AS NEW OLD AS OLD
FOR EACH ROW
BEGIN
  :NEW.FECHA := SYSDATE;
END;

-- Create table
create table WEB_API_RESPUESTAS
(
  status   VARCHAR2(80),
  tid      VARCHAR2(30),
  messages VARCHAR2(4000),
  tkt      VARCHAR2(80),
  aut_cod  VARCHAR2(80),
  prnt_msn VARCHAR2(4000),
  invoices CLOB,
  fecha    DATE
)
tablespace BS_DATA
  pctfree 10
  initrans 1
  maxtrans 255
  storage
  (
    initial 64K
    next 1M
    minextents 1
    maxextents unlimited
  );


-- Create table
create table WEB_API_MENSAJES
(
  tid          VARCHAR2(30),
  descripcion  VARCHAR2(200),
  id_mensaje   NUMBER,
  mensaje_json VARCHAR2(2000),
  fecha        DATE
)
tablespace BS_DATA
  pctfree 10
  initrans 1
  maxtrans 255
  storage
  (
    initial 64K
    next 1M
    minextents 1
    maxextents unlimited
  );



create or replace trigger WEB_API_CARGA_FEC_MENSAJE
before insert ON WEB_API_MENSAJES
REFERENCING NEW AS NEW OLD AS OLD
FOR EACH ROW
BEGIN
  :NEW.FECHA := SYSDATE;
END;

-- Create table
create table WEB_API_PAGOS
(
  id_transaccion NUMBER,
  tid            VARCHAR2(30),
  id_saldo       NUMBER,
  monto          NUMBER(18,4),
  siglas_iso     VARCHAR2(10),
  doc_cliente    VARCHAR2(80),
  fecha_env      VARCHAR2(200),
  forma_cob      VARCHAR2(30),
  estado         VARCHAR2(1),
  fecha          DATE
)
tablespace BS_DATA
  pctfree 10
  initrans 1
  maxtrans 255
  storage
  (
    initial 64K
    next 1M
    minextents 1
    maxextents unlimited
  );
-- Add comments to the columns 
comment on column WEB_API_PAGOS.estado
  is 'C = Concluido, P = pendiente, A= Anulado';
-- Create/Recreate check constraints 
alter table WEB_API_PAGOS
  add constraint CHK_WEB_API_ESTADO_PAGOS_
  check (ESTADO IN ('P','C','A'));


create or replace trigger WEB_API_CARGA_FEC_PAGO
before insert ON WEB_API_PAGOS
REFERENCING NEW AS NEW OLD AS OLD
FOR EACH ROW
BEGIN
  :NEW.FECHA := SYSDATE;
END; 

-- SECUENCIA TRANSACCION
CREATE SEQUENCE SQ_TRANSACCION
START WITH 1 INCREMENT BY 1;

SELECT SQ_TRANSACCION.NEXTVAL FROM DUAL;

CREATE OR REPLACE FUNCTION WEB_API_TRAE_ID_TRANCACCION RETURN NUMBER IS
       VID NUMBER;
BEGIN
  SELECT SQ_TRANSACCION.NEXTVAL
  INTO VID
  FROM DUAL;
  RETURN VID;
END;


CREATE OR REPLACE FUNCTION WEP_API_PROCESA_PAGOS (ID_TRANSACCION IN NUMBER) RETURN VARCHAR2 IS
  CURSOR PAGOS(PID_TRANSACCION IN NUMBER) IS
  SELECT S.*, P.MONTO
  FROM WEB_API_PAGOS P
       JOIN CC_SALDOS S
       ON P.ID_SALDO = S.ID_SALDO
  WHERE P.ID_TRANSACCION = PID_TRANSACCION
  AND NVL(P.ESTADO,'P') = 'P';
  VHABILITACION NUMBER;
  VCAJA VARCHAR2(30);
  VCOD_SUCURSAL VARCHAR2(20);
  VSER_CAJA VARCHAR2(20);
  VNRO_RECIBO NUMBER;
  VSER_RECIBO VARCHAR2(20);
  VNRO_TALONARIO NUMBER;
  VCOD_CLIENTE VARCHAR2(30);
  VCOD_MONEDA VARCHAR2(10);
  VNOM_CLIENTE VARCHAR2(2000);
  VTIMBRADO VARCHAR2(12);
  VSER_TIMBRADO VARCHAR2(12);
  VTID VARCHAR2(30);
  VNRO_MOV_CAJ NUMBER;
  VMONTO       NUMBER := 0;
  VERR VARCHAR2(32000);
  VID_TRANSACCION NUMBER := ID_TRANSACCION;
begin
  -- VER SI HAY HABILITACION ABIERTA
  BEGIN
    SELECT H.COD_SUCURSAL, H.Cod_Caja, H.NRO_HABILITACION
    INTO VCOD_SUCURSAL, VCAJA, VHABILITACION
    FROM CC_HABILITACIONES H
    WHERE H.COD_CAJA = bs_busca_parametro('BS','CAJA_INFONET')
    AND H.FEC_CIERRE IS NULL;
  EXCEPTION
    WHEN NO_DATA_FOUND THEN
      -- TRAER LA CAJA A HABILITAR
      BEGIN
        SELECT C.COD_SUCURSAL, C.COD_CAJA, C.SER_CAJA
        INTO VCOD_SUCURSAL, VCAJA, VSER_CAJA
        FROM CC_CAJAS C
        WHERE C.COD_CAJA = bs_busca_parametro('BS','CAJA_INFONET');
      END;
      -- SINO HABILITAR UNA
      INSERT INTO CC_HABILITACIONES (cod_empresa,
                                     cod_sucursal,
                                     cod_caja,
                                     nro_habilitacion,
                                     fec_habilitacion,
                                     cod_usuario,
                                     hora_habilitacion)
      VALUES
        ('1',
         VCOD_SUCURSAL,
         VCAJA,
         (SELECT MAX(NRO_HABILITACION)+1 FROM CC_HABILITACIONES WHERE COD_EMPRESA = '1' AND COD_SUCURSAL = VCOD_SUCURSAL),
         TRUNC(SYSDATE),
         USER,
         TO_CHAR(SYSDATE,'HH24:MI:SS'));
      COMMIT;

      SELECT H.COD_SUCURSAL, H.Cod_Caja, H.NRO_HABILITACION
      INTO VCOD_SUCURSAL, VCAJA, VHABILITACION
      FROM CC_HABILITACIONES H
      WHERE H.COD_CAJA = bs_busca_parametro('BS','CAJA_INFONET')
      AND H.FEC_CIERRE IS NULL;

   WHEN OTHERS THEN
     VERR := 'Error al buscar habilitacion: '||sqlerrm;
     RAISE_APPLICATION_ERROR(-20000,'Error al buscar habilitacion: '||sqlerrm);
  END;
  -- SERIE DEL RECIBO
  VSER_RECIBO := bs_busca_parametro('CC','SER_RECIBO_INFONET');
  -- TRAER MAXIMO NUMERO DE RECIBO
  BEGIN
    SELECT MAX(NVL(R.NRO_RECIBO,0)) NRO
    INTO VNRO_RECIBO
    FROM CC_RECIBOS R
    WHERE R.COD_EMPRESA = '1'
    --AND R.COD_SUCURSAL = VCOD_SUCURSAL
    AND R.SER_RECIBO = VSER_RECIBO;
    VNRO_RECIBO := NVL(VNRO_RECIBO,0) + 1;
  EXCEPTION
    WHEN NO_DATA_FOUND THEN
       VNRO_RECIBO := 1;
    WHEN OTHERS THEN
     VERR := 'Error al buscar recibo: '||sqlerrm;
     RAISE_APPLICATION_ERROR(-20000,'Error al buscar recibo: '||sqlerrm);
  END;
  -- BUSCAR DATOS PARA CABECERA DEL RECIBO
     -- BUSCAR DATOS DEL TALONARIO
  BEGIN
     SELECT T.Nro_Talonario, T.TIMBRADO, T.SER_TIMBRADO
     INTO VNRO_TALONARIO, VTIMBRADO, VSER_TIMBRADO
     FROM TALONARIOS T
     WHERE T.TIP_TALONARIO = 'REC'
     AND T.COD_SUCURSAL = VCOD_SUCURSAL
     AND NVL(T.ACTIVO,'N') =  'S'
     AND T.SERIE = VSER_RECIBO
     --AND NVL(T.VIGENCIA_DESDE,SYSDATE+1) <= SYSDATE
     --AND NVL(T.VIGENCIA_HASTA,SYSDATE-1) >= SYSDATE
     AND VNRO_RECIBO >= T.NUMERO_INICIAL
     AND VNRO_RECIBO <= T.NUMERO_FINAL
     AND ROWNUM = 1;
  EXCEPTION
    WHEN NO_DATA_FOUND THEN
       VERR := 'No se encuentra datos del talonario';
       RAISE_APPLICATION_ERROR(-20000,'No se encuentra datos del talonario');
    WHEN OTHERS THEN
      VERR := 'Error al buscar talonario: '||sqlerrm;
      RAISE_APPLICATION_ERROR(-20000,'Error al buscar talonario: '||sqlerrm);
  END;
     -- BUSCAR DATOS DEL CLIENTE
  BEGIN
    SELECT DISTINCT S.COD_CLIENTE, M.COD_MONEDA, PC.NOMBRE, P.TID
    INTO VCOD_CLIENTE, VCOD_MONEDA, VNOM_CLIENTE, VTID
    FROM WEB_API_PAGOS P,
         CC_SALDOS S,
         MONEDAS M,
         CC_CLIENTES C,
         PERSONAS PC
    WHERE P.ID_SALDO = S.ID_SALDO
    AND P.SIGLAS_ISO = M.SIGLAS_ISO
    AND S.COD_EMPRESA = C.COD_EMPRESA
    AND S.COD_CLIENTE = C.COD_CLIENTE
    AND C.COD_PERSONA = PC.COD_PERSONA
    AND P.ID_TRANSACCION = VID_TRANSACCION;
  EXCEPTION
    WHEN NO_DATA_FOUND THEN
       VERR := 'No se encuentra cliente';
       RAISE_APPLICATION_ERROR(-20000,'No se encuentra cliente');
    WHEN TOO_MANY_ROWS THEN
       VERR := 'Los comprobantes seleccionados corresponden a mas de 1 cliente tr: '||VID_TRANSACCION;
       RAISE_APPLICATION_ERROR(-20000,'Los comprobantes seleccionados corresponden a mas de 1 cliente');
    WHEN OTHERS THEN
       VERR := 'Error al buscar recibo: '||sqlerrm;
       RAISE_APPLICATION_ERROR(-20000,'Error al buscar recibo: '||sqlerrm);
  END;
  BEGIN
  -- CREAR CABECERA DEL RECIBO
      INSERT INTO CC_RECIBOS (COD_EMPRESA,
                            cod_sucursal,
                            ser_recibo,
                            nro_recibo,
                            fec_recibo,
                            cod_cliente,
                            cod_cobrador,
                            cod_moneda,
                            tip_cambio,
                            cod_usuario,
                            anulado,
                            estado,
                            fec_estado,
                            cod_custodio,
                            tip_recibo,
                            nom_cliente,
                            timbrado,
                            ser_timbrado,
                            nro_talonario,
                            tid,
                            id_transaccion)
    VALUES ('1',
            VCOD_SUCURSAL,
            VSER_RECIBO,
            VNRO_RECIBO,
            TRUNC(SYSDATE),
            VCOD_CLIENTE,
            bs_busca_parametro('BS','COBRADOR_INFONET'),
            VCOD_MONEDA,
            DECODE(VCOD_MONEDA,'1',1,ftipos_cambio(VCOD_MONEDA,SYSDATE,'D','V')),
            USER,
            'N',
            'C',
            SYSDATE,
            bs_busca_parametro('BS','CUSTODIO_INFONET'),
            'REC',
            VNOM_CLIENTE,
            VTIMBRADO,
            VSER_TIMBRADO,
            VNRO_TALONARIO,
            VTID,
            VID_TRANSACCION);
  EXCEPTION
    WHEN OTHERS THEN
      VERR := 'Error al insertar cabecera de recibo. '||sqlerrm;
      RAISE_APPLICATION_ERROR(-20000,'');
  END;

  -- GUARDAR DETALLES RECIBO
  FOR REG IN PAGOS(VID_TRANSACCION) LOOP
    BEGIN
      --DBMS_OUTPUT.put_line(REG.NRO_COMPROBANTE);
      INSERT INTO CC_DETALLE_RECIBOS (cod_empresa,
                                    ser_recibo,
                                    nro_recibo,
                                    tipo_trans,
                                    sub_tipo_trans,
                                    tip_factura_ref,
                                    ser_factura_ref,
                                    nro_factura_ref,
                                    nro_cuota,
                                    fec_vencimiento,
                                    monto_cuota,
                                    cod_moneda_cuota,
                                    tip_cambio,
                                    tip_recibo,
                                    generar_dc,
                                    genera_credito,
                                    timbrado_ref,
                                    ser_timbrado_ref,
                                    timbrado,
                                    ser_timbrado,
                                    ID_SALDO)
      VALUES                        ('1',
                                    VSER_RECIBO,
                                    VNRO_RECIBO,
                                    '100',
                                    10001,
                                    REG.TIPO_COMPROBANTE,
                                    REG.SER_COMPROBANTE,
                                    REG.NRO_COMPROBANTE,
                                    REG.NRO_CUOTA,
                                    REG.FEC_VENCIMIENTO,
                                    REG.monto,
                                    VCOD_MONEDA,
                                    DECODE(VCOD_MONEDA,'1',1,ftipos_cambio(VCOD_MONEDA,SYSDATE,'D','V')),
                                    'REC',
                                    'N',
                                    'N',
                                    REG.TIMBRADO,
                                    REG.SER_TIMBRADO,
                                    VTIMBRADO,
                                    VSER_TIMBRADO,
                                    REG.ID_SALDO);
       VMONTO := VMONTO + REG.monto;
    EXCEPTION
    WHEN OTHERS THEN
      VERR := 'Error al insertar detalle de recibo. '||sqlerrm;
      RAISE_APPLICATION_ERROR(-20000,'');
    END;
  END LOOP;
  -- TRAER DATOS DE CAJAS
  BEGIN
     SELECT MAX(NVL(nro_mov_caj,0)) + 1
     INTO VNRO_MOV_CAJ
     FROM CC_MOVIMIENTOS_CAJAS C
     WHERE C.COD_EMPRESA = '1'
     AND C.TIP_MOV_CAJ = 'CAJ'
     AND C.SER_MOV_CAJ = 'A';
  END;
  BEGIN
    -- GUARDAR FORMA DE COBRO
    INSERT INTO CC_MOVIMIENTOS_CAJAS
                (cod_empresa,
                 nro_mov_caj,
                 cod_sucursal,
                 fec_mov_caj,
                 cod_caja,
                 nro_habilitacion,
                 anulado,
                 cod_usuario,
                 tot_nro_mov_caj,
                 tip_cambio,
                 fec_alta,
                 cod_cliente,
                 ser_mov_caj,
                 tip_mov_caj,
                 cod_custodio,
                 TID,
                 id_transaccion )
              VALUES  ('1',
                       VNRO_MOV_CAJ,
                       VCOD_SUCURSAL,
                       SYSDATE,
                       VCAJA,
                       VHABILITACION,
                       'N',
                       USER,
                       VMONTO,
                       DECODE(VCOD_MONEDA,'1',1,ftipos_cambio(VCOD_MONEDA,SYSDATE,'D','V')),
                       SYSDATE,
                       VCOD_CLIENTE,
                       'A',
                       'CAJ',
                       bs_busca_parametro('BS','CUSTODIO_INFONET'),
                       VTID,
                       VID_TRANSACCION);
   EXCEPTION
    WHEN OTHERS THEN
      VERR := 'Error al insertar cabecera de movimiento de caja. '||sqlerrm;
      RAISE_APPLICATION_ERROR(-20000,'');
    END;
   BEGIN
        -- INSERTAR COMPROBANTES MOVCAJ
        INSERT INTO CC_MOVIMIENTOS_COMP
          (cod_empresa,
           nro_mov_caj,
           tipo_comprobante,
           ser_comprobante,
           nro_comprobante,
           cod_moneda,
           tip_cambio,
           tot_comprobante,
           ser_mov_caj,
           tip_mov_caj,
           nro_cuota,
           ind_tipo,
           timbrado,
           ser_timbrado)
        VALUES ('1',
                VNRO_MOV_CAJ,
                'REC',
                VSER_RECIBO,
                VNRO_RECIBO,
                VCOD_MONEDA,
                DECODE(VCOD_MONEDA,'1',1,ftipos_cambio(VCOD_MONEDA,SYSDATE,'D','V')),
                VMONTO,
                'A',
                'CAJ',
                1,
                'R',
                VTIMBRADO,
                VSER_TIMBRADO);
  EXCEPTION
  WHEN OTHERS THEN
    VERR := 'Error al insertar comprobantes de movimiento de caja. '||sqlerrm;
    RAISE_APPLICATION_ERROR(-20000,'');
  END;
  BEGIN
     -- VALORES
     INSERT INTO CC_FORMAS_COBROS
      (cod_empresa,
       cod_sucursal,
       nro_mov_caj,
       nro_secuencia,
       tipo_trans,
       sub_tipo_trans,
       cod_moneda_cobro,
       NRO_VALOR,
       tip_cambio,
       monto,
       tip_mov_caj,
       tip_documento,
       ser_mov_caj)
    VALUES ('1',
           VCOD_SUCURSAL,
           VNRO_MOV_CAJ,
           1,
           '101',
           bs_busca_parametro('BS','SUB_TIPO_INFONET'),
           VCOD_MONEDA,
           VTID,
           DECODE(VCOD_MONEDA,'1',1,ftipos_cambio(VCOD_MONEDA,SYSDATE,'D','V')),
           VMONTO,
           'CAJ',
           'CIN',
           'A');
  EXCEPTION
  WHEN OTHERS THEN
    VERR := 'Error al insertar forma de cobro. '||sqlerrm;
    RAISE_APPLICATION_ERROR(-20000,'');
  END;
  -- ACTUALIZAR PAGO
  BEGIN
     UPDATE WEB_API_PAGOS
     SET ESTADO = 'C'
     WHERE ID_TRANSACCION = VID_TRANSACCION
     AND ESTADO = 'P';
  END;
  COMMIT;
  RETURN 'OK';
EXCEPTION
  WHEN OTHERS THEN
    ROLLBACK;
    -- ACTUALIZAR PAGO
    BEGIN
       UPDATE WEB_API_PAGOS
       SET ESTADO = 'A'
       WHERE ID_TRANSACCION = VID_TRANSACCION
       AND ESTADO = 'P';
    END;
    COMMIT;
    RETURN 'ERROR: '||VERR||' '||SQLERRM;
    --DBMS_OUTPUT.put_line('ERROR: '||VERR||' '||SQLERRM);
end;

 


-- Create table
create table WEB_API_REVERSOS
(
  tid            VARCHAR2(30),
  id_transaccion NUMBER,
  estado         VARCHAR2(10),
  fecha          DATE
)
tablespace BS_DATA
  pctfree 10
  initrans 1
  maxtrans 255
  storage
  (
    initial 64K
    next 1M
    minextents 1
    maxextents unlimited
  );


create or replace trigger WEB_API_CARGA_FEC_REVERSO
before insert ON WEB_API_REVERSOS
REFERENCING NEW AS NEW OLD AS OLD
FOR EACH ROW
BEGIN
  :NEW.FECHA := SYSDATE;
END; 

CREATE OR REPLACE FUNCTION WEP_API_PROCESA_REVERSOS(PID_TRANSACCION IN VARCHAR2) RETURN VARCHAR2 IS
  VERR VARCHAR2(2000);
  VHABILITACION NUMBER;
  VCOD_SUCURSAL VARCHAR2(20);
  VABIERTA VARCHAR2(2);
  VPENDIENTE VARCHAR2(10);
  VTID VARCHAR2(30);
begin
  -- OBTENER TRANSACCION
  BEGIN
    SELECT TID
    INTO VTID
    FROM WEB_API_REVERSOS
    WHERE ID_TRANSACCION = PID_TRANSACCION
    AND ESTADO = 'P'
    AND ROWNUM = 1;
  EXCEPTION
  WHEN OTHERS THEN
    VERR := 'Error al buscar transaccion a reversar. '||sqlerrm;
    RAISE_APPLICATION_ERROR(-20000,'');
  END;
  -- VER SI YA ESTA REVERSADA
  -- SI YA ESTA REVERSADA NO A VA TENER RECIBO CON ESE TID, SI TIENE ANULAR
  BEGIN
    SELECT DISTINCT 'S'
    INTO VPENDIENTE
    FROM CC_RECIBOS R
    WHERE R.TID = VTID
    AND NVL(R.ANULADO,'N') != 'S';
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
      BEGIN
        SELECT DISTINCT 'N'
        INTO VPENDIENTE
        FROM CC_RECIBOS R
        WHERE R.TID = VTID
        AND NVL(R.ANULADO,'N') = 'S';
      EXCEPTION
        WHEN NO_DATA_FOUND THEN
          VERR := 'No se encuentra transaccion a reversar';
          RAISE_APPLICATION_ERROR(-20000,'');
        WHEN OTHERS THEN
          VERR := 'Error al buscar transaccion a reversar. '||sqlerrm;
          RAISE_APPLICATION_ERROR(-20000,'');
      END;
  WHEN OTHERS THEN
    VERR := 'Error al buscar transaccion a reversar. '||sqlerrm;
    RAISE_APPLICATION_ERROR(-20000,'');
  END;
  /*BEGIN
    SELECT R.ESTADO
    INTO VPENDIENTE
    FROM WEB_API_REVERSOS R
    WHERE R.TID = VTID
    AND R.ESTADO IN ('C')
    AND R.FECHA = (SELECT MAX(J.FECHA)
                   FROM WEB_API_REVERSOS J
                   WHERE J.TID = VTID
                   AND J.ESTADO = 'C')
    AND ROWNUM = 1;
  EXCEPTION
  WHEN NO_DATA_FOUND THEN
    VPENDIENTE := 'P';
  WHEN OTHERS THEN
    VERR := 'Error al buscar transaccion a reversar. '||sqlerrm;
    RAISE_APPLICATION_ERROR(-20000,'');
  END;*/
  IF NVL(VPENDIENTE,'N') = 'N' THEN
     BEGIN
      UPDATE WEB_API_REVERSOS C
      SET C.ESTADO = 'A'
      WHERE C.ID_TRANSACCION = PID_TRANSACCION;
    EXCEPTION
    WHEN OTHERS THEN
        VERR := 'Error al anular transaccion. '||sqlerrm;
        RAISE_APPLICATION_ERROR(-20000,'');
    END;
    COMMIT;
   VERR := 'R';
   RAISE_APPLICATION_ERROR(-20000,'');
  END IF;
  -- BUSCAR EL MOVIMIENTO DE CAJA DEL RECIBO
  BEGIN
    SELECT C.NRO_HABILITACION, C.COD_SUCURSAL
    INTO VHABILITACION, VCOD_SUCURSAL
    FROM CC_MOVIMIENTOS_CAJAS C
    WHERE C.COD_EMPRESA = '1'
    AND C.TID = VTID
    AND ROWNUM = 1;
  EXCEPTION
    WHEN NO_DATA_FOUND THEN
        VERR := 'No se encuentra transaccion a reversar';
        RAISE_APPLICATION_ERROR(-20000,'');
    WHEN OTHERS THEN
        VERR := 'Error al buscar transaccion a reversar. '||sqlerrm;
        RAISE_APPLICATION_ERROR(-20000,'');
  END;
  -- Ver si la habilitacion esta cerrada
  BEGIN
    SELECT 'S'
    INTO VABIERTA
    FROM CC_HABILITACIONES H
    WHERE H.COD_SUCURSAL = VCOD_SUCURSAL
    AND H.NRO_HABILITACION = VHABILITACION
    AND H.FEC_CIERRE IS NULL;
  EXCEPTION
    WHEN NO_DATA_FOUND THEN
        VERR := 'La caja de la transaccion que desea reversar ya esta cerrada';
        VABIERTA := 'N';
        RAISE_APPLICATION_ERROR(-20000,'');
    WHEN OTHERS THEN
        VERR := 'Error al buscar caja de la transaccion a reversar. '||sqlerrm;
        RAISE_APPLICATION_ERROR(-20000,'');
  END;
  IF NVL(VABIERTA,'N') = 'S' THEN
    -- Anular movimiento de caja
    BEGIN
      DELETE CC_MOVIMIENTOS_CAJAS C
      WHERE C.COD_EMPRESA = '1'
      AND C.TID = VTID;
    EXCEPTION
    WHEN OTHERS THEN
        VERR := 'Error al anular movimiento de caja. '||sqlerrm;
        RAISE_APPLICATION_ERROR(-20000,'');
    END;
    -- Anular recibos
    BEGIN
      UPDATE CC_RECIBOS C
      SET C.ANULADO = 'S',
          C.FEC_ANULACION = SYSDATE
      WHERE C.COD_EMPRESA = '1'
      AND C.TID = VTID;
    EXCEPTION
    WHEN OTHERS THEN
        VERR := 'Error al anular movimiento recibo. '||sqlerrm;
        RAISE_APPLICATION_ERROR(-20000,'');
    END;
  END IF;
  -- ACTUALIZAR PEDI DE REVERSO
  BEGIN
      UPDATE WEB_API_REVERSOS C
      SET C.ESTADO = 'C'
      WHERE C.ID_TRANSACCION = PID_TRANSACCION;
  EXCEPTION
  WHEN OTHERS THEN
      VERR := 'Error al confirmar reverso. '||sqlerrm;
      RAISE_APPLICATION_ERROR(-20000,'');
  END;
  COMMIT;
  RETURN 'OK';
EXCEPTION
WHEN OTHERS THEN
    ROLLBACK;
   BEGIN
     UPDATE WEB_API_REVERSOS C
        SET C.ESTADO = 'A'
      WHERE C.ID_TRANSACCION = PID_TRANSACCION;
   END;
   RETURN VERR;
end;

 

/*create table LOG_VARIOS
(
  fecha       DATE,
  descripcion VARCHAR2(4000),
  proceso     VARCHAR2(4000),
  result      VARCHAR2(200)
);*/

CREATE OR REPLACE PROCEDURE WEP_API_CIERRA_HABILITACION IS
  VHABILITACION NUMBER;
  VCAJA         VARCHAR2(30);
  VCOD_SUCURSAL VARCHAR2(20);
  AUX           VARCHAR2(2000);
BEGIN
  SELECT H.COD_SUCURSAL, H.Cod_Caja, H.NRO_HABILITACION
    INTO VCOD_SUCURSAL, VCAJA, VHABILITACION
    FROM CC_HABILITACIONES H
   WHERE H.COD_CAJA = bs_busca_parametro('BS', 'CAJA_INFONET')
     AND H.FEC_CIERRE IS NULL;

  UPDATE CC_HABILITACIONES H
     SET H.FEC_CIERRE  = TRUNC(SYSDATE),
         H.HORA_CIERRE = TO_CHAR(SYSDATE, 'HH24:MI:SS')
   WHERE H.COD_CAJA = VCAJA
     AND H.NRO_HABILITACION = VHABILITACION
     AND H.COD_SUCURSAL = VCOD_SUCURSAL;

  INSERT INTO LOG_VARIOS
    SELECT SYSDATE, 'Correcto', 'WEP_API_CIERRA_HABILITACION', 'C'
      FROM DUAL;
  COMMIT;

EXCEPTION
  WHEN NO_DATA_FOUND THEN
    NULL;
  WHEN TOO_MANY_ROWS THEN
    BEGIN
      UPDATE CC_HABILITACIONES H
         SET H.FEC_CIERRE  = TRUNC(SYSDATE),
             H.HORA_CIERRE = TO_CHAR(SYSDATE, 'HH24:MI:SS')
       WHERE H.COD_CAJA = bs_busca_parametro('BS', 'CAJA_INFONET')
         AND H.FEC_CIERRE IS NULL;
    
      INSERT INTO LOG_VARIOS
        SELECT SYSDATE,
               'Correcto. Mas de 1 habiilitacion cerrada',
               'WEP_API_CIERRA_HABILITACION',
               'C'
          FROM DUAL;
      COMMIT;
    EXCEPTION
      WHEN OTHERS THEN
        ROLLBACK;
        AUX := SQLERRM;
        BEGIN
          INSERT INTO LOG_VARIOS
            SELECT SYSDATE,
                   'Error al cerrar mas de 1 habilitacion:' || AUX,
                   'WEP_API_CIERRA_HABILITACION',
                   'E'
              FROM DUAL;
        END;
    END;
  WHEN OTHERS THEN
    ROLLBACK;
    AUX := SQLERRM;
    BEGIN
      INSERT INTO LOG_VARIOS
        SELECT SYSDATE, 'Error:' || AUX, 'WEP_API_CIERRA_HABILITACION', 'E'
          FROM DUAL;
    END;
END;


CREATE PUBLIC SYNONYM WEB_API_MENSAJES_DSC FOR INV.WEB_API_MENSAJES_DSC;
CREATE PUBLIC SYNONYM WEB_API_TIPOS_PETICIONES FOR INV.WEB_API_TIPOS_PETICIONES;
CREATE PUBLIC SYNONYM WEB_API_PETICIONES FOR INV.WEB_API_PETICIONES;
CREATE PUBLIC SYNONYM WEB_API_MENSAJES FOR INV.WEB_API_MENSAJES;
CREATE PUBLIC SYNONYM WEB_API_RESPUESTAS FOR INV.WEB_API_RESPUESTAS;
CREATE PUBLIC SYNONYM WEB_API_PAGOS FOR INV.WEB_API_PAGOS;
CREATE PUBLIC SYNONYM WEB_API_TRAE_ID_TRANCACCION FOR INV.WEB_API_TRAE_ID_TRANCACCION;
CREATE PUBLIC SYNONYM WEP_API_PROCESA_PAGOS FOR INV.WEP_API_PROCESA_PAGOS;
CREATE PUBLIC SYNONYM WEB_API_REVERSOS FOR INV.WEB_API_REVERSOS;
CREATE PUBLIC SYNONYM WEP_API_PROCESA_REVERSOS FOR INV.WEP_API_PROCESA_REVERSOS;
CREATE PUBLIC SYNONYM WEP_API_CIERRA_HABILITACION FOR INV.WEP_API_CIERRA_HABILITACION;



GRANT ALL ON WEB_API_MENSAJES_DSC TO PUBLIC;
GRANT ALL ON WEB_API_TIPOS_PETICIONES TO PUBLIC;
GRANT ALL ON WEB_API_PETICIONES TO PUBLIC;
GRANT ALL ON WEB_API_RESPUESTAS TO PUBLIC;
GRANT ALL ON WEB_API_MENSAJES TO PUBLIC;
GRANT ALL ON WEB_API_PAGOS TO PUBLIC;
GRANT ALL ON WEB_API_TRAE_ID_TRANCACCION TO PUBLIC;
GRANT ALL ON WEP_API_PROCESA_PAGOS TO PUBLIC;
GRANT ALL ON WEB_API_REVERSOS TO PUBLIC;
GRANT ALL ON WEP_API_PROCESA_REVERSOS TO PUBLIC;
GRANT ALL ON WEP_API_CIERRA_HABILITACION TO PUBLIC;


DECLARE
   jobno   NUMBER;
begin
  sys.dbms_job.submit(job => jobno,
                      what => 'WEP_API_CIERRA_HABILITACION;',
                      next_date => SYSDATE,
                      interval => 'trunc(SYSDATE) + 1');
  commit;
EXCEPTION
        WHEN OTHERS THEN
             RAISE_APPLICATION_ERROR(-20002,'Error :'||sqlerrm);
end;
