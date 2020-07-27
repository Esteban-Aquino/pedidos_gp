/*** ALTA CLIENTE ***/

$(document).ready(function () {
    inicializarAlta();
    $('[data-toggle="tooltip"]').tooltip();
});

function inicializarAlta() {
    $("#ALTA_NOMBRE").on('change', function () {
        if (empty($('#ALTA_NOMBRE_FANTASIA').val())) {
            $('#ALTA_NOMBRE_FANTASIA').val($("#ALTA_NOMBRE").val());
        }
    });

    $('#ALTA_CIUDAD').on('keydown', function (event) { // funcion callback , anonima
        //console.log(event.which);
        if (event.which === 120) {
            listarCiudadBarrio();
        }
        ;
    });
    $('#ALTA_BARRIO').on('keydown', function (event) { // funcion callback , anonima
        if (event.which === 120) {
            listarCiudadBarrio();
        }
        ;
    });
    validaDocs();
    validaNro();
    siguienteCampo('#ALTA_NOMBRE', '#ALTA_NOMBRE_FANTASIA', false);
    siguienteCampo('#ALTA_NOMBRE_FANTASIA', '#ALTA_DIRECCION', false);
    siguienteCampo('#ALTA_DIRECCION', '#ALTA_AREA1', false);
    siguienteCampo('#ALTA_AREA1', '#ALTA_NUMERO1', false);
    siguienteCampo('#ALTA_NUMERO1', '#ALTA_AREA2', false);
    siguienteCampo('#ALTA_AREA2', '#ALTA_RUC', false);
    siguienteCampo('#ALTA_RUC', '#ALTA_CI', false);
    siguienteCampo('#ALTA_CI', '#ALTA_NUMERO1', false);
    siguienteCampo('#ALTA_NUMERO1', '#botonGuardar', false);



}

function guardarCliente() {
    if (validarDatos()) {
        guardarClienteAjax();
    }
}

function validarDatos() {
    if (empty($('#ALTA_NOMBRE').val())) {
        swalError('Debe cargar nombre', '');
        return false;
    }
    if (empty($('#ALTA_NOMBRE_FANTASIA').val())) {
        swalError('Debe cargar nombre fantasia', '');
        return false;
    }
    if (empty($('#ALTA_DIRECCION').val())) {
        swalError('Debe cargar direccion', '');
        return false;
    }

    if (empty($('#ALTA_RUC').val()) && empty($('#ALTA_CI').val())) {
        swalError('Debe cargar RUC o CI', '');
        return false;
    }

    return true;
}
function guardarClienteAjax() {
    //console.log('Buscando precio');
    var pDatosFormulario = $("#alta_cliente").serialize() + "&ALTA_SEXO=" + $('#ALTA_SEXO').val();
    // console.log(pDatosFormulario);
    var pUrl = "api/guardarCliente";
    var pBeforeSend = "swalCargando('Guardando Cliente')";
    var pSucces = "guardarClienteAjaxSuccess(json)";
    var pError = "errorGuardarClienteAjax()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function guardarClienteAjaxSuccess(json) {
    swalCerrar();
    //console.log(json);
    if (!empty(json["mensaje"])) {
        var mensaje = json["mensaje"];
        if (mensaje !== 'OK') {
            swalError('Error al guadar', mensaje);
        } else {
            $('#divModal').modal('hide');
            BuscarDatosCliente();
        }
    }

}

function errorGuardarClienteAjax(json) {
    //console.log(json);
    //console.log('ERROR');
    swalCerrar();
}



/***** /ALTA CLIENTE ******/



/****** Buscar ciudad barrio ********/
function listarCiudadBarrio() {
    //console.log('Aqui');
    $('#buscado').append('<div id="modalCiudadBarrio"></div>');
    $.get("frm/pedidos/buscar_ciudad_barrio.html", function (htmlexterno) {
        $("#modalCiudadBarrio").html(htmlexterno);
        $('#divModalCiudadBarrio').modal('show');
        $('#divModalCiudadBarrio').on('hidden.bs.modal', function (e) {
            $('#modalCiudadBarrio').remove();
            $("#ALTA_NOMBRE").focus();
        });
    });
}

function listarCiudadBarrioAjax() {
    var pDatosFormulario = $("#form-buscar").serialize();
    //console.log('Buscando ciudad barrio');
    //console.log(pDatosFormulario);
    var pUrl = "api/listarCiudadBarrio";
    var pBeforeSend = "";
    var pSucces = "listarCiudadBarrioAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function listarCiudadBarrioAjaxSuccess(json) {
    var datos = "";
    //console.log(json);
    $datos = json["datos"];
    $.each($datos, function (key, value) {
        datos += "<tr onclick='seleccionar_ciudad_barrio($(this))'>";
        datos += "<td>" + value.COD_CIUDAD + "</td>";
        datos += "<td>" + value.DESC_CIUDAD + "</td>";
        datos += "<td>" + value.COD_BARRIO + "</td>";
        datos += "<td>" + value.DESC_BARRIO + "</td>";
        datos += "</tr>";
    });
    if (datos === '') {
        datos += "<tr><td colspan='4'>No existen mas registros ...</td></tr>";
    }
    $('#tbody_listar').html(datos);
}

function seleccionar_ciudad_barrio($this) {
    //console.log($this.find('td'));
    var cod_ciudad = $this.find('td').eq(0).text();
    var desc_ciudad = $this.find('td').eq(1).text();
    var cod_barrio = $this.find('td').eq(2).text();
    var desc_barrio = $this.find('td').eq(3).text();

    $("#ALTA_COD_CIUDAD").val(cod_ciudad);
    $("#ALTA_CIUDAD").val(desc_ciudad);
    $("#ALTA_COD_BARRIO").val(cod_barrio);
    $("#ALTA_BARRIO").val(desc_barrio);
    $("#ALTA_CIUDAD").focus();
    salir_busqueda_modal_ciudad();

}

/***********************************/
function salir_busqueda_modal_ciudad() {
    $('#divModalCiudadBarrio').find('#botonCancelar').click();
}