$(document).ready(function () {
    inicializar();
    //editable();
});
var pedido = {};
var pedido_cab = {};
var pedido_det = [{}];
var pedido_totales = {};
var vOrden = 0;
function inicializar() {

    formatoGs();
    formatoNro();
    //console.log(moment(new Date()).format("YYYY-MM-DD"));
    siguienteCampo('#DOCUMENTO', '#TIPO_PAGO', false);
    siguienteCampo('#COD_CONDICION_VENTA', '#COD_METODO', false);
    siguienteCampo('#COD_METODO', '#OBSERVACION', false);
    siguienteCampo('#OBSERVACION', '#COD_CLIENTE', false);
    siguienteCampo('#COD_CLIENTE', '#COD_DIRECCION', false);
    siguienteCampo('#COD_DIRECCION', '#RUC', false);
    siguienteCampo('#RUC', '#TEL_CLIENTE', false);
    siguienteCampo('#TEL_CLIENTE', '#COD_ARTICULO_AGREGAR', false);
    siguienteCampo('#COD_ARTICULO_AGREGAR', '#COD_COLOR_AGREGAR', false);
    siguienteCampo('#COD_COLOR_AGREGAR', '#COD_TALLA_AGREGAR', false);
    siguienteCampo('#COD_TALLA_AGREGAR', '#CANTIDAD_AGREGAR', false);
    siguienteCampo('#CANTIDAD_AGREGAR', '#BTN-AGREGAR', false);

    if ($('#FEC_COMPROBANTE').val() === "") {
        $('#FEC_COMPROBANTE').val(moment(new Date()).format("YYYY-MM-DD"));
    }

    $('.dt').datetimepicker({
        format: 'DD/MM/YYYY'
    });

    $('.hr').datetimepicker({
        format: 'HH:mm'
    });

    //Cliente
    $("#DOCUMENTO").on('change', function () {
        BuscarDatosCliente();
    });

    $("#COD_CLIENTE").on('change', function () {
        BuscarDatosCliente();
    });

    $('#COD_CLIENTE').on('keydown', function (event) { // funcion callback , anonima
        //console.log(event.which);
        if (event.which === 120) {
            listarClientes();
        }
        ;
    });

    //Condicion Venta
    $("#COD_CONDICION_VENTA").val(1);
    BuscarDatosCondicionVentas();
    $("#COD_CONDICION_VENTA").on('change', function () {
        BuscarDatosCondicionVentas();
    });

    $('#COD_CONDICION_VENTA').on('keydown', function (event) { // funcion callback , anonima
        if (event.which === 120) {
            buscarCondicionesVentas();
        }
        ;
    });
    // moneda
    $("#COD_MONEDA").val(1);
    BuscarDatosMonedas();

    $("#COD_MONEDA").on('change', function () {
        BuscarDatosMonedas();
    });

    $('#COD_MONEDA').on('keydown', function (event) { // funcion callback , anonima
        if (event.which === 120) {
            listarMonedas();
        }
        ;
    });

    // TIPOS DE DOCUMENTOS

    $("#TIP_DOCUMENTO").on('change', function () {
        BuscarTipoDocumento();
    });

    $('#TIP_DOCUMENTO').on('keydown', function (event) { // funcion callback , anonima
        if (event.which === 120) {
            listarTiposDocumentos();
        }
        ;
    });

    // METODOS PAGO

    $("#COD_METODO").on('change', function () {
        BuscarMetodoPago();
    });

    $('#COD_METODO').on('keydown', function (event) { // funcion callback , anonima
        if (event.which === 120) {
            listarMetodosPago();
        }
        ;
    });

    // DIRECCIONES
    $("#COD_DIRECCION").on('change', function () {
        BuscarDireccion();
    });

    $('#COD_DIRECCION').on('keydown', function (event) { // funcion callback , anonima
        if (event.which === 120) {
            listarDirecciones();
        }
        ;
    });

    // ARTICULOS
    $("#COD_ARTICULO_AGREGAR").on('change', function () {
        BuscarArticulo();
        //console.log('asd');
        if (!empty($("#COD_ARTICULO_AGREGAR").val()) /*&& empty($("#PRECIO_UNITARIO_AGREGAR").val())*/) {
            //console.log("Si.. LPM");
            buscarPrecioAjax();
        } else {
            //console.log("Nooo");
        }
        //console.log((!empty($("#COD_ARTICULO_AGREGAR").val()) && empty($("#PRECIO_UNITARIO_AGREGAR").val())));
    });

    $('#COD_ARTICULO_AGREGAR').on('keydown', function (event) { // funcion callback , anonima
        if (event.which === 120) {
            listarArticulos();
        }
        ;
    });
    // CANTIDAD
    $('#CANTIDAD_AGREGAR').on('change', function (event) { // funcion callback , anonima
        calculos();
    });


    // COLORES
    $("#COD_COLOR_AGREGAR").on('change', function () {
        buscarPrecioAjax();
    });

    $('#COD_COLOR_AGREGAR').on('keydown', function (event) { // funcion callback , anonima
        if (event.which === 120) {
            listarColoresTallas();
        }
        ;
    });

    // TALLAS
    $("#COD_TALLA_AGREGAR").on('change', function () {
        BuscarColores();
    });

    $('#COD_TALLA_AGREGAR').on('keydown', function (event) { // funcion callback , anonima
        if (event.which === 120) {
            listarColoresTallas();
        }
        ;
    });

    // DELIVERY
    $('#COD_DELIVERY').on('change', function (event) { // funcion callback , anonima
        precioDeliveryAjax();
    });
    listarDeliveryAjax();

    $(document).ready(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    // PORC DESCUENTO

    $("#PORC_DESCUENTO_AGREGAR").on('change', function () {
        ValidarPorc();
    });

    validaDocs();
    
    $('#OMITIR_OFERTA').click();
    $('#CAMBIAR_NOMBRE').click();
    $('#DOCUMENTO').focus();
    $('#DOCUMENTO').select();
}

function ValidarPorc() {
    if (!empty($("#PORC_DESCUENTO_AGREGAR").val())) {
        if (parseInt($("#PORC_DESCUENTO_AGREGAR").val()) < 0 || parseInt($("#PORC_DESCUENTO_AGREGAR").val()) > 100) {
            swalError('', 'El descuento puede ser de 0 a 100');
            $("#PORC_DESCUENTO_AGREGAR").val(0);
        } else {
            BuscarLimiteDescuento();
        }
    } else {
        $("#PORC_DESCUENTO_AGREGAR").val(0);
        calculos();
    }
}


/*** CLIENTES ***/
function BuscarDatosCliente() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = 'api/getdatoscliente';
    var pBeforeSend = "swalCargando('Buscando cliente')";
    var pSucces = "BuscarDatosClienteAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pUrl);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarDatosClienteAjaxSuccess(json) {
    swalCerrar();
    tieneAcceso(json["acceso"]);
    //console.log(json);
    if (!empty(json["datos"])) {
        var datos = json["datos"][0];
        $('#COD_CLIENTE').val(datos.COD_CLIENTE);
        $('#COD_DIRECCION').val(datos.COD_DIRECCION);
        $('#DESC_CLIENTE').val(datos.DESC_CLIENTE);
        $('#NOM_CLIENTE').val(datos.DESC_CLIENTE);
        $('#DESC_DIRECCION').val(datos.DESC_DIRECCION);
        $('#CIUDAD').val(datos.CIUDAD);
        $('#BARRIO').val(datos.BARRIO);
        $('#RUC').val(datos.RUC);
        $('#TEL_CLIENTE').val(datos.TEL_CLIENTE);
        BuscarDatosCondicionVentas();
        $('#COD_MONEDA').val(datos.COD_MONEDA);
        BuscarDatosMonedas();
        $('#TIP_DOCUMENTO').val(datos.TIP_DOCUMENTO);
        BuscarTipoDocumento();
        $('#DOCUMENTO').val('');
    } else {
        // Dar de alta 
        altaClientes();
        $('#DOCUMENTO').focus();
    }


}

