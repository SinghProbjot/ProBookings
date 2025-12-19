
<?php

/**
 * Plugin Name: ProBookings System 
 * Description: Sistema prenotazioni con Stripe, Gestione Ferie e Dashboard Admin.
 * Version: 3.0
 * Author: Probjot Singh
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once plugin_dir_path(__FILE__) . 'admin-panel.php';
require_once plugin_dir_path(__FILE__) . 'cancellation-logic.php';

// ======================================================
// 1. GESTIONE LINGUA E DIZIONARIO
// ======================================================

function mbs_get_current_lang() {
    if (is_admin() && !wp_doing_ajax()) {
        return get_option('mbs_admin_lang', 'it');
    }
    if (isset($_GET['lang'])) {
        return sanitize_text_field($_GET['lang']);
    }
    if (isset($_COOKIE['mbs_lang'])) {
        return sanitize_text_field($_COOKIE['mbs_lang']);
    }
    return 'it';
}

function mbs_t($key, $lang = null) {
    if (!$lang) $lang = mbs_get_current_lang();
    
    $dictionary = array(
        'it' => array(
            'title' => '📅 Prenota la tua Avventura',
            'subtitle' => 'Seleziona una data libera per iniziare.',
            'name_label' => 'Nome Completo',
            'email_label' => 'Email',
            'slot_label' => 'Scegli Slot:',
            'morning' => 'Mattina',
            'afternoon' => 'Pomeriggio',
            'full' => 'Giornata Intera',
            'pay_btn' => 'Paga e Prenota',
            'success_msg' => '✅ Pagamento confermato! Controlla la tua email.',
            'cancel_msg' => '🗑️ Prenotazione cancellata e rimborsata.',
            'error_msg' => '⚠️ Errore o link scaduto.',
            'full_error' => 'Ci dispiace, questa data è al completo.',
            'select_slot_error' => 'Seleziona un orario!',
            'redirect_msg' => 'Reindirizzamento al pagamento...',
            'email_subject' => 'Conferma Prenotazione - #',
            'email_intro' => 'La tua prenotazione è CONFERMATA.',
            'email_cancel_intro' => 'Se devi cancellare, clicca qui:',
            'booking_details' => 'Dettagli Prenotazione per il'
        ),
        'en' => array(
            'title' => '📅 Book your Adventure',
            'subtitle' => 'Select an available date to start.',
            'name_label' => 'Full Name',
            'email_label' => 'Email Address',
            'slot_label' => 'Choose Slot:',
            'morning' => 'Morning',
            'afternoon' => 'Afternoon',
            'full' => 'Full Day',
            'pay_btn' => 'Pay & Book',
            'success_msg' => '✅ Payment confirmed! Check your email.',
            'cancel_msg' => '🗑️ Booking cancelled and refunded.',
            'error_msg' => '⚠️ Error or expired link.',
            'full_error' => 'Sorry, this date is fully booked.',
            'select_slot_error' => 'Please select a time slot!',
            'redirect_msg' => 'Redirecting to payment...',
            'email_subject' => 'Booking Confirmation - #',
            'email_intro' => 'Your booking is CONFIRMED.',
            'email_cancel_intro' => 'If you need to cancel, click here:',
            'booking_details' => 'Booking Details for'
        )
    );

    return isset($dictionary[$lang][$key]) ? $dictionary[$lang][$key] : $key;
}

// ======================================================
// 2. SETUP DB
// ======================================================
function mbs_crea_tabella() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mbs_prenotazioni';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        data_prenotazione date NOT NULL,
        slot varchar(20) NOT NULL, 
        nome_cliente varchar(100),
        email_cliente varchar(100),
        prezzo decimal(10,2) NOT NULL,
        stripe_session_id varchar(255),
        token_cancellazione varchar(64),
        lang varchar(5) DEFAULT 'it', 
        stato varchar(20) DEFAULT 'pending' NOT NULL,
        data_creazione datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    $row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = 'lang'" );
    if(empty($row)) $wpdb->query("ALTER TABLE $table_name ADD lang varchar(5) DEFAULT 'it'");
}
register_activation_hook( __FILE__, 'mbs_crea_tabella' );

// ======================================================
// 3. ASSETS & LOCALIZZAZIONE JS
// ======================================================
function mbs_scripts() {
    $lang = mbs_get_current_lang();
    
    wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    wp_enqueue_style('mbs-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), null, true);
    
    if($lang == 'it') {
        wp_enqueue_script('flatpickr-it', 'https://npmcdn.com/flatpickr/dist/l10n/it.js', array('flatpickr-js'), null, true);
    }

    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
    wp_enqueue_script('mbs-js', plugin_dir_url(__FILE__) . 'booking.js', array('flatpickr-js', 'jquery'), '5.1', true);

    wp_localize_script('mbs-js', 'mbs_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('mbs_security'),
        'stripe_pk'=> get_option('mbs_stripe_pk'),
        'lang'     => $lang,
        'strings'  => array(
            'full_error' => mbs_t('full_error', $lang),
            'select_slot' => mbs_t('select_slot_error', $lang),
            'redirect' => mbs_t('redirect_msg', $lang)
        )
    ));
}
add_action('wp_enqueue_scripts', 'mbs_scripts');

// ======================================================
// 4. SHORTCODE
// ======================================================
function mbs_shortcode() {
    ob_start(); 
    $lang = mbs_get_current_lang();
    
    if(isset($_GET['lang'])) {
        ?>
        <script>
            document.cookie = "mbs_lang=<?php echo $lang; ?>; path=/; max-age=86400";
        </script>
        <?php
    }

    if (isset($_GET['mbs_msg'])) {
        if ($_GET['mbs_msg'] == 'success') echo '<div class="mbs-success-banner">'.mbs_t('success_msg', $lang).'</div>';
        if ($_GET['mbs_msg'] == 'cancelled') echo '<div class="mbs-success-banner cancel">'.mbs_t('cancel_msg', $lang).'</div>';
        if ($_GET['mbs_msg'] == 'error') echo '<div class="mbs-success-banner error">'.mbs_t('error_msg', $lang).'</div>';
    }
    ?>
    <div class="mbs-wrapper">
        <div class="mbs-lang-switch">
            <a href="?lang=it" class="<?php echo ($lang=='it')?'active':''; ?>">🇮🇹 ITA</a> | 
            <a href="?lang=en" class="<?php echo ($lang=='en')?'active':''; ?>">🇬🇧 ENG</a>
        </div>

        <div class="mbs-header">
            <h2><?php echo mbs_t('title'); ?></h2>
            <p><?php echo mbs_t('subtitle'); ?></p>
        </div>
        <div class="mbs-layout">
            <div class="mbs-col-calendar"><input type="text" id="mbs-datepicker" style="display:none;"></div>
            <div class="mbs-col-form" id="mbs-booking-form">
                <h3><?php echo mbs_t('booking_details'); ?> <span id="mbs-date-display"></span></h3>
                <form id="mbs-form-action">
                    <div class="mbs-input-group"><label><?php echo mbs_t('name_label'); ?></label><input type="text" name="nome" required></div>
                    <div class="mbs-input-group"><label><?php echo mbs_t('email_label'); ?></label><input type="email" name="email" required></div>
                    
                    <label><?php echo mbs_t('slot_label'); ?></label>
                    <div class="mbs-slot-cards">
                        <label class="mbs-card slot-morning"><input type="radio" name="slot" value="morning" hidden>
                            <div class="card-content"><span>☀️</span><strong><?php echo mbs_t('morning'); ?></strong><small>09:00 - 13:00</small></div><div class="card-price">€50</div></label>
                        <label class="mbs-card slot-afternoon"><input type="radio" name="slot" value="afternoon" hidden>
                            <div class="card-content"><span>🌅</span><strong><?php echo mbs_t('afternoon'); ?></strong><small>14:00 - 18:00</small></div><div class="card-price">€50</div></label>
                        <label class="mbs-card slot-full"><input type="radio" name="slot" value="full" hidden>
                            <div class="card-content"><span>⚓</span><strong><?php echo mbs_t('full'); ?></strong><small>09:00 - 18:00</small></div><div class="card-price">€90</div></label>
                    </div>
                    <div id="mbs-feedback"></div>
                    <button type="submit" class="mbs-submit-btn"><?php echo mbs_t('pay_btn'); ?></button>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('pro_bookings', 'mbs_shortcode');

// ======================================================
// 5. AJAX PAGAMENTO
// ======================================================
function mbs_ajax_create_checkout_session() {
    check_ajax_referer('mbs_security', 'security');
    global $wpdb;
    $table = $wpdb->prefix . 'mbs_prenotazioni';
    $sk = get_option('mbs_stripe_sk');

    if(!$sk) { wp_send_json_error('Stripe Keys Missing'); }

    $lang = sanitize_text_field($_POST['lang']); 

    $data = sanitize_text_field($_POST['date']);
    $slot = sanitize_text_field($_POST['slot']);
    $nome = sanitize_text_field($_POST['nome']);
    $email = sanitize_email($_POST['email']);
    $prezzo = ($slot === 'full') ? 90 : 50;
    $cancel_token = wp_generate_password(32, false);

    $wpdb->insert($table, array(
        'data_prenotazione' => $data,
        'slot' => $slot,
        'nome_cliente' => $nome,
        'email_cliente' => $email,
        'prezzo' => $prezzo,
        'token_cancellazione' => $cancel_token,
        'lang' => $lang,
        'stato' => 'pending'
    ));
    $order_id = $wpdb->insert_id;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $sk . ':' . '');
    
    $product_name = ($lang == 'en') ? "Boat Booking ($data)" : "Prenotazione Barca ($data)";

    $payload = http_build_query(array(
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => ['name' => $product_name],
                'unit_amount' => $prezzo * 100,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => get_site_url() . '/?mbs_payment=success&order_id='.$order_id.'&lang='.$lang, 
        'cancel_url' => get_site_url() . '/?mbs_payment=cancel&lang='.$lang,
        'customer_email' => $email,
    ));
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $result = curl_exec($ch);
    $json = json_decode($result);
    curl_close($ch);

    if (isset($json->id)) {
        $wpdb->update($table, array('stripe_session_id' => $json->id), array('id' => $order_id));
        wp_send_json_success(array('id' => $json->id));
    } else {
        wp_send_json_error($json->error->message ?? 'Error');
    }
}
add_action('wp_ajax_mbs_start_payment', 'mbs_ajax_create_checkout_session');
add_action('wp_ajax_nopriv_mbs_start_payment', 'mbs_ajax_create_checkout_session');

// ======================================================
// 6. GESTIONE EMAIL
// ======================================================
function mbs_verify_payment() {
    if (isset($_GET['mbs_payment']) && $_GET['mbs_payment'] == 'success' && isset($_GET['order_id'])) {
        global $wpdb;
        $table = $wpdb->prefix . 'mbs_prenotazioni';
        $order_id = intval($_GET['order_id']);
        $booking = $wpdb->get_row("SELECT * FROM $table WHERE id = $order_id");

        if ($booking && $booking->stato != 'paid') {
            $wpdb->update($table, array('stato' => 'paid'), array('id' => $order_id));
            
            $u_lang = $booking->lang;

            $cancel_link = add_query_arg(array('mbs_action' => 'cancel_booking', 'id' => $order_id, 'token' => $booking->token_cancellazione, 'lang' => $u_lang), get_site_url());

            $subject = mbs_t('email_subject', $u_lang) . $order_id;
            
            $msg = "Hello " . $booking->nome_cliente . ",\n\n";
            $msg .= mbs_t('email_intro', $u_lang) . "\n";
            $msg .= "Date: " . $booking->data_prenotazione . "\n";
            $msg .= "Slot: " . $booking->slot . "\n\n";
            $msg .= mbs_t('email_cancel_intro', $u_lang) . "\n";
            $msg .= $cancel_link;

            wp_mail($booking->email_cliente, $subject, $msg);
            wp_mail(get_option('admin_email'), "New Booking #$order_id", "New booking from " . $booking->nome_cliente);
            
            wp_redirect(remove_query_arg(array('mbs_payment', 'order_id'), get_site_url()) . '?mbs_msg=success&lang='.$u_lang);
            exit;
        }
    }
}
add_action('init', 'mbs_verify_payment');