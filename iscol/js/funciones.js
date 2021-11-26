$( document ).ready(function() {
    
	var year = 2014;
	var current_year = new Date().getFullYear();
	var current_month = new Date().getMonth();
	var list = [];
	for (var i = current_month + 1; i <= 12; i++)
		list.push(i);
	var month = "";
	
	options = 
	{
		pattern: 'm-yyyy', // Default is 'mm/yyyy' and separator char is not mandatory
		selectedYear: current_year,
		startYear: 2014,
		finalYear: current_year,
		monthNames: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']
	};
	$('#monthpicker').monthpicker(options);
	$('#monthpicker').monthpicker().bind('monthpicker-click-month', function (e, value) { month = value; $('#actualizar').attr("disabled", false); });
	$('#monthpicker').monthpicker().bind('monthpicker-change-year', function (e, value) { 
		year = value;
		if(value < current_year)
			$('#monthpicker').monthpicker('disableMonths', []);
		else
			$('#monthpicker').monthpicker('disableMonths', list);
		});
	$('#monthpicker').monthpicker('disableMonths', list);
	
	$('.tabla_secundaria tr').click( function() {
		
		$( ".operador" ).css("background-color", "white");
		var operador = $(this).children().first().text();
		$( "[id='"+operador+"']" ).css("background-color", "#5CADFF");
    });

    
    var input = $("#monthpicker");
    var offset = input.offset();
    $('.ui-datepicker').css("position","fixed");
    $('.ui-datepicker').css("left",offset.left);
    $('.ui-datepicker').css("top",offset.top+input.height()+10);
});