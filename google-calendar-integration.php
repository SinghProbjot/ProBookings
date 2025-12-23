<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Restituisce l'istanza del Client Google configurata.
 * Restituisce null se mancano le librerie o le credenziali.
 */
function mbs_get_google_client() {
    if (!file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
        return null;
    }
    
    // Assicuriamoci che la classe esista (doppio controllo)
    if (!class_exists('Google_Client')) {
        require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    }

    $client_id = get_option('mbs_gcal_client_id');
    $client_secret = get_option('mbs_gcal_client_secret');
    
    if (!$client_id || !$client_secret) return null;

    $client = new Google_Client();
    $client->setClientId($client_id);
    $client->setClientSecret($client_secret);

    // L'URI di reindirizzamento deve corrispondere ESATTAMENTE a quello nella Google Cloud Console.
    // La funzione admin_url() di WordPress potrebbe non includere la porta in ambienti di sviluppo
    // locali (es. http://localhost:8881), causando un errore "redirect_uri_mismatch".
    // La logica seguente costruisce un URI più robusto per prevenire questo problema.
    $base_url = admin_url('admin.php');

    if (isset($_SERVER['HTTP_HOST'])) {
        // HTTP_HOST include la porta (es. 'localhost:8881'), garantendo la corrispondenza.
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'];
        $path   = wp_parse_url($base_url, PHP_URL_PATH); // es. /wp-admin/admin.php
        $base_url = "{$scheme}://{$host}{$path}";
    }

    // Aggiungiamo il parametro della pagina e ci assicuriamo che sia l'unico rilevante per l'auth.
    $redirect_uri = add_query_arg('page', 'mbs-settings', $base_url);

    $client->setRedirectUri($redirect_uri);
    $client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
    $client->setAccessType('offline'); // Fondamentale per ottenere il refresh token
    $client->setPrompt('select_account consent');

    return $client;
}

/**
 * Genera l'URL per il pulsante "Connetti Google"
 */
function mbs_get_google_auth_url() {
    $client = mbs_get_google_client();
    if (!$client) return '#';
    return $client->createAuthUrl();
}

/**
 * Genera l'URL per il pulsante "Disconnetti Google"
 */
function mbs_get_google_disconnect_url() {
    // Aggiungiamo un nonce per rendere l'URL sicuro
    $nonce_url = wp_nonce_url(
        admin_url('admin.php?page=mbs-settings&mbs_gcal_disconnect=1'), 
        'mbs_gcal_disconnect_nonce'
    );
    return $nonce_url;
}

/**
 * Avvia il processo di autenticazione quando si clicca "Connetti"
 */
add_action('admin_init', 'mbs_start_google_auth');
function mbs_start_google_auth() {
    if (isset($_GET['mbs_gcal_auth']) && $_GET['mbs_gcal_auth'] == 1) {
        
        // 1. Controllo Librerie
        if (!file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
             wp_redirect(admin_url('admin.php?page=mbs-settings&mbs_msg=gcal_no_lib'));
             exit;
        }

        // 2. Controllo Credenziali Salvate
        $client_id = get_option('mbs_gcal_client_id');
        $client_secret = get_option('mbs_gcal_client_secret');
        if (!$client_id || !$client_secret) {
             wp_redirect(admin_url('admin.php?page=mbs-settings&mbs_msg=gcal_no_creds'));
             exit;
        }

        $url = mbs_get_google_auth_url();
        if ($url && $url !== '#') {
            wp_redirect($url);
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=mbs-settings&mbs_msg=gcal_error_gen'));
            exit;
        }
    }
}

/**
 * Intercetta il redirect da Google (codice OAuth) e salva il token
 */
