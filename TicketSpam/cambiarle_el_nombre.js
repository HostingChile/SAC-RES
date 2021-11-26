function brandClicked(brand_id){
    clearBrands();
    const id = brand_id['id'];
    $('#' + id).addClass('selected_option');
}

function clearBrands(){
    $('.custom_select_option').removeClass('selected_option');
}

function getSelectedBrandName(){
    return $('.selected_option').attr('brand-name');
}

function checkWhmcsTicketMailFilter(){
    let email_to_search = $('#search_email_input').val().trim();
    if(email_to_search === '' || !getSelectedBrandName()){
        clearResults();
        displayNotice('Seleccione marca e ingrese mail a buscar', 'danger');
        return;
    }
    $('#search_email_input').val('');
    const selected_brand_name = getSelectedBrandName();
    const query = getHttpQuery(email_to_search,selected_brand_name);
    clearResults();
    $.get(query, function(data){
        $("#result_bar").css("display", "inline-block");
        const decoded_data = JSON.parse(data);
        if(decoded_data['result'] == true){
            displayDeleteButton(email_to_search,selected_brand_name);
        }        
        else{
            displayNotFound(email_to_search, selected_brand_name);
        }
    } );
}

//If the email provided is in the spam filter, show a button to remove it from it
function displayDeleteButton(email, brand){
    let delete_button = $('#result');
    delete_button.css("display", "inline-block");
    delete_button.click(function(){
        deleteEmailFromSpamFilter(email, brand);
    })
    $("#searched_email").html(email);
}

//Function to be called in case that an email is not found in the spam filter
function displayNotFound(email,brand){
    const message = email + " no se encuentra en el filtro de spam del whmcs de " + brand;
    $("#searched_email").html(message);
}

function displayNotice(message, type_of_notice){
    $('#alert').css("display", "inline-block");
    $('#alert').html(message);
    $('#alert').removeClass('alert-success');
    $('#alert').removeClass('alert-danger');
    $('#alert').addClass('alert-' + type_of_notice);
}

//Clear previous results before a query
function clearResults(){
    $("#result_bar").css("display", "none");
    $("#result").css("display", "none");
    $("#alert").css("display", "none");
    $('#result').prop('onclick',null).off('click');
    $("#searched_email").html('');
    clearBrands();
}

//Remove an email from spam filter. Makes http request to do so.
function deleteEmailFromSpamFilter(email,brand){
    $.ajax({
        url: getHttpQuery(email,brand),
        type: 'DELETE',
        success: function(result){
            clearResults();
            displayNotice(email + " eliminado correctamente del filtro de spam de " + brand, 'success');
        }
    });
}

function getHttpQuery(email,brand){
    return "get_whmcs_ticket_mail_filter.php" + "?email=" +email + "&brand=" + brand;
}
