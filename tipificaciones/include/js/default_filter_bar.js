function applyDateRangePicker(){
	$('.date_range_picker').daterangepicker({
		autoUpdateInput: false,
		autoApply: true,
	    "ranges": {
	        "Hoy": [ moment(), moment() ],
	        "Ayer": [ moment().subtract(1, 'days'), moment().subtract(1, 'days') ],
	        "Últimos 7 días": [ moment().subtract(6, 'days'), moment() ],
	        // "Últimos 30 días": [ moment().subtract(29, 'days'), moment() ],
	        "Mes actual": [ moment().startOf('month'), moment().endOf('month') ],
	        "Mes pasado": [ moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month') ],
	        "Año actual": [ moment().startOf('year'), moment().endOf('year') ],
	        "Año pasado": [ moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year') ]
	    },
	    "locale": {
	        "format": "DD/MM/YYYY",
	        "separator": " - ",
	        "applyLabel": "Aplicar",
	        "cancelLabel": "Borrar",
	        "fromLabel": "Desde",
	        "toLabel": "Hasta",
	        "customRangeLabel": "Personalizado",
	        "daysOfWeek": ["Do","Lu","Ma","Mi","Ju","Vi","Sa"],
	        "monthNames": ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"],
	        "firstDay": 1
	    }
	}, function(start, end, label) {
	  	// console.log('New date range selected: '+ start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')');
	});

	$('.date_range_picker').on('apply.daterangepicker', function(ev, picker) {
		$(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
	});

	$('.date_range_picker').on('cancel.daterangepicker', function(ev, picker) {
		$(this).val('');
	});
}

$(function(){
	applyDateRangePicker();
});