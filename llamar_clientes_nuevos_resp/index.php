<?php require __DIR__ . '/../dashboard_header.php'; ?>

<link rel="stylesheet" type="text/css" href="css/style.css" />
<link rel="stylesheet" type="text/css" href="css/switch.css" />
<link rel="stylesheet" type="text/css" href="css/nouislider.min.css" />
<link rel="stylesheet" type="text/css" href="css/loading.css" />

<script src="js/helpers.js"></script>
<script src="js/nouislider.min.js"></script>
<script src="js/jquery.tablesorter.min.js"></script>
<script src="js/moment.min.js"></script>
<script src="js/slider.js"></script>
<script src="js/client.js"></script>
<script src="js/js-cookie.js"></script>

<h1>Llamar Clientes Nuevos</h1>

<div class="row slider-group panel panel-default">
    <div class="panel-body">
        <div class="col-md-8 col-sm-12 slider-row">
            <div id="slider"></div>       
        </div>
        <div class="col-md-4 col-sm-12 slider-values">
            <span>Mostrar solicitudes desde el </span>
            <b><span id="slider-value-start"></span></b>
            <span> hasta el </span>
            <b><span id="slider-value-end"></span></b>
        </div>
        
        <button type="button" id="filters-button" class="btn btn-default" data-toggle="modal" data-target="#modal-filters">
            <span class="glyphicon glyphicon-filter" aria-hidden="true"></span> Filtrar Resultados
        </button>
        
        </div>
   </div>
</div>

<table id="tbl-clients" class="table table-striped table-condensed table-responsive table-hover">
    <thead>
        <tr>
            <th class="order">Nombre</th>
            <th class="phones order">Tel&eacute;fonos</th>
            <th class="order center-text">Fecha Contrataci&oacute;n</th>
            <th class="order">Dominio</th>
            <th class="hosting order">Empresa</th>
            <th class="order">DNS</th>
            <th>&iquest;Contactado?</th>
            <th class="center-text">Comentario</th>
        </tr>
    </thead>
    <tbody>
        <!-- Loading row -->
        <tr class="loading">
            <td colspan="8">
                <div class="spinner">
                    <div class="rect1"></div>
                    <div class="rect2"></div>
                    <div class="rect3"></div>
                    <div class="rect4"></div>
                    <div class="rect5"></div>
                </div>
            </td>
        </tr>
        <!-- Error message row -->
        <tr class="error-message hidden danger">
            <td colspan="8" class="center-text">
                <span></span>
            </td>
        </tr>
    </tbody>
</table>

<!-- Comments Modal -->
<div id="modal-comments" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Comentarios</h4>
            </div>
            <div class="modal-body">
                <textarea></textarea>
                <div class="spinner hidden">
                  <div class="rect1"></div>
                  <div class="rect2"></div>
                  <div class="rect3"></div>
                  <div class="rect4"></div>
                  <div class="rect5"></div>
                </div>
                <div class="alert" role="alert"><span class="message"></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button id="update-comments" type="button" class="btn btn-primary">Guardar</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- Filters Modal -->
<div id="modal-filters" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Filtrar Resultados</h4>
            </div>
            <div class="modal-body">
                <div>
                    <span>Mostrar clientes con DNS correctos</span>
                    <label class="switch switch-yes-no">
                        <input id="show-clients-dns" class="switch-input" type="checkbox" checked/>
                        <span class="switch-label" data-on="SI" data-off="NO"></span>
                        <span class="switch-handle"></span>
                    </label>
                </div>
                <hr/>
                <div>
                    <span>Mostrar clientes ya contactados</span>
                    <label class="switch switch-yes-no">
                        <input id="show-clients-contacted" class="switch-input" type="checkbox" checked/>
                        <span class="switch-label" data-on="SI" data-off="NO"></span>
                        <span class="switch-handle"></span>
                    </label>
                </div>
                <hr/>
                <div>
                    <span class="mr15">Mostrar marcas</span>
                    <div class="btn-group" data-toggle="buttons" id="show-hosting">
                        <label class="btn btn-success">
                            <input type="checkbox" autocomplete="off"> <span>Hosting.cl</span>
                        </label>
                        <label class="btn btn-success">
                            <input type="checkbox" autocomplete="off"> <span>PlanetaHosting</span>
                        </label>
                        <label class="btn btn-success">
                            <input type="checkbox" autocomplete="off"> <span>HostingCenter</span>
                        </label>
                        <label class="btn btn-success">
                            <input type="checkbox" autocomplete="off"> <span>NinjaHosting</span>
                        </label>                        
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Filtrar</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
$(function(){
    //Initial request
    requestUpdate([3, 4]);
});
</script>

<?php require __DIR__ . '/../dashboard_footer.html'; ?>