function listarClientes() {
    $('#buscado').append('<div id="modalClientes"></div>');
    $.get("frm/pedidos/buscar_clientes.html", function (htmlexterno) {
        $("#modalClientes").html(htmlexterno);
        $("#buscar_texto").on('change', function () {
            $('#pagina').val(1);
            listarClientesAjax();
        });
        //console.log((('Modal: ' + $("#modalBienes").html());
        $('#divModal').modal('show');
        $('#divModal').on('hidden.bs.modal', function (e) {
            $('#modalClientes').remove();
            $("#COD_CLIENTE").focus();
        });
    });
}

function listarClientesAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/getdatoscliente";
    var pBeforeSend = "";
    var pSucces = "listarClientesAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function listarClientesAjaxSuccess(json) {
    var datos = "";
    //console.log(json);
    $datos = json["datos"];
    $.each($datos, function (key, value) {
        datos += "<tr onclick='seleccionar_cliente($(this))'>";
        datos += "<td>" + value.DESC_CLIENTE + "</td>";
        datos += "<td>" + value.COD_CLIENTE + "</td>";
        datos += "<td>" + value.RUC + "</td>";
        datos += "</tr>";
    });
    if (datos === '') {
        datos += "<tr><td colspan='4'>No existen mas registros ...</td></tr>";
    }
    $('#tbody_listar').html(datos);
}

function seleccionar_cliente($this) {
    //console.log($this.find('td'));
    var DESC_CLIENTE = $this.find('td').eq(0).text();
    var COD_CLIENTE = $this.find('td').eq(1).text();
    var RUC = $this.find('td').eq(2).text();
    $("#DESC_CLIENTE").val(DESC_CLIENTE);
    $("#COD_CLIENTE").val(COD_CLIENTE);
    $("#RUC").val(RUC);
    $("#COD_CLIENTE").focus();
    salir_busqueda_modal();
    BuscarDatosCliente();
}
/*** /CLIENTES ***/

/*** CONDICION DE VENTA ***/

function BuscarDatosCondicionVentas() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = 'api/getCondicionVenta';
    var pBeforeSend = "";
    var pSucces = "BuscarDatosCondicionVentasSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pUrl);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarDatosCondicionVentasSuccess(json) {
    tieneAcceso(json["acceso"]);
    //console.log(json);
    if (!empty(json["datos"])) {
        var datos = json["datos"][0];
        $('#DESC_CONDICION').val(datos.DESC_CONDICION);
    }

}

function buscarCondicionesVentas() {
    $('#buscado').append('<div id="modalCondiciones"></div>');
    $.get("frm/pedidos/buscar_condiciones_ventas.html", function (htmlexterno) {
        $("#modalCondiciones").html(htmlexterno);
        //console.log((('Modal: ' + $("#modalBienes").html());
        $('#divModal').modal('show');
        $('#divModal').on('hidden.bs.modal', function (e) {
            $('#modalCondiciones').remove();
        });
    });
}

function BuscarCondicionesVentasAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/listarCondicionesVentas";
    var pBeforeSend = "";
    var pSucces = "BuscarCondicionesVentasAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarCondicionesVentasAjaxSuccess(json) {
    var datos = "";
    //console.log(json);
    $datos = json["datos"];
    $.each($datos, function (key, value) {
        datos += "<tr onclick='seleccionar_condicion($(this))'>";
        datos += "<td>" + value.DESC_CONDICION + "</td>";
        datos += "<td>" + value.COD_CONDICION_VENTA + "</td>";
        datos += "</tr>";
    });
    if (datos === '') {
        datos += "<tr><td colspan='4'>No existen mas registros ...</td></tr>";
    }
    $('#tbody_listar_condiciones').html(datos);
}

