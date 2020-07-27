/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
// 
$(document).ready(function () {
    inicializar();
});

function inicializar() {
    // traer dato de cliente
    var vcod_cliente = $('#COD_CLIENTE').val();
    $('#MOD_COD_CLIENTE').val(vcod_cliente);
    // Obtener datos de cliente
    BuscarClienteCompleto();
}
var vnro_dir = 0;
var vnro_tel = 0;
var vnro_doc = 0;

var cliente = {};
var datos_cliente = {};
var direcciones_cliente = [{}];
var telefonos_cliente = [{}];
var documentos_cliente = [{}];

function BuscarClienteCompleto() {
    var pDatosCliente = $("#pedido").serialize();
    var pUrl = "api/getdatoscliente?COMPLETO=S";
    var pBeforeSend = "";
    var pSucces = "BuscarClienteCompletoAjaxSucces(json)";
    var pError = "ajax_error()";
    var pComplete = "";
    //console.log(pDatosCliente);
    ajax(pDatosCliente, pUrl, pBeforeSend, pSucces, pError, pComplete);
}

function BuscarClienteCompletoAjaxSucces(json) {
    //console.log(json);
    tieneAcceso(json["acceso"]);
    if (!empty(json["datos"])) {
        //console.log(json);
        var vcliente = json["datos"]["CLIENTE"];
        var vdirecciones = json["datos"]["DIRECCIONES"];
        var vtelefonos = json["datos"]["TELEFONOS"];
        var vdocumentos = json["datos"]["DOCUMENTOS"];
        //console.log(vcliente.NOMBRE);
        $('#MOD_NOMBRE').val(vcliente.NOMBRE);
        $('#MOD_NOMBRE_FANTASIA').val(vcliente.NOMBRE_FANTASIA);
        $('#MOD_SEXO').val(vcliente.SEXO);
        $('#MOD_FEC_NACIMIENTO').val(vcliente.FEC_NACIMIENTO);
        // CARGAR DIRECCIONES
        var datos = '';
        $.each(vdirecciones, function (key, value) {
            datos = '';
            vnro_dir = vnro_dir + 1;
            datos += `<div id = "div_direccion">
                            <div class="row item form-group">
                                <label class="control-label col-md-2 col-sm-1 col-xs-1" for="MOD_COD_DIRECCION` + vnro_dir + `">Direccion ` + vnro_dir + `:
                                </label>
                                <div class="col-md-1 col-sm-1 col-xs-2 oculto">
                                    <input id="MOD_COD_DIRECCION` + vnro_dir + `" class="form-control col-md-7 col-xs-4" name="MOD_COD_DIRECCION` + vnro_dir + `"   type="text" readonly="readonly" >
                                </div>
                                <div class="col-md-9 col-sm-1 col-xs-2">
                                    <input id="MOD_DIRECCION` + vnro_dir + `" class="form-control col-md-7 col-xs-4" name="MOD_DIRECCION` + vnro_dir + `"   type="text" maxlength="160" >
                                </div>
                            </div>
                            <div class="row item form-group">
                                <label class="control-label col-md-2 col-sm-1 col-xs-1" for="MOD_COD_DIRECCION">Ciudad:
                                </label>
                                <div class="col-md-1 col-sm-1 col-xs-2 oculto">
                                    <input id="MOD_COD_CIUDAD` + vnro_dir + `" class="form-control col-md-7 col-xs-4 " name="MOD_COD_CIUDAD` + vnro_dir + `"   type="text" readonly="readonly" >
                                </div>
                                <div class="col-md-4 col-sm-1 col-xs-2">
                                    <input id="MOD_CIUDAD` + vnro_dir + `" class="form-control col-md-7 col-xs-4" name="MOD_CIUDAD` + vnro_dir + `"   type="text" >
                                </div>

                                <label class="control-label col-md-1 col-sm-1 col-xs-1" for="MOD_COD_DIRECCION">Barrio:
                                </label>
                                <div class="col-md-1 col-sm-1 col-xs-2 oculto">
                                    <input id="MOD_COD_BARRIO` + vnro_dir + `" class="form-control col-md-7 col-xs-4 " name="MOD_COD_BARRIO` + vnro_dir + `"   type="text" readonly="readonly" >
                                </div>
                                <div class="col-md-4 col-sm-1 col-xs-2">
                                    <input id="MOD_BARRIO` + vnro_dir + `" class="form-control col-md-7 col-xs-4" name="MOD_BARRIO` + vnro_dir + `"   type="text" >
                                </div>
                            </div>
                      </div>`;
            $('#mod_direcciones').append(datos);
            $('#MOD_COD_DIRECCION' + vnro_dir).val(value.COD_DIRECCION);
            $('#MOD_DIRECCION' + vnro_dir).val(value.DESC_DIRECCION);

            $('#MOD_COD_CIUDAD' + vnro_dir).val(value.COD_CIUDAD);
            $('#MOD_CIUDAD' + vnro_dir).val(value.DESC_CIUDAD);

            $('#MOD_COD_BARRIO' + vnro_dir).val(value.COD_BARRIO);
            $('#MOD_BARRIO' + vnro_dir).val(value.DESC_BARRIO);

            $('#MOD_CIUDAD' + vnro_dir).on('keydown', function (event) { // funcion callback , anonima
                if (event.which === 120) {
                    listarCiudadBarrio(vnro_dir);
                }
                ;
            });
            $('#MOD_BARRIO' + vnro_dir).on('keydown', function (event) { // funcion callback , anonima
                if (event.which === 120) {
                    listarCiudadBarrio(vnro_dir);
                }
                ;
            });
        });

        // CARGAR TELEFONOS
        $.each(vtelefonos, function (key, value) {
            datos = '';
            vnro_tel = vnro_tel + 1;
            datos += `<div id = "div_telefonos">
                        <div class="row item form-group">
                            <label class="control-label col-md-2 col-sm-1 col-xs-1" for="MOD_AREA` + vnro_tel + `">Telefono ` + vnro_tel + `:
                            </label>
                            <div class="col-md-1 col-sm-1 col-xs-2">
                                <input id="MOD_AREA` + vnro_tel + `" class="form-control col-md-7 col-xs-4" name="MOD_AREA` + vnro_tel + `"   type="text">
                            </div>
                            <div class="col-md-4 col-sm-12 col-xs-2">
                                <input id="MOD_NUMERO` + vnro_tel + `" class="form-control col-md-7 col-xs-4" name="MOD_NUMERO` + vnro_tel + `"   type="text" >
                            </div>
                        </div>
                      </div>`;
            $('#mod_telefonos').append(datos);
            $('#MOD_AREA' + vnro_tel).val(value.CODIGO_AREA);
            $('#MOD_NUMERO' + vnro_tel).val(value.NUM_TELEFONO);

        });

        // documentos
        $.each(vdocumentos, function (key, value) {
            datos = '';
            vnro_doc = vnro_doc + 1;
            datos += `<div id = "div_documentos">
                        <div class="row item form-group">
                            <label class="control-label col-md-2 col-sm-1 col-xs-1" for="MOD_TIPO` + vnro_doc + `">Documento ` + vnro_doc + `:
                            </label>
                            <div class="col-md-1 col-sm-1 col-xs-2">
                                <select id="MOD_COD_IDENT` + vnro_doc + `" class="form-control col-md-7 col-xs-4" name="MOD_COD_IDENT` + vnro_doc + `">
                                    <option value="CI">CI</option> 
                                    <option value="RUC">RUC</option> 
                                </select>
                                <!--<input id="MOD_COD_IDENT` + vnro_doc + `" class="form-control col-md-7 col-xs-4" name="MOD_COD_IDENT` + vnro_doc + `"   type="text" >-->
                            </div>
                            <div class="col-md-4 col-sm-1 col-xs-2">
                                <input id="MOD_RUC` + vnro_doc + `" class="form-control col-md-7 col-xs-4" name="MOD_RUC` + vnro_doc + `"   type="text">
                            </div>
                        </div>
                      </div>`;
            $('#mod_documentos').append(datos);
            $('#MOD_COD_IDENT' + vnro_doc).val(value.COD_IDENT);
            $('#MOD_RUC' + vnro_doc).val(value.NUMERO);

        });

    }

}

