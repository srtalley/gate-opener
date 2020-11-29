$(document).ready(function() {

//GLOBAL VARS
var Gate1Text;
var Gate1ButtonTextTimeoutGlobal;

	$('button').click(function(e) {

		//Disable the button for 2 seconds to allow for our relay
		$(e.currentTarget).prop("disabled", true);
		$(e.currentTarget).addClass("disabled-button");

		//Get the gate element we will change
		var gateElement = $(this).attr("id");

		//Clear any current timeout that's changing the button text in case
		//the user is clicking the button rapidly
		if(gateElement == 1) {
			clearTimeout(Gate1ButtonTextTimeoutGlobal);
		}

		//Call the page with the _GET element to trigger the gate change
		$.get("/?trigger=" + gateElement);

		//Set the opening or closing text when the button is clicked.
		$('#gate' + gateElement + '-buttonText').hide();
		$('#gate' + gateElement + '-activating').fadeIn();

		//Re-enable the button after 1.5 seconds
		setTimeout(function() {
			//Disable the button for 1.5 seconds to allow for our relay
			$(e.currentTarget).prop("disabled", false);
			$(e.currentTarget).removeClass("disabled-button");
		}, 2000);

		//Set a timeout (which can be cleared) to set the button text back
		if(gateElement == 1) {
			Gate1ButtonTextTimeoutGlobal = setTimeout(ButtonRestore, 2000);
		} 

		function ButtonRestore() {
			$('#gate' + gateElement + '-activating').hide();
			$('#gate' + gateElement + '-buttonText').fadeIn();
			//Start the Gate Interval again
		} //end function ButtonRestore()

	});
	$('#cameraLinks').on('click', function(){
 		resizeViewportOnDesktop();
	});
	$('#cameraImg').on('click', function(){
		resizeViewportOnDesktop();
	});
	function resizeViewportOnDesktop() {
		$('#cameraImg').addClass('full-size-viewport');
	}
	//Handle the reload
	$('#refresh button').on('click', function() {
		console.log('Refresh button clicked');
		location.reload();
	});
});