function seleccionar_condicion($this) {
    //console.log($this.find('td').eq(0).text());
    var desc_condicion = $this.find('td').eq(0).text();
    var cod_condicion = $this.find('td').eq(1).text();
    $("#COD_CONDICION_VENTA").val(cod_condicion);
    $("#DESC_CONDICION").val(desc_condicion);
    salir_busqueda_modal();

    /*
     $("#id_aseguradora").val(id);
     $("#id_persona").val(id);
     $("#nombre_persona").val(nombre);
     
     $("#nombre_persona").focus();
     $("#nombre_persona").select();*/
}

/*** /CONDICIONES DE VENTAS ***/


/*** MONEDAS ***/
function listarMonedas() {
    $('#buscado').append('<div id="modalMonedas"></div>');
    $.get("frm/pedidos/buscar_monedas.html", function (htmlexterno) {
        $("#modalMonedas").html(htmlexterno);
        //console.log((('Modal: ' + $("#modalBienes").html());
        $('#divModal').modal('show');
        $('#divModal').on('hidden.bs.modal', function (e) {
            $('#modalMonedas').remove();
        });
    });
}

function listarMonedasAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/listarMonedas";
    var pBeforeSend = "";
    var pSucces = "listarMonedasAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function listarMonedasAjaxSuccess(json) {
    var datos = "";
    //console.log(json);
    $datos = json["datos"];
    $.each($datos, function (key, value) {
        datos += "<tr onclick='seleccionar_moneda($(this))'>";
        datos += "<td>" + value.DESCRIPCION + "</td>";
        datos += "<td>" + value.COD_MONEDA + "</td>";
        datos += "<td class='gs'>" + value.TIP_CAMBIO + "</td>";
        datos += "</tr>";
    });
    if (datos === '') {
        datos += "<tr><td colspan='4'>No existen mas registros ...</td></tr>";
    }
    $('#tbody_listar_monedas').html(datos);
    formatoGs();
}

function seleccionar_moneda($this) {
    //console.log($this.find('td').eq(0).text());
    var descripcion = $this.find('td').eq(0).text();
    var cod_moneda = $this.find('td').eq(1).text();
    var tip_cambio = $this.find('td').eq(2).text();
    $("#COD_MONEDA").val(cod_moneda);
    $("#DESC_MONEDA").val(descripcion);
    $("#TIP_CAMBIO").val(tip_cambio);
    salir_busqueda_modal();
    formatoGs();
}

function BuscarDatosMonedas() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = "api/listarMonedas";
    var pBeforeSend = "";
    var pSucces = "BuscarDatosMonedasAjaxSucces(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pDatosCliente);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarDatosMonedasAjaxSucces(json) {
    //console.log(json);
    tieneAcceso(json["acceso"]);
    //console.log(json);
    if (!empty(json["datos"])) {
        var datos = json["datos"][0];
        $('#DESC_MONEDA').val(datos.DESCRIPCION);
        $('#TIP_CAMBIO').val(datos.TIP_CAMBIO);
    }
    formatoGs();
}
/*** / MONEDAS***/

/*** TIPOS DE DOCUMENTOS ***/
function listarTiposDocumentos() {
    $('#buscado').append('<div id="modalTiposDocumentos"></div>');
    $.get("frm/pedidos/buscar_tipos_documentos.html", function (htmlexterno) {
        $("#modalTiposDocumentos").html(htmlexterno);
        //console.log((('Modal: ' + $("#modalBienes").html());
        $('#divModal').modal('show');
        $('#divModal').on('hidden.bs.modal', function (e) {
            $('#modalTiposDocumentos').remove();
            $("#TIP_DOCUMENTO").focus();
        });
    });
}

function listarTiposDocumentosAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/listarTiposDocumentos?COD_CONDICION_VENTA=" + $('#COD_CONDICION_VENTA').val();
    var pBeforeSend = "";
    var pSucces = "listarTiposDocumentosAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function listarTiposDocumentosAjaxSuccess(json) {
    var datos = "";
    //console.log(json);
    $datos = json["datos"];
    $.each($datos, function (key, value) {
        datos += "<tr onclick='seleccionar_tipos_documentos($(this))'>";
        datos += "<td>" + value.DESC_FORMA_PAGO + "</td>";
        datos += "<td>" + value.COD_FORMA_PAGO + "</td>";
        datos += "</tr>";
    });
    if (datos === '') {
        datos += "<tr><td colspan='4'>No existen mas registros ...</td></tr>";
    }
    $('#tbody_listar_condiciones').html(datos);
    formatoGs();
}

function seleccionar_tipos_documentos($this) {
    //console.log($this.find('td').eq(0).text());
    var descripcion = $this.find('td').eq(0).text();
    var cod_forma_pago = $this.find('td').eq(1).text();
    $("#TIP_DOCUMENTO").val(cod_forma_pago);
    $("#DESC_DOCUMENTO").val(descripcion);
    $("#TIP_DOCUMENTO").focus();
    salir_busqueda_modal();

    /*
     $("#id_aseguradora").val(id);
     $("#id_persona").val(id);
     $("#nombre_persona").val(nombre);
     
     $("#nombre_persona").focus();
     $("#nombre_persona").select();*/
}

function BuscarTipoDocumento() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = "api/listarTiposDocumentos?COD_CONDICION_VENTA=" + $('#COD_CONDICION_VENTA').val();
    var pBeforeSend = "";
    var pSucces = "BuscarTipoDocumentoAjaxSucces(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pDatosCliente);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarTipoDocumentoAjaxSucces(json) {
    //console.log(json);
    tieneAcceso(json["acceso"]);
    //console.log(json);
    if (!empty(json["datos"])) {
        var datos = json["datos"][0];
        $("#TIP_DOCUMENTO").val(datos.COD_FORMA_PAGO);
        $("#DESC_DOCUMENTO").val(datos.DESC_FORMA_PAGO);
    }
    formatoGs();
}
/*** /TIPOS DE DOCUMENTOS ***/

