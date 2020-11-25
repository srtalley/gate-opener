$(document).ready(function() {

//GLOBAL VARS
//Stores the SetInterval so it can be cleared
var DoorStatusIntervalGlobal;
var DoorClosingTextGlobal;// = "Closing...";
var DoorOpeningTextGlobal;// = "Opening...";
var Door1Text;
var Door2Text;
var Door1ButtonTextTimeoutGlobal;
var Door2ButtonTextTimeoutGlobal;
var Door1ButtonStatusTimeoutGlobal;
var Door2ButtonStatusTimeoutGlobal;

function GetDoorStatus()
{
	var doorOptions = [];

	//define each door. Repeat for each door you want.
	doorOptions.push({
		'gpio': 21,
		'door': 2,
	});
	doorOptions.push({
		'gpio': 22,
		'door': 1,
	});
	//loop through the array of doors and call the ajax portion of the page
	Object.keys(doorOptions).forEach(function(key){
		ajaxURL = 'doorstatus.php?gpio=' + doorOptions[key]['gpio'] + '&door=' + doorOptions[key]['door'];
		$.ajax({
			url: ajaxURL,
			cache: false,
			success: function(data){
				$('#door' + doorOptions[key]['door'] + '-status').html(data);
				//Set the hidden element for the button text when clicked
				var doorStatus = $.trim($('#door' + doorOptions[key]['door'] + '-status').text());
				// console.log(doorStatus);
				if( doorStatus == DoorOpeningTextGlobal )
				{
					$('#door' + doorElement + '-opening').text(DoorClosingTextGlobal);
				} else if ( doorStatus == DoorClosingTextGlobal ) {
					$('#door' + doorElement + '-opening').text(DoorOpeningTextGlobal);
				}
			}
		});


	});


} //end function GetDoorStatus

GetDoorStatus();
DoorStatusIntervalGlobal = setInterval(GetDoorStatus, 30000);

	$('button').click(function(e) {
		//Clear the current interval so that the door status doesn't change
		//in the middle if the user stops and starts and stops the door again
		clearInterval(DoorStatusIntervalGlobal);

		//Disable the button for 2 seconds to allow for our relay
		$(e.currentTarget).prop("disabled", true);
		$(e.currentTarget).addClass("disabled-button");

		//Get the door element we will change
		var doorElement = $(this).attr("id");

		//Clear any current timeout that's changing the button text in case
		//the user is clicking the button rapidly
		if(doorElement == 1) {
			clearTimeout(Door1ButtonTextTimeoutGlobal);
		} else if (doorElement == 2) {
			clearTimeout(Door2ButtonTextTimeoutGlobal);
		}


		//Call the page with the _GET element to trigger the door change
		$.get("/?trigger=" + doorElement);

		//Set the opening or closing text when the button is clicked.
		$('#door' + doorElement + '-buttonText').hide();
		$('#door' + doorElement + '-opening').fadeIn();

		//Re-enable the button after 1.5 seconds
		setTimeout(function() {
			//Disable the button for 1.5 seconds to allow for our relay
			$(e.currentTarget).prop("disabled", false);
			$(e.currentTarget).removeClass("disabled-button");
		}, 2000);

		//Set a timeout (which can be cleared) to set the button text back
		if(doorElement == 1) {
			Door1ButtonTextTimeoutGlobal = setTimeout(ButtonRestore, 2000);
		} else if (doorElement==2) {
			Door2ButtonTextTimeoutGlobal = setTimeout(ButtonRestore, 2000);
		}

		function ButtonRestore() {
			$('#door' + doorElement + '-opening').hide();
			$('#door' + doorElement + '-buttonText').fadeIn();
			//Start the Door Interval again
		} //end function ButtonRestore()

		//Restart the status interval
		DoorStatusIntervalGlobal = setInterval(GetDoorStatus, 30000);
	});
	$('#cameraLinks').on('click', function(){
		console.log('b');
		resizeViewportOnDesktop();
	});
	$('#cameraImg').on('click', function(){
		resizeViewportOnDesktop();
	});
	function resizeViewportOnDesktop() {
		$('#cameraImg').addClass('full-size-viewport');
	}
});
