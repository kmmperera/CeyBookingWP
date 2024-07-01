<?php
/*
Plugin Name: Simple Booking Plugin Two
Description: A simple booking plugin for managing staff and services.
Version: 1.0
Author: Your Name
*/

// Enqueue scripts and styles
function sbp_enqueue_scripts() {
    wp_enqueue_style('sbp-style', plugin_dir_url(__FILE__) . 'assets/style.css','', '1.0.5', 'all');
    wp_enqueue_script('sbp-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.4', true);
    wp_localize_script('sbp-script', 'sbp_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sbp_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'sbp_enqueue_scripts');
function my_admin_enqueue($hook) {
  
    wp_enqueue_style('sbp-style', plugin_dir_url(__FILE__) . 'assets/admin_styles.css');

    wp_enqueue_script('my_custom_admin_script', plugin_dir_url(__FILE__) . 'assets/adminscripts.js',array('jquery'),'1.0.3',true);
}

add_action('admin_enqueue_scripts', 'my_admin_enqueue');


//new code 


function sbp_admin_menu() {
    add_menu_page('Staff Booking', 'Staff Booking', 'manage_options', 'staff-booking', 'sbp_admin_page', 'dashicons-calendar', 26);
    add_submenu_page('staff-booking', 'Manage Staff', 'Manage Staff', 'manage_options', 'manage-staff', 'sbp_manage_staff_page');
    add_submenu_page('staff-booking', 'Manage Services', 'Manage Services', 'manage_options', 'manage-services', 'sbp_manage_services_page');
    add_submenu_page('staff-booking', 'Working Hours', 'Working Hours', 'manage_options', 'manage-holidays', 'sbp_manage_holidays_page');

}
add_action('admin_menu', 'sbp_admin_menu');

function sbp_admin_page() {
    global $wpdb;
    ?>
    <div class="wrap">
        <h1>Simple Booking Plugin</h1>
        
        <h2>Staff Members</h2>
        <?php
        $staff_members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sbp_staff");
        if ($staff_members) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>Name</th></tr></thead><tbody>';
            foreach ($staff_members as $staff) {
                echo "<tr><td>{$staff->id}</td><td>{$staff->name}</td></tr>";
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No staff members found.</p>';
        }
        ?>

        <h2>Services</h2>
        <?php
        $services = $wpdb->get_results("SELECT s.id, s.service_name, st.name as staff_name FROM {$wpdb->prefix}sbp_services s LEFT JOIN {$wpdb->prefix}sbp_staff st ON s.staff_id = st.id");
        if ($services) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>Service Name</th><th>Staff Member</th></tr></thead><tbody>';
            foreach ($services as $service) {
                echo "<tr><td>{$service->id}</td><td>{$service->service_name}</td><td>{$service->staff_name}</td></tr>";
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No services found.</p>';
        }
        ?>

        <h2>Bookings</h2>
        <?php
        $bookings = $wpdb->get_results("SELECT b.id, st.name as staff_name, s.service_name, b.date, b.time, b.name as client_name, b.email, b.telephone FROM {$wpdb->prefix}sbp_bookings b LEFT JOIN {$wpdb->prefix}sbp_services s ON b.service_id = s.id LEFT JOIN {$wpdb->prefix}sbp_staff st ON b.staff_id = st.id");
        if ($bookings) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>Staff Member</th><th>Service</th><th>Date</th><th>Time</th><th>Client Name</th><th>Email</th><th>Telephone</th></tr></thead><tbody>';
            foreach ($bookings as $booking) {
                echo "<tr><td>{$booking->id}</td><td>{$booking->staff_name}</td><td>{$booking->service_name}</td><td>{$booking->date}</td><td>{$booking->time}</td><td>{$booking->client_name}</td><td>{$booking->email}</td><td>{$booking->telephone}</td></tr>";
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No bookings found.</p>';
        }
        ?>
    </div>
    <?php
}


function sbp_manage_staff_page() {
    global $wpdb;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sbp_add_staff'])) {
        $staff_name = sanitize_text_field($_POST['staff_name']);
        $wpdb->insert("{$wpdb->prefix}sbp_staff", ['name' => $staff_name]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tables'])) {

        sbp_uninstall();
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tables'])) {
        
        sbp_create_tables();
    }
    ?>
    <h1>Manage Staff</h1>
    <form method="post">
        <label for="staff_name">Staff Name:</label>
        <input type="text" id="staff_name" name="staff_name" required>
        <button type="submit" name="sbp_add_staff">Add Staff</button>
    </form>
    <h2>Staff Members</h2>
    <ul>
        <?php
        $staff = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sbp_staff");
        foreach ($staff as $member) {
            echo "<li>{$member->name}</li>";
        }
        ?>
    </ul>
    <form method="post">
    <button type="submit" name="delete_tables">
        Delete Tables
    </button>
    </form>
    <form method="post">
    <button type="submit" name="create_tables">
       Create Tables
    </button>
    </form>
    <?php
}

function sbp_manage_services_page() {
    global $wpdb;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sbp_add_service'])) {
        $staff_id = sanitize_text_field($_POST['staff_id']);
        $service_name = sanitize_text_field($_POST['service_name']);
        $service_from=sanitize_text_field($_POST['from_date']);
        $service_to=sanitize_text_field($_POST['to_date']);
        $service_price=sanitize_text_field($_POST['service_price']);
        $wpdb->insert("{$wpdb->prefix}sbp_services", ['staff_id' => $staff_id, 'service_name' => $service_name,'service_from' => $service_from,'service_to' => $service_to,'service_price' => $service_price]);
    }
    ?>
    <h1>Manage Services</h1>
    <form method="post">
        <label for="staff_id">Staff Member:</label>
        <select id="staff_id" name="staff_id" required>
            <option value="">Select Staff Member</option>
            <?php
            $staff = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sbp_staff");
            foreach ($staff as $member) {
                echo "<option value='{$member->id}'>{$member->name}</option>";
            }
            ?>
        </select>
        <label for="service_name">Service Name:</label>
        <input type="text" id="service_name" name="service_name" required>
        <label for="from_date">From:</label>
        <input type="date" id="from_date" name="from_date" required>
        <label for="to_date">To:</label>
        <input type="date" id="to_date" name="to_date" required>
        <label for="service_price">Price:</label>
        <input type="number" id="service_price" name="service_price"   required>


        <button type="submit" name="sbp_add_service">Add Service</button>
    </form>
    <h2>Services</h2>
    <ul>
        <?php
        $nodata="";
        $services = $wpdb->get_results("SELECT s.*, t.name AS staff_name FROM {$wpdb->prefix}sbp_services s JOIN {$wpdb->prefix}sbp_staff t ON s.staff_id = t.id");
        foreach ($services as $service) {
          
            echo "<li>{$service->staff_name} - {$service->service_name} price: {$service->service_price}  </li>";
        }
        ?>
    </ul>
    <?php
}


function sbp_manage_holidays_page(){

 //   global $wpdb;

    $special_holidays=get_option('special_holidays',array());

    $cey_off_days=get_option('cey_off_days',array());

    $interval_from_saved=get_option('interval_from_saved',array());

    $interval_to_saved=get_option('interval_to_saved',array());

    $opening_time_saved=get_option('opening_time_saved',array());

    $closing_time_saved=get_option('closing_time_saved',array());

                 

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['holiday_save'])) {


      
        if ( isset( $_POST['interval_from'] ) ) { 

        $interval_from = sanitize_text_field($_POST['interval_from']);
            update_option('interval_from_saved',$interval_from);
            $interval_from_saved=get_option('interval_from_saved',array());

        }

        if ( isset( $_POST['interval_to'] ) ) { 
        $interval_to = sanitize_text_field($_POST['interval_to']);

            update_option('interval_to_saved',$interval_to);
            $interval_to_saved=get_option('interval_to_saved',array());

        }

         if ( isset( $_POST['opening_time'] ) ) { 
        $opening_time = sanitize_text_field($_POST['opening_time']);

             update_option('opening_time_saved',$opening_time);
             $opening_time_saved=get_option('opening_time_saved',array());


         }

         if ( isset( $_POST['closing_time'] ) ) { 
        $closing_time = sanitize_text_field($_POST['closing_time']);

         update_option('closing_time_saved',$closing_time);
         $closing_time_saved=get_option('closing_time_saved',array());

         }

        if ( isset( $_POST['holiday_date-input'] ) ) {    
                    // $holiday_array=$_POST['holiday_date-input'];
                        $holiday_array = array_map( 'sanitize_text_field', $_POST['holiday_date-input'] );

                                foreach($holiday_array as $holiday_single_key => $holiday_val){
                                    $special_holidays[]= $holiday_val;
                                }

                                $special_holidays_updated=update_option('special_holidays',$special_holidays);

          }                        
        
       if ( isset( $_POST['cey_off_days'] ) ) {            
                $updated_off_days = array_map( 'sanitize_text_field', $_POST['cey_off_days'] );

                        foreach($updated_off_days as $updated_off_day_key => $updated_off_day_val){
                            $updated_off_days_for_save[]= $updated_off_day_val;
                        }
                           // update_option('cey_off_days',array());
                            update_option( 'cey_off_days', $updated_off_days_for_save );
                            $cey_off_days=get_option('cey_off_days',array());
                  
         }  
         else {
            // If no checkboxes are selected, save an empty array
            update_option( 'cey_off_days', array() );
            $cey_off_days=get_option('cey_off_days',array());
            }       
                  

    }

    ?>

    <h1>This is holidy page</h1>

    <form method="post">
        <label for="interval_from">Lunch Break From:</label>
        <input type="time" name="interval_from"  value="<?php echo isset($interval_from_saved) ? esc_attr($interval_from_saved) : ''; ?>"   >
        <label for="interval_to">Lunch Break Till:</label>
        <input type="time" name="interval_to"  value="<?php echo isset($interval_to_saved) ? esc_attr($interval_to_saved) : ''; ?>"  >
       
        <div id="holiday_date_container">

            <h2>Choose a holiday</h2>

            <div id="add_hoiday_btn" name="add_holiday">
                Add a Holiday
            </div>
        
        </div>
        <div id="special_hodilays_container_div">
             <h2> Added Holidays so far</h2>
            <?php 
                foreach($special_holidays as $special_holiday){
                    ?>
                        <div class="special_hodilay" name="special_hodilay[]">
                        <?php    echo $special_holiday; ?>
                        </div>
            <?php    }
            ?>
        </div>
        <div class="opeing-closing">
        <h2>Opening Closing Hours</h2>
                <label for="opening_time">Opening Time:</label>
                <input type="time" name="opening_time"  value="<?php echo isset($opening_time_saved) ? esc_attr($opening_time_saved) : ''; ?>"  >
                <label for="closing_time">Closing Time:</label>
                <input type="time" name="closing_time" value="<?php echo isset($closing_time_saved) ? esc_attr($closing_time_saved) : ''; ?>"   >


        </div>
        <div class="off-days">
                    <h2>Off days</h2>
                <input type="checkbox" id="cey_sunday_off" name="cey_off_days[]" value="sunday" <?php echo in_array('sunday', $cey_off_days) ? 'checked' : ''; ?>>
                <label for="cey_sunday_off">Sunday</label><br>

                <input type="checkbox" id="cey_monday_off" name="cey_off_days[]" value="monday" <?php echo in_array('monday', $cey_off_days) ? 'checked' : ''; ?>  >
                <label for="cey_monday_off">Monday</label><br>

                <input type="checkbox" id="cey_teusday_off" name="cey_off_days[]" value="teusday" <?php echo in_array('teusday', $cey_off_days) ? 'checked' : ''; ?>  >
                <label for="cey_teusday_off">Teusday</label><br>

                <input type="checkbox" id="cey_wednesday_off" name="cey_off_days[]" value="wednesday" <?php echo in_array('wednesday', $cey_off_days) ? 'checked' : ''; ?> >
                <label for="cey_wednesday_off">Wednesday</label><br>

                <input type="checkbox" id="cey_thursday_off" name="cey_off_days[]" value="thursday" <?php echo in_array('thursday', $cey_off_days) ? 'checked' : ''; ?> >
                <label for="cey_thursday_off">Thursday</label><br>

                <input type="checkbox" id="cey_friday_off" name="cey_off_days[]" value="friday" <?php echo in_array('friday', $cey_off_days) ? 'checked' : ''; ?> >
                <label for="cey_friday_off">Friday</label><br>

                <input type="checkbox" id="cey_saturday_off" name="cey_off_days[]" value="saturday" <?php echo in_array('saturday', $cey_off_days) ? 'checked' : ''; ?>   >
                <label for="cey_saturday_off">Saturday</label><br>


        </div>
        <button type="submit" name="holiday_save">
            Save
        </button>
    </form>

    <?php
}

