<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

<script src="include/bootstrap-datepicker-1.4.0-dist/js/bootstrap-datepicker.js"></script>
<script src="include/bootstrap-datepicker-1.4.0-dist/js/bootstrap-datepicker.min.js"></script>
<script src="include/bootstrap-datepicker-1.4.0-dist/locales/bootstrap-datepicker.es.min.js"></script>
<link rel="stylesheet" href="include/bootstrap-datepicker-1.4.0-dist/css/bootstrap-datepicker3.css">

<script type="text/javascript">
$(function () {
	$('input').datepicker({
	});
});
</script>
</head>

<div class="input-daterange input-group" id="datepicker">
    <input type="text" class="input-sm form-control" name="start" />
    <span class="input-group-addon">to</span>
    <input type="text" class="input-sm form-control" name="end" />
</div>

