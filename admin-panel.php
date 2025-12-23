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
            'phone' => 'Telefono',
            'slot' => 'Slot',
            'price' => 'Prezzo (€)',
            'status' => 'Stato',
            'actions' => 'Azioni',
            'contact_btn' => 'Contatta',
            'contact_title' => 'Dettagli Contatto',
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
            'partially_paid' => 'PARZIALMENTE PAGATO',
            'pending' => 'In attesa',
            'blocked' => 'BLOCCATO',
            'confirm_del' => 'Vuoi davvero eliminare questa prenotazione?',
            'enable_payments' => 'Abilita Pagamenti (Stripe)',
            'enable_payments_desc' => 'Se disabilitato, le prenotazioni verranno confermate subito senza pagamento.',
            'prices_title' => 'Gestione Prezzi',
            'enable_pay_on_site' => 'Abilita Pagamento in Loco',
            'enable_pay_on_site_desc' => 'Permetti ai clienti di scegliere di pagare sul posto.',
            'hide_prices' => 'Nascondi Prezzi (se pagamenti OFF)',
            'hide_prices_desc' => 'Nasconde i prezzi nel modulo se i pagamenti sono disabilitati.',
            'theme_label' => 'Tema Grafico',
            'theme_default' => 'Minimal (Default)',
            'theme_sea' => 'Sea & Boats (Mare)',
            'theme_sunset' => 'Sunset (Tramonto)',
            'theme_forest' => 'Forest (Natura)',
            'theme_elegant' => 'Elegant (Lusso)',
            'edit_title' => '✏️ Modifica Prenotazione',
            'update_btn' => 'Aggiorna',
            'msg_updated' => '✅ Prenotazione aggiornata con successo!',
            'error_overlap' => '⚠️ Errore: La data/slot selezionata è già occupata!',
            'gcal_title' => 'Integrazione Google Calendar',
            'gcal_client_id' => 'Google Client ID',
            'gcal_client_secret' => 'Google Client Secret',
            'gcal_calendar_id' => 'ID Calendario (es. primary)',
            'gcal_help' => 'Per configurare: Vai su Google Cloud Console, crea credenziali OAuth (Web App) e inserisci questo <strong>URI di Reindirizzamento</strong>:'
        ],
        'en' => [
            'dash_title' => 'Booking Management',
            'settings' => 'Settings',
            'manual_title' => '➕ Add Manual Booking',
            'block_title' => '⛔ Block Date (Holiday)',
            'block_desc' => 'Make a date unavailable in the calendar.',
            'date' => 'Date',
            'client' => 'Client',
            'phone' => 'Phone',
            'slot' => 'Slot',
            'price' => 'Price (€)',
            'status' => 'Status',
            'actions' => 'Actions',
            'contact_btn' => 'Contact',
            'contact_title' => 'Contact Details',
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
            'partially_paid' => 'PARTIALLY PAID',
            'pending' => 'Pending',
            'blocked' => 'BLOCKED',
            'confirm_del' => 'Are you sure you want to delete this booking?',
            'enable_payments' => 'Enable Payments (Stripe)',
            'enable_payments_desc' => 'If disabled, bookings will be confirmed immediately without payment.',
            'prices_title' => 'Price Management',
            'enable_pay_on_site' => 'Enable Pay on Site',
            'enable_pay_on_site_desc' => 'Allow customers to choose to pay on site.',
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
            'error_overlap' => '⚠️ Error: The selected date/slot is already booked!',
            'gcal_title' => 'Google Calendar Integration',
            'gcal_client_id' => 'Google Client ID',
            'gcal_client_secret' => 'Google Client Secret',
            'gcal_calendar_id' => 'Calendar ID (e.g. primary)',
            'gcal_help' => 'To configure: Go to Google Cloud Console, create OAuth credentials (Web App) and use this <strong>Redirect URI</strong>:'
        ]
    ];
    return isset($dict[$lang][$key]) ? $dict[$lang][$key] : $key;
}

function mbs_admin_menu() {
    add_menu_page('Booking', 'ProBookings', 'manage_options', 'mbs-dashboard', 'mbs_page_dashboard', 'dashicons-calendar-alt', 26);
    add_submenu_page('mbs-dashboard', 'Settings', mbs_adm_t('settings'), 'manage_options', 'mbs-settings', 'mbs_page_settings');
}
add_action('admin_menu', 'mbs_admin_menu');
add_action('wp_ajax_mbs_update_booking', 'mbs_ajax_update_booking');

