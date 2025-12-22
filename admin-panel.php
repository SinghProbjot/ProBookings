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
            'confirmed' => 'CONFERMATO',
            'pending' => 'In attesa',
            'blocked' => 'BLOCCATO',
            'confirm_del' => 'Vuoi davvero eliminare questa prenotazione?',
            'enable_payments' => 'Abilita Pagamenti (Stripe)',
            'enable_payments_desc' => 'Se disabilitato, le prenotazioni verranno confermate subito senza pagamento.',
            'prices_title' => 'Gestione Prezzi',
            'hide_prices' => 'Nascondi Prezzi (se pagamenti OFF)',
            'hide_prices_desc' => 'Nasconde i prezzi nel modulo se i pagamenti sono disabilitati.',
            'theme_label' => 'Tema Grafico',
            'theme_default' => 'Minimal (Default)',
            'theme_sea' => '🌊 Sea & Boats (Mare)',
            'theme_sunset' => '🌅 Sunset (Tramonto)',
            'theme_forest' => '🌲 Forest (Natura)',
            'theme_elegant' => '💎 Elegant (Lusso)',
            'edit_title' => '✏️ Modifica Prenotazione',
            'update_btn' => 'Aggiorna',
            'msg_updated' => '✅ Prenotazione aggiornata con successo!',
            'error_overlap' => '⚠️ Errore: La data/slot selezionata è già occupata!'
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
            'confirmed' => 'CONFIRMED',
            'pending' => 'Pending',
            'blocked' => 'BLOCKED',
            'confirm_del' => 'Are you sure you want to delete this booking?',
            'enable_payments' => 'Enable Payments (Stripe)',
            'enable_payments_desc' => 'If disabled, bookings will be confirmed immediately without payment.',
            'prices_title' => 'Price Management',
            'hide_prices' => 'Hide Prices (if payments OFF)',
            'hide_prices_desc' => 'Hides prices in the form if payments are disabled.',
            'theme_label' => 'Design Theme',
            'theme_default' => 'Minimal (Default)',
            'theme_sea' => '🌊 Sea & Boats',
            'theme_sunset' => '🌅 Sunset',
            'theme_forest' => '🌲 Forest',
            'theme_elegant' => '💎 Elegant',
            'edit_title' => '✏️ Edit Booking',
            'update_btn' => 'Update',
            'msg_updated' => '✅ Booking updated successfully!',
            'error_overlap' => '⚠️ Error: The selected date/slot is already booked!'
        ]
    ];
    return isset($dict[$lang][$key]) ? $dict[$lang][$key] : $key;
}