//end new code 











// Create custom database tables
function sbp_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "
    CREATE TABLE {$wpdb->prefix}sbp_staff (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}sbp_services (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        staff_id mediumint(9) NOT NULL,
        service_name tinytext NOT NULL,
        service_from date NOT NULL,
        service_to date NOT NULL,
        service_price mediumint(9) NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}sbp_bookings (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        staff_id mediumint(9) NOT NULL,
        service_id mediumint(9) NOT NULL,
        date date NOT NULL,
        time time NOT NULL,
        name tinytext NOT NULL,
        email tinytext NOT NULL,
        telephone tinytext NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'sbp_create_tables');

// Handle AJAX request to get services
function sbp_get_services() {
    check_ajax_referer('sbp_nonce', 'nonce');

    if (!isset($_POST['staff_id'])) {
        wp_send_json_error('Invalid request.');
    }

    global $wpdb;
    $staff_id = intval($_POST['staff_id']);
    $services = $wpdb->get_results($wpdb->prepare("SELECT id, service_name FROM {$wpdb->prefix}sbp_services WHERE staff_id = %d", $staff_id));

    if ($services) {
        wp_send_json_success($services);
    } else {
        wp_send_json_error('No services found.');
    }
}
add_action('wp_ajax_sbp_get_services', 'sbp_get_services');
add_action('wp_ajax_nopriv_sbp_get_services', 'sbp_get_services');