/*** METODOS PAGO ***/
function listarMetodosPago() {
    $('#buscado').append('<div id="modalMetodosPago"></div>');
    $.get("frm/pedidos/buscar_metodos_pagos.html", function (htmlexterno) {
        $("#modalMetodosPago").html(htmlexterno);
        //console.log((('Modal: ' + $("#modalBienes").html());
        $('#divModal').modal('show');
        $('#divModal').on('hidden.bs.modal', function (e) {
            $('#modalMetodosPago').remove();
            $("#COD_METODO").focus();
        });
    });
}

function listarMetodosPagoAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/listarMetodosPago?TIPO_PAGO=" + $('#TIPO_PAGO').val();
    //console.log(pDatosFormulario);
    var pBeforeSend = "";
    var pSucces = "listarMetodosPagoAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function listarMetodosPagoAjaxSuccess(json) {
    var datos = "";
    //console.log(json);
    $datos = json["datos"];
    $.each($datos, function (key, value) {
        datos += "<tr onclick='seleccionar_metodos_pago($(this))'>";
        datos += "<td>" + value.DESC_METODO + "</td>";
        datos += "<td>" + value.COD_METODO + "</td>";
        datos += "</tr>";
    });
    if (datos === '') {
        datos += "<tr><td colspan='4'>No existen mas registros ...</td></tr>";
    }
    $('#tbody_listar').html(datos);
}

function seleccionar_metodos_pago($this) {
    //console.log($this.find('td').eq(0).text());
    var descripcion = $this.find('td').eq(0).text();
    var cod_forma_pago = $this.find('td').eq(1).text();
    $("#COD_METODO").val(cod_forma_pago);
    $("#DESC_METODO").val(descripcion);
    salir_busqueda_modal();
}

function BuscarMetodoPago() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = "api/listarMetodosPago?TIPO_PAGO=" + $('#TIPO_PAGO').val();
    //console.log(pUrl);
    var pBeforeSend = "";
    var pSucces = "BuscarMetodoPagoAjaxSucces(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pDatosCliente);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarMetodoPagoAjaxSucces(json) {
    //console.log(json);
    tieneAcceso(json["acceso"]);
    //console.log(json);
    if (!empty(json["datos"])) {
        var datos = json["datos"][0];
        $("#COD_METODO").val(datos.COD_METODO);
        $("#DESC_METODO").val(datos.DESC_METODO);
    }
}
/*** /METODOS PAGO***/


/*** DIRECCIONES ***/
function listarDirecciones() {
    $('#buscado').append('<div id="modalDirecciones"></div>');
    $.get("frm/pedidos/buscar_direcciones.html", function (htmlexterno) {
        $("#modalDirecciones").html(htmlexterno);
        //console.log((('Modal: ' + $("#modalBienes").html());
        $('#divModal').modal('show');
        $('#divModal').on('hidden.bs.modal', function (e) {
            $('#modalDirecciones').remove();
            $("#COD_DIRECCION").focus();
        });
    });
}

function listarDireccionesAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/listarDirecciones?COD_CLIENTE=" + $('#COD_CLIENTE').val();
    var pBeforeSend = "";
    var pSucces = "listarDireccionesAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function listarDireccionesAjaxSuccess(json) {
    var datos = "";
    //console.log(json);
    $datos = json["datos"];
    $.each($datos, function (key, value) {
        datos += "<tr onclick='seleccionar_Direcciones($(this))'>";
        datos += "<td>" + value.DESC_DIRECCION + "</td>";
        datos += "<td>" + value.CIUDAD + "</td>";
        datos += "<td>" + value.BARRIO + "</td>";
        datos += "<td>" + value.COD_DIRECCION + "</td>";
        datos += "</tr>";
    });
    if (datos === '') {
        datos += "<tr><td colspan='4'>No existen mas registros ...</td></tr>";
    }
    $('#tbody_listar').html(datos);
}

function seleccionar_Direcciones($this) {
    //console.log($this.find('td'));
    var descripcion = $this.find('td').eq(0).text();
    var ciudad = $this.find('td').eq(1).text();
    var barrio = $this.find('td').eq(2).text();
    var cod_direccion = $this.find('td').eq(3).text();
    $("#COD_DIRECCION").val(cod_direccion);
    $("#DESC_DIRECCION").val(descripcion);
    $("#CIUDAD").val(ciudad);
    $("#BARRIO").val(barrio);
    $("#COD_DIRECCION").focus();
    salir_busqueda_modal();

}

function BuscarDireccion() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = "api/listarDirecciones?COD_CLIENTE=" + $('#COD_CLIENTE').val();
    var pBeforeSend = "";
    var pSucces = "BuscarDireccionAjaxSucces(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pDatosCliente);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarDireccionAjaxSucces(json) {
    //console.log(json);
    tieneAcceso(json["acceso"]);
    if (!empty(json["datos"])) {
        var datos = json["datos"][0];
        $("#COD_DIRECCION").val(datos.COD_DIRECCION);
        $("#DESC_DIRECCION").val(datos.DESC_DIRECCION);
    }
}
/*** /DIRECCIONES ***/

/****  ALTA DE CLIENTES *****/
function altaClientes() {
    $('#buscado').append('<div id="modalAltaClientes"></div>');
    $.get("frm/pedidos/alta_cliente.html", function (htmlexterno) {
        $("#modalAltaClientes").html(htmlexterno);
        //console.log((('Modal: ' + $("#modalBienes").html());
        $('#divModal').modal('show');
        $('#ALTA_NOMBRE').focus();
        //console.log($('#DOCUMENTO').val().indexOf("-"));
        if ($('#DOCUMENTO').val().indexOf("-") > 0) {
            $('#ALTA_RUC').val($('#DOCUMENTO').val());
        } else {
            $('#ALTA_CI').val($('#DOCUMENTO').val());
        }
        $('#divModal').on('hidden.bs.modal', function (e) {
            $('#modalAltaClientes').remove();
        });
    });
}
/**** /ALTA DE CLIENTES *****/


