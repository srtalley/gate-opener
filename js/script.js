//v2026.01.23 - Added schedule management
$(document).ready(function() {

    //GLOBAL VARS
    var Gate1Text;
    var Gate1ButtonTextTimeoutGlobal;

    // ==========================================
    // Gate Button Handling (existing code)
    // ==========================================
    
    // Only handle gate buttons, not schedule/refresh buttons
    $('div.activation-button button').click(function(e) {

        //Disable the button for 2 seconds to allow for our relay
        $(e.currentTarget).prop("disabled", true);
        $(e.currentTarget).addClass("disabled-button");

        //Get the gate element we will change
        var gateElement = $(this).attr("id");

        //Clear any current timeout that's changing the button text in case
        //the user is clicking the button rapidly
        if (gateElement == 1) {
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
        if (gateElement == 1) {
            Gate1ButtonTextTimeoutGlobal = setTimeout(ButtonRestore, 2000);
        }

        function ButtonRestore() {
            $('#gate' + gateElement + '-activating').hide();
            $('#gate' + gateElement + '-buttonText').fadeIn();
        }

    });

    // ==========================================
    // Schedule Management
    // ==========================================
    
    // Toggle schedule panel
    $('#scheduleToggle').on('click', function() {
        $(this).toggleClass('open');
        $('#schedulePanel').toggleClass('open');
        
        // Load current schedule when opening
        if ($(this).hasClass('open')) {
            loadSchedule();
        }
    });
    
    // Load current schedule from API
    function loadSchedule() {
        $('#scheduleStatus').text('Loading...').removeClass('success error');
        
        $.ajax({
            url: '/crontab-api.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.schedule) {
                    var schedule = response.schedule;
                    $('#scheduleEnabled').prop('checked', schedule.enabled);
                    
                    // Format time for input
                    var hour = String(schedule.hour).padStart(2, '0');
                    var minute = String(schedule.minute).padStart(2, '0');
                    $('#scheduleTime').val(hour + ':' + minute);
                    
                    $('#scheduleStatus').text('').removeClass('success error');
                }
            },
            error: function() {
                $('#scheduleStatus').text('Failed to load schedule').addClass('error');
            }
        });
    }
    
    // Save schedule
    $('#saveSchedule').on('click', function() {
        var $btn = $(this);
        var $status = $('#scheduleStatus');
        
        // Parse time
        var timeVal = $('#scheduleTime').val();
        var timeParts = timeVal.split(':');
        var hour = parseInt(timeParts[0], 10);
        var minute = parseInt(timeParts[1], 10);
        var enabled = $('#scheduleEnabled').is(':checked');
        
        $btn.prop('disabled', true);
        $status.text('Saving...').removeClass('success error');
        
        $.ajax({
            url: '/crontab-api.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                enabled: enabled,
                hour: hour,
                minute: minute
            }),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $status.text('Saved!').addClass('success');
                    setTimeout(function() {
                        $status.text('').removeClass('success');
                    }, 2000);
                } else {
                    $status.text(response.error || 'Save failed').addClass('error');
                }
            },
            error: function() {
                $status.text('Save failed').addClass('error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // ==========================================
    // Camera Options (existing code)
    // ==========================================
    
    $('#showCameraOptions').on('click', function() {
        if ($(this).hasClass('camera-options-open')) {
            $('#cameraSelection').slideUp();
            $(this).removeClass('camera-options-open');
        } else {
            $('#cameraSelection').slideDown();
            $(this).addClass('camera-options-open');
        }
    });
    
    $('#cameraLinks').on('click', function() {
        resizeViewportOnDesktop();
    });
    
    $('#cameraImg').on('click', function() {
        resizeViewportOnDesktop();
    });

    function resizeViewportOnDesktop() {
        $('#cameraImg').addClass('full-size-viewport');
    }
    
    // ==========================================
    // Refresh Button
    // ==========================================
    
    $('#refresh button').on('click', function() {
        console.log('Refresh button clicked');
        location.reload();
    });
});
