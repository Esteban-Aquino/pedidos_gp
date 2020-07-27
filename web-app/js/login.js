/**
 * Autor: Esteban Aqiono
 * Fecha: 15/11/2019
 * Descripcion: Funciones del Login y varias del sistema
 */
/********* INDEX LOGGIN *********/
function inicializaLogin() {
    // verificar si tiene session abierta
    var token = sessionStorage.getItem("token");
    //console.log(usr);
    if (token !== null && token !== "") {
        //console.log("nulo");
        location.href = "home";
    }
    //cerrarSessionAjax();
    $("#usuario").focus();
    siguienteCampo('#usuario', '#clave', false);
    siguienteCampo('#clave', '#btn-ingresar button', false);
}

function validarLoggin() {
    var vusuario = $("#usuario").val();
    var vclave = $("#clave").val();
    if (vusuario.trim() === '') {
        mensaje('Ingrese usuario', 'Aceptar', '#usuario_usuario');
        $("#usuario_usuario").focus();
    } else if (vclave.trim() === '') {
        mensaje('Contraseña Invalida', 'Aceptar', '#clase_usuario');
        $("#clave_usuario").focus();
    } else {
        swalCargando("Por favor espere");
        validarAccesoAjax();
        //location.href = "menu.html";
    }
    ;
}

function validarAccesoAjax() {
    var pDatosFormulario = JSON.stringify(getFormData($('#formAcceso')));//$('#formAcceso');
    //console.log(pDatosFormulario);
    var pUrl = 'api/validar';
    var pBeforeSend = "";
    var pSucces = "ValidarAccesoAjaxSuccess(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    ajaxValidarSession(pDatosFormulario, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function ValidarAccesoAjaxSuccess(json) {
    //console.log("Recibiendo");
    /*console.log(json);
    var token = json.token;
    console.log(token);
    var datos_token = jwt_decode(token);
    console.log(datos_token);*/
    if (json.acceso) {
        // guardar en storage
        sessionStorage.setItem("token", json.token);
        location.href = "home";
        console.log(datosUsuario());
    } else {
        sessionStorage.setItem("token", "");
        console.log("Fail");
        swalCerrar();
        swalError("Error de autenticacion", "Credenciales invalidas");
        //mensaje('Credenciales Invalidas', 'Aceptar', '#usuario_usuario');
    }
    ;
}