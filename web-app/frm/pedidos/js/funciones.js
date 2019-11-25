$(document).ready(function () {
    inicializar();
    //editable();
});

function inicializar() {

    formatoGs();
    formatoNro();
    //console.log(moment(new Date()).format("YYYY-MM-DD"));
    siguienteCampo('#DOCUMENTO', '#COD_CONDICION_VENTA', false);
    siguienteCampo('#COD_CONDICION_VENTA', '#COD_MONEDA', false);
    siguienteCampo('#COD_MONEDA', '#TIP_DOCUMENTO', false);
    siguienteCampo('#TIP_DOCUMENTO', '#COD_CLIENTE', false);
    siguienteCampo('#COD_CLIENTE', '#COD_DIRECCION', false);
    siguienteCampo('#COD_DIRECCION', '#RUC', false);
    siguienteCampo('#RUC', '#TEL_CLIENTE', false);
    siguienteCampo('#TEL_CLIENTE', '#COD_PROMOCION', false);
    siguienteCampo('#TEL_CLIENTE', '#COD_PROMOCION', false);
    siguienteCampo('#COD_PROMOCION', '#COD_ARTICULO_AGREGAR', false);
    siguienteCampo('#COD_ARTICULO_AGREGAR', '#COD_COLOR_AGREGAR', false);
    siguienteCampo('#COD_COLOR_AGREGAR', '#COD_TALLA_AGREGAR', false);
    siguienteCampo('#COD_TALLA_AGREGAR', '#CANTIDAD_AGREGAR', false);
    
    if ($('#FEC_COMPROBANTE').val() === "") {
        $('#FEC_COMPROBANTE').val(moment(new Date()).format("YYYY-MM-DD"));
    }
    
    //Cliente
    $("#DOCUMENTO").on('change', function () {
        BuscarDatosCliente();
    });
    //Condicion Venta
    $("#COD_CONDICION_VENTA").on('change', function () {
        BuscarDatosCondicionVentas();
    });

    $('#COD_CONDICION_VENTA').on('keydown', function (event) { // funcion callback , anonima
       if (event.which === 120){
           buscarCondicionesVentas();
       };
    });
    
    $("#COD_MONEDA").on('change', function () {
        BuscarDatosMonedas();
    });
    
    $('#COD_MONEDA').on('keydown', function (event) { // funcion callback , anonima
       if (event.which === 120){
           buscarMonedas();
       };
    });
    
    $('#DOCUMENTO').focus();
    $('#DOCUMENTO').select();
}

/*** BUSCAR CLIENTE POR DOCUMENTO O CI ***/
function BuscarDatosCliente() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = 'api/getdatoscliente';
    var pBeforeSend = "";
    var pSucces = "BuscarDatosClienteAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pUrl);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarDatosClienteAjaxSuccess(json) {
    tieneAcceso(json["acceso"]);
    //console.log(json);
    if (!empty(json["datos"])) {
        var datos = json["datos"][0];
        $('#COD_CLIENTE').val(datos.COD_CLIENTE);
        $('#COD_DIRECCION').val(datos.COD_DIRECCION);
        $('#DESC_CLIENTE').val(datos.DESC_CLIENTE);
        $('#NOM_CLIENTE').val(datos.DESC_CLIENTE);
        $('#DESC_DIRECCION').val(datos.DESC_DIRECCION);
        $('#RUC').val(datos.RUC);
        $('#TEL_CLIENTE').val(datos.TEL_CLIENTE);
        $('#COD_CONDICION_VENTA').val(datos.COD_CONDICION_VENTA);
        BuscarDatosCondicionVentas();
        $('#COD_MONEDA').val(datos.COD_MONEDA);
        $('#TIP_DOCUMENTO').val(datos.TIP_DOCUMENTO);
    } else {
        $('#DOCUMENTO').focus();
    }
    $('#DOCUMENTO').val('');
    
}
/*** /BUSCAR CLIENTE POR DOCUMENTO O CI ***/

/*** BUSCAR DATOS DE CONDICION DE VENTA ***/

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
/*** /BUSCAR DATOS DE CONDICION DE VENTA ***/

/*** LISTAR CONDICIONES DE VENTAS ***/
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
    salir_busqueda();
    
    /*
    $("#id_aseguradora").val(id);
    $("#id_persona").val(id);
    $("#nombre_persona").val(nombre);
    
    $("#nombre_persona").focus();
    $("#nombre_persona").select();*/
}

function salir_busqueda() {
    $("#botonCancelar").click();
}
/*** /LISTAR CONDICIONES DE VENTAS ***/


/*** BUSCAR DATOS DE MONEDAS ***/

function BuscarDatosMonedas() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = 'api/listarMonedas';
    var pBeforeSend = "";
    var pSucces = "BuscarDatosMonedas(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pUrl);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarDatosMonedas(json) {
    tieneAcceso(json["acceso"]);
    //console.log(json);
    if (!empty(json["datos"])) {
        var datos = json["datos"][0];
        $('#DESC_MONEDA').val(datos.DESC_MONEDA);
        $('#TIP_CAMBIO').val(datos.TIP_CAMBIO);
    }
    
}
/*** /BUSCAR DATOS DE MONEDAS***/

/*** LISTAR MONEDAS ***/
function buscarMonedas() {
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

function BuscarMonedasAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/listarMonedas";
    var pBeforeSend = "";
    var pSucces = "BuscarMonedasAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}
function BuscarMonedasAjaxSuccess(json) {
    var datos = "";
    console.log(json);
    $datos = json["datos"];
    $.each($datos, function (key, value) {
        datos += "<tr onclick='seleccionar_monedas($(this))'>";
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

function seleccionar_monedas($this) {
    //console.log($this.find('td').eq(0).text());
    var descripcion = $this.find('td').eq(0).text();
    var cod_moneda = $this.find('td').eq(1).text();
    var tip_cambio = $this.find('td').eq(2).text();
    $("#COD_MONEDA").val(cod_moneda);
    $("#DESC_MONEDA").val(descripcion);
    $("#TIP_CAMBIO").val(tip_cambio);
    salir_busqueda();
    
    /*
    $("#id_aseguradora").val(id);
    $("#id_persona").val(id);
    $("#nombre_persona").val(nombre);
    
    $("#nombre_persona").focus();
    $("#nombre_persona").select();*/
}

function salir_busqueda() {
    $("#botonCancelar").click();
}
/*** /LISTAR CONDICIONES DE VENTAS ***/





function editable() {
    //Basic editor
    var simpleEditor = new SimpleTableCellEditor("EditableTable");
    simpleEditor.SetEditableClass("editMe");

    $('#simpleEditableTable').on("cell:edited", function (event) {
        console.log(`'${event.oldValue}' changed to '${event.newValue}'`);
    });
}

