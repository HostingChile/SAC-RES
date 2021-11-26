
<script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
<script src="include/js/drilldown-menu/fg.menu.js"></script>
<link href="include/js/drilldown-menu/fg.menu.css" rel="stylesheet">

<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css">


<script type="text/javascript">
	$(function() {
		// BUTTONS
		$('.fg-button').hover(
		  function(){ $(this).removeClass('ui-state-default').addClass('ui-state-focus'); },
		  function(){ $(this).removeClass('ui-state-focus').addClass('ui-state-default'); }
		);

		// MENUS      
		$('#hierarchy').menu({
		content: $('#hierarchy').next().html(),
		crumbDefaultText: ' '
		});


	});
</script>

<style>
	#menu{
		width: 200px;
	}
</style>

<a tabindex="0" href="#search-engines" class="fg-button fg-button-icon-right ui-widget ui-state-default ui-corner-all" id="flat">
  <span class="ui-icon ui-icon-triangle-1-s"></span>
  Flat Dropdown Menu
</a>
<div id="search-engines" class="hidden">




<ul>
  <li><a href="#">Item 1</a>
    <ul>
      <li><a href="#">Item 1.1</a></li>
      <li><a href="#">Item 1.2</a></li>
      <li><a href="#">Item 1.3</a></li>
      <ul>
        <li><a href="#">Item 1.3.1</a></li>
        <li><a href="#">Item 1.3.2</a></li>
        <li><a href="#">Item 1.3.3</a></li>
      </ul>
      <li><a href="#">Item 1.4</a></li>
      <li><a href="#">Item 1.5</a></li>
    </ul>
  </li>
  <li><a href="#">Item 2</a></li>
</ul>

</div>

