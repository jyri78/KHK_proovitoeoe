<?php
/*
  KHK tarkvaraarendaja konkursi teise vooru proovitöö.
  
  Koostada API, millega serverist dal-api.services.iot.telia.ee pärida andmeid soojusvaheti kohta,
  mille nimeks on: SV_Kopli1 seisuga 02.09.2020 kell 00:00.
  
  
  Lahendus:
  antud projekti puhul kasutan GET meetodit, aga reaalse API korral kasutaksin .htaccess-i mod_rewrite
  moodulit, et teha lingi viisakamaks, näit.:  example.com/api/<nimi>/<kuupäev>;
  samuti paneks funktsiooni eraldi faili.
*/


define('API_KEY', '6368b9d8-37ad-4c0f-b073-8e7021d4c501');
define('API_URL', 'https://dal-api.services.iot.telia.ee/person-series');

date_default_timezone_set('UTC');


// Soojusvaheti nimi ja kuupäev;
// PHP 7 lubab määrata vea korral vaikeväärtuse (?? operaator)
$name = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_ENCODED) ?? false;
$time = strtotime(filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING)) ?? false;


// Kui nime pole antud, siis pole mõtet jätkata
if (!$name) {
	header('HTTP/1.0 400 Bad Request');
	echo 'Sisesta soojusvaheti nimmi!';  // sõltuvalt serverist
	exit;
}

// Kui kuupäeva pole ette antud, siis määrab selleks hetke kuupäeva
if (!$time) $time = mktime(0, 0, 0, date('n'), date('j'), date('Y'));


/*
	Soojusvaheti nimele vastava ID pärimine
*/
$result = json_decode(get_result(API_URL .'?query='. $name), true);

// Kui otsitavat soojusvahetit ei leitud, siis annab vastava teate ja lõpetab töö
if ($result['totalElements'] == 0) {
	header('HTTP/1.0 404 Not Found');
	echo 'Sisestatud soojusvahetit ei leitud.';  // sõltuvalt serverist
	exit;
}

// Eeldab, et vastuseks saab olla üks samanimeline soojusvaheti
$id = $result['content'][0]['id'];


/*
	Soojusvaheti ID ja määratud kuupäevale vastava info leidmine
*/
$begTime = mktime(0, 0, 0, date('n',$time), 1, date('Y',$time));
$begDate = date('Y-m-d', $begTime) .'T00%3A00%3A00.000Z';
$endDate = date('Y-m-d', $time) .'T00%3A00%3A00.000Z';


$result = get_result(API_URL .'/'. $id .'/latest'
					.'?aggregationGroupingType=HOURLY'
					.'&dateFrom='. $begDate .'&dateTo='. $endDate);

// Kuna ülesandes ei selgu, kuidas andmed peaks väljastatama,
// väljastab siis sama tulemused
echo $result;


// -----------------------------------------------------------------------------
// Funktsioon päringu tegemiseks
// -----------------------------------------------------------------------------
function get_result($url) {
	$ch = curl_init($url);

	// Mõned päringu seaded
	$httpHeader = array(
		'Accept: */*',
		'X-Auth-Token: '. API_KEY
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	// Teostab päringu
	$result = curl_exec($ch);
	curl_close($ch);
	
	return $result;
}
