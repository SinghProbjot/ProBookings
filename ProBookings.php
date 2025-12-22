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
            'title' => 'Prenota la tua Avventura',
            'subtitle' => 'Seleziona una data libera per iniziare.',
            'name_label' => 'Nome Completo',
            'email_label' => 'Email',
            'phone_label' => 'Telefono',
            'privacy_label' => 'Accetto i Termini e la Privacy Policy',
            'slot_label' => 'Scegli Slot:',
            'morning' => 'Mattina',
            'afternoon' => 'Pomeriggio',
            'full' => 'Giornata Intera',
            'legend_available' => 'Disponibile',
            'legend_partially' => 'Parzialmente Occupato',
            'legend_full' => 'Completo',
            'partially_paid' => 'Parzialmente Pagato',
            'pay_method_label' => 'Metodo di Pagamento:',
            'pay_card' => 'Carta di Credito (Stripe)',
            'pay_cash' => 'Paga in Loco',
            'pay_btn' => 'Paga e Prenota',
            'book_btn' => 'Prenota Ora',
            'success_msg' => '✅ Pagamento confermato! Controlla la tua email.',
            'cancel_msg' => '🗑️ Prenotazione cancellata e rimborsata.',
            'error_msg' => '⚠️ Errore o link scaduto.',
            'privacy_error' => 'Devi accettare la privacy policy per procedere.',
            'full_error' => 'Ci dispiace, questa data è al completo.',
            'select_slot_error' => 'Seleziona un orario!',
            'redirect_msg' => 'Reindirizzamento al pagamento...',
            'email_subject' => 'Conferma Prenotazione - #',
            'email_intro' => 'La tua prenotazione è CONFERMATA.',
            'email_cancel_intro' => 'Se devi cancellare, clicca qui:',
            'booking_details' => 'Dettagli Prenotazione per il'
        ),
        'en' => array(
            'title' => 'Book your Adventure',
            'subtitle' => 'Select an available date to start.',
            'name_label' => 'Full Name',
            'email_label' => 'Email Address',
            'phone_label' => 'Phone Number',
            'privacy_label' => 'I accept Terms and Privacy Policy',
            'slot_label' => 'Choose Slot:',
            'morning' => 'Morning',
            'afternoon' => 'Afternoon',
            'full' => 'Full Day',
            'legend_available' => 'Available',
            'legend_partially' => 'Partially Booked',
            'legend_full' => 'Fully Booked',
            'partially_paid' => 'Partially Paid',
            'pay_method_label' => 'Payment Method:',
            'pay_card' => 'Credit Card (Stripe)',
            'pay_cash' => 'Pay on Site',
            'pay_btn' => 'Pay & Book',
            'book_btn' => 'Book Now',
            'success_msg' => '✅ Payment confirmed! Check your email.',
            'cancel_msg' => '🗑️ Booking cancelled and refunded.',
            'error_msg' => '⚠️ Error or expired link.',
            'privacy_error' => 'You must accept the privacy policy to proceed.',
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
        telefono varchar(20),
        prezzo decimal(10,2) NOT NULL,
        stripe_session_id varchar(255),
        token_cancellazione varchar(64),
        lang varchar(5) DEFAULT 'it', 
        metodo_pagamento varchar(50) DEFAULT 'stripe',
        stato varchar(20) DEFAULT 'pending' NOT NULL,
        data_creazione datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    $row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = 'lang'" );
    if(empty($row)) $wpdb->query("ALTER TABLE $table_name ADD lang varchar(5) DEFAULT 'it'");

    $row_tel = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = 'telefono'" );
    if(empty($row_tel)) $wpdb->query("ALTER TABLE $table_name ADD telefono varchar(20)");

    $row_metodo = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = 'metodo_pagamento'" );
    if(empty($row_metodo)) $wpdb->query("ALTER TABLE $table_name ADD metodo_pagamento varchar(50) DEFAULT 'stripe'");
}
register_activation_hook( __FILE__, 'mbs_crea_tabella' );
add_action( 'init', 'mbs_crea_tabella' ); // Forza l'aggiornamento DB se mancano colonne

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

    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
    wp_enqueue_script('mbs-js', plugin_dir_url(__FILE__) . 'booking.js', array('flatpickr-js', 'jquery'), '5.1', true);

    $enable_payments = get_option('mbs_enable_payments', 1);
    $enable_pay_on_site = get_option('mbs_enable_pay_on_site', 0);
    $theme = get_option('mbs_theme', 'default');
    
    // Variabili base
    $primary_color = ($theme == 'sea') ? '#0088cc' : '#0073aa'; 

    wp_localize_script('mbs-js', 'mbs_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('mbs_security'),
        'stripe_pk'=> get_option('mbs_stripe_pk') ?: 'pk_test_placeholder',
        'lang'     => $lang,
        'enable_payments' => $enable_payments,
        'enable_pay_on_site' => $enable_pay_on_site,
        'strings'  => array(
            'full_error' => mbs_t('full_error', $lang),
            'select_slot' => mbs_t('select_slot_error', $lang),
            'privacy_error' => mbs_t('privacy_error', $lang),
            'redirect' => mbs_t('redirect_msg', $lang)
        )
    ));

    // --- GESTIONE TEMI CSS ---
    $css_base = "
        :root {
            --mbs-primary: {$primary_color};
            --mbs-bg: #f9f9f9;
            --mbs-text: #333;
            --mbs-radius: 12px;
            --mbs-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .mbs-wrapper { 
            max-width: 900px; margin: 40px auto; 
            font-family: 'Poppins', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
            color: var(--mbs-text);
            background: #fff;
            border-radius: var(--mbs-radius);
            box-shadow: var(--mbs-shadow);
            overflow: hidden; /* Importante per l'header image */
        }
    ";

    if ($theme == 'sea') {
        // TEMA MARE (SEA & BOATS)
        $css_theme = "
        .mbs-header { 
            text-align: center; 
            padding: 60px 20px 80px; /* Spazio extra sotto per l'onda */
            background: linear-gradient(to bottom, rgba(0, 60, 120, 0.7), rgba(0, 136, 204, 0.4)), url('https://images.unsplash.com/photo-1566552881560-0be862a7c445?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80');
            background-size: cover;
            background-position: center;
            color: #fff;
            position: relative;
            clip-path: ellipse(150% 100% at 50% 0%); /* Effetto Onda Curva */
            margin-bottom: 30px;
        }
        .mbs-header h2 { 
            font-size: 2.5rem; margin: 0 0 10px; color: #fff !important; 
            font-weight: 700; text-shadow: 0 3px 6px rgba(0,0,0,0.3); 
        }
        .mbs-header p { 
            font-size: 1.2rem; color: rgba(255,255,255,0.95) !important; 
            font-weight: 500; text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        .mbs-layout { padding: 0 30px 40px; }
        ";
    } else {
        // TEMA DEFAULT (MINIMAL PULITO)
        $css_theme = "
        .mbs-header { 
            text-align: center; padding: 40px 20px; 
            background: #fff; border-bottom: 1px solid #eee;
            margin-bottom: 30px;
        }
        .mbs-header h2 { 
            font-size: 2rem; margin: 0 0 10px; color: #2c3e50; font-weight: 600; 
        }
        .mbs-header p { 
            font-size: 1.1rem; color: #7f8c8d; 
        }
        .mbs-layout { padding: 0 30px 40px; }
        ";
    }

    $css_common = "
        
        /* Layout Animato */
        .mbs-layout { display: flex; flex-direction: column; align-items: center; gap: 40px; transition: all 0.5s ease; }
        .mbs-col-calendar { 
            width: 100%; max-width: 400px; transition: all 0.5s ease;
            /* Forza layout verticale per mantenere la legenda sotto */
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .mbs-col-form { width: 100%; max-width: 500px; opacity: 0; transform: translateY(20px); pointer-events: none; transition: all 0.5s ease; display: none; }
        
        /* Stato: Data Selezionata (Step 2) */
        .mbs-wrapper.step-2 .mbs-layout { flex-direction: row; align-items: flex-start; justify-content: center; }
        .mbs-wrapper.step-2 .mbs-col-calendar { max-width: 320px; }
        .mbs-wrapper.step-2 .mbs-col-form { opacity: 1; transform: translateY(0); pointer-events: auto; display: block; }
        
        /* Flatpickr Customization */
        .flatpickr-calendar { 
            box-shadow: var(--mbs-shadow) !important; border: none !important; 
            border-radius: var(--mbs-radius) !important; margin: 0 auto;
            font-size: 1.2rem !important; /* Ingrandisce il calendario */
        }
        .flatpickr-day.selected { background: var(--mbs-primary) !important; border-color: var(--mbs-primary) !important; }

        /* Stili Giorni Calendario */
        .flatpickr-day.day-full-booked {
            background: #f44336 !important;
            border-color: #d32f2f !important;
            color: white !important;
            cursor: not-allowed;
        }
        .flatpickr-day.day-morning-booked,
        .flatpickr-day.day-afternoon-booked {
            background: #fff59d !important;
            border-color: #fbc02d !important;
            color: #5f4300 !important;
        }

        /* Legenda Calendario */
        .mbs-legend {
            display: flex; justify-content: center; gap: 15px;
            margin-top: 15px; padding: 10px;
            background: #f8f9fa; border-radius: 8px; flex-wrap: wrap;
        }
        .legend-item { display: flex; align-items: center; font-size: 13px; color: #555; }
        .legend-color {
            width: 14px; height: 14px; border-radius: 50%;
            margin-right: 7px; border: 1px solid rgba(0,0,0,0.1);
        }
        .legend-color.available { background: #fff; border: 2px solid #bbb; }
        .legend-color.partially { background: #fff59d; }
        .legend-color.full { background: #f44336; }

        /* Cards Slot */
        .mbs-slot-cards { display: grid; grid-template-columns: 1fr; gap: 10px; margin-top: 10px; }
        .mbs-card { 
            display: flex; align-items: center; justify-content: space-between; 
            background: #fff; border: 2px solid #eee; border-radius: var(--mbs-radius); 
            padding: 10px 15px; cursor: pointer; transition: all 0.2s ease; position: relative; overflow: hidden;
        }
        .mbs-card:hover { border-color: var(--mbs-primary); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .mbs-card input:checked + .card-content { color: var(--mbs-primary); }
        
        /* Highlight Selected Card Border */
        .mbs-card:has(input:checked) { border-color: var(--mbs-primary); background: #f0f9ff; }

        .card-content { display: flex; align-items: center; gap: 10px; }
        .card-icon { width: 34px; height: 34px; background: #eef2f7; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #555; }
        .card-info strong { display: block; font-size: 0.95rem; margin-bottom: 0; }
        .card-info small { color: #888; font-size: 0.8rem; }
        .card-price { font-weight: bold; font-size: 1rem; color: #333; }
        
        /* Form Inputs */
        .mbs-input-group { margin-bottom: 12px; }
        .mbs-input-group label { display: block; font-weight: 600; margin-bottom: 4px; color: #555; font-size: 0.9rem; }
        .mbs-input-group input { 
            width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem; 
            transition: all 0.2s ease; background: #fff;
        }
        .mbs-input-group input:focus { border-color: var(--mbs-primary); outline: none; box-shadow: 0 0 0 3px rgba(0,115,170,0.1); background: #fdfdfd; }
        
        .mbs-submit-btn { 
            width: 100%; background: var(--mbs-primary); color: #fff; border: none; 
            padding: 15px; font-size: 1.1rem; font-weight: bold; border-radius: 8px; 
            cursor: pointer; transition: background 0.3s; margin-top: 10px;
        }
        .mbs-submit-btn:hover { filter: brightness(110%); }

        /* Responsive */
        @media (max-width: 768px) {
            .mbs-wrapper.step-2 .mbs-layout { flex-direction: column; }
            .mbs-wrapper.step-2 .mbs-col-calendar, .mbs-wrapper.step-2 .mbs-col-form { max-width: 100%; width: 100%; }
        }
    ";
    wp_add_inline_style('mbs-style', $css_base . $css_theme . $css_common);
}
add_action('wp_enqueue_scripts', 'mbs_scripts');

function mbs_admin_scripts() {
    $lang = get_option('mbs_admin_lang', 'it');
    wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), null, true);
    
    if($lang == 'it') {
        wp_enqueue_script('flatpickr-it', 'https://npmcdn.com/flatpickr/dist/l10n/it.js', array('flatpickr-js'), null, true);
    }
}
add_action('admin_enqueue_scripts', 'mbs_admin_scripts');

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

    $enable_payments = get_option('mbs_enable_payments', 1);
    $enable_pay_on_site = get_option('mbs_enable_pay_on_site', 0);
    
    // Genera link sicuri per la lingua (mantiene la pagina corrente)
    $link_it = add_query_arg('lang', 'it', get_permalink());
    $link_en = add_query_arg('lang', 'en', get_permalink());

    // Recupera prezzi dalle opzioni
    $p_morn = get_option('mbs_price_morning', 50);
    $p_aft  = get_option('mbs_price_afternoon', 50);
    $p_full = get_option('mbs_price_full', 90);

    // Logica visualizzazione prezzi
    $hide_prices_opt = get_option('mbs_hide_prices', 0);
    // Mostra i prezzi SOLO SE: i pagamenti sono attivi OPPURE l'opzione "nascondi" è disattivata
    $show_prices = ($enable_payments || !$hide_prices_opt);
    $price_style = $show_prices ? '' : 'style="display:none;"';
    ?>
    <div class="mbs-wrapper" id="mbs-main-wrapper">
        <div class="mbs-lang-switch">
            <a href="<?php echo esc_url($link_it); ?>" class="<?php echo ($lang=='it')?'active':''; ?>">🇮🇹 ITA</a> | 
            <a href="<?php echo esc_url($link_en); ?>" class="<?php echo ($lang=='en')?'active':''; ?>">🇬🇧 ENG</a>
        </div>

        <div class="mbs-header">
            <h2><?php echo mbs_t('title'); ?></h2>
            <p><?php echo mbs_t('subtitle'); ?></p>
        </div>
        <div class="mbs-layout">
            <div class="mbs-col-calendar">
                <input type="text" id="mbs-datepicker" style="display:none;">
                <div class="mbs-legend">
                    <div class="legend-item"><span class="legend-color available"></span> <?php echo mbs_t('legend_available'); ?></div>
                    <div class="legend-item"><span class="legend-color partially"></span> <?php echo mbs_t('legend_partially'); ?></div>
                    <div class="legend-item"><span class="legend-color full"></span> <?php echo mbs_t('legend_full'); ?></div>
                </div>
            </div>
            <div class="mbs-col-form" id="mbs-booking-form">
                <h3><?php echo mbs_t('booking_details'); ?> <span id="mbs-date-display"></span></h3>
                <form id="mbs-form-action">
                    <div class="mbs-input-group"><label><?php echo mbs_t('name_label'); ?></label><input type="text" name="nome" required></div>
                    <div class="mbs-input-group"><label><?php echo mbs_t('email_label'); ?></label><input type="email" name="email" required></div>
                    <div class="mbs-input-group"><label><?php echo mbs_t('phone_label'); ?></label><input type="tel" name="phone" required></div>
                    
                    <label><?php echo mbs_t('slot_label'); ?></label>
                    <div class="mbs-slot-cards">
                        <!-- MATTINA -->
                        <label class="mbs-card slot-morning">
                            <input type="radio" name="slot" value="morning" hidden>
                            <div class="card-content">
                                <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path></svg></div>
                                <div class="card-info"><strong><?php echo mbs_t('morning'); ?></strong><small>09:00 - 13:00</small></div>
                            </div>
                            <div class="card-price" <?php echo $price_style; ?>>€<?php echo $p_morn; ?></div>
                        </label>
                        <!-- POMERIGGIO -->
                        <label class="mbs-card slot-afternoon">
                            <input type="radio" name="slot" value="afternoon" hidden>
                            <div class="card-content">
                                <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 18a5 5 0 0 0-10 0"></path><line x1="12" y1="9" x2="12" y2="2"></line><line x1="4.22" y1="10.22" x2="5.64" y2="11.64"></line><line x1="1" y1="18" x2="3" y2="18"></line><line x1="21" y1="18" x2="23" y2="18"></line><line x1="18.36" y1="11.64" x2="19.78" y2="10.22"></line><line x1="23" y1="22" x2="1" y2="22"></line><polyline points="16 5 12 9 8 5"></polyline></svg></div>
                                <div class="card-info"><strong><?php echo mbs_t('afternoon'); ?></strong><small>14:00 - 18:00</small></div>
                            </div>
                            <div class="card-price" <?php echo $price_style; ?>>€<?php echo $p_aft; ?></div>
                        </label>
                        <!-- FULL DAY -->
                        <label class="mbs-card slot-full">
                            <input type="radio" name="slot" value="full" hidden>
                            <div class="card-content">
                                <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 22h20"></path><path d="M12 2v13"></path><path d="M5 15h14"></path><path d="M5 15a7 7 0 0 1 14 0"></path></svg></div>
                                <div class="card-info"><strong><?php echo mbs_t('full'); ?></strong><small>09:00 - 18:00</small></div>
                            </div>
                            <div class="card-price" <?php echo $price_style; ?>>€<?php echo $p_full; ?></div>
                        </label>
                    </div>
                    
                    <div style="margin-top:15px; font-size:0.9rem;">
                        <label><input type="checkbox" name="privacy" required> <?php echo mbs_t('privacy_label'); ?></label>
                    </div>

                    <?php if($enable_payments && $enable_pay_on_site): ?>
                    <div style="margin-top:15px; background:#f0f0f0; padding:10px; border-radius:8px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;"><?php echo mbs_t('pay_method_label'); ?></label>
                        <label style="margin-right:15px;"><input type="radio" name="payment_method" value="stripe" checked> <?php echo mbs_t('pay_card'); ?></label>
                        <label><input type="radio" name="payment_method" value="cash"> <?php echo mbs_t('pay_cash'); ?></label>
                    </div>
                    <?php endif; ?>

                    <div id="mbs-feedback"></div>
                    <button type="submit" class="mbs-submit-btn"><?php echo $enable_payments ? mbs_t('pay_btn') : mbs_t('book_btn'); ?></button>
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
    $enable_payments = get_option('mbs_enable_payments', 1);
    $sk = get_option('mbs_stripe_sk');
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'stripe';

    if($enable_payments && !$sk) { wp_send_json_error('Stripe Keys Missing'); }

    $lang = sanitize_text_field($_POST['lang']); 

    $data = sanitize_text_field($_POST['date']);
    $slot = sanitize_text_field($_POST['slot']);
    $nome = sanitize_text_field($_POST['nome']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    
    // Calcolo prezzo dinamico
    if ($slot === 'full') $prezzo = get_option('mbs_price_full', 90);
    elseif ($slot === 'morning') $prezzo = get_option('mbs_price_morning', 50);
    else $prezzo = get_option('mbs_price_afternoon', 50);

    $cancel_token = wp_generate_password(32, false);

    $wpdb->insert($table, array(
        'data_prenotazione' => $data,
        'slot' => $slot,
        'nome_cliente' => $nome,
        'email_cliente' => $email,
        'telefono' => $phone,
        'prezzo' => $prezzo,
        'token_cancellazione' => $cancel_token,
        'lang' => $lang,
        'metodo_pagamento' => $payment_method,
        'stato' => 'pending'
    ));
    $order_id = $wpdb->insert_id;

    if (!$enable_payments) {
        $wpdb->update($table, array('stato' => 'confirmed'), array('id' => $order_id));
        mbs_send_notifications($order_id);
        wp_send_json_success(array('message' => mbs_t('success_msg', $lang)));
        return;
    }

    // Gestione Pagamento in Loco
    if ($enable_payments && $payment_method === 'cash') {
        $wpdb->update($table, array('stato' => 'confirmed'), array('id' => $order_id));
        mbs_send_notifications($order_id);
        wp_send_json_success(array('message' => mbs_t('success_msg', $lang)));
        return;
    }

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
function mbs_send_notifications($order_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'mbs_prenotazioni';
    $booking = $wpdb->get_row("SELECT * FROM $table WHERE id = $order_id");
    if (!$booking) return;

    $u_lang = $booking->lang;
    $cancel_link = add_query_arg(array('mbs_action' => 'cancel_booking', 'id' => $order_id, 'token' => $booking->token_cancellazione, 'lang' => $u_lang), get_site_url());

    $subject = mbs_t('email_subject', $u_lang) . $order_id;
    
    $msg = "Hello " . $booking->nome_cliente . ",\n\n";
    $msg .= mbs_t('email_intro', $u_lang) . "\n";
    $msg .= "Date: " . $booking->data_prenotazione . "\n";
    $msg .= "Phone: " . $booking->telefono . "\n";
    $msg .= "Slot: " . $booking->slot . "\n\n";
    $msg .= mbs_t('email_cancel_intro', $u_lang) . "\n";
    $msg .= $cancel_link;

    wp_mail($booking->email_cliente, $subject, $msg);
    wp_mail(get_option('admin_email'), "New Booking #$order_id", "New booking from " . $booking->nome_cliente);
}

function mbs_verify_payment() {
    if (isset($_GET['mbs_payment']) && $_GET['mbs_payment'] == 'success' && isset($_GET['order_id'])) {
        global $wpdb;
        $table = $wpdb->prefix . 'mbs_prenotazioni';
        $order_id = intval($_GET['order_id']);
        $booking = $wpdb->get_row("SELECT * FROM $table WHERE id = $order_id");

        if ($booking && $booking->stato != 'paid') {
            $wpdb->update($table, array('stato' => 'paid'), array('id' => $order_id));
            mbs_send_notifications($order_id);
            $u_lang = $booking->lang;
            
            wp_redirect(remove_query_arg(array('mbs_payment', 'order_id'), get_site_url()) . '?mbs_msg=success&lang='.$u_lang);
            exit;
        }
    }
}
add_action('init', 'mbs_verify_payment');