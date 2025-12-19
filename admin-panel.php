<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Dizionario Admin Completo
function mbs_adm_t($key) {
    $lang = get_option('mbs_admin_lang', 'it');
    $dict = [
        'it' => [
            'dash_title' => 'Gestione Prenotazioni',
            'settings' => 'Impostazioni',
            'manual_title' => '➕ Aggiungi Prenotazione (Manuale)',
            'block_title' => '⛔ Blocca Data (Ferie)',
            'block_desc' => 'Rendi una data indisponibile nel calendario.',
            'date' => 'Data',
            'client' => 'Cliente',
            'slot' => 'Slot',
            'price' => 'Prezzo (€)',
            'status' => 'Stato',
            'actions' => 'Azioni',
            'save_btn' => 'Salva Prenotazione',
            'block_btn' => 'Blocca Giornata',
            'lang_label' => 'Lingua Pannello Admin',
            'save_settings' => 'Salva Impostazioni',
            'msg_manual_ok' => '✅ Prenotazione manuale aggiunta!',
            'msg_block_ok' => '⛔ Data bloccata con successo!',
            'msg_deleted' => '🗑️ Prenotazione eliminata.',
            'morning' => 'Mattina',
            'afternoon' => 'Pomeriggio',
            'full' => 'Giornata Intera',
            'paid' => 'PAGATO',
            'pending' => 'In attesa',
            'blocked' => 'BLOCCATO',
            'confirm_del' => 'Vuoi davvero eliminare questa prenotazione?'
        ],
        'en' => [
            'dash_title' => 'Booking Management',
            'settings' => 'Settings',
            'manual_title' => '➕ Add Manual Booking',
            'block_title' => '⛔ Block Date (Holiday)',
            'block_desc' => 'Make a date unavailable in the calendar.',
            'date' => 'Date',
            'client' => 'Client',
            'slot' => 'Slot',
            'price' => 'Price (€)',
            'status' => 'Status',
            'actions' => 'Actions',
            'save_btn' => 'Save Booking',
            'block_btn' => 'Block Day',
            'lang_label' => 'Admin Panel Language',
            'save_settings' => 'Save Settings',
            'msg_manual_ok' => '✅ Manual booking added!',
            'msg_block_ok' => '⛔ Date blocked successfully!',
            'msg_deleted' => '🗑️ Booking deleted.',
            'morning' => 'Morning',
            'afternoon' => 'Afternoon',
            'full' => 'Full Day',
            'paid' => 'PAID',
            'pending' => 'Pending',
            'blocked' => 'BLOCKED',
            'confirm_del' => 'Are you sure you want to delete this booking?'
        ]
    ];
    return isset($dict[$lang][$key]) ? $dict[$lang][$key] : $key;
}

function mbs_admin_menu() {
    add_menu_page('Booking', 'Booking PRO', 'manage_options', 'mbs-dashboard', 'mbs_page_dashboard', 'dashicons-calendar-alt', 26);
    add_submenu_page('mbs-dashboard', 'Settings', mbs_adm_t('settings'), 'manage_options', 'mbs-settings', 'mbs_page_settings');
}
add_action('admin_menu', 'mbs_admin_menu');

