<?php
/**
 * Plugin Name: ProBookings System 
 * Description: Sistema prenotazioni con Stripe, Gestione Ferie e Dashboard Admin.
 * Version: 3.0
 * Author: Probjot Singh
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Includiamo il file dell'admin (lo creiamo tra poco)
require_once plugin_dir_path(__FILE__) . 'admin-panel.php';

// ======================================================
// 1. DATABASE SETUP
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
        stato varchar(20) DEFAULT 'pending' NOT NULL,
        note text,
        data_creazione datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    
    // Salviamo le opzioni di default se non esistono
    add_option('mbs_stripe_pk', '');
    add_option('mbs_stripe_sk', '');
}
register_activation_hook( __FILE__, 'mbs_crea_tabella' );

// ======================================================
// 2. ASSETS (CSS/JS)
// ======================================================
function mbs_scripts() {
    // Frontend Styles & Scripts
    wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    wp_enqueue_style('mbs-style', plugin_dir_url(__FILE__) . 'style.css');

    wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), null, true);
    wp_enqueue_script('flatpickr-it', 'https://npmcdn.com/flatpickr/dist/l10n/it.js', array('flatpickr-js'), null, true);
    
    // Libreria Stripe JS (Essenziale per il redirect)
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);

    wp_enqueue_script('mbs-js', plugin_dir_url(__FILE__) . 'booking.js', array('flatpickr-js', 'jquery'), '3.0', true);

    wp_localize_script('mbs-js', 'mbs_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('mbs_security'),
        'stripe_pk'=> get_option('mbs_stripe_pk') // Passiamo la chiave pubblica al JS
    ));
}
add_action('wp_enqueue_scripts', 'mbs_scripts');

// ======================================================
// 3. SHORTCODE FRONTEND
// ======================================================
function mbs_shortcode() {
    ob_start(); 
    // Se il pagamento è avvenuto con successo, mostriamo messaggio
    if (isset($_GET['mbs_payment']) && $_GET['mbs_payment'] == 'success') {
        echo '<div class="mbs-success-banner">✅ Pagamento confermato! Grazie per la prenotazione. Ti abbiamo inviato una email.</div>';
    }
    ?>
    <div class="mbs-wrapper">
        <div class="mbs-header">
            <h2>📅 Prenota la tua Avventura</h2>
            <p>Seleziona una data libera.</p>
        </div>
        <div class="mbs-layout">
            <div class="mbs-col-calendar"><input type="text" id="mbs-datepicker" style="display:none;"></div>
            <div class="mbs-col-form" id="mbs-booking-form">
                <h3>Prenotazione per il <span id="mbs-date-display"></span></h3>
                <form id="mbs-form-action">
                    <div class="mbs-input-group"><label>Nome</label><input type="text" name="nome" required></div>
                    <div class="mbs-input-group"><label>Email</label><input type="email" name="email" required></div>
                    <label>Scegli Slot:</label>
                    <div class="mbs-slot-cards">
                        <label class="mbs-card slot-morning"><input type="radio" name="slot" value="morning" hidden>
                            <div class="card-content"><span>☀️</span><strong>Mattina</strong><small>09:00 - 13:00</small></div><div class="card-price">€50</div></label>
                        <label class="mbs-card slot-afternoon"><input type="radio" name="slot" value="afternoon" hidden>
                            <div class="card-content"><span>🌅</span><strong>Pomeriggio</strong><small>14:00 - 18:00</small></div><div class="card-price">€50</div></label>
                        <label class="mbs-card slot-full"><input type="radio" name="slot" value="full" hidden>
                            <div class="card-content"><span>⚓</span><strong>Full Day</strong><small>09:00 - 18:00</small></div><div class="card-price">€90</div></label>
                    </div>
                    <div id="mbs-feedback"></div>
                    <button type="submit" class="mbs-submit-btn">Paga e Prenota</button>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('mio_calendario', 'mbs_shortcode');

// ======================================================
// 4. AJAX: INIZIALIZZA PAGAMENTO STRIPE
// ======================================================
function mbs_ajax_create_checkout_session() {
    check_ajax_referer('mbs_security', 'security');
    
    global $wpdb;
    $table = $wpdb->prefix . 'mbs_prenotazioni';

    $sk = get_option('mbs_stripe_sk');
    if(!$sk) { wp_send_json_error('Configurazione Stripe mancante nel pannello Admin.'); }

    $data = sanitize_text_field($_POST['date']);
    $slot = sanitize_text_field($_POST['slot']);
    $nome = sanitize_text_field($_POST['nome']);
    $email = sanitize_email($_POST['email']);
    $prezzo = ($slot === 'full') ? 90 : 50; // In centesimi per Stripe * 100

    // 1. Salva prenotazione come "pending"
    $wpdb->insert($table, array(
        'data_prenotazione' => $data,
        'slot' => $slot,
        'nome_cliente' => $nome,
        'email_cliente' => $email,
        'prezzo' => $prezzo,
        'stato' => 'pending'
    ));
    $order_id = $wpdb->insert_id;

    // 2. Chiama API Stripe (Senza librerie esterne usando cURL)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $sk . ':' . '');
    
    $payload = http_build_query(array(
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => ['name' => "Prenotazione Barca ($data - $slot)"],
                'unit_amount' => $prezzo * 100, // Stripe vuole centesimi
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => get_site_url() . '/?mbs_payment=success&session_id={CHECKOUT_SESSION_ID}&order_id='.$order_id,
        'cancel_url' => get_site_url() . '/?mbs_payment=cancel',
        'customer_email' => $email,
    ));
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $result = curl_exec($ch);
    $json = json_decode($result);
    curl_close($ch);

    if (isset($json->id)) {
        // Aggiorna DB con session ID
        $wpdb->update($table, array('stripe_session_id' => $json->id), array('id' => $order_id));
        wp_send_json_success(array('id' => $json->id));
    } else {
        wp_send_json_error('Errore Stripe: ' . ($json->error->message ?? 'Sconosciuto'));
    }
}
add_action('wp_ajax_mbs_start_payment', 'mbs_ajax_create_checkout_session');
add_action('wp_ajax_nopriv_mbs_start_payment', 'mbs_ajax_create_checkout_session');

// ======================================================
// 5. GESTIONE RITORNO DA STRIPE (Webhook simulato)
// ======================================================
function mbs_verify_payment() {
    if (isset($_GET['mbs_payment']) && $_GET['mbs_payment'] == 'success' && isset($_GET['order_id'])) {
        global $wpdb;
        $table = $wpdb->prefix . 'mbs_prenotazioni';
        $order_id = intval($_GET['order_id']);
        
        // Aggiorniamo a 'paid' (In produzione dovresti verificare la sessione con Stripe, ma per ora fidiamoci del return)
        $wpdb->update($table, array('stato' => 'paid'), array('id' => $order_id));
    }
}
add_action('init', 'mbs_verify_payment');

// ======================================================
// 6. GET DATES (Uguale a prima ma gestisce anche lo stato 'blocked')
// ======================================================
function mbs_ajax_get_dates() {
    global $wpdb;
    $table = $wpdb->prefix . 'mbs_prenotazioni';
    
    // Mostriamo occupate sia quelle pagate che quelle bloccate dall'admin
    $res = $wpdb->get_results("SELECT data_prenotazione, slot, stato FROM $table WHERE stato IN ('paid', 'blocked', 'pending')");
    
    $cal = array();
    foreach($res as $p) {
        $d = $p->data_prenotazione;
        if($p->stato == 'blocked') {
            $cal[$d] = 'full'; // Se l'admin blocca, è FULL.
        } else {
            if (!isset($cal[$d])) $cal[$d] = $p->slot;
            else if ($cal[$d] !== $p->slot) $cal[$d] = 'full';
        }
    }
    wp_send_json($cal);
}
add_action('wp_ajax_mbs_get_dates', 'mbs_ajax_get_dates');
add_action('wp_ajax_nopriv_mbs_get_dates', 'mbs_ajax_get_dates');