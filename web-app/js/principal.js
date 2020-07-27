/**
 * Autor: Esteban Aqiono
 * Fecha: 30/09/2018
 * Descripcion: Funciones de la pantalla principal
 */
$.ready(inicializaPrincipal());

function inicializaPrincipal() {
    // verificar si tiene session abierta
    validarSesion();
    //cargar_formulario('frm/dashboard');
    cargar_formulario('frm/dashboard');
    $('#nombre_usuario').text(datosUsuario('USR_CALL'));
    $('#empresa_call').text(datosUsuario('NOM_CALL'));
    $('#log_out').on('click',function(){
        sessionStorage.removeItem('token');
        location.href = "home";
    });
}

/*function mostrarInformes(){
    console.log('Mostrar informes');
    console.log($('#modalInformes'));
    $('#modalInformes').on('shown.bs.modal', function () {
        $('#myInput').focus();
    });
}*/
