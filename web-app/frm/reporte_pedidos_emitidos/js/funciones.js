$(document).ready(function () {
    inicializar();
    //editable();
});
function inicializar() {
    //listarPedidoAjax();
    //console.log(datosUsuario('COD_CALL') );
    $('#COD_CALL').val(datosUsuario('COD_CALL'));
    $('#NOM_CALL').val(datosUsuario('NOM_CALL'));
    /*$('#COD_DELIVERY').on('change', function (event) { // funcion callback , anonima
     precioDeliveryAjax();
     });*/

    $('.dt').datetimepicker({
        format: 'DD/MM/YYYY'
    });
    init_DataTables();
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
        estaSeguroSwal('Desea confirmar?', 'confirma_pedido(' + $nro_pedido + ')', '#');
    }
//listarDeliveryAjax();
}


function confirma_pedido_voucher() {
    if (!empty($('#NRO_VOUCHER').val())) {
        confirma_pedido($('#CONF_NRO').val());
    } else {
        swalError('Datos faltantes', 'Debe cargar numero de voucher');
    }
}

/*** PEDIDOS ***/

function listarCompletoPedidoAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    var pUrl = "api/listarPerdidoCompletos?COD_CALL=" + $('#COD_CALL').val() + "&USR_CALL=" + datosUsuario('USR_CALL') + "&FEC_DESDE=" + $('#FEC_DESDE').val() + "&FEC_HASTA=" + $('#FEC_HASTA').val();
    var pBeforeSend = "";
    var pSucces = "listarPedidoAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    // console.log(pDatosFormulario);
    // console.log(pUrl);
    swalCargando("Buscando Datos");
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function listarPedidoAjaxSuccess(json) {
    var vTotCant = 0;
    var vTotMonto = 0;
    var vTotMontoCIVA = 0;
    var vTotCantDevueltos = 0;
    var vTotMontoDevueltos = 0;
    var vTotMontoDevueltosCIVA = 0;
    var vTotCantFact = 0;
    var vTotMontoFact = 0;
    var vTotMontoFactCIVA = 0;
    var vTotCantPend = 0;
    var vTotMontoPend = 0;
    var vTotMontoPendCIVA = 0;

    $("#dtListarPedidos").DataTable().destroy();
    tieneAcceso(json["acceso"]);
    var datos = "";
    // console.log(json);
    $datos = json["datos"];
    var vcolor = "";
    $.each($datos, function (key, value) {
        vTotCant += parseInt(value.CANTIDAD);
        vTotMonto += parseInt(value.MONTO_TOTAL);
        vTotMontoCIVA += parseInt(value.TOTAL_CON_IVA);
        if (value.ESTADO === 'DEVOLUCION') {
            vTotCantDevueltos += parseInt(value.CANTIDAD);
            vTotMontoDevueltos += parseInt(value.MONTO_TOTAL);
            vTotMontoDevueltosCIVA += parseInt(value.TOTAL_CON_IVA);
            vcolor = `class="orange"`;
        } else if (value.ESTADO === 'PENDIENTE') {
            vTotCantPend += parseInt(value.CANTIDAD);
            vTotMontoPend += parseInt(value.MONTO_TOTAL);
            vTotMontoPendCIVA += parseInt(value.TOTAL_CON_IVA);
            vcolor = `class="red"`;
        } else if (value.ESTADO === 'FACTURADO') {
            vTotCantFact += parseInt(value.CANTIDAD);
            vTotMontoFact += parseInt(value.MONTO_TOTAL);
            vTotMontoFactCIVA += parseInt(value.TOTAL_CON_IVA);
            vcolor = `class="green"`;
        }
        ;
        datos += `<tr>
                    <td>` + value.TIP_COMPROBANTE + ' ' + value.SER_COMPROBANTE + ' ' + value.NRO_COMPROBANTE + `</td>
                    <td>` + value.COD_PEDIDO_WEB + `</td>
                    <td>` + value.FEC_COMPROBANTE + `</td>
                    <td>` + value.COD_CLIENTE + `</td>
                    <td>` + value.NOM_CLIENTE + `</td>
                    <td>` + value.TEL_CLIENTE + `</td>
                    <td>` + value.DIRECCION + `</td>
                    <td>` + value.COD_ARTICULO + `</td>
                    <td>` + value.DESCRIPCION + `</td>
                    <td>` + value.COD_COLOR + `</td>
                    <td>` + value.COD_TALLA + `</td>
                    <td>` + value.CANTIDAD + `</td>
                    <td>` + value.MONTO_TOTAL + `</td>
                    <td>` + value.TOTAL_CON_IVA + `</td>
                    <td  ` + vcolor + `>` + value.ESTADO + `</td>
                    <td>` + value.USR_CALL + `</td>
                    <td>` + value.COD_CALL + `</td>
                    <td>` + value.DESC_CALL + `</td>
                </tr>`;
        // console.log(datos);
    });
    $('#tbody_listar').html(datos);
    $('#cant_pend').text(vTotCantPend);
    $('#tot_pend').text(vTotMontoPend);
    $('#tot_pend_c_iva').text(vTotMontoPendCIVA);
    $('#cant_dev').text(vTotCantDevueltos);
    $('#mon_dev').text(vTotMontoDevueltos);
    $('#mon_dev_c_iva').text(vTotMontoDevueltosCIVA);
    $('#cant_fact').text(vTotCantFact);
    $('#mon_fact').text(vTotMontoFact);    
    $('#mon_fact_c_iva').text(vTotMontoFactCIVA);
    $('#cant_tot').text(vTotCant);
    $('#mon_tot').text(vTotMonto);    
    $('#mon_tot_c_iva').text(vTotMontoCIVA);
    formatoGs();
    init_DataTables();
    // cargar resumen
    
    
    swalCerrar();
}


function init_DataTables() {

//console.log('run_datatables');

    if (typeof ($.fn.DataTable) === 'undefined') {
        return;
    }
//console.log('init_DataTables');

    var handleDataTableButtons = function () {
        if ($("#dtListarPedidos").length) {
            $("#dtListarPedidos").DataTable({
                dom: "Blfrtip",
                buttons: [
                    {
                        extend: "excel",
                        className: "btn-lg btn-primary"
                    },
                    {
                        extend: "print",
                        className: "btn-lg btn-primary"
                    }
                ],
                language: {
                    processing: "Procesando...",
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ Registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ Registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 Registros",
                    infoFiltered: "(Filtrando de _MAX_ registros del total)",
                    infoPostFix: "",
                    loadingRecords: "Cargando..",
                    zeroRecords: "No hay registros",
                    emptyTable: "No hay registros",
                    paginate: {
                        first: "Primero",
                        previous: "Anterior",
                        next: "Siguiente",
                        last: "Ultimo"
                    },
                    aria: {
                        sortAscending: ": Odenar Ascendente",
                        sortDescending: ": denar Descendente"
                    },
                    buttons: {
                        print: '<i class="fa fa-print"></i>',
                        excel: '<i class="fa fa-file-excel-o"></i>'
                    }
                },
                formatNumber: function (toFormat) {
                    return toFormat.toString().replace(
                            /\B(?=(\d{3})+(?!\d))/g, "'"
                            );
                },
                responsive: false,
                autoWidth: false,
                scrollX: true
            });
        }
    };
    TableManageButtons = function () {
        "use strict";
        return {
            init: function () {
                handleDataTableButtons();
            }
        };
    }();
    TableManageButtons.init();
}
;
