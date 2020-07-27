$(document).ready(function () {
    inicializar();
    //editable();
});

function inicializar() {

    formatoGs();
    formatoNro();
    $('#articulo').focus();
    $('#articulo').select();
    
    $('#articulo').on('keydown', function (event) { // funcion callback , anonima
        //console.log(event.which);
        if (event.which === 13){
           BuscarDatosArticulosAjax();
       };
    });
}



/*** COLORES Y TALLAS ***/

function BuscarDatosArticulosAjax() {
    var pDatosFormulario = $("#consulta").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/consultaExistencia?COD_ARTICULO="+$('#articulo').val();
    var pBeforeSend = "";
    var pSucces = "BuscarDatosArticulosAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarDatosArticulosAjaxSuccess(json) {
    var datos = "";
    var img = "";
    //console.log(json);
    $('#cod_articulo').text(json["datos"][0].COD_ARTICULO);
    $('#desc_articulo').text(json["datos"][0].DESCRIPCION);
    $datos = json["datos"];
    $.each($datos, function (key, value) {
        datos += "<tr onclick='seleccionar_colores_tallas($(this))'>";
        datos += "<td>" + value.DESC_SUCURSAL + "</td>";
        datos += "<td>" + value.COD_COLOR + "</td>";
        datos += "<td>" + value.DESC_COLOR + "</td>";
        datos += "<td>" + value.COD_TALLA + "</td>";
        datos += "<td>" + value.CANTIDAD + "</td>";
        datos += "</tr>";
        img = value.IMAGEN;
    });
    if (datos === '') {
        datos += "<tr><td colspan='4'>No existen mas registros ...</td></tr>";
    }
    $('#tbody_listar').html(datos);
    $("#img_articulo").attr("src",img);
    BuscarPrecioArticulosAjax();
    
    formatoGs();
}


/*** /COLORES Y TALLAS ***/



/***** CONSULTA PRECIO ******/
function BuscarPrecioArticulosAjax() {
    var pDatosFormulario = '';//$("#consulta").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/consultaPrecio?COD_ARTICULO_AGREGAR="+$('#articulo').val();
    var pBeforeSend = "";
    var pSucces = "BuscarPrecioArticulosAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarPrecioArticulosAjaxSuccess(json) {
    var datos = "";
    //console.log(json["datos"][0]);
    //console.log(json["datos"][0].DESCRIPCION);
    var precio = json["datos"][0].PRECIO;
    var oferta = json["datos"][0].OFERTA;
    $('#precio').text(precio);
    if (!empty(oferta)) {
        BuscarDatosOfertaAjax(oferta);
    }
    formatoGs();
}
/****************************/

/***** CONSULTA DATOS OFERTA ******/
function BuscarDatosOfertaAjax($cod_oferta) {
    var pDatosFormulario = '';//$("#consulta").serialize();
    //console.log(pDatosFormulario);
    var pUrl = "api/datosOferta?COD_OFERTA="+$cod_oferta;
    var pBeforeSend = "";
    var pSucces = "BuscarDatosOfertaAjaxAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarDatosOfertaAjaxAjaxSuccess(json) {
    var datos = "";
    //console.log(json["datos"][0]);
    $('#es_oferta').text('***** OFERTA: '+json["datos"][0].DESC_OFERTA+' *****');
}
/****************************/