/******* DETALLES *********/
// ARTICULOS
function listarArticulos() {
    /*$('#buscado').append('<div id="modalArticulos"></div>');
     $.get("frm/pedidos/buscar_articulo.html", function (htmlexterno) {
     $("#modalArticulos").html(htmlexterno);
     //console.log((('Modal: ' + $("#modalBienes").html());
     $('#divModal').modal('show');
     $('#divModal').on('hidden.bs.modal', function (e) {
     $('#modalArticulos').remove();
     $("#COD_ARTICULO").focus();
     });
     });*/
    cargar_busqueda('frm/pedidos/buscar_articulo.html');
    //console.log('+++');
    /*$("#buscar_texto").on('change', function () {
     console.log('+++');
     listarArticulosAjaxBtn();
     });**/
}

function listarArticulosAjaxBtn() {
    //console.log('-----');
    $('#pagina').val(1);
    listarArticulosAjax();
}

function listarArticulosAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/listarArticulos";
    var pBeforeSend = "";
    var pSucces = "listarArticulosAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function listarArticulosAjaxSuccess(json) {
    var datos = "";
    //console.log(json);
    $datos = json["datos"];
    $.each($datos, function (key, value) {

        datos += '<div class="col-md-55 sel-articulo" onclick="seleccionar_articulos($(this))">';
        datos += '     <div class="thumbnail centrado">';
        datos += '          <div class="image view view-first img-galeria">';
        datos += '              <img style="width: 90%; display: block;" src="' + value.IMAGEN + '" alt="image" />';
        datos += '           </div>';
        datos += '           <div class="caption">';
        datos += '                <p><b id="COD_ARTICULO_PRE">' + value.COD_ARTICULO + '</b></p>';
        datos += '                <p id="DESC_ARTICULO_PRE">' + value.DESCRIPCION + '</p>';
        datos += '                <p id="CANT_EXI_PRE"> Stock:' + value.CANTIDAD + '</p>';
        datos += '                <p id="COD_UNIDAD_MEDIDA" class="oculto"> Stock:' + value.COD_UNIDAD_MEDIDA + '</p>';
        datos += '           </div>';
        datos += '      </div>';
        datos += '</div>';


        /*datos += "<tr onclick='seleccionar_articulos($(this))'>";
         datos += "<td>" + value.DESC_DIRECCION + "</td>";
         datos += "<td>" + value.CIUDAD + "</td>";
         datos += "<td>" + value.COD_DIRECCION + "</td>";
         datos += "</tr>";*/
    });
    if (datos === '') {
        datos += "No existen mas registros ...";
    }
    $('#divListarArticulos').html(datos);
    formatoGs();
}

function seleccionar_articulos($this) {
    //console.log($this.find('p'));
    var cod_articulo = $this.find('p').eq(0).text();
    var descripcion = $this.find('p').eq(1).text();
    var cod_unidad_medida = $this.find('p').eq(1).text();
    $("#COD_ARTICULO_AGREGAR").val(cod_articulo);
    $("#DESC_ARTICULO_AGREGAR").val(descripcion);
    $("#COD_UNIDAD_MEDIDA_AGREGAR").val(cod_unidad_medida);
    salir_busqueda();
    $("#COD_ARTICULO_AGREGAR").focus();
    if (!empty($("#COD_ARTICULO_AGREGAR").val()) /*&& empty($("#PRECIO_UNITARIO_AGREGAR").val())*/) {
        buscarPrecioAjax();
    }
    //console.log(cod_articulo);

}

function BuscarArticulo() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = "api/listarArticulos?COD_ARTICULO=" + $("#COD_ARTICULO_AGREGAR").val();
    ;
    var pBeforeSend = "";
    var pSucces = "BuscarArticuloAjaxSucces(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pDatosCliente);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarArticuloAjaxSucces(json) {
    //console.log(json);
    tieneAcceso(json["acceso"]);
    if (!empty(json["datos"])) {
        var datos = json["datos"][0];
        $("#DESC_ARTICULO_AGREGAR").val(datos.DESCRIPCION);
    }
    if (!empty($("#COD_ARTICULO_AGREGAR").val()) /*&& empty($("#PRECIO_UNITARIO_AGREGAR").val())*/) {
        buscarPrecioAjax();
    }
}

/*** COLORES Y TALLAS ***/
function listarColoresTallas() {
    //console.log('Listando');
    $('#buscado').append('<div id="modalColoresTallas"></div>');
    $.get("frm/pedidos/buscar_colores_tallas.html", function (htmlexterno) {
        $("#modalColoresTallas").html(htmlexterno);
        //console.log((('Modal: ' + $("#modalBienes").html());
        $('#divModal').modal('show');
        $('#divModal').on('hidden.bs.modal', function (e) {
            $('#modalColoresTallas').remove();
            $("#COD_COLOR_AGREGAR").focus();
        });
    });
}

function listarColoresTallasAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/listarColoresTallas?COD_ARTICULO=" + $('#COD_ARTICULO_AGREGAR').val();
    var pBeforeSend = "";
    var pSucces = "listarColoresTallasAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function listarColoresTallasAjaxSuccess(json) {
    var datos = "";
    //console.log(json);
    $datos = json["datos"];
    $.each($datos, function (key, value) {
        datos += "<tr onclick='seleccionar_colores_tallas($(this))'>";
        datos += "<td>" + value.COD_COLOR + "</td>";
        datos += "<td>" + value.DESC_COLOR + "</td>";
        datos += "<td>" + value.COD_TALLA + "</td>";
        datos += "<td>" + value.EXISTENCIA + "</td>";
        datos += "</tr>";
    });
    if (datos === '') {
        datos += "<tr><td colspan='4'>No existen mas registros ...</td></tr>";
    }
    $('#tbody_listar').html(datos);
    formatoGs();
}

