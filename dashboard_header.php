<?php 
include __DIR__ ."/config.php";
?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>SAC</title>

    <!-- Bootstrap Core CSS -->
    <link href="<?= $root_dir_name;?>/include/css/bootstrap.css" rel="stylesheet" type="text/css">

    <!-- MetisMenu CSS -->
    <link href="<?=$root_dir_name;?>/include/css/metisMenu.min.css" rel="stylesheet" type="text/css">

    <!-- Custom CSS -->
    <link href="<?= $root_dir_name;?>/include/css/sb-admin-2.css" rel="stylesheet" type="text/css">

    <!-- Custom Fonts -->
    <link href="<?= $root_dir_name;?>/include/css/font-awesome.min.css" rel="stylesheet" type="text/css">


    <!-- jQuery -->
    <script src="<?=$root_dir_name;?>/include/js/jquery.min.js"></script>
    <script src="<?=$root_dir_name;?>/include/js/jquery-ui.min.js"></script>
    <!-- BOOTSTRAP js -->
    <!--
    <script src="http://code.jquery.com/jquery-2.1.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    -->

    <style type="text/css">
    /*AZUL OSCURO*/
        #nav_sac{
            background-color: #31708F;
        }
        #nav_sac .dropdown-menu{
            background-color: #31708F;
        }
        #nav_sac .navbar-top-links .dropdown{
            background-color: #31708F;
        }
    /*AZUL INTERMEDIO*/ /*#62AFD6*/
        
    /*AZUL CLARO*/
        #nav_sac .navbar-top-links ul{
            background-color: #4194BD;
        }
        #nav_sac .open>a{
            background-color: #4194BD;
        }
        #nav_sac .navbar-top-links a:hover{
            background-color: #4194BD;
        }
        #nav_sac .navbar-top-links a:active{
            background-color: #4194BD;
        }
    /*COLOR LETRAS BLANCO*/
        #nav_sac .navbar-header{
            margin-top: 15px;
            color: #ECECEC;
            font-size: 18px;
        }
        #nav_sac .navbar-header span{
            margin-left: 10px;
        }
        #nav_sac .navbar-top-links a{
            color: #ECECEC;
        }

        #toggle_sidebar{
            cursor: pointer;
        }
    /*CONTENIDO DE LA PAGINA*/
    <?php 
    if($noborder){
        echo'#contenedor_principal{
            margin-left: -30px;
            margin-right: -30px;
        }';
    }
    else
    {
        echo '.row{
            margin-top: 20px;
        }';
    }
    ?>


    </style>

    <script type="text/javascript">
    $(function() {
        var hidden_sidebar = false;
        $("#toggle_sidebar").on("click",function(){
            if(hidden_sidebar){
                $(".sidebar").show();
                $("#page-wrapper").css("margin-left","250px");
            }
            else{
                $(".sidebar").hide();
                $("#page-wrapper").css("margin-left","0px");
            }
            hidden_sidebar = !hidden_sidebar;
            $("#toggle_sidebar").removeClass("glyphicon-menu-left");
            $("#toggle_sidebar").removeClass("glyphicon-menu-right");
            if(hidden_sidebar){
                $("#toggle_sidebar").addClass("glyphicon-menu-right");
            }else{
                $("#toggle_sidebar").addClass("glyphicon-menu-left");
            }
        });

    });

    </script>
</head>

<body>

    <div id="wrapper">

        <nav id="nav_sac" class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">

            <!-- Barra de arriba -->
            <div class="navbar-header">
                <span id="toggle_sidebar" class="glyphicon glyphicon-menu-left"></span>
                <span>Sistema de Atención al Cliente</span>
            </div>
            

            <ul class="nav navbar-top-links navbar-right">
 
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-user fa-fw"></i> <?= utf8_decode($_SESSION["user_name"]);?> <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <!-- <li><a href="#"><i class="fa fa-user fa-fw"></i> Mi perfil</a>
                        </li>
                        <li><a href="#"><i class="fa fa-gear fa-fw"></i> Ajustes</a>
                        </li>
                        <li class="divider"></li> -->
                        <li><a href="<?=$root_dir_name;?>/yubikey_login/logout.php"><i class="fa fa-sign-out fa-fw"></i> Salir</a>
                        </li>
                    </ul>
                </li>
            </ul>
           
            <!-- Barra de la derecha -->
            <div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
                        
                       <!--  <li>
                            <a href="<?=$root_dir_name?>/dashboard.php"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
                        </li> -->
                        <!--TIPIFICACIONES-->
                        <li>
                            <a href="#"><i class="fa fa-pencil fa-fw"></i> Tipificaciones<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="<?=$root_dir_name?>/tipificaciones/index.php"><i class="fa fa-pencil fa-fw"></i> Nueva</a>
                                </li>
                                <li>
                                    <a href="<?=$root_dir_name?>/tipificaciones/buscar.php"><i class="fa fa-search fa-fw"></i> Buscar</a>
                                </li>
                            </ul>
                        </li>
                        <!--ESTADISTICAS-->
                        <li>
                            <a href="<?=$root_dir_name?>/stats/porc_clientes_contactan.php"><i class="fa fa-bar-chart fa-fw"></i> Estadisticas</a>
                        </li>
                        <li>
                            <a href="http://sistemas.hosting.cl/SAC/llamar_clientes_nuevos"><i class="fa fa-volume-control-phone fa-fw"></i> Llamar Clientes Nuevos</a>
                        </li>
                        <!-- Info historica centinela -->
                        <li>
                            <a href="http://sistemas.hosting.cl/SAC/centinela"><i class="fa fa-line-chart fa-fw"></i> Uptime Servidores</a>
                        </li>
                        <!--Reportar errores páginas-->
                        <li>
                            <a target="_blank" rel="noopener noreferrer"  href="http://www.centraldehosting.net/opina/"><i class="fa fa-bug fa-fw"></i> Ticket mejoras en sitios</a>
                        </li>
                    </ul>
                </div>
                <!-- /.sidebar-collapse -->
            </div>
            
        </nav>

        <!-- Contenido de la página -->
        <div id="page-wrapper">
            <div id="contenedor_principal" class="container-fluid">
                <div class="row">