// Handle AJAX request to get available slots
function sbp_get_available_slots() {
    check_ajax_referer('sbp_nonce', 'nonce');

    if (!isset($_POST['staff_id'], $_POST['service_id'], $_POST['date'])) {
        wp_send_json_error('Invalid request.');
    }

    global $wpdb;
    $staff_id = intval($_POST['staff_id']);
    $service_id = intval($_POST['service_id']);
    $date = sanitize_text_field($_POST['date']);

    // Example: Generate time slots from 9:00 AM to 5:00 PM with a 30-minute interval
    $time_slots = [];
    $start_time = strtotime('09:00');
    $end_time = strtotime('17:00');

    for ($time = $start_time; $time <= $end_time; $time = strtotime('+30 minutes', $time)) {
        $time_slots[] = date('H:i', $time);
    }

    // Fetch already booked slots for the selected date
    $booked_slots = $wpdb->get_col($wpdb->prepare("SELECT time FROM {$wpdb->prefix}sbp_bookings WHERE staff_id = %d AND service_id = %d AND date = %s", $staff_id, $service_id, $date));

    // Filter out booked slots
    $available_slots = array_diff($time_slots, $booked_slots);

    wp_send_json_success($available_slots);
}
add_action('wp_ajax_sbp_get_available_slots', 'sbp_get_available_slots');
add_action('wp_ajax_nopriv_sbp_get_available_slots', 'sbp_get_available_slots');