function seleccionar_colores_tallas($this) {
    //console.log($this.find('td'));
    var cod_color = $this.find('td').eq(0).text();
    var cod_talla = $this.find('td').eq(2).text();
    $("#COD_COLOR_AGREGAR").val(cod_color);
    $("#COD_TALLA_AGREGAR").val(cod_talla);
    $("#COD_COLOR_AGREGAR").focus();
    salir_busqueda_modal();
    buscarPrecioAjax();
    /*
     $("#id_aseguradora").val(id);
     $("#id_persona").val(id);
     $("#nombre_persona").val(nombre);
     
     $("#nombre_persona").focus();
     $("#nombre_persona").select();*/
}

function BuscarTipoDocumentoTallas() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = "api/listarTiposDocumentos?COD_CONDICION_VENTA=" + $('#COD_CONDICION_VENTA').val();
    var pBeforeSend = "";
    var pSucces = "BuscarTipoDocumentoAjaxSucces(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pDatosCliente);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarTipoDocumentoAjaxSucces(json) {
    //console.log(json);
    tieneAcceso(json["acceso"]);
    //console.log(json);
    if (!empty(json["datos"])) {
        var datos = json["datos"][0];
        $("#TIP_DOCUMENTO").val(datos.COD_FORMA_PAGO);
        $("#DESC_DOCUMENTO").val(datos.DESC_FORMA_PAGO);
    }
    formatoGs();
}
/*** /COLORES Y TALLAS ***/


/*** CONSULTAR PRECIO ***/
function buscarPrecioAjax() {
    var vcheck;
    if ($('#OMITIR_OFERTA').prop('checked')) {
        vcheck = 'S';
    } else {
        vcheck = 'N';
    }

    //console.log('Buscando precio');
    var pDatosFormulario = " ";//;$("#form-buscar").serialize();
    var pUrl = "api/consultaPrecio?COD_ARTICULO_AGREGAR=" + $('#COD_ARTICULO_AGREGAR').val() + "&COD_COLOR_AGREGAR=" + $('#COD_COLOR_AGREGAR').val() + "&OMITIR_OFERTA=" + vcheck;
    var pBeforeSend = "";
    var pSucces = "buscarPrecioAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pUrl);
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function buscarPrecioAjaxSuccess(json) {
    var datos = "";
    //console.log(json);
    datos = json["datos"][0];
    //console.log($datos.PRECIO);
    $('#PRECIO_UNITARIO_AGREGAR').val(datos.PRECIO);
    $('#CANTIDAD_AGREGAR').val(1);
    $('#CANTIDAD_AGREGAR').focus();
    calculos();
}

/***** /CONSULTA PRECIO ******/

/*** BUSCAR LIMITE DE DESCUENTO ***/
function buscarLimiteAjax() {
    //console.log('Buscando precio');
    var pDatosFormulario = " ";//;$("#form-buscar").serialize();
    var pUrl = "api/consultaPrecio?COD_ARTICULO_AGREGAR=" + $('#COD_ARTICULO_AGREGAR').val();
    var pBeforeSend = "";
    var pSucces = "buscarPrecioAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function buscarbuscarLimiteAjaxAjaxSuccess(json) {
    var datos = "";
    //console.log(json);
    datos = json["datos"][0];
    //console.log($datos.PRECIO);
    $('#PRECIO_UNITARIO_AGREGAR').val(datos.PRECIO);
    $('#CANTIDAD_AGREGAR').val(1);
    $('#CANTIDAD_AGREGAR').focus();
    calculos();
}

/***** /BUSCAR LIMITE DE DESCUENTO******/

/****** BUSCAR EXISTENCIA  ***********/
function BuscarExistenciaArticulo() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = "api/consultaExistencia?COD_ARTICULO=" + $("#COD_ARTICULO_AGREGAR").val() + '&COD_COLOR=' + $("#COD_COLOR_AGREGAR").val() + '&COD_TALLA=' + $("#COD_TALLA_AGREGAR").val();
    var pBeforeSend = "";
    var pSucces = "BuscarExistenciaArticuloAjaxSucces(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pDatosCliente);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarExistenciaArticuloAjaxSucces(json) {
    //console.log(json);
    var vcant = 0;
    tieneAcceso(json["acceso"]);
    if (!empty(json["datos"])) {
        var datos = json["datos"][0];
        vcant = parseInt(datos.CANTIDAD);
        //console.log(parseInt($('#CANTIDAD_AGREGAR').val())+' '+vcant);
        if (parseInt($('#CANTIDAD_AGREGAR').val()) > vcant) {
            swalError('Error de existencia', 'La cantidad solicitada supera la existencia: ' + vcant);
        } else {
            agregarDetalle();
        }
    }

}
/****** /BUSCAR EXISTENCIA ***********/


/******* /AGREGAR DETALLE **********/
function verificarAgregarDetalle() {
    if (empty($('#COD_ARTICULO_AGREGAR').val())) {
        swalError('Debe agregar Articulo', '');
        return;
    }
    if (empty($('#COD_COLOR_AGREGAR').val())) {
        swalError('Debe agregar Color', '');
        return;
    }
    if (empty($('#COD_TALLA_AGREGAR').val())) {
        swalError('Debe agregar talla', '');
        return;
    }
    // verificar existencia
    BuscarExistenciaArticulo();

    return true;
}
function agregarDetalle() {
    //console.log();
    //if (verificarAgregarDetalle()) {
    this.vOrden += Number(1);
    var detalle = "";
    detalle += `<tr>
                <td class="gs" id="orden1">` + this.vOrden + `</td>
                <td>` + $('#COD_ARTICULO_AGREGAR').val() + `</td>
                <td>` + $('#DESC_ARTICULO_AGREGAR').val() + `</td>
                <td>` + $('#COD_COLOR_AGREGAR').val() + `</td>
                <td>` + $('#COD_TALLA_AGREGAR').val() + `</td>
                <td class="gs">` + $('#PRECIO_UNITARIO_AGREGAR').val() + `</td>
                <td class="gs">` + parseInt(sacarFormatoGs($('#PORC_DESCUENTO_AGREGAR').val())) + `</td>
                <td class="gs">` + $('#DESCUENTO_AGREGAR').val() + `</td>
                <td class="gs">` + $('#CANTIDAD_AGREGAR').val() + `</td>
                <td class="gs">` + $('#TOTAL_IVA_AGREGAR').val() + `</td>
                <td class="gs">` + $('#TOTAL_AGREGAR').val() + `</td>
                <th>
                    <div class="row">
                        <div class="col-12 centrado">
                            <button type="button"  class="btn btn-success btn-sm"><i class="fa fa-edit"></i></button>
                            <button type="button"  class="btn btn-danger btn-sm" onclick="borrarDetalle($(this).parent().parent().parent().parent())"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                </th>
            </tr>`;
    $("#tbody_detalle").append(detalle);
    vaciarAgregar();
    actualiza_detalle();
    //}
}
function vaciarAgregar() {
    $('#COD_ARTICULO_AGREGAR').val('');
    $('#DESC_ARTICULO_AGREGAR').val('');
    $('#COD_COLOR_AGREGAR').val('');
    $('#COD_TALLA_AGREGAR').val('');
    $('#PRECIO_UNITARIO_AGREGAR').val(0);
    $('#CANTIDAD_AGREGAR').val(0);
    $('#PORC_DESCUENTO_AGREGAR').val(0);
    $('#DESCUENTO_AGREGAR').val(0);
    $('#TOTAL_IVA_AGREGAR').val(0);
    $('#TOTAL_AGREGAR').val(0);
}
/**********************************/

