<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ATTENZIONE: Per far funzionare questa integrazione, è necessario installare la libreria Google API Client.
 * Il modo più semplice è usare Composer nel tuo tema o in questo plugin:
 * composer require google/apiclient:^2.0
 * 
 * E poi includere l'autoloader di Composer all'inizio di ProBookings.php:
 * if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
 *     require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
 * }
 */

define('MBS_GCAL_TOKEN_OPTION', 'mbs_gcal_oauth_token');

/**
 * Inizia il processo di autorizzazione OAuth2.
 */
function mbs_google_calendar_auth_start() {
    if (!isset($_GET['mbs_gcal_auth']) || $_GET['mbs_gcal_auth'] !== '1') return;
    if (!current_user_can('manage_options')) return;

    $client = mbs_google_calendar_get_client();
    if (!$client) return;

    $auth_url = $client->createAuthUrl();
    wp_redirect(filter_var($auth_url, FILTER_SANITIZE_URL));
    exit;
}
add_action('admin_init', 'mbs_google_calendar_auth_start');

/**
 * Gestisce il ritorno da Google dopo l'autorizzazione.
 */
function mbs_google_calendar_oauth_callback() {
    if (!isset($_GET['code']) || !isset($_GET['page']) || $_GET['page'] !== 'mbs-settings') return;
    if (!current_user_can('manage_options')) return;

    $client = mbs_google_calendar_get_client();
    if (!$client) return;

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token['error'])) {
        update_option(MBS_GCAL_TOKEN_OPTION, $token);
    }

    // Rimuovi il codice dall'URL per pulizia
    wp_redirect(remove_query_arg('code'));
    exit;
}
add_action('admin_init', 'mbs_google_calendar_oauth_callback');

/**
 * Crea e configura il client Google API.
 */
function mbs_google_calendar_get_client() {
    if (!class_exists('Google_Client')) return null;

    $client_id = get_option('mbs_gcal_client_id');
    $client_secret = get_option('mbs_gcal_client_secret');
    if (!$client_id || !$client_secret) return null;

    $client = new Google_Client();
    $client->setApplicationName('ProBookings WordPress Plugin');
    $client->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);
    $client->setClientId($client_id);
    $client->setClientSecret($client_secret);
    $client->setRedirectUri(admin_url('admin.php?page=mbs-settings'));
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    $token = get_option(MBS_GCAL_TOKEN_OPTION);
    if ($token) {
        $client->setAccessToken($token);
    }

    // Se il token è scaduto, lo rinfresca
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            update_option(MBS_GCAL_TOKEN_OPTION, $client->getAccessToken());
        }
    }
    return $client;
}

/**
 * Aggiunge un evento a Google Calendar.
 */
function mbs_google_calendar_add_event($booking_id) {
    $client = mbs_google_calendar_get_client();
    $calendar_id = get_option('mbs_gcal_calendar_id', 'primary');
    if (!$client || !$client->getAccessToken() || !$calendar_id) return;

    global $wpdb;
    $booking = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}mbs_prenotazioni WHERE id = $booking_id");
    if (!$booking) return;

    $service = new Google_Service_Calendar($client);

    // Definisci orari
    $start_time = ($booking->slot == 'afternoon') ? 'T14:00:00' : 'T09:00:00';
    $end_time = ($booking->slot == 'morning') ? 'T13:00:00' : 'T18:00:00';

    $event = new Google_Service_Calendar_Event(array(
        'summary' => 'Prenotazione Barca: ' . $booking->nome_cliente,
        'description' => "Dettagli:\nCliente: {$booking->nome_cliente}\nEmail: {$booking->email_cliente}\nTelefono: {$booking->telefono}\nSlot: {$booking->slot}",
        'start' => array('dateTime' => $booking->data_prenotazione . $start_time, 'timeZone' => 'Europe/Rome'),
        'end' => array('dateTime' => $booking->data_prenotazione . $end_time, 'timeZone' => 'Europe/Rome'),
        'reminders' => array('useDefault' => FALSE),
        'extendedProperties' => array('private' => array('mbs_booking_id' => $booking_id)) // ID per ritrovarlo
    ));

    try {
        $created_event = $service->events->insert($calendar_id, $event);
        // Salva l'ID dell'evento di Google per poterlo cancellare in futuro
        $wpdb->update("{$wpdb->prefix}mbs_prenotazioni", array('gcal_event_id' => $created_event->getId()), array('id' => $booking_id));
    } catch (Exception $e) {
        // Logga l'errore
    }
}

/**
 * Cancella un evento da Google Calendar.
 */
function mbs_google_calendar_delete_event($booking_id) {
    $client = mbs_google_calendar_get_client();
    $calendar_id = get_option('mbs_gcal_calendar_id', 'primary');
    if (!$client || !$client->getAccessToken() || !$calendar_id) return;

    global $wpdb;
    $event_id = $wpdb->get_var("SELECT gcal_event_id FROM {$wpdb->prefix}mbs_prenotazioni WHERE id = $booking_id");
    if (!$event_id) return;

    $service = new Google_Service_Calendar($client);
    try {
        $service->events->delete($calendar_id, $event_id);
    } catch (Exception $e) {
        // Logga l'errore (es. evento già cancellato)
    }
}