// Handle AJAX request to handle booking
function sbp_handle_booking() {
    check_ajax_referer('sbp_nonce', 'nonce');

    if (!isset($_POST['staff'], $_POST['service'], $_POST['date'], $_POST['time'], $_POST['name'], $_POST['email'], $_POST['telephone'])) {
        wp_send_json_error('Invalid request.');
    }

    global $wpdb;
    $staff_id = intval($_POST['staff']);
    $service_id = intval($_POST['service']);
    $date = sanitize_text_field($_POST['date']);
    $time = sanitize_text_field($_POST['time']);
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $telephone = sanitize_text_field($_POST['telephone']);

    // Check if the selected slot is already booked
    $is_booked = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sbp_bookings WHERE staff_id = %d AND service_id = %d AND date = %s AND time = %s", $staff_id, $service_id, $date, $time));

    if ($is_booked) {
        wp_send_json_error('This time slot is already booked.');
    }

    $wpdb->insert(
        "{$wpdb->prefix}sbp_bookings",
        [
            'staff_id' => $staff_id,
            'service_id' => $service_id,
            'date' => $date,
            'time' => $time,
            'name' => $name,
            'email' => $email,
            'telephone' => $telephone
        ]
    );

    if ($wpdb->insert_id) {
        // Send a confirmation email to the client
        $subject = "Booking Confirmation";
        $message = "Thank you for your booking.\n\nDetails:\n\nName: $name\nEmail: $email\nTelephone: $telephone\nDate: $date\nTime: $time\n\nWe look forward to serving you.";
        wp_mail($email, $subject, $message);

        wp_send_json_success('Booking successful.');
    } else {
        wp_send_json_error('Failed to save booking.');
    }
}
add_action('wp_ajax_sbp_handle_booking', 'sbp_handle_booking');
add_action('wp_ajax_nopriv_sbp_handle_booking', 'sbp_handle_booking');

