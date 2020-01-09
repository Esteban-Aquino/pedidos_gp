/**
 * Autor: Esteban Aqiono
 * Fecha: 30/09/2018
 * Descripcion: Funciones varias del sistema
 */

function ajax(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete) {
    //validarSesion();
    var usr = new Object();
    usr.token = sessionStorage.getItem('token');
    eval(pBeforeSend);
    $.ajax({
        async: true,
        type: 'POST',
        url: pUrl,
        data: pDatosFormulario,
        dataType: 'json',
        headers: usr
    }).done(function (json) {
        eval(pSucces);
    }).fail(function (e) {
        eval(pError);
    }).always(function (objeto, exito, error) {
        eval(pComplete);
    });
}

function ajaxGet(pUrl, pBeforeSend, pSucces, pError, pComplete) {
    validarSesion();
    var usr = new Object();
    usr.token = sessionStorage.getItem('token');
    //console.log("enviando-ajax");
    //console.log(usr);
    eval(pBeforeSend);
    $.ajax({
        async: true,
        type: 'GET',
        url: pUrl,
        dataType: 'json',
        headers: usr
    }).done(function (json) {
        eval(pSucces);
    }).fail(function (e) {
        eval(pSucces);
    }).always(function (objeto, exito, error) {
        eval(pComplete);
    });
}

function ajax_error(vmens) {
    mensaje(vmens, 'Aceptar', '');
}

function siguienteCampo(actual, siguiente, preventDefault) {
    $(actual).on('keydown', function (event) { // funcion callback , anonima
        //console.log("---> " + event.which);
        if (event.which === 13) {
            if (preventDefault) {
                event.preventDefault();
            }
            $(siguiente).focus();
            $(siguiente).select();
        }
    });
}

function mensaje(mensaje, textoBoton, focusCampo) {
    // 1. Agregar al final un div con id=mensajes al body
    $('body').append('<div id="mensajes"></div>');
    // 2. Se guarda el contenido del componente en una variable -> modal
    modal = '<div id="divModal" class="modal fade" tabindex="-1" role="dialog" ';
    modal += '     aria-labelledby="gridSystemModalLabel">';
    modal += '<div class="modal-dialog" role="document">';
    modal += '<div class="modal-content">';
    modal += '  <div class="modal-header">';
    modal += '    <button type="button" class="close" data-dismiss="modal" ';
    modal += '            aria-label="Close"><span aria-hidden="true">&times;</span></button>';
    modal += '    <h4 class="modal-title" id="gridSystemModalLabel">Mensaje del Sistema</h4>';
    modal += '  </div>';
    modal += '  <div class="modal-body">';
    modal += '     ' + mensaje;
    modal += '  </div>';
    modal += '  <div class="modal-footer">';
    modal += '    <button id="botonAceptar" type="button" class="btn btn-primary"';
    modal += '            data-dismiss="modal">' + textoBoton + '</button>';
    modal += '  </div>';
    modal += '</div>';
    modal += '</div>';
    modal += '</div>';
    // 3. Insertar el contenido de la variable modal en el div mensajes
    $('#mensajes').html(modal);
    // 4. Mostar el modal
    $('#divModal').modal('show');
    // 5. Cuando se visualiza todo el modal lleva el cursor al boton Aceptar
    $('#divModal').on('shown.bs.modal', function (e) {
        $('#botonAceptar').focus();
    });
    // 6. Cuando se oculta el modal se lleva el foco al campo que se recibio como parametro
    $('#divModal').on('hidden.bs.modal', function (e) {
        $(focusCampo).focus();
        $(focusCampo).select();
        // 7. Remueve el div mensajes del body
        $('#mensajes').remove();
    });
}