/******* /BORRAR DETALLE **********/
function borrarDetalle($this) {
    //console.log($this);
    $this.remove();
    actualiza_detalle();
}
/**********************************/

/******* DELIVERY **********/
function listarDeliveryAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/listarDelivery";
    //console.log(pDatosFormulario);
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
    var pDatosFormulario = $("#form-delivery").serialize();
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
    actualiza_detalle();
    formatoGs();
}
/******** /DELIVERY ********/


function actualizar_todo() {
    actualiza_cabecera();
    actualiza_detalle();

    //(JSON.stringify(pedido));
}
function actualiza_cabecera() {
    var vcambia_nombre = "";
    if ($('#CAMBIAR_NOMBRE').prop('checked')) {
        vcambia_nombre = "S";
    } else {
        vcambia_nombre = "N";
    }
    pedido_cab['FEC_COMPROBANTE'] = $('#FEC_COMPROBANTE').val();
    pedido_cab['TIPO_PAGO'] = $('#TIPO_PAGO').val();
    pedido_cab['COD_METODO'] = $('#COD_METODO').val();
    pedido_cab['COD_CLIENTE'] = $('#COD_CLIENTE').val();
    pedido_cab['NRO_SOLICUTUD'] = $('#NRO_SOLICUTUD').val();
    pedido_cab['CAMBIAR_NOMBRE'] = vcambia_nombre;
    pedido_cab['NOM_CLIENTE'] = $('#NOM_CLIENTE').val();
    pedido_cab['COD_DIRECCION'] = $('#COD_DIRECCION').val();
    pedido_cab['DESC_DIRECCION'] = $('#DESC_DIRECCION').val();
    pedido_cab['RUC'] = $('#RUC').val();
    pedido_cab['TEL_CLIENTE'] = $('#TEL_CLIENTE').val();
    pedido_cab['USR_CALL'] = datosUsuario('USR_CALL');
    pedido_cab['COD_CALL'] = datosUsuario('COD_CALL');

    pedido_cab['COD_DELIVERY'] = $('#COD_DELIVERY').val();
    pedido_cab['FEC_ENTREGA'] = $('#fec_entrega').val();
    pedido_cab['HOR_ENTREGA'] = $('#hor_entrega').val();
    pedido_cab['MONTO_DELIVERY'] = sacarFormatoGs($('#monto_delivery').text());
    pedido_cab['OBSERVACION'] = $('#OBSERVACION').val();

    pedido['CABECERA'] = pedido_cab;
    //console.log(pedido);
    //console.log(JSON.stringify(pedido));

}

function actualiza_detalle() {
    //pedido['DETALLE'] = {};
    var filas = $('#tbody_detalle tr');
    var vnro = 0;
    //console.log(filas);    
    var detalle = {};
    pedido_det = [{}];
    //console.log(filas.find('td'));
    $.each(filas, function (index, fila) {
        //console.log($(this).find('td').eq(1).text());
        detalle = {};
        detalle['ORDEN'] = $(this).find('td').eq(0).text();
        detalle['COD_ARTICULO'] = $(this).find('td').eq(1).text();
        detalle['DESC_ARTICULO'] = $(this).find('td').eq(2).text();
        detalle['COD_COLOR'] = $(this).find('td').eq(3).text();
        detalle['COD_TALLA'] = $(this).find('td').eq(4).text();
        detalle['PRECIO_UNITARIO'] = sacarFormatoGs($(this).find('td').eq(5).text());
        detalle['PORC_DESCUENTO'] = sacarFormatoGs($(this).find('td').eq(6).text());
        detalle['DESCUENTO'] = sacarFormatoGs($(this).find('td').eq(7).text());
        detalle['CANTIDAD'] = sacarFormatoGs($(this).find('td').eq(8).text());
        detalle['TOTAL_IVA'] = sacarFormatoGs($(this).find('td').eq(9).text());
        detalle['MONTO_TOTAL'] = sacarFormatoGs($(this).find('td').eq(10).text());
        pedido_det[vnro] = detalle;
        vnro = vnro + 1;
    });
    //console.log(pedido_det);
    pedido['DETALLE'] = pedido_det;
    calculaTotales();
    //console.log(pedido['DETALLE']);
}

