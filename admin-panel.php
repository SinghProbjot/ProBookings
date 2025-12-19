<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Aggiungi menu
function mbs_admin_menu() {
    add_menu_page('Booking', 'Gestione Prenotazioni', 'manage_options', 'mbs-dashboard', 'mbs_page_dashboard', 'dashicons-calendar-alt', 26);
    add_submenu_page('mbs-dashboard', 'Impostazioni', 'Impostazioni', 'manage_options', 'mbs-settings', 'mbs_page_settings');
}
add_action('admin_menu', 'mbs_admin_menu');

// --- PAGINA 1: DASHBOARD & ELENCO PRENOTAZIONI ---
function mbs_page_dashboard() {
    global $wpdb;
    $table = $wpdb->prefix . 'mbs_prenotazioni';

    // Gestione Azione: Blocca Data (Ferie)
    if (isset($_POST['mbs_action']) && $_POST['mbs_action'] == 'block_date') {
        $date = sanitize_text_field($_POST['block_date']);
        $wpdb->insert($table, array(
            'data_prenotazione' => $date,
            'slot' => 'full',
            'nome_cliente' => 'ADMIN - FERIE', // Nome fittizio
            'email_cliente' => '',
            'prezzo' => 0,
            'stato' => 'blocked'
        ));
        echo '<div class="updated"><p>Data bloccata con successo!</p></div>';
    }

    // Gestione Azione: Cancella Prenotazione
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $wpdb->delete($table, array('id' => intval($_GET['id'])));
        echo '<div class="updated"><p>Prenotazione cancellata.</p></div>';
    }

    // Recupera dati
    $prenotazioni = $wpdb->get_results("SELECT * FROM $table ORDER BY data_prenotazione DESC");
    ?>
    <div class="wrap">
        <h1>🛥️ Gestione Prenotazioni</h1>
        
        <!-- BLOCCO FERIE -->
        <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-left:4px solid #d63638; margin-bottom:20px;">
            <h3>⛔ Blocca Data (Ferie/Non Disponibile)</h3>
            <form method="POST">
                <input type="hidden" name="mbs_action" value="block_date">
                <input type="date" name="block_date" required>
                <button class="button button-secondary">Blocca Giornata</button>
            </form>
        </div>

        <!-- TABELLA PRENOTAZIONI -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Orario</th>
                    <th>Cliente</th>
                    <th>Prezzo</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($prenotazioni as $p): ?>
                <tr>
                    <td><strong><?php echo $p->data_prenotazione; ?></strong></td>
                    <td>
                        <?php 
                        if($p->slot == 'morning') echo '☀️ Mattina';
                        elseif($p->slot == 'afternoon') echo '🌅 Pomeriggio';
                        else echo '⚓ Giornata Intera';
                        ?>
                    </td>
                    <td>
                        <?php echo $p->nome_cliente; ?><br>
                        <small><?php echo $p->email_cliente; ?></small>
                    </td>
                    <td>€<?php echo $p->prezzo; ?></td>
                    <td>
                        <?php 
                        if($p->stato == 'paid') echo '<span style="color:green; font-weight:bold;">PAGATO</span>';
                        elseif($p->stato == 'pending') echo '<span style="color:orange;">In attesa</span>';
                        elseif($p->stato == 'blocked') echo '<span style="color:red; font-weight:bold;">⛔ BLOCCATO</span>';
                        ?>
                    </td>
                    <td>
                        <a href="?page=mbs-dashboard&action=delete&id=<?php echo $p->id; ?>" class="button button-small" onclick="return confirm('Sicuro?');">Elimina</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($prenotazioni)) echo '<tr><td colspan="6">Nessuna prenotazione trovata.</td></tr>'; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// --- PAGINA 2: IMPOSTAZIONI (STRIPE KEYS) ---
function mbs_page_settings() {
    if (isset($_POST['mbs_save_settings'])) {
        update_option('mbs_stripe_pk', sanitize_text_field($_POST['mbs_stripe_pk']));
        update_option('mbs_stripe_sk', sanitize_text_field($_POST['mbs_stripe_sk']));
        echo '<div class="updated"><p>Impostazioni salvate!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>⚙️ Configurazione Pagamenti</h1>
        <form method="POST">
            <table class="form-table">
                <tr>
                    <th>Stripe Publishable Key (PK)</th>
                    <td><input type="text" name="mbs_stripe_pk" value="<?php echo get_option('mbs_stripe_pk'); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Stripe Secret Key (SK)</th>
                    <td><input type="text" name="mbs_stripe_sk" value="<?php echo get_option('mbs_stripe_sk'); ?>" class="regular-text"></td>
                </tr>
            </table>
            <input type="hidden" name="mbs_save_settings" value="1">
            <?php submit_button(); ?>
        </form>
        <p><em>Trova le chiavi su <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard</a>.</em></p>
    </div>
    <?php
}