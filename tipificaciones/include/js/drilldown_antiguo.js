$(function() {

    var list = [];

    $.post( "http://sistemas.hosting.cl/SAC/pages/tipificaciones/ajax/getAllCategories.php", function(data) {
        console.log(data);
        data = jQuery.parseJSON(data);
        console.log(data);
        if(data.success){
            list = data.categories;
            //Render the initial list
            renderList(list);
        }
        else{
            alert("No se pudo cargar las categorias de tipificacion: "+data.message);
        }
    });

    //Helper to search objects based on the key value pair. Optionally can define exact search or not
    function getObjects(obj, key, val, exact) {
        var objects = [];
        var exact = typeof exact == 'undefined' ? true : exact;
        
        for (var i in obj) {
            if (!obj.hasOwnProperty(i)) continue;
            if (typeof obj[i] == 'object') {
                objects = objects.concat(getObjects(obj[i], key, val, exact));
            } 
            else if ((i == key && obj[key] == val) || (!exact && i == key && obj[key].toString().toLowerCase().indexOf(val) != -1 )) {  
                objects.push(obj);
            }
        }
        
        return objects;
    }

    //Appends a new item to the list using the info contained in the passed object
    function newItem(obj){
        var $item = $('<a href="#" class="list-group-item" >'+obj.title+'</a>');
        $item.data('id', obj.id);
        if(obj.children){
            $item.append('<span class="badge"><span class="glyphicon glyphicon-menu-right" aria-hidden="true"></span></span>');
        }
        
        return $item;
    }

    //Fills the list with the objects contained in the list object
    function renderList(list){   
        $('#drill-down .items .list-group-item').fadeOut().promise().done(function(){
            list.forEach(function(obj){
                var $new_item = newItem(obj);
                $new_item.hide().appendTo('#drill-down .items').fadeIn();
            });
        });
    }

    //Performs a search using the value in the search input
    var timer;
    function search(){
        var search_text = $('#drill-down .search input').val().toLowerCase();
        
        clearInterval(timer);
        timer = setTimeout(function(){
            if(!search_text && $('#drill-down .breadcumb .breadcumb-item').last().text() != 'Inicio'){
                $('#drill-down .breadcumb').html('<span class="breadcumb-item" data-id="0">Inicio</span>');
                renderList(list);
            } else if(search_text){
                $('#drill-down .breadcumb').html('<span class="breadcumb-item search_title" data-id="0">Resultados de la Búsqueda</span>');
                var objs = getObjects(list, 'title', search_text, false);
                renderList(objs);
                if(!objs.length){
                    $('#drill-down .breadcumb .search_title').text('Resultados de la Búsqueda - (No hay resultados)');
                }
            }
        },500);
    }

    //What to do when a user clicks on a list item
    $('#drill-down .items').on('click','.list-group-item',function(){
        if($('.list-group-item.selected').length){
            $('.list-group-item.selected').removeClass('selected');
            $('#drill-down').trigger('unselect', [$(this)]);
        }
        if($(this).find('.badge').length){
            var title = $(this).text();
            var id = $(this).data('id');
            var $breadcumb_item = $('<span class="breadcumb-item">'+title+'</span>');
            $breadcumb_item.data('id',id);
            $('#drill-down .breadcumb').append($breadcumb_item);       
            renderList(getObjects(list, 'id', id, true)[0].children);       
        }
        else{
            $(this).addClass('selected');
            $('#drill-down').trigger('select', [$(this)]);
        }
    });

    //What to do when a user clicks on a breadcumb item
    $('#drill-down .breadcumb').on('click', '.breadcumb-item', function(){
        if($(this).is(':last-child')) return;
        if($(this).hasClass('search_title')){
            search();
            return;
        }
        
        if($('.list-group-item.selected').length){
            $('.list-group-item.selected').removeClass('selected');
            $('#drill-down').trigger('unselect', [$(this)]);
        }
        
        $(this).nextAll('.breadcumb-item').remove();
        var id = $(this).data('id');
        if(id == 0){
            renderList(list);
        }
        else{
            renderList(getObjects(list, 'id', id, true)[0].children);
        }
    });

    //What to do when the user type sin the search input or clicks the remove glyphicon
    $('#drill-down .search').on('keyup', 'input', search).on('click','#remove-glyph',function(){
        $(this).siblings('input').val('');
        if($('#drill-down .breadcumb .breadcumb-item').last().text() != 'Inicio'){
            $('#drill-down .breadcumb').html('<span class="breadcumb-item" data-id="0">Inicio</span>');
            renderList(list);
        }
    });

    //Eventos del drilldown
    $('#drill-down').on('select',function(event, sel){
        $('#btn_tipificar').show();
        $('#selection .data').text(sel.text());
    }).on('unselect',function(event){
        $('#btn_tipificar').hide();
        $('#selection .data').text('Ninguna');
    });

});





