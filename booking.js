jQuery(document).ready(function($) {
    let bookedDates = {};
    const stripe = Stripe(mbs_vars.stripe_pk); // Inizializza Stripe con la chiave pubblica

    // 1. CARICA DATE
    $.ajax({
        url: mbs_vars.ajax_url,
        data: { action: 'mbs_get_dates' },
        success: function(res) {
            bookedDates = res;
            initCalendar();
        }
    });

    function initCalendar() {
        flatpickr("#mbs-datepicker", {
            inline: true,
            minDate: "today",
            locale: "it",
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
                    alert("Data non disponibile.");
                } else {
                    showForm(dateStr, bookedDates[dateStr]);
                }
            }
        });
    }

    function showForm(dateStr, status) {
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

    // UX Cards
    $('.mbs-card').click(function() {
        if($(this).hasClass('disabled')) return;
        $('.mbs-card').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input').prop('checked', true);
    });

    // 2. CLICK PAGA E PRENOTA
    $('#mbs-form-action').submit(function(e) {
        e.preventDefault();
        
        let btn = $('.mbs-submit-btn');
        btn.text('Reindirizzamento a Stripe...').prop('disabled', true);

        let data = {
            action: 'mbs_start_payment',
            security: mbs_vars.nonce,
            date: $('#mbs-date-display').text(),
            slot: $('input[name="slot"]:checked').val(),
            nome: $('input[name="nome"]').val(),
            email: $('input[name="email"]').val()
        };

        if(!data.slot) { alert("Scegli uno orario!"); btn.prop('disabled',false).text('Paga'); return; }

        $.post(mbs_vars.ajax_url, data, function(res) {
            if(res.success) {
                // REDIRECT A STRIPE
                stripe.redirectToCheckout({ sessionId: res.data.id });
            } else {
                alert('Errore: ' + res.data);
                btn.prop('disabled',false).text('Riprova');
            }
        });
    });

    function formatDate(d) {
        return d.getFullYear() + '-' + ('0'+(d.getMonth()+1)).slice(-2) + '-' + ('0'+d.getDate()).slice(-2);
    }
});