function estaSeguro(mensaje, accion, focusCampo) {
    // 1. Agregar al final un div con id=mensajes al body
    $('body').append('<div id="estaSeguro"></div>');
    // 2. Se guarda el contenido del componente en una variable -> modal
    modal = '<div id="divModalEstaSeguro" class="modal fade" tabindex="-1" role="dialog" ';
    modal += '     aria-labelledby="gridSystemModalLabel">';
    modal += '<div class="modal-dialog" role="document">';
    modal += '<div class="modal-content">';
    modal += '  <div class="modal-header">';
    modal += '    <button type="button" class="close" data-dismiss="modal" ';
    modal += '            aria-label="Close"><span aria-hidden="true">&times;</span></button>';
    modal += '    <h4 class="modal-title" id="gridSystemModalLabel">Mensaje del Sistema</h4>';
    modal += '  </div>';
    modal += '  <div class="modal-body">';
    modal += '     ' + mensaje;
    modal += '  </div>';
    modal += '  <div class="modal-footer">';
    modal += '    <button id="botonSi" type="button" class="btn btn-primary"';
    modal += '            data-dismiss="modal">Si</button>';
    modal += '    <button id="botonNo" type="button" class="btn btn-primary"';
    modal += '            data-dismiss="modal">No</button>';
    modal += '  </div>';
    modal += '</div>';
    modal += '</div>';
    modal += '</div>';
    // 3. Insertar el contenido de la variable modal en el div mensajes
    $('#estaSeguro').html(modal);
    // 4. Mostar el modal
    $('#divModalEstaSeguro').modal('show');
    // 5. Cuando se visualiza todo el modal lleva el cursor al boton Aceptar
    $('#divModalEstaSeguro').on('shown.bs.modal', function (e) {
        $('#botonNo').focus();
    });
    // 6. Cuando se oculta el modal se lleva el foco al campo que se recibio como parametro
    $('#divModalEstaSeguro').on('hidden.bs.modal', function (e) {
        $(focusCampo).focus();
        $(focusCampo).select();
        // 7. Remueve el div mensajes del body
        $('#estaSeguro').remove();
    });
    // eventos a si y no
    $('#botonSi').on('click', function () {
        eval(accion);
    });

}

function estaSeguroSwal(mensaje, accion, focusCampo) {
    Swal.fire({
        title: 'Esta seguro/a?',
        text: mensaje,
        type: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.value) {
            eval(accion);
        }
    });
    $(focusCampo).focus();
}


function mostrar_foto($this) {
    //console.log($this.attr("src"));
    mostrar_imagen($this.attr("src"), "Imagen de Usuario");
}

function mostrar_imagen(pfoto, pDesc) {
    // 1. Agregar al final un div con id=mensajes al body
    $('body').append('<div id="mensajes"></div>');
    // 2. Se guarda el contenido del componente en una variable -> modal
    modal = '<div id="divModal" class="modal fade" tabindex="-1" role="dialog" ';
    modal += '     aria-labelledby="gridSystemModalLabel">';
    modal += '<div class="modal-dialog" role="document">';
    modal += '<div class="modal-content">';
    modal += '  <div class="modal-header">';
    modal += '    <button type="button" class="close" data-dismiss="modal" ';
    modal += '            aria-label="Close"><span aria-hidden="true">&times;</span></button>';
    modal += '    <h4 class="modal-title" id="gridSystemModalLabel">' + pDesc + '</h4>';
    modal += '  </div>';
    modal += '  <div class="modal-body centrado">';
    modal += '      <img src="' + pfoto + '" height="350" alt="" class="img-circle"/>';
    modal += '  </div>';
    modal += '  <div class="modal-footer">';
    modal += '    <button id="botonAceptar" type="button" class="btn btn-primary"';
    modal += '            data-dismiss="modal">Cerrar</button>';
    modal += '  </div>';
    modal += '</div>';
    modal += '</div>';
    modal += '</div>';
    // 3. Insertar el contenido de la variable modal en el div mensajes
    $('#mensajes').html(modal);
    // 4. Mostar el modal
    $('#divModal').modal('show');
    // 5. Cuando se visualiza todo el modal lleva el cursor al boton Aceptar
    $('#divModal').on('shown.bs.modal', function (e) {
        $('#botonAceptar').focus();
    });
    // 6. Cuando se oculta el modal se lleva el foco al campo que se recibio como parametro
    $('#divModal').on('hidden.bs.modal', function (e) {
        // 7. Remueve el div mensajes del body
        $('#mensajes').remove();
    });
}

function swalCargando(vtexto) {
    Swal.fire({
        allowOutsideClick: false,
        type: "info",
        text: vtexto
    });
    Swal.showLoading();
}

function swalError(vtitulo, vtexto) {
    Swal.fire({
        allowOutsideClick: false,
        type: "error",
        title: vtitulo,
        text: vtexto
    });
}

function swalCorrecto(vtitulo, vmensaje) {
    Swal.fire({
        type: 'success',
        title: vtitulo,
        text: vmensaje
    });
}

function swalCerrar() {
    Swal.close();
}

function getFormData($form) {
    var unindexed_array = $form.serializeArray();
    var indexed_array = {};

    $.map(unindexed_array, function (n, i) {
        indexed_array[n['name']] = n['value'];
    });

    return indexed_array;
}