function calculaTotales() {
    //console.log(pedido['DETALLE']);
    var filas = pedido['DETALLE'];
    var vtotales = {};
    var vGravadas = 0;
    var vTotal_det = 0;
    var vTotal = 0;
    var vTotalIVA_det = 0;
    var vTotalIVA = 0;
    var vDescuento = 0;
    var vCantidad = 0;
    var vEnvio = 0;
    var vSubTotal = 0;
    //console.log(filas);
    $.each(filas, function (index, fila) {
        vGravadas = vGravadas + Math.round(fila.MONTO_TOTAL - fila.TOTAL_IVA, 0);
        vTotal_det = vTotal_det + Math.round(fila.MONTO_TOTAL, 0);
        vTotalIVA_det = vTotalIVA_det + Math.round(fila.TOTAL_IVA, 0);
        vDescuento = vDescuento + Math.round(fila.DESCUENTO, 0);
        vCantidad = vCantidad + Math.round(fila.CANTIDAD, 0);
    });
    //console.log(vGravadas);
    vEnvio = sacarFormatoGs($('#monto_delivery').text());

    vTotal = vTotal_det + vEnvio;
    vTotalIVA = Math.round(vTotal / 11, 0);
    vSubTotal = vGravadas + Math.round(vEnvio / 1.1);
    vtotales['DESCUENTO'] = vDescuento;
    vtotales['GRAVADAS'] = vGravadas;
    vtotales['ENVIO'] = vEnvio;
    vtotales['SUBTOTAL'] = vSubTotal;
    vtotales['IVA'] = vTotalIVA;
    vtotales['TOTAL'] = vTotal;

    pedido['TOTALES'] = vtotales;
    //console.log(vtotales);

    // Totales de tabla
    $('#total_descuento').text(vDescuento);
    $('#total_cantidad').text(vCantidad);
    $('#total_iva').text(vTotalIVA_det);
    $('#total_total').text(vTotal_det);
    //
    // Totales generales
    $('#res_descuento').text(vDescuento);
    $('#res_gravada').text(vGravadas);
    $('#res_envio').text(vEnvio);
    $('#res_subtotal').text(vSubTotal);
    $('#res_iva').text(vTotalIVA);
    $('#res_total').text(vTotal);


    formatoGs();
}


/****** CALCULOS  *******/
function calculos() {
    var vPrecioUni = sacarFormatoGs($('#PRECIO_UNITARIO_AGREGAR').val());
    var vCantidad = sacarFormatoGs($('#CANTIDAD_AGREGAR').val());
    var vDescuento = Math.round((vPrecioUni * vCantidad) * (parseInt($('#PORC_DESCUENTO_AGREGAR').val()) / 100));
    var vTotal = (vPrecioUni * vCantidad) - vDescuento;
    var vIva = Math.round(vTotal / 11);
    $('#DESCUENTO_AGREGAR').val(vDescuento);
    $('#TOTAL_AGREGAR').val(vTotal);
    $('#TOTAL_IVA_AGREGAR').val(vIva);
    formatoGs();
}

/****** /CALCULOS ******/
function salir_busqueda_modal() {
    $("#botonCancelar").click();
}

// GUARDAR
function guardar() {
    estaSeguroSwal('Desea guardar la solicitud?', 'GuardarPedido()', '#COD_CLIENTE');
}




function GuardarPedido() {
    actualizar_todo();
    var pDatos = JSON.stringify(pedido);
    var pUrl = 'api/guardarPedido';
    var pBeforeSend = "swalCargando('Guardar pedido')";
    var pSucces = "GuardarPedidoAjaxSuccess(json)";
    var pError = "errorGuardarAjax()";
    var pComplete = "";
    //console.log(pDatos);
    ajax(pDatos, pUrl, pBeforeSend, pSucces, pError, pComplete);
}
function errorGuardarAjax() {
    //console.log('ERROR ASD');
    swalCerrar();
}
function GuardarPedidoAjaxSuccess(json) {

    //console.log(json);
    swalCerrar();
    tieneAcceso(json["acceso"]);
    var $solicitud = json["datos"];
    //console.log(json);
    if (!empty(json["mensaje"])) {
        var mensaje = json["mensaje"];
        if (mensaje !== 'OK') {
            swalError('Error al guadar', mensaje);
        } else {
            swalCorrectoAccion("Guardado", 'Numero Generado: ' + $solicitud, 'nuevo_registro()');
        }
    }

}

function nuevo_registro() {
    cargar_formulario('frm/pedidos');
}


/****  ACTUALIZA CLIENTE *****/
function actualizaCliente() {
    if (!empty($('#COD_CLIENTE').val())) {
        cargar_inframe('frm/pedidos/modifica_cliente.html');
    }
}
function actualizacionCompleta() {
    BuscarDatosCliente();
    salir_inframe();
}
/**** /ACTUALIZA CLIENTE *****/




/****** BUSCAR LIMITE DE DESCUENTO  ***********/
function BuscarLimiteDescuento() {
    var pDatosCliente = '';//$("#pedido").serialize();
    var pUrl = "api/limiteDescuento?COD_ARTICULO=" + $("#COD_ARTICULO_AGREGAR").val() + '&PORC_DESCUENTO=' + $("#PORC_DESCUENTO_AGREGAR").val();
    var pBeforeSend = "";
    var pSucces = "BuscarLimiteDescuentoAjaxSucces(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pUrl);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarLimiteDescuentoAjaxSucces(json) {
    //console.log(json);
    var vcant = 0;
    tieneAcceso(json["acceso"]);

    if (!empty(json["datos"])) {
        var datos = json["datos"];
        //console.log(json);
        vcant = parseInt(datos);
        //console.log(datos);
        //console.log(parseInt(vcant)+' '+parseInt($('#PORC_DESCUENTO_AGREGAR').val()));
        if (parseInt($('#PORC_DESCUENTO_AGREGAR').val()) !== parseInt(vcant) && parseInt($('#PORC_DESCUENTO_AGREGAR').val()) !== 0) {
            $('#PORC_DESCUENTO_AGREGAR').val(0);
            calculos();
            swalError('', 'No puede usar este porcentaje, comuniquese con comercial');
        } else {
            calculos();
        }
    }
}
/****** /BUSCAR LIMITE DE DESCUENTO ***********/