jQuery(document).ready(function($) {
    let bookedDates = {};
    const stripe = Stripe(mbs_vars.stripe_pk);
    const lang = mbs_vars.lang; // 'it' or 'en'
    
    // Testi tradotti passati da PHP
    const txt = mbs_vars.strings;
    let fpInstance; // Istanza Flatpickr

    // Funzione per caricare/aggiornare le date
    function loadDates() {
        $.ajax({
            url: mbs_vars.ajax_url,
            data: { action: 'mbs_get_dates' },
            success: function(res) {
                bookedDates = res;
                if (fpInstance) fpInstance.redraw(); // Ridisegna se esiste già
                else initCalendar(); // Inizializza se è la prima volta
            }
        });
    }
    loadDates(); // Avvio immediato

    function initCalendar() {
        // Flatpickr supporta la localizzazione. Se siamo in 'it', è già caricato lo script it.js
        let localeOpt = (lang === 'it') ? "it" : "default";

        fpInstance = flatpickr("#mbs-datepicker", {
            inline: true,
            minDate: "today",
            locale: localeOpt,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                let dateKey = formatDate(dayElem.dateObj);
                if (bookedDates[dateKey]) {
                    if (bookedDates[dateKey] === 'full') dayElem.classList.add('day-full-booked');
                    else if (bookedDates[dateKey] === 'morning') dayElem.classList.add('day-morning-booked');
                    else if (bookedDates[dateKey] === 'afternoon') dayElem.classList.add('day-afternoon-booked');
                }
            },
            onChange: function(sel, dateStr) {
                if (bookedDates[dateStr] === 'full') {
                    $('#mbs-booking-form').hide();
                    $('.mbs-wrapper').removeClass('step-2'); // Torna al centro se pieno
                    alert(txt.full_error);
                } else {
                    showForm(dateStr, bookedDates[dateStr]);
                }
            }
        });
    }

    function showForm(dateStr, status) {
        // 1. Attiva l'animazione (sposta calendario a sx, mostra form a dx)
        $('.mbs-wrapper').addClass('step-2');

        // 2. Scroll automatico su mobile per far vedere il form
        if(window.innerWidth < 768) {
            $('html, body').animate({ scrollTop: $('#mbs-booking-form').offset().top - 20 }, 500);
        }

        $('#mbs-booking-form').fadeIn();
        $('#mbs-date-display').text(dateStr);
        $('input[name="slot"]').prop('disabled', false).closest('.mbs-card').removeClass('disabled selected');
        $('input[name="slot"]').prop('checked', false);

        if (status === 'morning') {
            disableSlot('morning');
            disableSlot('full');
        } else if (status === 'afternoon') {
            disableSlot('afternoon');
            disableSlot('full');
        }
    }

    function disableSlot(val) {
        $(`input[value="${val}"]`).prop('disabled', true).closest('.mbs-card').addClass('disabled');
    }

    $('.mbs-card').click(function() {
        if($(this).hasClass('disabled')) return;
        $('.mbs-card').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input').prop('checked', true);
    });

    $('#mbs-form-action').submit(function(e) {
        e.preventDefault();
        
        let btn = $('.mbs-submit-btn');
        let originalText = btn.text();
        let loadingText = (mbs_vars.enable_payments == '1') ? txt.redirect : 'Processing...';
        btn.text(loadingText).prop('disabled', true);

        let data = {
            action: 'mbs_start_payment',
            security: mbs_vars.nonce,
            lang: lang, // Importante: inviamo la lingua corrente al backend
            date: $('#mbs-date-display').text(),
            slot: $('input[name="slot"]:checked').val(),
            nome: $('input[name="nome"]').val(),
            email: $('input[name="email"]').val(),
            phone: $('input[name="phone"]').val(),
            privacy: $('input[name="privacy"]').is(':checked'),
            payment_method: $('input[name="payment_method"]:checked').val()
        };

        if(!data.slot) { alert(txt.select_slot); btn.text(originalText).prop('disabled',false); return; }
        if(!data.privacy) { alert(txt.privacy_error); btn.text(originalText).prop('disabled',false); return; }

        $.post(mbs_vars.ajax_url, data, function(res) {
            if(res.success) {
                if (res.data.redirect_url) {
                    window.location.href = res.data.redirect_url;
                } else {
                    // SUCCESSO SENZA PAGAMENTO (Popup + Reset)
                    Swal.fire({
                        icon: 'success',
                        title: 'Prenotazione Confermata!',
                        text: res.data.message,
                        confirmButtonColor: '#0073aa'
                    }).then(() => {
                        // 1. Chiudi form e torna al centro
                        $('#mbs-booking-form').fadeOut();
                        $('.mbs-wrapper').removeClass('step-2');
                        // 2. Resetta campi
                        $('#mbs-form-action')[0].reset();
                        $('.mbs-card').removeClass('selected disabled');
                        btn.text(originalText).prop('disabled', false);
                        // 3. Aggiorna calendario (così la data appena presa risulta occupata)
                        loadDates();
                    });
                }
            } else {
                alert('Error: ' + res.data);
                btn.text(originalText).prop('disabled',false);
            }
        });
    });

    function formatDate(d) {
        return d.getFullYear() + '-' + ('0'+(d.getMonth()+1)).slice(-2) + '-' + ('0'+d.getDate()).slice(-2);
    }
});