function mbs_page_dashboard() {
    global $wpdb;
    $table = $wpdb->prefix . 'mbs_prenotazioni';

    // 1. Inserimento Manuale
    if (isset($_POST['mbs_action']) && $_POST['mbs_action'] == 'manual_add') {
        $date = sanitize_text_field($_POST['date']);
        $slot = sanitize_text_field($_POST['slot']);
        $nome = sanitize_text_field($_POST['nome']);
        $prezzo = floatval($_POST['prezzo']);
        
        // Controllo Conflitti
        $check = $wpdb->get_var($wpdb->prepare(
            "SELECT count(*) FROM $table 
             WHERE data_prenotazione = %s 
             AND stato IN ('paid', 'confirmed', 'pending', 'blocked', 'partially_paid')
             AND (slot = 'full' OR slot = %s OR %s = 'full')",
            $date, $slot, $slot
        ));

        if ($check > 0) {
            echo '<div class="error"><p>'.mbs_adm_t('error_overlap').'</p></div>';
        } else {
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
    
    // --- PAGINAZIONE ---
    $items_per_page = 15;
    $page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
    $offset = ( $page * $items_per_page ) - $items_per_page;
    $total = $wpdb->get_var( "SELECT COUNT(id) FROM $table" );
    $total_pages = ceil( $total / $items_per_page );
    $prenotazioni = $wpdb->get_results("SELECT * FROM $table ORDER BY data_prenotazione DESC LIMIT $offset, $items_per_page");
    ?>
    <style>
        .mbs-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
        .mbs-modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 8px; position: relative; }
        .mbs-modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; position: absolute; top: 5px; right: 15px; }
        .mbs-modal-close:hover, .mbs-modal-close:focus { color: black; text-decoration: none; }
        .mbs-modal-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        #mbs-contact-modal p { margin: 5px 0; }
        #mbs-contact-modal strong { display: block; color: #555; }
        #mbs-contact-modal a { text-decoration: none; }
        
        /* Stili Calendario Admin */
        .flatpickr-day.day-full-booked { background: #f44336 !important; border-color: #d32f2f !important; color: white !important; }
        .flatpickr-day.day-morning-booked, 
        .flatpickr-day.day-afternoon-booked { 
            background: #fff59d !important; border-color: #fbc02d !important; color: #5f4300 !important; 
        }
    </style>

    <div class="wrap">
        <h1>🛥️ <?php echo mbs_adm_t('dash_title'); ?></h1>

        <!-- MODAL PER MODIFICA -->
        <div id="mbs-edit-modal" class="mbs-modal">
            <div class="mbs-modal-content">
                <span class="mbs-modal-close">&times;</span>
                <h3 id="mbs-edit-modal-title"></h3>
                <form id="mbs-edit-form">
                    <input type="hidden" id="edit-booking-id" name="booking_id">
                    <div class="mbs-modal-grid">
                        <p><label><strong><?php echo mbs_adm_t('date'); ?>:</strong></label><br><input type="date" id="edit-date" name="date" required></p>
                        <p><label><strong><?php echo mbs_adm_t('client'); ?>:</strong></label><br><input type="text" id="edit-nome" name="nome" required></p>
                        <p><label><strong><?php echo mbs_adm_t('phone'); ?>:</strong></label><br><input type="tel" id="edit-telefono" name="telefono"></p>
                        <p><label><strong>Email:</strong></label><br><input type="email" id="edit-email" name="email"></p>
                        <p><label><strong><?php echo mbs_adm_t('slot'); ?>:</strong></label><br><select id="edit-slot" name="slot"><option value="morning"><?php echo mbs_adm_t('morning'); ?></option><option value="afternoon"><?php echo mbs_adm_t('afternoon'); ?></option><option value="full"><?php echo mbs_adm_t('full'); ?></option></select></p>
                        <p><label><strong><?php echo mbs_adm_t('status'); ?>:</strong></label><br><select id="edit-stato" name="stato"><option value="paid"><?php echo mbs_adm_t('paid'); ?></option><option value="partially_paid"><?php echo mbs_adm_t('partially_paid'); ?></option><option value="confirmed"><?php echo mbs_adm_t('confirmed'); ?></option><option value="pending"><?php echo mbs_adm_t('pending'); ?></option><option value="blocked"><?php echo mbs_adm_t('blocked'); ?></option></select></p>
                    </div>
                    <button type="submit" class="button button-primary"><?php echo mbs_adm_t('update_btn'); ?></button>
                </form>
            </div>
        </div>

        <!-- MODAL PER CONTATTO -->
        <div id="mbs-contact-modal" class="mbs-modal">
            <div class="mbs-modal-content">
                <span class="mbs-modal-close">&times;</span>
                <h3 id="mbs-contact-modal-title"></h3>
                <p><strong><?php echo mbs_adm_t('phone'); ?>:</strong> <a id="contact-phone-link"></a></p>
                <p><strong>Email:</strong> <a id="contact-email-link"></a></p>
            </div>
        </div>
        
        <div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:20px;">
            
            <!-- BOX 1: NUOVA PRENOTAZIONE MANUALE -->
            <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:4px solid #0073aa; flex:1; min-width:300px;">
                <h3><?php echo mbs_adm_t('manual_title'); ?></h3>
                <form method="POST">
                    <input type="hidden" name="mbs_action" value="manual_add">
                    <p>
                        <label><strong><?php echo mbs_adm_t('date'); ?>:</strong></label><br>
                        <input type="text" id="manual-date" name="date" required style="width:100%" placeholder="YYYY-MM-DD">
                    </p>
                    <p>
                        <label><strong><?php echo mbs_adm_t('client'); ?>:</strong></label><br>
                        <input type="text" name="nome" placeholder="Mario Rossi" required style="width:100%">
                    </p>
                    <div style="display:flex; gap:10px;">
                        <p style="flex:1">
                            <label><strong><?php echo mbs_adm_t('slot'); ?>:</strong></label><br>
                            <select name="slot" id="manual-slot" style="width:100%">
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
                    <th><?php echo mbs_adm_t('phone'); ?></th>
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
                    <td><?php echo esc_html($p->telefono ?? ''); ?></td>
                    <td>
                        <?php echo esc_html($p->nome_cliente); ?>
                        <?php if($p->lang) echo ' <small style="color:#888;">('.strtoupper($p->lang).')</small>'; ?>
                    </td>
                    <td>€<?php echo $p->prezzo; ?></td>
                    <td>
                        <?php 
                        if($p->stato == 'paid') echo '<strong style="color:green;">'.mbs_adm_t('paid').'</strong>';
                        elseif($p->stato == 'confirmed') echo '<strong style="color:#0073aa;">'.mbs_adm_t('confirmed').'</strong>';
                        elseif($p->stato == 'partially_paid') echo '<strong style="color:#d35400;">'.mbs_adm_t('partially_paid').'</strong>';
                        elseif($p->stato == 'pending') echo '<span style="color:orange;">'.mbs_adm_t('pending').'</span>';
                        elseif($p->stato == 'blocked') echo '<strong style="color:red;">'.mbs_adm_t('blocked').'</strong>';
                        elseif($p->stato == 'cancelled') echo '<span style="color:grey; text-decoration:line-through;">CANCELLED</span>';
                        ?>
                    </td>
                    <td>
                        <button class="button button-small contact-btn" data-name="<?php echo esc_attr($p->nome_cliente); ?>" data-phone="<?php echo esc_attr($p->telefono ?? ''); ?>" data-email="<?php echo esc_attr($p->email_cliente); ?>"><?php echo mbs_adm_t('contact_btn'); ?></button>
                        <button class="button button-small edit-booking-btn" data-id="<?php echo $p->id; ?>" data-date="<?php echo $p->data_prenotazione; ?>" data-slot="<?php echo $p->slot; ?>" data-nome="<?php echo esc_attr($p->nome_cliente); ?>" data-telefono="<?php echo esc_attr($p->telefono ?? ''); ?>" data-email="<?php echo esc_attr($p->email_cliente); ?>" data-stato="<?php echo $p->stato; ?>">Edit</button>
                        <a href="?page=mbs-dashboard&action=delete&id=<?php echo $p->id; ?>" style="color:#a00;" onclick="return confirm('<?php echo mbs_adm_t('confirm_del'); ?>');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($prenotazioni)) echo '<tr><td colspan="7" style="text-align:center;">No bookings found.</td></tr>'; ?>
            </tbody>
        </table>
        
        <!-- PAGINAZIONE LINKS -->
        <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links( array(
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $page
                ));
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <script>
    jQuery(document).ready(function($){
        const editModal = $('#mbs-edit-modal');
        const contactModal = $('#mbs-contact-modal');
        const closeModalButtons = $('.mbs-modal-close');
        
        let bookedDates = {};
        let currentEditDate = '';
        let currentEditSlot = '';
        let fpInstance;

        // Funzione per gestire la disponibilità degli slot nel menu a tendina
        function updateSlotOptions(dateStr, selectSelector, originalSlot) {
            const status = bookedDates[dateStr];
            const select = $(selectSelector);
            select.find('option').prop('disabled', false); // Reset

            if (!status) return;

            // Logica per MODIFICA (Stessa data)
            if (originalSlot && dateStr === currentEditDate) {
                if (status === 'full') {
                    if (originalSlot === 'morning') {
                        select.find('option[value="afternoon"]').prop('disabled', true);
                        select.find('option[value="full"]').prop('disabled', true);
                    } else if (originalSlot === 'afternoon') {
                        select.find('option[value="morning"]').prop('disabled', true);
                        select.find('option[value="full"]').prop('disabled', true);
                    }
                }
                return;
            }

            // Logica per NUOVA DATA / MANUALE
            if (status === 'full') {
                select.find('option').prop('disabled', true);
                select.val('');
            } else if (status === 'morning') {
                select.find('option[value="morning"]').prop('disabled', true);
                select.find('option[value="full"]').prop('disabled', true);
                if (select.val() === 'morning' || select.val() === 'full') select.val('afternoon');
            } else if (status === 'afternoon') {
                select.find('option[value="afternoon"]').prop('disabled', true);
                select.find('option[value="full"]').prop('disabled', true);
                if (select.val() === 'afternoon' || select.val() === 'full') select.val('morning');
            }
        }

        // Scarica le date occupate e inizializza Flatpickr
        $.ajax({
            url: ajaxurl,
            data: { action: 'mbs_get_dates' },
            success: function(res) {
                bookedDates = res;
                
                const onDayCreateLogic = function(dObj, dStr, fp, dayElem) {
                    let dateKey = flatpickr.formatDate(dayElem.dateObj, "Y-m-d");
                    if (bookedDates[dateKey]) {
                        if (bookedDates[dateKey] === 'full') dayElem.classList.add('day-full-booked');
                        else if (bookedDates[dateKey] === 'morning' || bookedDates[dateKey] === 'afternoon') dayElem.classList.add('day-morning-booked');
                    }
                };

                fpInstance = flatpickr("#edit-date", {
                    dateFormat: "Y-m-d",
                    locale: "<?php echo get_option('mbs_admin_lang', 'it'); ?>",
                    disable: [
                        function(date) {
                            let dateStr = flatpickr.formatDate(date, "Y-m-d");
                            // Disabilita se è piena E non è la data che stiamo già modificando
                            return (bookedDates[dateStr] === 'full' && dateStr !== currentEditDate);
                        },
                    ],
                    onDayCreate: onDayCreateLogic,
                    onChange: function(selectedDates, dateStr, instance) {
                        updateSlotOptions(dateStr, '#edit-slot', currentEditSlot);
                    }
                });

                // Init Manual Booking Flatpickr
                flatpickr("#manual-date", {
                    dateFormat: "Y-m-d",
                    locale: "<?php echo get_option('mbs_admin_lang', 'it'); ?>",
                    disable: [
                        function(date) {
                            let dateStr = flatpickr.formatDate(date, "Y-m-d");
                            return (bookedDates[dateStr] === 'full');
                        },
                    ],
                    onDayCreate: onDayCreateLogic,
                    onChange: function(selectedDates, dateStr, instance) {
                        updateSlotOptions(dateStr, '#manual-slot', null);
                    }
                });
            }
        });

        // --- LOGICA GENERALE MODAL ---
        closeModalButtons.on('click', function() {
            $(this).closest('.mbs-modal').hide();
        });
        $(window).on('click', function(e) {
            if ($(e.target).is('.mbs-modal')) {
                $(e.target).hide();
            }
        });

        // --- LOGICA MODAL CONTATTO ---
        $('.contact-btn').on('click', function(){
            const name = $(this).data('name');
            const phone = $(this).data('phone');
            const email = $(this).data('email');

            $('#mbs-contact-modal-title').text('<?php echo mbs_adm_t("contact_title"); ?>: ' + name);
            $('#contact-phone-link').text(phone).attr('href', 'tel:' + phone);
            $('#contact-email-link').text(email).attr('href', 'mailto:' + email);
            contactModal.show();
        });

        // --- LOGICA MODAL MODIFICA ---
        $('.edit-booking-btn').on('click', function(){
            const bookingData = $(this).data();
            $('#mbs-edit-modal-title').text('<?php echo mbs_adm_t("edit_title"); ?> (ID: ' + bookingData.id + ')');
            $('#edit-booking-id').val(bookingData.id);
            
            currentEditDate = bookingData.date;
            currentEditSlot = bookingData.slot;
            if(fpInstance) {
                fpInstance.setDate(currentEditDate);
                fpInstance.redraw(); // Ridisegna per aggiornare le date disabilitate
            } else {
                $('#edit-date').val(bookingData.date);
            }
            
            $('#edit-slot').val(bookingData.slot);
            $('#edit-nome').val(bookingData.nome);
            $('#edit-telefono').val(bookingData.telefono);
            $('#edit-email').val(bookingData.email);
            $('#edit-stato').val(bookingData.stato);
            
            // Aggiorna subito le opzioni slot per la data corrente
            updateSlotOptions(currentEditDate, '#edit-slot', currentEditSlot);
            editModal.show();
        });

        $('#mbs-edit-form').on('submit', function(e){
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            const originalText = btn.text();
            btn.text('Saving...').prop('disabled', true);

            const formData = $(this).serializeArray();
            const data = {
                action: 'mbs_update_booking',
                _ajax_nonce: '<?php echo wp_create_nonce("mbs_update_booking_nonce"); ?>'
            };
            $.each(formData, function(i, field){
                data[field.name] = field.value;
            });

            $.post(ajaxurl, data, function(res){
                btn.text(originalText).prop('disabled', false);
                if(res.success) {
                    editModal.hide();
                    // Aggiorna la riga della tabella dinamicamente
                    const row = $('#booking-row-' + res.data.id);
                    row.find('td:nth-child(1)').html('<strong>' + res.data.date_formatted + '</strong>');
                    row.find('td:nth-child(2)').html(res.data.slot_html);
                    row.find('td:nth-child(3)').text(res.data.telefono);
                    row.find('td:nth-child(4)').html(res.data.nome_cliente + ' <small style="color:#888;">(' + res.data.lang.toUpperCase() + ')</small>');
                    row.find('td:nth-child(6)').html(res.data.status_html);
                    
                    // Aggiorna i data attributes del pulsante edit
                    const editBtn = row.find('.edit-booking-btn');
                    editBtn.data('date', res.data.data_prenotazione);
                    editBtn.data('slot', res.data.slot);
                    editBtn.data('nome', res.data.nome_cliente);
                    editBtn.data('telefono', res.data.telefono);
                    editBtn.data('email', res.data.email_cliente);
                    editBtn.data('stato', res.data.stato);

                } else {
                    alert('Error: ' + res.data);
                }
            }).fail(function() {
                alert('Errore di connessione o Server Error.');
                btn.text(originalText).prop('disabled', false);
            });
        });
    });
    </script>
    <?php
}

function mbs_ajax_update_booking() {
    check_ajax_referer('mbs_update_booking_nonce');
    global $wpdb;
    $table = $wpdb->prefix . 'mbs_prenotazioni';

    $id = intval($_POST['booking_id']);
    $date = sanitize_text_field($_POST['date']);
    $slot = sanitize_text_field($_POST['slot']);
    $nome = sanitize_text_field($_POST['nome']);
    $telefono = sanitize_text_field($_POST['telefono']);
    $email = sanitize_email($_POST['email']);
    $stato = sanitize_text_field($_POST['stato']);

    $check = $wpdb->get_var($wpdb->prepare(
        "SELECT count(*) FROM $table WHERE data_prenotazione = %s AND id != %d AND stato IN ('paid', 'confirmed', 'pending', 'blocked', 'partially_paid') AND (slot = 'full' OR slot = %s OR %s = 'full')",
        $date, $id, $slot, $slot
    ));

    if ($check > 0) {
        wp_send_json_error(mbs_adm_t('error_overlap'));
    } else {
        // Calcolo nuovo prezzo in base allo slot
        if ($slot === 'full') $new_price = get_option('mbs_price_full', 90);
        elseif ($slot === 'morning') $new_price = get_option('mbs_price_morning', 50);
        else $new_price = get_option('mbs_price_afternoon', 50);

        $wpdb->update($table, 
            array('data_prenotazione' => $date, 'slot' => $slot, 'nome_cliente' => $nome, 'telefono' => $telefono, 'email_cliente' => $email, 'stato' => $stato, 'prezzo' => $new_price), 
            array('id' => $id)
        );

        // Prepara dati per la risposta JSON per aggiornare la UI
        $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        
        $slot_html = '';
        switch($booking->slot) {
            case 'morning': $slot_html = '<span class="dashicons dashicons-sun"></span> ' . mbs_adm_t('morning'); break;
            case 'afternoon': $slot_html = '<span class="dashicons dashicons-sunset"></span> ' . mbs_adm_t('afternoon'); break;
            case 'full': $slot_html = '<span class="dashicons dashicons-admin-site"></span> ' . mbs_adm_t('full'); break;
        }

        $status_html = '';
        if($booking->stato == 'paid') $status_html = '<strong style="color:green;">'.mbs_adm_t('paid').'</strong>';
        elseif($booking->stato == 'confirmed') $status_html = '<strong style="color:#0073aa;">'.mbs_adm_t('confirmed').'</strong>';
        elseif($booking->stato == 'partially_paid') $status_html = '<strong style="color:#d35400;">'.mbs_adm_t('partially_paid').'</strong>';
        elseif($booking->stato == 'pending') $status_html = '<span style="color:orange;">'.mbs_adm_t('pending').'</span>';
        elseif($booking->stato == 'blocked') $status_html = '<strong style="color:red;">'.mbs_adm_t('blocked').'</strong>';
        elseif($booking->stato == 'cancelled') $status_html = '<span style="color:grey; text-decoration:line-through;">CANCELLED</span>';

        $response_data = (array) $booking;
        $response_data['date_formatted'] = date('d/m/Y', strtotime($booking->data_prenotazione));
        $response_data['slot_html'] = $slot_html;
        $response_data['status_html'] = $status_html;

        wp_send_json_success($response_data);
    }
}

function mbs_page_settings() {
    if (isset($_POST['mbs_save_settings'])) {
        update_option('mbs_stripe_pk', sanitize_text_field($_POST['mbs_stripe_pk']));
        update_option('mbs_stripe_sk', sanitize_text_field($_POST['mbs_stripe_sk']));
        update_option('mbs_admin_lang', sanitize_text_field($_POST['mbs_admin_lang']));
        update_option('mbs_enable_payments', isset($_POST['mbs_enable_payments']) ? 1 : 0);
        update_option('mbs_enable_pay_on_site', isset($_POST['mbs_enable_pay_on_site']) ? 1 : 0);
        update_option('mbs_hide_prices', isset($_POST['mbs_hide_prices']) ? 1 : 0);
        update_option('mbs_theme', sanitize_text_field($_POST['mbs_theme']));
        update_option('mbs_price_morning', floatval($_POST['mbs_price_morning']));
        update_option('mbs_price_afternoon', floatval($_POST['mbs_price_afternoon']));
        update_option('mbs_price_full', floatval($_POST['mbs_price_full']));
        update_option('mbs_gcal_client_id', sanitize_text_field($_POST['mbs_gcal_client_id']));
        update_option('mbs_gcal_client_secret', sanitize_text_field($_POST['mbs_gcal_client_secret']));
        update_option('mbs_gcal_calendar_id', sanitize_text_field($_POST['mbs_gcal_calendar_id']));
        echo '<div class="updated"><p>Saved!</p></div>';
    }

    // Messaggi di ritorno da Google
    if (isset($_GET['mbs_msg'])) {
        if ($_GET['mbs_msg'] == 'gcal_success') echo '<div class="updated"><p>✅ Google Calendar Connesso con successo!</p></div>';
        if ($_GET['mbs_msg'] == 'gcal_error') {
            echo '<div class="error"><p>⚠️ Errore durante la connessione a Google.</p>';
            if (isset($_GET['err'])) echo '<p><i>Dettaglio: '.esc_html($_GET['err']).'</i></p>';
            echo '</div>';
        }
        if ($_GET['mbs_msg'] == 'gcal_no_lib') echo '<div class="error"><p>⚠️ <b>Errore Librerie:</b> Manca la cartella <code>vendor</code>. Hai eseguito <code>composer require google/apiclient:^2.0</code>?</p></div>';
        if ($_GET['mbs_msg'] == 'gcal_no_creds') echo '<div class="error"><p>⚠️ <b>Errore Credenziali:</b> Client ID o Secret mancanti. Inseriscili e clicca su <b>"Salva Impostazioni"</b> PRIMA di cliccare su Connetti.</p></div>';
        if ($_GET['mbs_msg'] == 'gcal_error_gen') echo '<div class="error"><p>⚠️ <b>Errore Generico:</b> Impossibile inizializzare il client Google.</p></div>';
        if ($_GET['mbs_msg'] == 'gcal_access_denied') echo '<div class="error"><p>⛔ <b>Accesso Negato:</b> Hai annullato la connessione o Google ha bloccato l\'accesso (Verifica i "Test Users" nella console).</p></div>';
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
                    <th><?php echo mbs_adm_t('enable_pay_on_site'); ?></th>
                    <td>
                        <input type="checkbox" name="mbs_enable_pay_on_site" value="1" <?php checked(get_option('mbs_enable_pay_on_site', 0), 1); ?>>
                        <p class="description"><?php echo mbs_adm_t('enable_pay_on_site_desc'); ?></p>
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
                
                <tr><th colspan="2"><hr><h3><?php echo mbs_adm_t('gcal_title'); ?></h3>
                    <div style="background:#fff; border-left:4px solid #0073aa; padding:10px; margin-bottom:15px;">
                        <p><?php echo mbs_adm_t('gcal_help'); ?></p>
                        <code style="background:#eee; padding:5px; display:block; margin-top:5px;"><?php echo admin_url('admin.php?page=mbs-settings'); ?></code>
                    </div>
                </th></tr>
                <tr><th><?php echo mbs_adm_t('gcal_client_id'); ?></th><td><input type="text" name="mbs_gcal_client_id" value="<?php echo get_option('mbs_gcal_client_id'); ?>" class="regular-text"></td></tr>
                <tr><th><?php echo mbs_adm_t('gcal_client_secret'); ?></th><td><input type="text" name="mbs_gcal_client_secret" value="<?php echo get_option('mbs_gcal_client_secret'); ?>" class="regular-text"></td></tr>
                <tr><th><?php echo mbs_adm_t('gcal_calendar_id'); ?></th><td><input type="text" name="mbs_gcal_calendar_id" value="<?php echo get_option('mbs_gcal_calendar_id', 'primary'); ?>" class="regular-text"> <p class="description">Usa "primary" per il calendario principale.</p></td></tr>
                <tr>
                    <th>Autorizzazione</th>
                    <td>
                        <?php 
                        $has_creds = get_option('mbs_gcal_client_id') && get_option('mbs_gcal_client_secret');
                        if(get_option('mbs_gcal_token')): ?>
                            <span style="color:green; font-weight:bold;">✅ Connesso a Google Calendar</span>
                            <a href="<?php echo admin_url('admin.php?page=mbs-settings&mbs_gcal_auth=1'); ?>" class="button button-secondary">Riconnetti</a>
                        <?php elseif($has_creds): ?>
                            <a href="<?php echo admin_url('admin.php?page=mbs-settings&mbs_gcal_auth=1'); ?>" class="button button-primary">Connetti Google Account</a>
                        <?php else: ?>
                            <p class="description" style="color:#d63638;">⚠️ Inserisci e salva <b>Client ID</b> e <b>Client Secret</b> prima di connettere.</p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="mbs_save_settings" value="1">
            <?php submit_button(mbs_adm_t('save_settings')); ?>
        </form>
    </div>
    <?php
}