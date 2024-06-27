
jQuery(document).ready(function($) {
    $('#add_hoiday_btn').on('click', function() {
        // Check if the date input already exists
        
            let dateInput = $('<input>', {
                type: 'date',
                class: 'date-input',
                name: 'holiday_date-input[]'
            });

            // Append the date input to the container
            $('#holiday_date_container').append(dateInput);
        
    });

});    