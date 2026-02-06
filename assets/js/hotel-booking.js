/**
 * Altavista Hotel Booking System – Frontend logic
 *
 * Requirements implemented:
 * - Prefill from URL
 * - Occupancy rules + room auto / choice handling
 * - Pricing model mirroring server logic
 * - Live breakdown & total hidden input sync
 * - Capacity validation (max 4 guests)
 * - Flatpickr optional init
 * - AJAX submission with nonce + honeypot
 *
 * Global localized object expected: HBS_VARS = {
 *   ajax_url, nonce, prices: { single, double, extra_adult, extra_kid }
 * }
 */
(function ($) {
    'use strict';

    /* -------------------------------------------------------
     * Helper: Read URL parameters into a plain object
     * ----------------------------------------------------- */
    function readURLParams() {
        const params = {};
        const qs = window.location.search.substring(1);
        if (!qs) {
            return params;
        }
        qs.split('&').forEach(pair => {
            const [k, v] = pair.split('=');
            if (!k) {
                return;
            }
            params[decodeURIComponent(k)] = decodeURIComponent(v || '');
        });
        return params;
    }

    /* -------------------------------------------------------
     * Helper: Clamp numeric value
     * ----------------------------------------------------- */
    function clamp(val, min, max) {
        val = isNaN(val) ? min : val;
        if (val < min) return min;
        if (typeof max === 'number' && val > max) return max;
        return val;
    }

    /* -------------------------------------------------------
     * Helper: Currency formatting (MXN)
     * ----------------------------------------------------- */
    const numberMXN = (function () {
        let formatter;
        try {
            formatter = new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN',
                minimumFractionDigits: 2
            });
        } catch (e) {
            formatter = null;
        }
        return function (n) {
            n = Number(n) || 0;
            return formatter ? formatter.format(n) : '$' + n.toFixed(2);
        };
    })();

    /* -------------------------------------------------------
     * Helper: Get prices from localized vars or data-* fallback
     * ----------------------------------------------------- */
    function getPrices($form) {
        const fallback = {
            single: parseFloat($form.data('price-single')) || 0,
            double: parseFloat($form.data('price-double')) || 0,
            extra_adult: parseFloat($form.data('price-extra-adult')) || 0,
            extra_kid: parseFloat($form.data('price-extra-kid')) || 0
        };
        if (window.HBS_VARS && window.HBS_VARS.prices) {
            return {
                single: parseFloat(window.HBS_VARS.prices.single) || fallback.single,
                double: parseFloat(window.HBS_VARS.prices.double) || fallback.double,
                extra_adult: parseFloat(window.HBS_VARS.prices.extra_adult) || fallback.extra_adult,
                extra_kid: parseFloat(window.HBS_VARS.prices.extra_kid) || fallback.extra_kid
            };
        }
        return fallback;
    }

    /* -------------------------------------------------------
     * Occupancy & room selection rules
     * Returns:
     *  {
     *    forced: 'single'|'double'|null,
     *    choice: boolean,
     *    error: string|null,
     *    hint: string
     *  }
     * ----------------------------------------------------- */
    /**
     * Apply Room Rules - Dynamic Validation Based on Occupancy Parameters
     * This mirrors the backend HBS_Occupancy_Validator logic
     */
    function applyRoomRules(adults, kids) {
        const total = adults + kids;

        // Get room types configuration
        const roomTypes = window.HBS_VARS && window.HBS_VARS.room_types ? window.HBS_VARS.room_types : {};

        // If no room types configured, return error
        if (Object.keys(roomTypes).length === 0) {
            return {
                forced: null,
                choice: false,
                error: 'Error de configuración: tipos de habitación no disponibles.',
                hint: 'Por favor contacte al administrador'
            };
        }

        // Validate minimum requirements (at least 1 adult and 1 guest total)
        if (adults < 1 || total < 1) {
            return {
                forced: null,
                choice: false,
                error: 'Debe haber al menos 1 adulto y 1 huésped total.',
                hint: 'Configuración inválida'
            };
        }

        // Check each room type to see which ones can accommodate this occupancy
        const compatibleRooms = [];
        const errors = [];

        for (const [slug, roomData] of Object.entries(roomTypes)) {
            const validation = validateOccupancyForRoom(slug, roomData, adults, kids, total);

            if (validation.valid) {
                compatibleRooms.push({
                    slug: slug,
                    name: roomData.name
                });
            } else {
                errors.push(validation.error);
            }
        }

        // If no rooms fit, return error with most relevant message
        if (compatibleRooms.length === 0) {
            return {
                forced: null,
                choice: false,
                error: errors[0] || 'Ninguna habitación disponible cumple con estos requisitos.',
                hint: 'Reducir número de huéspedes'
            };
        }

        // If only one room fits, force it
        if (compatibleRooms.length === 1) {
            return {
                forced: compatibleRooms[0].slug,
                choice: false,
                error: null,
                hint: `${compatibleRooms[0].name} seleccionada automáticamente.`
            };
        }

        // Multiple rooms fit, allow choice
        const roomNames = compatibleRooms.map(r => r.name).join(' o ');
        return {
            forced: null,
            choice: true,
            error: null,
            hint: `Puede elegir ${roomNames}.`
        };
    }

    /**
     * Validate if a specific room can accommodate the given occupancy
     * Mirrors HBS_Occupancy_Validator::validate()
     */
    function validateOccupancyForRoom(slug, roomData, adults, kids, total) {
        const maxAdults = roomData.max_adults || 3;
        const maxKids = roomData.max_kids || 3;
        const maxTotal = roomData.max_total || 4;
        const baseOccupancy = roomData.base_occupancy || 2;
        const overflowRule = roomData.overflow_rule || 'kids_only';

        // Check hard limits
        if (adults > maxAdults) {
            return {
                valid: false,
                error: `Máximo ${maxAdults} adultos para ${roomData.name}.`
            };
        }

        if (kids > maxKids) {
            return {
                valid: false,
                error: `Máximo ${maxKids} niños (4-11 años) para ${roomData.name}.`
            };
        }

        if (total > maxTotal) {
            return {
                valid: false,
                error: `Capacidad máxima de ${maxTotal} personas para ${roomData.name}.`
            };
        }

        // Check overflow rules - only apply if exceeding base occupancy
        if (total > baseOccupancy) {
            if (overflowRule === 'kids_only') {
                // Only kids can exceed base occupancy, max 2 adults
                if (adults > 2) {
                    return {
                        valid: false,
                        error: `Para ${roomData.name}, solo se permiten 2 adultos. Huéspedes adicionales deben ser niños.`
                    };
                }
            }
            // 'any' rule: both adults and kids can exceed, already checked by hard limits above
        }

        return {
            valid: true,
            error: null
        };
    }

    /* -------------------------------------------------------
     * Compute pricing totals using dynamic occupancy parameters
     * Now uses base_occupancy from room type configuration
     * ----------------------------------------------------- */
    function computeTotals(roomType, adults, kids, nights, prices) {
        const totalGuests = adults + kids;
        let base = 0;
        let extras = 0;
        let extraAdults = 0;
        let extraKids = 0;

        // Get room configuration
        const roomTypes = window.HBS_VARS && window.HBS_VARS.room_types ? window.HBS_VARS.room_types : {};
        const roomConfig = roomTypes[roomType] || {};
        const baseOccupancy = roomConfig.base_occupancy || 2; // Fallback to 2 if not configured
        const basePrice = roomConfig.base_price || (roomType === 'single' ? prices.single : prices.double);

        base = basePrice;

        // Calculate extras only if total guests exceed base occupancy
        if (totalGuests > baseOccupancy) {
            const overflow = totalGuests - baseOccupancy;

            if (roomType === 'single') {
                // For single rooms, treat all overflow as extra adults
                extras = overflow * prices.extra_adult;
                extraAdults = overflow;
                extraKids = 0;
            } else {
                // For other rooms (typically 'double'), calculate separately
                // Extra adults first, then the rest are kids
                extraAdults = Math.max(adults - baseOccupancy, 0);
                extraKids = totalGuests - baseOccupancy - extraAdults;
                extras = (extraAdults * prices.extra_adult) + (extraKids * prices.extra_kid);
            }
        }

        const subtotalPerNight = base + extras;
        const total = subtotalPerNight * nights;

        return {
            base,
            extras,
            subtotalPerNight,
            total,
            baseOccupancy, // Include for rendering
            detail: {
                extraAdults,
                extraKids
            }
        };
    }

    /* -------------------------------------------------------
     * Render breakdown table
     * ----------------------------------------------------- */
    function renderBreakdown($target, pricing, nights, prices, roomType) {
        if (!$target.length) return;

        const r = pricing;
        const baseOcc = r.baseOccupancy || 2; // Use dynamic base occupancy
        const rows = [];
        const roomTypeName = roomType === 'single' ? 'Sencilla' : 'Doble';

        rows.push(`<tr><td>Tipo de habitación</td><td>${roomTypeName}</td></tr>`);
        rows.push(`<tr><td>Base por noche (incluye ${baseOcc} ${baseOcc === 1 ? 'persona' : 'personas'})</td><td>${numberMXN(r.base)}</td></tr>`);

        if (r.detail.extraAdults || r.detail.extraKids) {
            if (r.detail.extraAdults) {
                rows.push(`<tr><td>Adultos extra (${r.detail.extraAdults} × ${numberMXN(prices.extra_adult)})</td><td>${numberMXN(r.detail.extraAdults * prices.extra_adult)}</td></tr>`);
            }
            if (r.detail.extraKids) {
                rows.push(`<tr><td>Niños extra (${r.detail.extraKids} × ${numberMXN(prices.extra_kid)})</td><td>${numberMXN(r.detail.extraKids * prices.extra_kid)}</td></tr>`);
            }
        } else {
            rows.push('<tr><td>Extras</td><td>' + numberMXN(0) + '</td></tr>');
        }

        rows.push(`<tr><td>Subtotal por noche</td><td>${numberMXN(r.subtotalPerNight)}</td></tr>`);
        rows.push(`<tr><td>Noches</td><td>${nights}</td></tr>`);
        rows.push(`<tr><th>Total Estancia</th><th>${numberMXN(r.total)}</th></tr>`);

        const html = `<table class="hbs-breakdown" aria-label="Desglose de precio">${rows.join('')}</table>`;
        $target.html(html);
    }

    /* -------------------------------------------------------
     * Flatpickr optional initialization
     * ----------------------------------------------------- */
    function initFlatpickrIfPresent() {
        if (window.flatpickr && $('.js-flatpickr').length) {
            try {
                // Initialize each element individually to handle dynamically added elements
                $('.js-flatpickr').each(function () {
                    // Check if this element already has a flatpickr instance
                    if (!this._flatpickr) {
                        window.flatpickr(this, {
                            dateFormat: 'Y-m-d',
                            minDate: 'today',
                            locale: (window.flatpickr).l10ns && (window.flatpickr).l10ns.es ? 'es' : undefined
                        });
                    }
                });
            } catch (e) {
                console.error('Flatpickr initialization error:', e);
            }
        }
    }

    /* -------------------------------------------------------
     * Prefill from URL once
     * ----------------------------------------------------- */
    function prefillFromURL($form, updateUI) {
        const params = readURLParams();
        const today = new Date();
        const todayStr = today.toISOString().slice(0, 10);

        if (params.check_in_date) {
            const d = new Date(params.check_in_date);
            if (!isNaN(d.getTime()) && d >= new Date(todayStr)) {
                $form.find('[name="check_in_date"]').val(params.check_in_date);
            }
        }

        if (params.nights) {
            const n = clamp(parseInt(params.nights, 10), 1);
            $form.find('[name="nights"]').val(n);
        }
        if (params.adults) {
            const a = clamp(parseInt(params.adults, 10), 1);
            $form.find('[name="adults_count"]').val(a);
        }
        if (params.kids) {
            const k = clamp(parseInt(params.kids, 10), 0);
            $form.find('[name="kids_count"]').val(k);
        }

        updateUI();
    }

    /* -------------------------------------------------------
     * Main UI update pipeline
     * ----------------------------------------------------- */
    function makeUpdateUI($form) {
        const $adults = $form.find('[name="adults_count"]');
        const $kids = $form.find('[name="kids_count"]');
        const $nights = $form.find('[name="nights"]');
        const $roomRadios = $form.find('[name="room_type"]');
        const $hint = $('#hbs-room-hint');
        const $breakdown = $('#hbs-price-breakdown');
        const $totalField = $form.find('[name="total_price"]');
        const $submit = $form.find('button[type="submit"]');
        const prices = getPrices($form);

        return function updateUI() {
            if (!$form.length) return;

            let adults = clamp(parseInt($adults.val(), 10), 1);
            let kids = clamp(parseInt($kids.val(), 10), 0);
            let nights = clamp(parseInt($nights.val(), 10), 1);
            const total = adults + kids;

            // Sync clamped values back
            $adults.val(adults);
            $kids.val(kids);
            $nights.val(nights);

            const rules = applyRoomRules(adults, kids);

            // Handle capacity error
            if (rules.error) {
                $hint.text(rules.hint + ' — ' + rules.error);
                if (window.HBS_VARS && window.HBS_VARS.show_price_breakdown) {
                    $breakdown.html('<p style="color:#a00;font-weight:bold;">' + rules.error + '</p>');
                }
                $totalField.val('0');
                $submit.prop('disabled', true);
                return;
            }

            $submit.prop('disabled', false);

            let selectedRoom = $roomRadios.filter(':checked').val() || null;

            if (rules.forced) {
                selectedRoom = rules.forced;
                $roomRadios.filter('[value="' + rules.forced + '"]').prop('checked', true);
            } else if (rules.choice) {
                // If user hasn't picked yet, default to single
                if (!selectedRoom) {
                    selectedRoom = 'single';
                    $roomRadios.filter('[value="single"]').prop('checked', true);
                }
            } else {
                // Neither forced nor choice (unlikely path) fallback to double
                if (!selectedRoom) {
                    selectedRoom = 'double';
                    $roomRadios.filter('[value="double"]').prop('checked', true);
                }
            }

            // Update hint
            $hint.text(rules.hint);

            // Compute pricing
            const pricing = computeTotals(selectedRoom, adults, kids, nights, prices);

            // Only render breakdown if enabled in settings
            if (window.HBS_VARS && window.HBS_VARS.show_price_breakdown) {
                renderBreakdown($breakdown, pricing, nights, prices, selectedRoom);
            }

            // Set hidden total
            $totalField.val(pricing.total.toFixed(2));
        };
    }

    /* -------------------------------------------------------
     * AJAX submission handler
     * ----------------------------------------------------- */
    function attachSubmitHandler($form, updateUI) {
        const $message = $('#hbs-form-message');
        if (!$form.length) return;

        $form.on('submit', function (e) {
            e.preventDefault();

            const $submit = $form.find('button[type="submit"]');
            const adults = parseInt($form.find('[name="adults_count"]').val(), 10) || 1;
            const kids = parseInt($form.find('[name="kids_count"]').val(), 10) || 0;
            const nights = parseInt($form.find('[name="nights"]').val(), 10) || 1;
            const totalGuests = adults + kids;
            const hp = $form.find('[name="hbs_hp_field"]').val();
            const accepted = $form.find('[name="accept_policies"]').is(':checked');
            const guestName = $form.find('[name="guest_name"]').val().trim();
            const guestEmail = $form.find('[name="guest_email"]').val().trim();
            const guestPhone = $form.find('[name="guest_phone"]').val().trim();

            // Basic validations
            if (hp) {
                return; // Silent abort (honeypot filled)
            }
            if (totalGuests > 4) {
                showMessage('Capacidad máxima 4 personas.', false);
                return;
            }
            if (nights < 1) {
                showMessage('Número de noches inválido.', false);
                return;
            }
            if (!accepted) {
                showMessage('Debe aceptar las políticas del hotel.', false);
                return;
            }
            if (!guestName || !guestEmail || !guestPhone) {
                showMessage('Complete todos los datos de contacto.', false);
                return;
            }

            // Show loading overlay
            const $overlay = $('#hbs-loading-overlay');
            $overlay.fadeIn(200);
            $submit.prop('disabled', true);

            const data = $form.serialize();

            $.ajax({
                method: 'POST',
                url: (window.HBS_VARS && window.HBS_VARS.ajax_url) ? window.HBS_VARS.ajax_url : (window.ajaxurl || ''),
                data,
                dataType: 'json'
            })
                .done(function (resp) {
                    if (resp && resp.success) {
                        const bookingId = resp.data && resp.data.booking_id ? resp.data.booking_id : 0;
                        const thankyouUrl = (window.HBS_VARS && window.HBS_VARS.thankyou_url) ? window.HBS_VARS.thankyou_url : '';

                        // If thank you URL is set, redirect (keep overlay visible)
                        if (thankyouUrl && bookingId) {
                            const separator = thankyouUrl.indexOf('?') > -1 ? '&' : '?';
                            window.location.href = thankyouUrl + separator + 'booking_id=' + bookingId;
                            return;
                        }

                        // Fallback to inline message
                        const msg = resp.data && resp.data.msg ? resp.data.msg : 'Solicitud enviada correctamente.';
                        showMessage(msg, true);
                        // Preserve date; reset others
                        const currentDate = $form.find('[name="check_in_date"]').val();
                        $form[0].reset();
                        $form.find('[name="check_in_date"]').val(currentDate);
                        updateUI();
                    } else {
                        const err = resp && resp.data && resp.data.msg ? resp.data.msg : 'Ocurrió un error, inténtalo de nuevo.';
                        showMessage(err, false);
                    }
                })
                .fail(function () {
                    showMessage('Ocurrió un error, inténtal de nuevo.', false);
                })
                .always(function () {
                    // Hide overlay and re-enable button (unless redirecting)
                    $overlay.fadeOut(200);
                    $submit.prop('disabled', false);
                });

            function showMessage(text, success) {
                $message
                    .removeClass('success error')
                    .addClass(success ? 'success' : 'error')
                    .text(text)
                    .attr('tabindex', '-1')
                    .focus();
            }
        });
    }

    /* -------------------------------------------------------
     * Boot
     * ----------------------------------------------------- */
    $(function () {
        const $form = $('#hbs-booking-form');
        if (!$form.length) {
            return;
        }

        const updateUI = makeUpdateUI($form);

        // Prefill from URL (then initial compute)
        prefillFromURL($form, updateUI);

        // Event listeners for live updates
        $form.on('input change', '[name="adults_count"],[name="kids_count"],[name="nights"],[name="room_type"]', updateUI);

        // Initial compute (in case no URL params)
        updateUI();

        // Attach submit logic
        attachSubmitHandler($form, updateUI);
    });

    // ===============================================================
    // FLATPICKR INITIALIZATION - Runs on ALL pages (including floating form only)
    // ===============================================================
    $(function () {
        // Flatpickr optional - initial call
        initFlatpickrIfPresent();

        // Re-initialize multiple times to catch dynamically added elements (like floating form)
        // This handles cases where elements load after document.ready
        var retryCount = 0;
        var maxRetries = 5;
        var retryInterval = setInterval(function () {
            initFlatpickrIfPresent();
            retryCount++;
            if (retryCount >= maxRetries) {
                clearInterval(retryInterval);
            }
        }, 200); // Check every 200ms for up to 1 second total
    });
})(jQuery);