<?php
	
	//v1.0

	error_reporting(E_ALL);
	//Create an array for our gate values
	$gateArray = [];

	//Set the GPIO pin number to the number of the gate you want to control
	$gateArray[] = array(
		"gateNumber" => 1,
		"gpioNumber" => 9
	);//end $gateArray


	if(isset($_GET['trigger']) && $_GET['trigger'] >= 1 && $_GET['trigger'] <= 10 ) {

		$gateTrigger = $_GET['trigger'];

		foreach( $gateArray as $gateItem ) {

			if ( $gateItem['gateNumber'] == $gateTrigger ) {
				shell_exec ('/usr/bin/gpio mode ' . $gateItem['gpioNumber'] . ' out');
				shell_exec('/usr/bin/gpio write ' . $gateItem['gpioNumber'] . ' 0');
				usleep(1000000);
				shell_exec('/usr/bin/gpio write ' . $gateItem['gpioNumber'] . ' 1');
			} //end if 

		} //end	foreach
	} //end if

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Gate Opener</title>
		<meta http-equiv="cache-control" content="max-age=0" />
		<meta name="apple-mobile-web-app-capable" content="yes">

		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="apple-mobile-web-app-title" content="Opener">
		<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

		<!-- <link rel="apple-touch-icon" href="touch-icon-ipad.png?ver=2020.11.29" /> -->
		<link rel="apple-touch-icon" sizes="152x152" href="touch-icon-ipad.png?ver=2020.11.29" />
		<link rel="apple-touch-icon" sizes="180x180" href="touch-icon-iphone-retina.png?ver=2020.11.29" />
		<link rel="apple-touch-icon" sizes="167x167" href="touch-icon-ipad-retina.png?ver=2020.11.29" />

		<link href='https://fonts.googleapis.com/css?family=Archivo+Narrow:400,700' rel='stylesheet' type='text/css'>

		<link rel="stylesheet" href="css/style.css?ver=2020.12.28" type="text/css">
		<link rel="manifest" href="manifest.json">

		<script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
		<script type="text/javascript" src="js/script.js?ver=2020.12.28"></script>
	</head>
	<body>
		<!-- <div class="title"> -->
			<!-- <h2>Gate Control</h2> -->
		<!-- </div> -->
		<div class="controller-wrapper">
			<div class="controller">
				<!-- <div class="gate-title"> -->
		      		<!-- <h2>Gate</h2> -->
				<!-- </div> -->
				<div class="activation-button">
			    <button id="1"><span class="buttonText" id="gate1-buttonText">Activate Gate</span><span id="gate1-activating" class="gate-activating">Wait...</span></button>
				</div>
			</div>
		</div>
		<div id="ui3"><iframe src="http://sol.home/ui3.htm?maximize=1&cam=DwyGate"></iframe></div>
		<div id="solarStatus"><iframe src="http://mule.home:3000/d/-eq_QUmRk/mule-solar-summary?orgId=1&refresh=5s&var-job=node&var-name=mule&var-node=localhost&var-port=9100&from=now-24h&to=now&kiosk"></iframe></div>
		<div id="refresh"><button>Refresh</button></div>

	</body>
</html>