function nuevaDireccion() {
    vnro_dir = vnro_dir + 1;
    var datos = `<div id = "div_direccion">
                    <div class="row item form-group">
                            <label class="control-label col-md-2 col-sm-1 col-xs-1" for="MOD_COD_DIRECCION` + vnro_dir + `">Direccion ` + vnro_dir + `:
                            </label>
                            <div class="col-md-1 col-sm-1 col-xs-2 oculto">
                                <input id="MOD_COD_DIRECCION` + vnro_dir + `" class="form-control col-md-7 col-xs-4" name="MOD_COD_DIRECCION` + vnro_dir + `"   type="text" readonly="readonly" >
                            </div>
                            <div class="col-md-9 col-sm-1 col-xs-2">
                                <input id="MOD_DIRECCION` + vnro_dir + `" class="form-control col-md-7 col-xs-4" name="MOD_DIRECCION` + vnro_dir + `"   type="text" maxlength="160">
                            </div>
                        </div>
                        <div class="row item form-group">
                            <label class="control-label col-md-2 col-sm-1 col-xs-1" for="MOD_COD_CIUDAD">Ciudad:
                            </label>
                            <div class="col-md-1 col-sm-1 col-xs-2 oculto">
                                <input id="MOD_COD_CIUDAD` + vnro_dir + `" class="form-control col-md-7 col-xs-4 " name="MOD_COD_CIUDAD` + vnro_dir + `"   type="text" readonly="readonly" >
                            </div>
                            <div class="col-md-4 col-sm-1 col-xs-2">
                                <input id="MOD_CIUDAD` + vnro_dir + `" class="form-control col-md-7 col-xs-4" name="MOD_CIUDAD` + vnro_dir + `"   type="text" >
                            </div>

                            <label class="control-label col-md-1 col-sm-1 col-xs-1" for="MOD_COD_BARRIO">Barrio:
                            </label>
                            <div class="col-md-1 col-sm-1 col-xs-2 oculto">
                                <input id="MOD_COD_BARRIO` + vnro_dir + `" class="form-control col-md-7 col-xs-4 " name="MOD_COD_BARRIO` + vnro_dir + `"   type="text" readonly="readonly" >
                            </div>
                            <div class="col-md-4 col-sm-1 col-xs-2">
                                <input id="MOD_BARRIO` + vnro_dir + `" class="form-control col-md-7 col-xs-4" name="MOD_BARRIO` + vnro_dir + `"   type="text" >
                            </div>
                        </div>
                    </div>`;
    $('#mod_direcciones').append(datos);

    $('#MOD_CIUDAD' + vnro_dir).on('keydown', function (event) { // funcion callback , anonima
        if (event.which === 120) {
            listarCiudadBarrio(vnro_dir);
        }
        ;
    });
    $('#MOD_BARRIO' + vnro_dir).on('keydown', function (event) { // funcion callback , anonima
        if (event.which === 120) {
            listarCiudadBarrio(vnro_dir);
        }
        ;
    });
}