add_action('admin_init', 'mbs_handle_google_oauth');
function mbs_handle_google_oauth() {
    // Verifica che siamo sulla pagina giusta e che ci sia il codice
    if (isset($_GET['page']) && $_GET['page'] == 'mbs-settings' && isset($_GET['code'])) {
        
        if (!current_user_can('manage_options')) return;

        $client = mbs_get_google_client();
        if (!$client) return;

        try {
            // Scambia il codice con il token di accesso
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            
            if (!isset($token['error'])) {
                update_option('mbs_gcal_token', $token);
                // Redirect pulito per rimuovere il codice dall'URL
                wp_redirect(admin_url('admin.php?page=mbs-settings&mbs_msg=gcal_success'));
                exit;
            } else {
                // Log dell'errore specifico restituito da Google per un debug più facile
                $error_details = isset($token['error_description']) ? $token['error_description'] : $token['error'];
                error_log('GCal OAuth Token Error: ' . $error_details . ' (Verifica che l\'URI di reindirizzamento autorizzato in Google Cloud sia: ' . $client->getRedirectUri() . ')');
                wp_redirect(admin_url('admin.php?page=mbs-settings&mbs_msg=gcal_error'));
                exit;
            }
        } catch (Exception $e) {
            // Log dell'errore per debug
            error_log('GCal OAuth Error: ' . $e->getMessage());
            wp_redirect(admin_url('admin.php?page=mbs-settings&mbs_msg=gcal_error'));
            exit;
        }
    }
}

/**
 * Disconnette l'account Google cancellando il token
 */
add_action('admin_init', 'mbs_disconnect_google_account');
function mbs_disconnect_google_account() {
    if (isset($_GET['mbs_gcal_disconnect']) && $_GET['mbs_gcal_disconnect'] == 1) {
        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi per eseguire questa azione.');
        }

        // Verifica il nonce per sicurezza
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mbs_gcal_disconnect_nonce')) {
            wp_die('Azione non permessa. Nonce non valido.');
        }

        delete_option('mbs_gcal_token');
        wp_redirect(admin_url('admin.php?page=mbs-settings&mbs_msg=gcal_disconnected'));
        exit;
    }
}

/**
 * Aggiunge l'evento al calendario (chiamato dopo il pagamento/conferma)
 */
function mbs_google_calendar_add_event($booking_id) {
    $token = get_option('mbs_gcal_token');
    if (!$token) return;

    $client = mbs_get_google_client();
    if (!$client) return;

    $client->setAccessToken($token);

    // Refresh del token se scaduto
    if ($client->isAccessTokenExpired()) {
        // Il refresh token viene fornito solo la prima volta. Dobbiamo assicurarci di averlo.
        if (isset($token['refresh_token'])) {
            try {
                $new_access_token = $client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                // Unisci il nuovo token (che non ha il refresh_token) con il vecchio per non perderlo.
                $token = array_merge($token, $new_access_token);
                update_option('mbs_gcal_token', $token);
                // Assicurati che il client usi il token appena aggiornato per questa richiesta
                $client->setAccessToken($token);
            } catch (Exception $e) {
                error_log('GCal Refresh Token Error: ' . $e->getMessage());
                // Se il refresh fallisce, il token potrebbe essere revocato. Cancelliamolo
                // per forzare una nuova autenticazione da parte dell'admin.
                delete_option('mbs_gcal_token');
                return;
            }
        } else {
            // Non c'è refresh token, l'utente deve ri-autenticarsi.
            delete_option('mbs_gcal_token');
            return;
        }
    }

    global $wpdb;
    $table = $wpdb->prefix . 'mbs_prenotazioni';
    $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $booking_id));
    if (!$booking) return;

    $service = new Google_Service_Calendar($client);
    $calendarId = get_option('mbs_gcal_calendar_id');
    if (empty($calendarId)) {
        $calendarId = 'primary';
    }

    // Gestione robusta di date e orari usando il fuso orario di WordPress
    $timezone_string = wp_timezone_string();
    $times = [
        'morning'   => ['start' => '09:00:00', 'end' => '13:00:00'],
        'afternoon' => ['start' => '14:00:00', 'end' => '18:00:00'],
        'full_day'  => ['start' => '09:00:00', 'end' => '18:00:00'],
    ];
    // Usa 'full_day' come default se lo slot non è riconosciuto
    $slot_key = in_array($booking->slot, ['morning', 'afternoon', 'full_day']) ? $booking->slot : 'full_day';
    $slot_times = $times[$slot_key];

    try {
        $start_datetime = new DateTime($booking->data_prenotazione . ' ' . $slot_times['start'], new DateTimeZone($timezone_string));
        $end_datetime = new DateTime($booking->data_prenotazione . ' ' . $slot_times['end'], new DateTimeZone($timezone_string));
    } catch (Exception $e) {
        error_log('GCal Error: Invalid date format for booking ID ' . $booking_id . '. Details: ' . $e->getMessage());
        return; // Non continuare se la data non è valida
    }

    $event = new Google_Service_Calendar_Event(array(
        'summary' => 'Prenotazione Barca: ' . $booking->nome_cliente,
        'location' => 'Porto Turistico (da specificare)',
        'description' => "Dettagli prenotazione:\n\nCliente: {$booking->nome_cliente}\nTelefono: {$booking->telefono}\nEmail: {$booking->email_cliente}\nSlot: {$booking->slot}\nID Prenotazione: {$booking_id}",
        'start' => array(
            'dateTime' => $start_datetime->format(DateTimeInterface::RFC3339),
            'timeZone' => $timezone_string,
        ),
        'end' => array(
            'dateTime' => $end_datetime->format(DateTimeInterface::RFC3339),
            'timeZone' => $timezone_string,
        ),
        'reminders' => array(
            'useDefault' => FALSE,
            'overrides' => array(
                array('method' => 'email', 'minutes' => 24 * 60), // Promemoria 1 giorno prima
                array('method' => 'popup', 'minutes' => 60),      // Promemoria 1 ora prima
            ),
        ),
    ));

    try {
        $createdEvent = $service->events->insert($calendarId, $event);
        $wpdb->update(
            $table, 
            array('gcal_event_id' => $createdEvent->getId()), 
            array('id' => $booking_id),
            array('%s'),
            array('%d')
        );
    } catch (Exception $e) {
        error_log('GCal Insert Event Error: ' . $e->getMessage());
    }
}

