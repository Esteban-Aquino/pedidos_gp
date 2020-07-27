$(document).ready(function () {
    inicializar();
    //editable();
});

function inicializar() {
    listarPedidoAjax();
    /*$('#COD_DELIVERY').on('change', function (event) { // funcion callback , anonima
     precioDeliveryAjax();
     });*/
}

function datos_confirmar($this) {
    //console.log('Datos confirmar');
    //console.log($this.parent().parent().find('td'));
    //console.log($this.parent().parent().find('td').eq(11).text());
    var $usa_voucher = $this.parent().parent().find('td').eq(12).text();
    var $nro_pedido = $this.parent().parent().find('td').eq(0).text();
    //console.log($usa_voucher);
    if ($usa_voucher === 'S') {
        $('#modal_confirmar').modal('show');
        $('#modal_confirmar').on('shown.bs.modal', function () {
            $('#NRO_VOUCHER').focus();
            $('#CONF_NRO').val($nro_pedido);
        });
    } else {
        estaSeguroSwal('Desea confirmar?','confirma_pedido('+$nro_pedido+')','#');
    }
    //listarDeliveryAjax();
}


function confirma_pedido_voucher(){
    if (!empty($('#NRO_VOUCHER').val())) {
        confirma_pedido($('#CONF_NRO').val());
    } else {
        swalError('Datos faltantes', 'Debe cargar numero de voucher');
    }
}

/*** PEDIDOS ***/

function listarPedidoAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    var pUrl = "api/listarPedidos?COD_CALL=" + datosUsuario('COD_CALL') + "&USR_CALL=" + datosUsuario('USR_CALL');
    var pBeforeSend = "";
    var pSucces = "listarPedidoAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pDatosFormulario);
    //console.log(pUrl);
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function listarPedidoAjaxSuccess(json) {
    tieneAcceso(json["acceso"]);
    var datos = "";
    console.log(json);
    $datos = json["datos"];
    $.each($datos, function (key, value) {
        datos += `<tr>
                    <tD>` + value.COD_PEDIDO + `</tD>
                    <td>` + value.FEC_COMPROBANTE + `</td>
                    <td>` + value.COD_CLIENTE + `</td>
                    <td>` + value.NOM_CLIENTE + `</td>
                    <td class="gs">` + value.MONTO_DELIVERY + `</td>
                    <td class="gs">` + value.TOTAL_SOLICITUD + `</td>
                    <td class="gs">` + value.TOTAL + `</td>
                    <td>` + value.VOUCHER + `</td>
                    <td>` + value.TIPO_PAGO + `</td>
                    <td>` + value.METODO_PAGO + `</td>
                    <td>` + value.DELIVERY + `</td>
                    <td>` + value.ESTADO + `</td>
                    <td class="oculto">` + value.USA_VOUCHER + `</td>
                    <td class="oculto">` + value.CONTADO + `</td>
                    <td>
                        <button id="CONFIRMAR" class="btn btn-primary" onclick="datos_confirmar($(this))"> 
                            <a class="fa fa-check" 
                               style="color: white;"></a> Confirmar 
                        </button>
                    </td>
                </tr>`;
    });
    $('#tbody_listar').html(datos);
    formatoGs();
}

function precioDeliveryAjax() {
    var pDatosFormulario = $("#form-confirmar").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/listarDelivery";
    //console.log(pDatosFormulario);
    var pBeforeSend = "";
    var pSucces = "precioDeliveryAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function precioDeliveryAjaxSuccess(json) {
    var datos = "";
    datos = json['datos'];
    //console.log(json);
    $('#monto_delivery').html(datos[0].MONTO);
    $('#div_delivery').removeClass('oculto');
    formatoGs();
}
/*** /PEDIDOS***/

// CONFIRMAR PEDIDO
function confirma_pedido($nro_pedido) {
    var pDatos = '';
    var pUrl = 'api/confirmarPedido?NRO_SOLICITUD='+$nro_pedido+'&VOUCHER='+$('#NRO_VOUCHER').val('');
    var pBeforeSend = "swalCargando('Confirmando Pedido')";
    var pSucces = "ConfirmarPedidoAjaxSuccess(json)";
    var pError = "errorGuardarAjax()";
    var pComplete = "";
    console.log(pUrl);
    ajax(pDatos, pUrl, pBeforeSend, pSucces, pError, pComplete);
}
 function errorGuardarAjax(){
     swalCerrar();
 }
function ConfirmarPedidoAjaxSuccess(json) {
    swalCerrar();
    tieneAcceso(json["acceso"]);
    var $solicitud = json["datos"];
    console.log(json);
    if ($solicitud) {
        var mensaje = json["mensaje"];
        if ($solicitud !== 'OK' ) {
            swalError('Error al guadar', mensaje);
        } else {
            swalCorrecto("Guardado",'Solicitud Confirmada: '+$solicitud);
        }
    }
    $('#CONF_NRO').val('');
    $('#NRO_VOUCHER').val('');
    listarPedidoAjax();
}








/*** DELIVERY ***
 
 function listarDeliveryAjax() {
 var pDatosFormulario = $("#form-buscar").serialize();
 var pUrl = "api/listarDelivery";
 var pBeforeSend = "";
 var pSucces = "listarDeliveryAjaxSuccess(json)";
 var pError = "ajax_error()";
 var pComplete = "";
 ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
 }
 
 function listarDeliveryAjaxSuccess(json) {
 var datos = "";
 //console.log(json);
 $datos = json["datos"];
 $.each($datos, function (key, value) {
 datos += `<option value="` + value.COD_DELIVERY + `">` + value.DESCRIPCION + `</option>`;
 });
 $('#COD_DELIVERY').html(datos);
 precioDeliveryAjax();
 }
 
 function precioDeliveryAjax() {
 var pDatosFormulario = $("#form-confirmar").serialize();
 //console.log(pDatosFormulario);
 var pUrl = "api/listarDelivery";
 //console.log(pDatosFormulario);
 var pBeforeSend = "";
 var pSucces = "precioDeliveryAjaxSuccess(json)";
 var pError = "ajax_error()";
 var pComplete = "";
 ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
 }
 
 function precioDeliveryAjaxSuccess(json) {
 var datos = "";
 datos = json['datos'];
 //console.log(json);
 $('#monto_delivery').html(datos[0].MONTO);
 $('#div_delivery').removeClass('oculto');
 formatoGs();
 }
 *** /DELIVERY***/