function nuevoTelefono() {
    vnro_tel = vnro_tel + 1;
    var datos = `<div id = "div_telefonos">
                    <div class="row item form-group">
                                <label class="control-label col-md-2 col-sm-1 col-xs-1" for="MOD_AREA` + vnro_tel + `">Telefono ` + vnro_tel + `:
                                </label>
                                <div class="col-md-1 col-sm-1 col-xs-2">
                                    <input id="MOD_AREA` + vnro_tel + `" class="form-control col-md-7 col-xs-4" name="MOD_AREA` + vnro_tel + `"   type="text">
                                </div>
                                <div class="col-md-4 col-sm-12 col-xs-2">
                                    <input id="MOD_NUMERO` + vnro_tel + `" class="form-control col-md-7 col-xs-4" name="MOD_NUMERO` + vnro_tel + `"   type="text" >
                                </div>
                    </div>
                 </div>`;
    $('#mod_telefonos').append(datos);
}

function nuevoDocumento() {
    vnro_doc = vnro_doc + 1;
    var datos = `<div id = "div_documentos">
                    <div class="row item form-group">
                        <label class="control-label col-md-2 col-sm-1 col-xs-1" for="MOD_TIPO` + vnro_doc + `">Documento ` + vnro_doc + `:
                        </label>
                        <div class="col-md-1 col-sm-1 col-xs-2">
                            <!--<input id="MOD_COD_IDENT` + vnro_doc + `" class="form-control col-md-7 col-xs-4" name="MOD_COD_IDENT` + vnro_doc + `"   type="text">-->
                            <select id="MOD_COD_IDENT` + vnro_doc + `" class="form-control col-md-7 col-xs-4 docs" name="MOD_COD_IDENT` + vnro_doc + `">
                                <option value="CI">CI</option> 
                                <option value="RUC">RUC</option> 
                            </select>
                        </div>
                        <div class="col-md-4 col-sm-1 col-xs-2">
                            <input id="MOD_RUC` + vnro_doc + `" class="form-control col-md-7 col-xs-4" name="MOD_RUC` + vnro_doc + `"   type="text">
                        </div>
                    </div>
                 </div>`;
    $('#mod_documentos').append(datos);
    validaDocs();
}