function cargar_formulario(frm) {
    //$('#boton-menu').click();
    //"restrict mode";
    /*$("#main_container").fadeOut(0, function () {
     $("#main_container").load(frm);
     $("#main_container").fadeIn(800, function () {});
     });*/

    clearInterval(this.interval);
    $("#busquedas").remove();

    $("#formulario").fadeOut(0, function () {
        $.ajax({
            async: true,
            type: 'GET',
            url: frm,
            dataType: 'html'
        }).done(function (htmlexterno) {
            $("#formulario").html(htmlexterno);
            $("#formulario").fadeIn(800, function () {});
        }).fail(function (e) {
            null;
        }).always(function (objeto, exito, error) {
            null;
        });

        /*$.ajax({
         async: true,
         url: frm,
         dataType: "html",
         success: function (htmlexterno) {
         $("#formulario").html(htmlexterno);
         $("#formulario").fadeIn(800, function () {});
         }
         });*/


        /*$.get(frm).done(
         function (htmlexterno) {
         $("#formulario").html(htmlexterno);
         $("#formulario").fadeIn(800, function () {});
         }
         
         ).fail(
         function (xhr) {
         null;
         }
         );*/

        /*$.get(frm, function (htmlexterno) {
         $("#formulario").html(htmlexterno);
         $("#formulario").fadeIn(800, function () {});
         });*/
    });

    /*
     $("#busquedas").remove();
     $("#formularios").load(frm);
     */
}

function salir_formulario() {
    $("#formularios").html("");
}

function siguiente(funcion) {
    var pag = Number($("#pagina").val());
    $("#pagina").val(pag + 1);
    eval(funcion);
}

function anterior(funcion) {
    var pag = Number($("#pagina").val());
    if (pag - 1 > 0) {
        //console.log(pag - 1);
        $("#pagina").val(pag - 1);
    }
    eval(funcion);
}

function formatoGs() {
    $('.gs').priceFormat({
        prefix: '',
        centsSeparator: ',',
        thousandsSeparator: '.',
        centsLimit: 0,
        allowNegative: true
    });
}

function sacarFormatoGs($nro) {
    var vnro;
    vnro = parseInt($nro.replace(',', '').replace('.', '').replace('.', ''));
    return vnro;
}

function formatoNro() {
    $('.nro').priceFormat({
        prefix: '',
        centsSeparator: ',',
        thousandsSeparator: '.',
        centsLimit: 2,
        allowNegative: true
    });
}

function datosUsuario(tipo) {
    var token = sessionStorage.getItem("token");
    if (token === null || token === "") {
        return "";
    } else {
        var usr = jwt_decode(token);
        return usr[0][tipo];
    }
}

function empty(data) {
    if (typeof (data) == 'number' || typeof (data) == 'boolean')
    {
        return false;
    }
    if (typeof (data) == 'undefined' || data === null)
    {
        return true;
    }
    if (typeof (data.length) != 'undefined')
    {
        return data.length == 0;
    }
    var count = 0;
    for (var i in data)
    {
        if (data.hasOwnProperty(i))
        {
            count++;
        }
    }
    return count == 0;
}

function validarSesion() {
    // verificar si tiene session abierta
    var token = sessionStorage.getItem("token");
    //console.log(usr);
    if (token === null || token === "") {
        location.href = "index.html";
    } else {
        validarSesionAjax();
    }
}

function validarSesionAjax() {
    var pDatosFormulario = '';
    var pUrl = 'api/validar';
    var pBeforeSend = "";
    var pSucces = "ValidarSesionAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajaxValidarSession(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function ValidarSesionAjaxSuccess(json) {
    //console.log(json.acceso);
    if (!json.acceso) {
        sessionStorage.removeItem('token');
        location.href = "index.html";
    }
}

function cargar_busqueda(frm) {
    "restrict mode";
    $('#buscado').append('<div id="busquedas" class="oculto"></div>');
    $("#busquedas").load(frm);
    $("#formulario").fadeOut(500, function () {
        $("#busquedas").fadeIn(500, function () { // callback - funcion anonima
            $("#buscar_texto").focus();
        });
    });
}

function salir_busqueda() {
    //console.log('Salir busqueda');
    $("#busquedas").fadeOut(500, function () {
        $("#busquedas").remove();
        $("#formulario").fadeIn(500, function () {
            //$("#boton-buscar-usuario").focus();
        });
    });
}

function tieneAcceso(acceso) {
    if (!acceso) {
        // verificar si tiene session abierta
        sessionStorage.removeItem("token");
        location.href = "index.html";
    }
}

function ajaxValidarSession(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete) {
    var usr = new Object();
    usr.token = sessionStorage.getItem('token');
    eval(pBeforeSend);
    $.ajax({
        async: true,
        type: 'POST',
        url: pUrl,
        data: pDatosFormulario,
        dataType: 'json',
        headers: usr
    }).done(function (json) {
        eval(pSucces);
    }).fail(function (e) {
        eval(pSucces);
    }).always(function (objeto, exito, error) {
        eval(pComplete);
    });
}