/**
 * Rimuove l'evento da Google Calendar (chiamato alla cancellazione della prenotazione)
 */
function mbs_google_calendar_delete_event($booking_id) {
    $token = get_option('mbs_gcal_token');
    if (!$token) return;

    $client = mbs_get_google_client();
    if (!$client) return;

    $client->setAccessToken($token);

    // Refresh del token se scaduto (stessa logica di 'add_event')
    if ($client->isAccessTokenExpired()) {
        if (isset($token['refresh_token'])) {
            try {
                $new_access_token = $client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                $token = array_merge($token, $new_access_token);
                update_option('mbs_gcal_token', $token);
                $client->setAccessToken($token);
            } catch (Exception $e) {
                error_log('GCal Refresh Token Error on delete: ' . $e->getMessage());
                delete_option('mbs_gcal_token');
                return;
            }
        } else {
            delete_option('mbs_gcal_token');
            return;
        }
    }

    global $wpdb;
    $table = $wpdb->prefix . 'mbs_prenotazioni';
    // Prendiamo solo l'ID evento di GCal, non serve altro
    $gcal_event_id = $wpdb->get_var($wpdb->prepare("SELECT gcal_event_id FROM $table WHERE id = %d", $booking_id));

    if (!$gcal_event_id) {
        // Nessun ID evento associato, non c'è nulla da cancellare
        return;
    }

    $service = new Google_Service_Calendar($client);
    $calendarId = get_option('mbs_gcal_calendar_id', 'primary');

    try {
        $service->events->delete($calendarId, $gcal_event_id);
        // Opzionale: pulire il gcal_event_id dal DB
        $wpdb->update($table, array('gcal_event_id' => null), array('id' => $booking_id));
    } catch (Exception $e) {
        // Se l'evento è già stato cancellato manualmente, Google restituisce un errore 410 "Gone".
        // Lo ignoriamo per non creare log inutili.
        if ($e->getCode() == 410) {
            // Evento già cancellato, tutto ok.
        } else {
            error_log('GCal Delete Error: ' . $e->getMessage());
        }
    }
}