function actualizar_datos() {
    var datos = {};
    // CABECERA
    var vcod_cliente = $('#MOD_COD_CLIENTE').val();
    var vnombre = $('#MOD_NOMBRE').val();
    var vnom_fantasia = $('#MOD_NOMBRE_FANTASIA').val();
    var vfec_nacimiento = $('#MOD_FEC_NACIMIENTO').val();
    var vsexo = $('#MOD_SEXO').val();
    datos['COD_CLIENTE'] = vcod_cliente;
    datos['NOMBRE'] = vnombre;
    datos['NOM_FANTASIA'] = vnom_fantasia;
    datos['FEC_NACIMIENTO'] = vfec_nacimiento;
    datos['SEXO'] = vsexo;
    cliente['CLIENTE'] = datos;

    // DIRECCIONES
    var direcciones = $('#mod_direcciones #div_direccion');
    datos = {};
    $.each(direcciones, function (index, fila) {
        datos = {};
        //console.log(direcciones.eq(index).find('input').eq(0).val());
        var vcod_dir = direcciones.eq(index).find('input').eq(0).val();
        var vdir = direcciones.eq(index).find('input').eq(1).val();
        var vcod_ciudad = direcciones.eq(index).find('input').eq(2).val();
        var vciudad = direcciones.eq(index).find('input').eq(3).val();
        var vcod_barrio = direcciones.eq(index).find('input').eq(4).val();
        var vbarrio = direcciones.eq(index).find('input').eq(5).val();
        datos['COD_DIRECCION'] = vcod_dir;
        datos['DIRECCION'] = vdir;
        datos['COD_CIUDAD'] = vcod_ciudad;
        datos['CIUDAD'] = vciudad;
        datos['COD_BARRIO'] = vcod_barrio;
        datos['BARRIO'] = vbarrio;
        direcciones_cliente[index] = datos;
    });
    cliente['DIRECCIONES'] = direcciones_cliente;

    // TELEFONOS
    var telefonos = $('#mod_telefonos #div_telefonos');
    datos = {};
    $.each(telefonos, function (index, fila) {
        datos = {};
        //console.log(direcciones.eq(index).find('input').eq(0).val());
        var vcod_area = telefonos.eq(index).find('input').eq(0).val();
        var vnumero = telefonos.eq(index).find('input').eq(1).val();

        datos['COD_AREA'] = vcod_area;
        datos['NUMERO'] = vnumero;

        telefonos_cliente[index] = datos;
    });
    cliente['TELEFONOS'] = telefonos_cliente;
    // DOCUMENTOS
    var documentos = $('#mod_documentos #div_documentos');
    datos = {};
    $.each(documentos, function (index, fila) {
        datos = {};
        //console.log(direcciones.eq(index).find('input').eq(0).val());
        var vcod_ident = documentos.eq(index).find('select').eq(0).val();
        var vnumero = documentos.eq(index).find('input').eq(0).val();

        datos['COD_IDENT'] = vcod_ident;
        datos['NUMERO'] = vnumero;

        documentos_cliente[index] = datos;
    });
    cliente['DOCUMENTOS'] = documentos_cliente;

    //console.log(JSON.stringify(cliente));

}



/****** Buscar ciudad barrio ********/
function listarCiudadBarrio(vnro_dir) {
    $('#buscado').append('<div id="modalCiudadBarrio"></div>');
    $.get("frm/pedidos/buscar_ciudad_barrio.html", function (htmlexterno) {
        $("#modalCiudadBarrio").html(htmlexterno);
        $('#divModalCiudadBarrio').modal('show');
        $('#nro_dir').val(vnro_dir);
        $('#divModalCiudadBarrio').on('hidden.bs.modal', function (e) {
            $('#modalCiudadBarrio').remove();
            $("#MOD_NOMBRE").focus();
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
    
    $("#MOD_COD_CIUDAD"+$('#nro_dir').val()).val(cod_ciudad);
    $("#MOD_CIUDAD"+$('#nro_dir').val()).val(desc_ciudad);
    $("#MOD_COD_BARRIO"+$('#nro_dir').val()).val(cod_barrio);
    $("#MOD_BARRIO"+$('#nro_dir').val()).val(desc_barrio);
    $("#MOD_COD_CIUDAD"+$('#nro_dir').val()).focus();
    salir_busqueda_modal_ciudad();

}

/***********************************/

function salir_busqueda_modal_ciudad() {
    $('#divModalCiudadBarrio').find('#botonCancelar').click();
}


/**************** GUARDAR ******************/
function guardarModCliente() {
    actualizar_datos();
    var pDatos = JSON.stringify(cliente);
    var pUrl = 'api/actualizaCliente';
    var pBeforeSend = "swalCargando('Actualizando cliente')";
    var pSucces = "AguardarModPedidoAjaxSuccess(json)";
    var pError = "errorGuardarAjax()";
    var pComplete = "";
    //console.log(pDatos);
    ajax(pDatos, pUrl, pBeforeSend, pSucces, pError, pComplete);
}
 function errorGuardarAjax(){
     //console.log('ERROR ASD');
     swalCerrar();
 }
function AguardarModPedidoAjaxSuccess(json) {
    
    //console.log(json);
    swalCerrar();
    tieneAcceso(json["acceso"]);
    if (!empty(json["mensaje"])) {
        var mensaje = json["mensaje"];
        if (mensaje !== 'OK' ) {
            swalError('Error al guadar', mensaje);
        } else {
            swalCorrectoAccion("Guardado",'Cliente Actualizado', 'actualizacionCompleta()');
        }
    }
    
}
/************************************************/