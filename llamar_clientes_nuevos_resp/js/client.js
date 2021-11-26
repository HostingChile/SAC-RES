$(function(){
    
    var alert_timeout;
    
    //Change contacted status
    $("#tbl-clients").on("change", ".contacted", function(){
        var $this = $(this);
        var $client = $this.parents(".client");
        var id = $client.data('id');
        var hosting = $client.data('empresa');
        var contacted = $this.is(":checked");
        var $loading = $("tr.loading");
        var $err_message = $("tr.error-message");
        
        $client.hide().after($loading);
        $loading.show();
        
        var params = { id: id, hosting: hosting, contacted: contacted };
        
        $.post( "request_handler.php", { action: "change_contacted", params: params}, function( response ) {
            $loading.hide();    
            if(!response.success){
                $this.prop("checked", !contacted);
                $loading.after($err_message);
                $err_message.find("span").text(response.msg);
                $err_message.removeClass("hidden");
                
                setTimeout(function(){
                    $err_message.addClass("hidden");
                    $client.show();
                }, 5000);
                
                return;
            }
            
            $this.parent().attr("title", contacted ? response.msg : "");
            
            $client.show();
        },"json"); 
               
    });
    
    //Change comments
    $('#update-comments').click(function(){
        var $modal = $('#modal-comments');
        var id = $modal.data('id');
        var hosting = $modal.data('empresa');
        var comment = $modal.find("textarea").val();
        var $loading = $modal.find('.spinner');
        var $alert = $modal.find('.alert');
        
        $alert.addClass("hidden").removeClass("alert-success alert-danger");
        
        var params = { id: id, hosting: hosting, comment: comment };
        
        clearTimeout(alert_timeout);
        
        $loading.removeClass("hidden");
        
        $.post( "request_handler.php", { action: "edit_comment", params: params}, function( response ) {
            $loading.addClass("hidden");
            if(!response.success){
                $alert.find("span.message").text(response.msg);
                $alert.addClass("alert-danger");
            }
            else{
                $alert.find("span.message").text("Changes saved");
                $alert.addClass("alert-success");
                $(".client").filter(function(){
                    return $(this).data("id") === id && $(this).data("empresa") == hosting;
                }).data('comentario', comment);
            }
            
            $alert.removeClass("hidden");
            alert_timeout = setTimeout(function(){
                $alert.addClass("hidden");
            }, 5000);
            
        },"json"); 
    });
    
    //Open modal
    $("#tbl-clients").on("click", ".btn-comments", function(){
        var $client = $(this).parents(".client");
        var id = $client.data('id');
        var hosting = $client.data('empresa');
        var comment = $client.data('comentario');
        var $modal = $('#modal-comments');
        
        $modal.data('id', id);
        $modal.data('empresa', hosting);
        $modal.find("textarea").val(comment);;
        
        $modal.modal('show');
    });
    
    $('#modal-comments').on('hidden.bs.modal', function () {
        clearTimeout(alert_timeout);
        $(this).find('.alert').addClass("hidden");
    });
});