function mbs_page_dashboard() {
    global $wpdb;
    $table = $wpdb->prefix . 'mbs_prenotazioni';

    // 1. Inserimento Manuale
    if (isset($_POST['mbs_action']) && $_POST['mbs_action'] == 'manual_add') {
        $date = sanitize_text_field($_POST['date']);
        $slot = sanitize_text_field($_POST['slot']);
        $nome = sanitize_text_field($_POST['nome']);
        $prezzo = floatval($_POST['prezzo']);
        
        $wpdb->insert($table, array(
            'data_prenotazione' => $date,
            'slot' => $slot,
            'nome_cliente' => $nome . ' (Manuale)',
            'email_cliente' => '',
            'prezzo' => $prezzo,
            'stato' => 'paid',
            'lang' => get_option('mbs_admin_lang', 'it')
        ));
        echo '<div class="updated"><p>'.mbs_adm_t('msg_manual_ok').'</p></div>';
    }

    // 2. Blocca Data
    if (isset($_POST['mbs_action']) && $_POST['mbs_action'] == 'block_date') {
        $date = sanitize_text_field($_POST['block_date']);
        $wpdb->insert($table, array(
            'data_prenotazione' => $date,
            'slot' => 'full',
            'stato' => 'blocked',
            'nome_cliente' => 'ADMIN - BLOCK',
            'prezzo' => 0
        ));
        echo '<div class="updated"><p>'.mbs_adm_t('msg_block_ok').'</p></div>';
    }

    // 3. Elimina
    if (isset($_GET['action']) && $_GET['action'] == 'delete') {
        $wpdb->delete($table, array('id' => intval($_GET['id'])));
        // Redirect per pulire l'URL ed evitare azioni duplicate al refresh
        echo '<script>window.location.href="'.remove_query_arg(array('action', 'id')).'&msg=deleted";</script>';
    }

    if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
        echo '<div class="updated"><p>'.mbs_adm_t('msg_deleted').'</p></div>';
    }

    $prenotazioni = $wpdb->get_results("SELECT * FROM $table ORDER BY data_prenotazione DESC");
    ?>
    <div class="wrap">
        <h1>🛥️ <?php echo mbs_adm_t('dash_title'); ?></h1>
        
        <div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:20px;">
            
            <!-- BOX 1: NUOVA PRENOTAZIONE MANUALE -->
            <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:4px solid #0073aa; flex:1; min-width:300px;">
                <h3><?php echo mbs_adm_t('manual_title'); ?></h3>
                <form method="POST">
                    <input type="hidden" name="mbs_action" value="manual_add">
                    <p>
                        <label><strong><?php echo mbs_adm_t('date'); ?>:</strong></label><br>
                        <input type="date" name="date" required style="width:100%">
                    </p>
                    <p>
                        <label><strong><?php echo mbs_adm_t('client'); ?>:</strong></label><br>
                        <input type="text" name="nome" placeholder="Mario Rossi" required style="width:100%">
                    </p>
                    <div style="display:flex; gap:10px;">
                        <p style="flex:1">
                            <label><strong><?php echo mbs_adm_t('slot'); ?>:</strong></label><br>
                            <select name="slot" style="width:100%">
                                <option value="morning"><?php echo mbs_adm_t('morning'); ?></option>
                                <option value="afternoon"><?php echo mbs_adm_t('afternoon'); ?></option>
                                <option value="full"><?php echo mbs_adm_t('full'); ?></option>
                            </select>
                        </p>
                        <p style="flex:1">
                            <label><strong><?php echo mbs_adm_t('price'); ?>:</strong></label><br>
                            <input type="number" name="prezzo" value="0" step="0.01" style="width:100%">
                        </p>
                    </div>
                    <button class="button button-primary"><?php echo mbs_adm_t('save_btn'); ?></button>
                </form>
            </div>

            <!-- BOX 2: BLOCCA DATA -->
            <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:4px solid #d63638; flex:1; min-width:300px;">
                <h3><?php echo mbs_adm_t('block_title'); ?></h3>
                <p><?php echo mbs_adm_t('block_desc'); ?></p>
                <form method="POST">
                    <input type="hidden" name="mbs_action" value="block_date">
                    <input type="date" name="block_date" required style="width:100%; margin-bottom:10px;">
                    <button class="button button-secondary"><?php echo mbs_adm_t('block_btn'); ?></button>
                </form>
            </div>

        </div>

        <!-- TABELLA DATI -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo mbs_adm_t('date'); ?></th>
                    <th><?php echo mbs_adm_t('slot'); ?></th>
                    <th><?php echo mbs_adm_t('client'); ?></th>
                    <th><?php echo mbs_adm_t('price'); ?></th>
                    <th><?php echo mbs_adm_t('status'); ?></th>
                    <th><?php echo mbs_adm_t('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($prenotazioni as $p): ?>
                <tr>
                    <td><strong><?php echo date('d/m/Y', strtotime($p->data_prenotazione)); ?></strong></td>
                    <td>
                        <?php 
                        switch($p->slot) {
                            case 'morning': echo '<span class="dashicons dashicons-sun"></span> ' . mbs_adm_t('morning'); break;
                            case 'afternoon': echo '<span class="dashicons dashicons-sunset"></span> ' . mbs_adm_t('afternoon'); break;
                            case 'full': echo '<span class="dashicons dashicons-admin-site"></span> ' . mbs_adm_t('full'); break;
                        }
                        ?>
                    </td>
                    <td>
                        <?php echo esc_html($p->nome_cliente); ?>
                        <?php if($p->lang) echo ' <small style="color:#888;">('.strtoupper($p->lang).')</small>'; ?>
                    </td>
                    <td>€<?php echo $p->prezzo; ?></td>
                    <td>
                        <?php 
                        if($p->stato == 'paid') echo '<strong style="color:green;">'.mbs_adm_t('paid').'</strong>';
                        elseif($p->stato == 'pending') echo '<span style="color:orange;">'.mbs_adm_t('pending').'</span>';
                        elseif($p->stato == 'blocked') echo '<strong style="color:red;">'.mbs_adm_t('blocked').'</strong>';
                        elseif($p->stato == 'cancelled') echo '<span style="color:grey; text-decoration:line-through;">CANCELLED</span>';
                        ?>
                    </td>
                    <td>
                        <a href="?page=mbs-dashboard&action=delete&id=<?php echo $p->id; ?>" style="color:#a00;" onclick="return confirm('<?php echo mbs_adm_t('confirm_del'); ?>');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($prenotazioni)) echo '<tr><td colspan="6" style="text-align:center;">No bookings found.</td></tr>'; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function mbs_page_settings() {
    if (isset($_POST['mbs_save_settings'])) {
        update_option('mbs_stripe_pk', sanitize_text_field($_POST['mbs_stripe_pk']));
        update_option('mbs_stripe_sk', sanitize_text_field($_POST['mbs_stripe_sk']));
        update_option('mbs_admin_lang', sanitize_text_field($_POST['mbs_admin_lang']));
        echo '<div class="updated"><p>Saved!</p></div>';
    }
    $curr_lang = get_option('mbs_admin_lang', 'it');
    ?>
    <div class="wrap">
        <h1>⚙️ <?php echo mbs_adm_t('settings'); ?></h1>
        <form method="POST">
            <table class="form-table">
                <tr>
                    <th><?php echo mbs_adm_t('lang_label'); ?></th>
                    <td>
                        <select name="mbs_admin_lang">
                            <option value="it" <?php selected($curr_lang, 'it'); ?>>Italiano 🇮🇹</option>
                            <option value="en" <?php selected($curr_lang, 'en'); ?>>English 🇬🇧</option>
                        </select>
                    </td>
                </tr>
                <tr><th>Stripe Publishable Key</th><td><input type="text" name="mbs_stripe_pk" value="<?php echo get_option('mbs_stripe_pk'); ?>" class="regular-text"></td></tr>
                <tr><th>Stripe Secret Key</th><td><input type="text" name="mbs_stripe_sk" value="<?php echo get_option('mbs_stripe_sk'); ?>" class="regular-text"></td></tr>
            </table>
            <input type="hidden" name="mbs_save_settings" value="1">
            <?php submit_button(mbs_adm_t('save_settings')); ?>
        </form>
    </div>
    <?php
}