function mbs_admin_menu() {
    add_menu_page('Booking', 'ProBookings', 'manage_options', 'mbs-dashboard', 'mbs_page_dashboard', 'dashicons-calendar-alt', 26);
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

    // 4. Aggiorna (Modifica)
    if (isset($_POST['mbs_action']) && $_POST['mbs_action'] == 'update_booking') {
        $id = intval($_POST['booking_id']);
        $date = sanitize_text_field($_POST['date']);
        $slot = sanitize_text_field($_POST['slot']);
        $nome = sanitize_text_field($_POST['nome']);
        $stato = sanitize_text_field($_POST['stato']);
        
        // Controllo Sovrapposizioni (Escludendo se stesso)
        // Logica: Se esiste un altro record (id diverso) in quella data che è 'full' O il cui slot coincide col mio O il mio è 'full'
        $check = $wpdb->get_var($wpdb->prepare(
            "SELECT count(*) FROM $table 
             WHERE data_prenotazione = %s 
             AND id != %d 
             AND stato IN ('paid', 'confirmed', 'pending', 'blocked')
             AND (slot = 'full' OR slot = %s OR %s = 'full')",
            $date, $id, $slot, $slot
        ));

        if ($check > 0) {
            echo '<div class="error"><p>'.mbs_adm_t('error_overlap').'</p></div>';
        } else {
            $wpdb->update($table, array(
                'data_prenotazione' => $date,
                'slot' => $slot,
                'nome_cliente' => $nome,
                'stato' => $stato
            ), array('id' => $id));
            echo '<div class="updated"><p>'.mbs_adm_t('msg_updated').'</p></div>';
            echo '<script>window.history.replaceState(null, null, "'.remove_query_arg(array('action', 'id')).'");</script>';
        }
    }

    if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
        echo '<div class="updated"><p>'.mbs_adm_t('msg_deleted').'</p></div>';
    }

    // Recupera dati per Modifica se richiesto
    $edit_booking = null;
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
        $edit_booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['id'])));
    }

    $prenotazioni = $wpdb->get_results("SELECT * FROM $table ORDER BY data_prenotazione DESC");
    ?>
    <div class="wrap">
        <h1>🛥️ <?php echo mbs_adm_t('dash_title'); ?></h1>
        
        <?php if($edit_booking): ?>
        <!-- BOX MODIFICA -->
        <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-left:4px solid #f39c12; margin-bottom:20px;">
            <h3><?php echo mbs_adm_t('edit_title'); ?> (ID: <?php echo $edit_booking->id; ?>)</h3>
            <form method="POST">
                <input type="hidden" name="mbs_action" value="update_booking">
                <input type="hidden" name="booking_id" value="<?php echo $edit_booking->id; ?>">
                <div style="display:flex; gap:15px; flex-wrap:wrap;">
                    <p><label><strong><?php echo mbs_adm_t('date'); ?>:</strong></label><br>
                    <input type="date" name="date" value="<?php echo $edit_booking->data_prenotazione; ?>" required></p>
                    
                    <p><label><strong><?php echo mbs_adm_t('client'); ?>:</strong></label><br>
                    <input type="text" name="nome" value="<?php echo esc_attr($edit_booking->nome_cliente); ?>" required></p>
                    
                    <p><label><strong><?php echo mbs_adm_t('slot'); ?>:</strong></label><br>
                    <select name="slot">
                        <option value="morning" <?php selected($edit_booking->slot, 'morning'); ?>><?php echo mbs_adm_t('morning'); ?></option>
                        <option value="afternoon" <?php selected($edit_booking->slot, 'afternoon'); ?>><?php echo mbs_adm_t('afternoon'); ?></option>
                        <option value="full" <?php selected($edit_booking->slot, 'full'); ?>><?php echo mbs_adm_t('full'); ?></option>
                    </select></p>

                    <p><label><strong><?php echo mbs_adm_t('status'); ?>:</strong></label><br>
                    <select name="stato">
                        <option value="paid" <?php selected($edit_booking->stato, 'paid'); ?>><?php echo mbs_adm_t('paid'); ?></option>
                        <option value="confirmed" <?php selected($edit_booking->stato, 'confirmed'); ?>><?php echo mbs_adm_t('confirmed'); ?></option>
                        <option value="pending" <?php selected($edit_booking->stato, 'pending'); ?>><?php echo mbs_adm_t('pending'); ?></option>
                        <option value="blocked" <?php selected($edit_booking->stato, 'blocked'); ?>><?php echo mbs_adm_t('blocked'); ?></option>
                    </select></p>
                </div>
                <button class="button button-primary"><?php echo mbs_adm_t('update_btn'); ?></button>
                <a href="?page=mbs-dashboard" class="button"><?php echo mbs_adm_t('actions'); ?> (Cancel)</a>
            </form>
        </div>
        <?php else: ?>

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
        <?php endif; ?>

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
                        elseif($p->stato == 'confirmed') echo '<strong style="color:#0073aa;">'.mbs_adm_t('confirmed').'</strong>';
                        elseif($p->stato == 'pending') echo '<span style="color:orange;">'.mbs_adm_t('pending').'</span>';
                        elseif($p->stato == 'blocked') echo '<strong style="color:red;">'.mbs_adm_t('blocked').'</strong>';
                        elseif($p->stato == 'cancelled') echo '<span style="color:grey; text-decoration:line-through;">CANCELLED</span>';
                        ?>
                    </td>
                    <td>
                        <a href="?page=mbs-dashboard&action=edit&id=<?php echo $p->id; ?>" class="button button-small">Edit</a>
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
        update_option('mbs_enable_payments', isset($_POST['mbs_enable_payments']) ? 1 : 0);
        update_option('mbs_hide_prices', isset($_POST['mbs_hide_prices']) ? 1 : 0);
        update_option('mbs_theme', sanitize_text_field($_POST['mbs_theme']));
        update_option('mbs_price_morning', floatval($_POST['mbs_price_morning']));
        update_option('mbs_price_afternoon', floatval($_POST['mbs_price_afternoon']));
        update_option('mbs_price_full', floatval($_POST['mbs_price_full']));
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
                <tr>
                    <th><?php echo mbs_adm_t('theme_label'); ?></th>
                    <td>
                        <select name="mbs_theme">
                            <option value="default" <?php selected(get_option('mbs_theme', 'default'), 'default'); ?>><?php echo mbs_adm_t('theme_default'); ?></option>
                            <option value="sea" <?php selected(get_option('mbs_theme'), 'sea'); ?>><?php echo mbs_adm_t('theme_sea'); ?></option>
                            <option value="sunset" <?php selected(get_option('mbs_theme'), 'sunset'); ?>><?php echo mbs_adm_t('theme_sunset'); ?></option>
                            <option value="forest" <?php selected(get_option('mbs_theme'), 'forest'); ?>><?php echo mbs_adm_t('theme_forest'); ?></option>
                            <option value="elegant" <?php selected(get_option('mbs_theme'), 'elegant'); ?>><?php echo mbs_adm_t('theme_elegant'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php echo mbs_adm_t('enable_payments'); ?></th>
                    <td>
                        <input type="checkbox" name="mbs_enable_payments" value="1" <?php checked(get_option('mbs_enable_payments', 1), 1); ?>>
                        <p class="description"><?php echo mbs_adm_t('enable_payments_desc'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php echo mbs_adm_t('hide_prices'); ?></th>
                    <td>
                        <input type="checkbox" name="mbs_hide_prices" value="1" <?php checked(get_option('mbs_hide_prices', 0), 1); ?>>
                        <p class="description"><?php echo mbs_adm_t('hide_prices_desc'); ?></p>
                    </td>
                </tr>
                <tr><th>Stripe Publishable Key</th><td><input type="text" name="mbs_stripe_pk" value="<?php echo get_option('mbs_stripe_pk'); ?>" class="regular-text"></td></tr>
                <tr><th>Stripe Secret Key</th><td><input type="text" name="mbs_stripe_sk" value="<?php echo get_option('mbs_stripe_sk'); ?>" class="regular-text"></td></tr>
                
                <tr><th colspan="2"><hr><h3><?php echo mbs_adm_t('prices_title'); ?></h3></th></tr>
                
                <tr><th><?php echo mbs_adm_t('morning'); ?> (€)</th><td><input type="number" step="0.01" name="mbs_price_morning" value="<?php echo get_option('mbs_price_morning', 50); ?>" class="small-text"></td></tr>
                <tr><th><?php echo mbs_adm_t('afternoon'); ?> (€)</th><td><input type="number" step="0.01" name="mbs_price_afternoon" value="<?php echo get_option('mbs_price_afternoon', 50); ?>" class="small-text"></td></tr>
                <tr><th><?php echo mbs_adm_t('full'); ?> (€)</th><td><input type="number" step="0.01" name="mbs_price_full" value="<?php echo get_option('mbs_price_full', 90); ?>" class="small-text"></td></tr>
            </table>
            <input type="hidden" name="mbs_save_settings" value="1">
            <?php submit_button(mbs_adm_t('save_settings')); ?>
        </form>
    </div>
    <?php
}