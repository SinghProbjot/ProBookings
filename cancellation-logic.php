<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// LOGICA CANCELLAZIONE
function mbs_handle_user_cancellation() {
    if (isset($_GET['mbs_action']) && $_GET['mbs_action'] == 'cancel_booking') {
        global $wpdb;
        $table = $wpdb->prefix . 'mbs_prenotazioni';
        
        $id = intval($_GET['id']);
        $token = sanitize_text_field($_GET['token']);
        $sk = get_option('mbs_stripe_sk');

        $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND token_cancellazione = %s", $id, $token));

        if (!$booking) { wp_redirect(get_site_url() . '?mbs_msg=error'); exit; }
        
        // Lingua utente per il redirect
        $ul = $booking->lang ? $booking->lang : 'it';

        if ($booking->stato == 'cancelled') {
             wp_redirect(get_site_url() . '?mbs_msg=cancelled&lang='.$ul); exit;
        }

        // Refund Stripe
        if ($booking->stripe_session_id && $sk) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions/' . $booking->stripe_session_id);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERPWD, $sk . ':' . '');
            $sd = json_decode(curl_exec($ch));
            curl_close($ch);

            if (isset($sd->payment_intent)) {
                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, 'https://api.stripe.com/v1/refunds');
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch2, CURLOPT_POST, 1);
                curl_setopt($ch2, CURLOPT_USERPWD, $sk . ':' . '');
                curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query(array('payment_intent' => $sd->payment_intent)));
                curl_exec($ch2);
                curl_close($ch2);
            }
        }

        $wpdb->update($table, array('stato' => 'cancelled'), array('id' => $id));
        wp_mail(get_option('admin_email'), "Cancelled #$id", "User cancelled booking #$id");
        
        wp_redirect(get_site_url() . '?mbs_msg=cancelled&lang='.$ul);
        exit;
    }
}
add_action('init', 'mbs_handle_user_cancellation');

// GET DATES
function mbs_ajax_get_dates() {
    global $wpdb;
    $table = $wpdb->prefix . 'mbs_prenotazioni';
    $res = $wpdb->get_results("SELECT data_prenotazione, slot, stato FROM $table WHERE stato IN ('paid', 'confirmed', 'blocked', 'pending')");
    
    $cal = array();
    foreach($res as $p) {
        $d = $p->data_prenotazione;
        if($p->stato == 'blocked') $cal[$d] = 'full';
        else {
            if (!isset($cal[$d])) $cal[$d] = $p->slot;
            else if ($cal[$d] !== $p->slot) $cal[$d] = 'full';
        }
    }
    wp_send_json($cal);
}
add_action('wp_ajax_mbs_get_dates', 'mbs_ajax_get_dates');
add_action('wp_ajax_nopriv_mbs_get_dates', 'mbs_ajax_get_dates');