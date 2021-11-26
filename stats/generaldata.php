<?php

$hostings_sac = array(
	'hosting'=> 'Hosting',
	'planeta'=> 'PlanetaHosting',
	'hcenter'=> 'Hostingcenter',
	'ihost' =>  'iHost',
	'ninja'=> 'NinjaHosting',
	'planetape'=> 'PlanetaPeru',
	'inka'=> 'InkaHosting',
	'1hosting' => '1Hosting',
	'ninjape' => 'NinjaPeru',
	'planetaco' => 'PlanetaColombia',
	'hcenterco' => 'HostingcenterColombia',
	'ninjaco' => 'NinjaColombia',
	'dehosting' => "Dehosting"
);

$hosting_names = array_values($hostings_sac);

$colors = array(
	'Hosting' => '#009900', 
	'PlanetaHosting' => '#FF5B00', 
	'HostingCenter' => '#2F52FF', 
	'iHost' => '#6B2B8F',
	'NinjaHosting' => '#FF0005', 
	'PlanetaPeru' => '#FD8A4A', 
	'InkaHosting' => '#FF3FB2',
	'1Hosting' => '#7E7E7E',
	'NinjaPeru' => '#FF4043',
	'PlanetaColombia' => '#FFAD80',
	'HostingcenterColombia' => '#5B77FF',
	'NinjaColombia' => '#F9797C', 
	'Total' => '#E0E0DD');

$color_string = "";
foreach ($colors as $brand => $color) {
	$color_string .= "'$brand': '$color',";
}
$color_string = substr($color_string, 0,-1);

$hosting_names_string = "";
foreach ($hosting_names as $hosting_name) {
	$hosting_names_string .= "'$hosting_name',";
}
$hosting_names_string = substr($hosting_names_string, 0,-1);

$medios = array('Chat','Telefono','Ticket');

$months = array();
$current_month = date("m",strtotime($start_date));
$current_year = date("Y",strtotime($start_date));
if(date("d",strtotime($end_date)) == 1)
	$end_month = date("m",strtotime($end_date)) - 1;
else
	$end_month = date("m",strtotime($end_date));
$end_year = date("Y",strtotime($end_date));

// echo "start_date: $start_date, end_date: $end_date<br>";
// echo "current_year: $current_year, end_year: $end_year<br>";
while($current_year < $end_year || ($current_year == $end_year && $current_month <= $end_month))
{
	// echo "$current_year-$current_month<br>";
	$months[] = "$current_year-$current_month";
	$current_month++;
	if($current_month>12)
	{
		$current_month = 1;
		$current_year++;
	}
	if(strlen($current_month)<=1)
		$current_month = "0$current_month";
}

?>