function sbp_uninstall() {
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sbp_bookings");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sbp_services");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sbp_staff");
}
register_uninstall_hook(__FILE__, 'sbp_uninstall');

function sbp_booking_form_shortcode() {
    ob_start();
    ?>
    <div id="sbp-booking-form">
        <form id="sbp-form">
            <label for="sbp-staff">Select Staff Member:</label>
            <select id="sbp-staff" name="staff" required >
                <option value="">Select</option>
                <?php
                global $wpdb;
                $staff_members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sbp_staff");
                foreach ($staff_members as $staff) {
                    echo "<option value='{$staff->id}'>{$staff->name}</option>";
                }
                ?>
            </select>

            <label for="sbp-service">Select Service:</label>
            <select id="sbp-service" name="service" required disabled>
                <option value="">Select</option>
            </select>

            <label for="sbp-date">Select Date:</label>
            <input type="date" id="sbp-date" name="date" required disabled>

            <label for="sbp-time">Select Time:</label>
            <select id="sbp-time" name="time" required disabled>
                <option value="">Select</option>
            </select>

            <label for="sbp-name">Name:</label>
            <input type="text" id="sbp-name" name="name" required>

            <label for="sbp-email">Email:</label>
            <input type="email" id="sbp-email" name="email" required>

            <label for="sbp-telephone">Telephone:</label>
            <input type="tel" id="sbp-telephone" name="telephone" required>

            <button id="booking-plu-submit" class="butn-with-disable" type="submit" disabled >Book Now</button>
        </form>
        <div id="sbp-message"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('sbp_booking_form', 'sbp_booking_form_shortcode');
