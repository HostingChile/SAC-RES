//Process request
function requestUpdate(values){
    var start = values[1];
    var end = values[0];
    var $loading = $("tr.loading");
    var $err_message = $("tr.error-message");

    last_values = [start, end];

    $loading.show();
    $("#tbl-clients tbody .client").remove();
    $err_message.addClass("hidden");
    $("#filters-button").prop("disabled", true);
    slider.setAttribute('disabled', true);
    
    var params = { start: start, end: end };

    $.post( "request_handler.php", { action: "get_all", params: params }, function( response ) {
        $loading.hide();     
        if(!response.success){
            $err_message.find("span").text(response.msg);
            $err_message.removeClass("hidden");
            return;
        }
        var data = response.msg;
        $.each(data, function(i, item){
            var $row = createRow(item);
            $("#tbl-clients tbody").append($row);
        });
        filterClients();
        $("#filters-button").prop("disabled", false);
        slider.removeAttribute('disabled');
         
        $("#tbl-clients").trigger("update").trigger("appendCache").trigger("sorton",[ [[2,1]] ]);
    }, "json");      
};
  
//Creates a client (table row)  
function createRow(item){
    var $row = $('<tr class="client middle '+item.dns_status+'"></tr>');
    $row.data('id', item.id);
    $row.data('empresa', item.empresa);
    $row.data('comentario', item.comentario);
    $row.append('<td>'+item.nombre+'</td>');
    $row.append('<td>'+item.fono+' / '+item.cel+'</td>');
    $row.append('<td class="center-text">'+item.fecha+'</td>');
    $row.append('<td><a href="http://'+item.dominio+'" target="_blank">'+item.dominio+'</a></td>');
    $row.append('<td class="hosting">'+item.empresa+'</td>');

    var $dns_list = $('<td></td>');
    if(item.dns.length == 0){
        $dns_list.append('<span>Sin DNS</span>');
    }
    else if(item.dns.length == 1){
        $dns_list.append('<span>'+item.dns+'</span>');
    }
    else{
        $dns_list.append('<ul class="list-group"></ul>');
        $.each(item.dns, function(i, dns_item){
            if(i == 0){
                $dns_list.find('ul').append('  <li class="list-group-item" data-toggle="collapse" data-target="#'+item.dominio+'">'+dns_item+'</li>'+
                                    '<div id="'+item.dominio+'" class="collapse"></div>');
            }
            else{
                $dns_list.find('div').append('<li class="list-group-item" data-toggle="collapse" data-target="#'+item.dominio+'">'+dns_item+'</li>');
            }
        });
    }

    $row.append($dns_list);
    
    var contacted = (item.contactado == "si") ? "checked" : " ";
    var contacted_by = (item.contactado == "si") ? item.contactado_por : "";
    $row.append('<td>'+
                    '<label class="switch switch-yes-no" title="'+contacted_by+'">'+
                        '<input class="switch-input contacted" type="checkbox"'+contacted+'/>'+
                        '<span class="switch-label" data-on="SI" data-off="NO"></span>'+
                        '<span class="switch-handle"></span>'+
                    '</label>'+
                '</td>');
        
    $row.append('<td class="center-text">'+
                    '<button type="button" class="btn-comments btn btn-warning" aria-label="Comment">'+
                        '<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>'+
                    '</button>'+
                '</td>');

    return $row;
}

//Filters the clients table
function filterClients(){
    var hide_clients_dns = $("#show-clients-dns").is(":not(:checked)");
    var hide_clients_contacted = $("#show-clients-contacted").is(":not(:checked)");
    var hide_hosting = [];
    $("#show-hosting").find('.active span').each(function(){
       hide_hosting.push($(this).text());
    });

    $(".client").show();

    //Hide/show by dns
    if(hide_clients_dns) {$(".client.success").hide();}

    //Hide/show by contacted
    if(hide_clients_contacted) {$("input:checked.contacted").parents(".client").hide();};

    //Hide/show by hosting
    $(".client").filter(function(){
        return hide_hosting.indexOf($(this).find(".hosting").text()) !== -1; 
    }).hide();
}

$(function(){
   //Fixed collapse on dynamiccally generated elements
    $("#tbl-clients").on("click", ".list-group", function(e){
        e.preventDefault();
        $(this).find(".collapse").collapse('toggle');
    }); 
    
    //Initialize table sorter
    $("#tbl-clients").tablesorter({ 
        dateFormat : "ddmmyyyy",
        headers: { 
            2: { sorter: "shortDate", sortInitialOrder : 'asc' },
            6: { sorter: false }, 
            7: { sorter: false } 
        }
    });
    
    //On filter modal close, update cookeis and filter
    $("#modal-filters").on("hidden.bs.modal", function(){
        filterClients();
        
        var hide_clients_dns = $("#show-clients-dns").is(":not(:checked)");
        var hide_clients_contacted = $("#show-clients-contacted").is(":not(:checked)");
        var hide_hosting = [];
        $("#show-hosting").find('.active span').each(function(){
           hide_hosting.push($(this).text());
        });
        
        //Update cookies
        Cookies.set("hide_clients_dns", hide_clients_dns);
        Cookies.set("hide_clients_contacted", hide_clients_contacted);
        Cookies.set("hide_hosting", hide_hosting);
    });
    
    //Load cookies on page load
    function loadCookies(){
        var hide_clients_dns = typeof Cookies.get("hide_clients_dns") !== "undefined" ? Cookies.get("hide_clients_dns") === "true" : false;
        var hide_clients_contacted = typeof Cookies.get("hide_clients_contacted") !== "undefined" ? Cookies.get("hide_clients_contacted") === "true" : false;
        var hide_hosting = typeof Cookies.get("hide_hosting") !== "undefined" ? JSON.parse(Cookies.get("hide_hosting")) : [];        
        
        $("#show-clients-dns").prop('checked', !hide_clients_dns);
        $("#show-clients-contacted").prop('checked', !hide_clients_contacted);
        $("#show-hosting span").each(function(){
            if(hide_hosting.indexOf($(this).text().trim()) !== -1) {$(this).parents(".btn").addClass("active");}
        });
    };
    
    loadCookies();
    
});