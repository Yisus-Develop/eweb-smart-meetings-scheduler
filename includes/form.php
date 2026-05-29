<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function esms_render_shortcode(): string {
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }
    nocache_headers();

    wp_enqueue_style( 'esms-form' );
    wp_enqueue_script( 'esms-form' );

    $notice = '';
    $notice_cls = '';
    $is_success = false;

    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['esms_submit'] ) ) {
        $result = esms_handle_submission();
        $notice = $result['message'] ?? '';
        $is_success = ! empty( $result['ok'] );
        $notice_cls = $is_success ? 'esms-success' : 'esms-error';
    }

    $locations = esms_meeting_locations();
    $available_by_location = esms_available_slots_by_location();
    $default_location = esms_default_meeting_location();
    $selected_location = $default_location;

    if ( ! $is_success && isset( $_POST['meeting_location'] ) ) {
        $posted_location = sanitize_key( wp_unslash( $_POST['meeting_location'] ) );
        if ( isset( $locations[ $posted_location ] ) ) {
            $selected_location = $posted_location;
        }
    }

    $sticky = esms_get_sticky_form_values( $is_success );

    ob_start();
    ?>
    <div class="esms-wrap">
        <?php if ( $notice ) : ?><div class="esms-note <?php echo esc_attr( $notice_cls ); ?>"><?php echo esc_html( $notice ); ?></div><?php endif; ?>
        <form method="post"
            data-validation-segment="<?php echo esc_attr( esms_tr( array( 'pt' => 'Selecione pelo menos um segmento de mercado.', 'es' => 'Selecciona al menos un segmento de mercado.', 'en' => 'Please select at least one market segment.', 'fr' => 'Veuillez sélectionner au moins un segment de marché.' ) ) ); ?>"
            data-validation-gender="<?php echo esc_attr( esms_tr( array( 'pt' => 'Selecione o seu género.', 'es' => 'Selecciona tu género.', 'en' => 'Please select your gender.', 'fr' => 'Veuillez sélectionner votre genre.' ) ) ); ?>"
            data-validation-slot="<?php echo esc_attr( esms_tr( array( 'pt' => 'Selecione uma data e um horário.', 'es' => 'Selecciona una fecha y una hora.', 'en' => 'Please select a date and time.', 'fr' => 'Veuillez sélectionner une date et un horaire.' ) ) ); ?>">
            <?php wp_nonce_field( 'esms_book_meeting', 'esms_nonce' ); ?>
            <input type="hidden" name="esms_form_ts" value="<?php echo esc_attr( (string) time() ); ?>">
            <div style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">
                <label for="esms_website">Website</label>
                <input id="esms_website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>
            <div class="esms-grid">
                <div><label for="esms_full_name"><?php echo esc_html( esms_text( 'label_full_name', esms_tr( array( 'pt' => 'Nome e Apelido', 'es' => 'Nombre y Apellido', 'en' => 'First and Last Name', 'fr' => 'Nom et Prénom' ) ) ) ); ?></label><input id="esms_full_name" name="full_name" required type="text" value="<?php echo esc_attr( $sticky['full_name'] ); ?>"></div>
                <div><label for="esms_email"><?php echo esc_html( esms_text( 'label_email', esms_tr( array( 'pt' => 'Mail', 'es' => 'Correo', 'en' => 'Email', 'fr' => 'E-mail' ) ) ) ); ?></label><input id="esms_email" name="email" required type="email" value="<?php echo esc_attr( $sticky['email'] ); ?>"></div>
                <div><label for="esms_phone"><?php echo esc_html( esms_text( 'label_phone', esms_tr( array( 'pt' => 'Contacto Telefónico', 'es' => 'Teléfono de Contacto', 'en' => 'Phone Number', 'fr' => 'Téléphone' ) ) ) ); ?></label><input id="esms_phone" name="phone" required type="text" value="<?php echo esc_attr( $sticky['phone'] ); ?>"></div>
                <div><label for="esms_business_name"><?php echo esc_html( esms_text( 'label_business', esms_tr( array( 'pt' => 'Nome do Negócio', 'es' => 'Nombre del Negocio', 'en' => 'Business Name', 'fr' => 'Nom de l’Entreprise' ) ) ) ); ?></label><input id="esms_business_name" name="business_name" required type="text" value="<?php echo esc_attr( $sticky['business_name'] ); ?>"></div>
                <div class="esms-grid-1"><label for="esms_location_country"><?php echo esc_html( esms_text( 'label_location', esms_tr( array( 'pt' => 'Localidade e País', 'es' => 'Ciudad y País', 'en' => 'City and Country', 'fr' => 'Ville et Pays' ) ) ) ); ?></label><input id="esms_location_country" name="location_country" required type="text" value="<?php echo esc_attr( $sticky['location_country'] ); ?>"></div>
                <div><label><?php echo esc_html( esms_text( 'label_segment', esms_tr( array( 'pt' => 'Em que segmento de mercado trabalha', 'es' => 'Segmento de mercado', 'en' => 'Market segment', 'fr' => 'Segment de marché' ) ) ) ); ?></label><div class="esms-chipset"><input id="esms_seg_mtm" type="checkbox" name="market_segments[]" value="Made to Measure" <?php checked( in_array( 'Made to Measure', $sticky['market_segments'], true ) ); ?>><label for="esms_seg_mtm"><?php echo esc_html( esms_text( 'label_mtm', 'Made to Measure' ) ); ?></label><input id="esms_seg_rtw" type="checkbox" name="market_segments[]" value="Ready to Wear" <?php checked( in_array( 'Ready to Wear', $sticky['market_segments'], true ) ); ?>><label for="esms_seg_rtw"><?php echo esc_html( esms_text( 'label_rtw', 'Ready to Wear' ) ); ?></label></div></div>
                <div><label><?php echo esc_html( esms_text( 'label_gender', esms_tr( array( 'pt' => 'Para que género produzem', 'es' => 'Género', 'en' => 'Gender', 'fr' => 'Genre' ) ) ) ); ?></label><div class="esms-chipset"><input id="esms_gen_h" type="radio" name="target_gender" value="Men" required <?php checked( $sticky['target_gender'], 'Men' ); ?>><label for="esms_gen_h"><?php echo esc_html( esms_text( 'label_men', esms_tr( array( 'pt' => 'Homem', 'es' => 'Hombre', 'en' => 'Men', 'fr' => 'Homme' ) ) ) ); ?></label><input id="esms_gen_m" type="radio" name="target_gender" value="Women" <?php checked( $sticky['target_gender'], 'Women' ); ?>><label for="esms_gen_m"><?php echo esc_html( esms_text( 'label_women', esms_tr( array( 'pt' => 'Mulher', 'es' => 'Mujer', 'en' => 'Women', 'fr' => 'Femme' ) ) ) ); ?></label></div></div>
                <div class="esms-grid-1">
                    <label for="esms_meeting_location"><?php echo esc_html( esms_text( 'label_meeting_location', esms_tr( array( 'pt' => 'Local do encontro', 'es' => 'Lugar de la reunión', 'en' => 'Meeting location', 'fr' => 'Lieu du rendez-vous' ) ) ) ); ?></label>
                    <select id="esms_meeting_location" name="meeting_location" required class="esms-select-location">
                        <?php foreach ( $locations as $location_key => $location ) : ?>
                            <option value="<?php echo esc_attr( $location_key ); ?>" <?php selected( $location_key, $selected_location ); ?>><?php echo esc_html( $location['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="esms-grid-1">
                    <label><?php echo esc_html( esms_text( 'label_slot', esms_tr( array( 'pt' => 'Data e horário (30 min)', 'es' => 'Fecha y horario (30 min)', 'en' => 'Date and time (30 min)', 'fr' => 'Date et horaire (30 min)' ) ) ) ); ?></label>
                    <div class="esms-location-panels">
                        <?php foreach ( $available_by_location as $location_key => $location_data ) : ?>
                            <?php $grouped = $location_data['grouped_days']; ?>
                            <div class="esms-location-panel <?php echo $location_key === $selected_location ? 'active' : ''; ?>" data-location-panel="<?php echo esc_attr( $location_key ); ?>">
                                <?php if ( empty( $grouped ) ) : ?>
                                    <p class="esms-empty-slots"><?php echo esc_html( esms_text( 'msg_all_booked', esms_tr( array( 'pt' => 'Todas as datas e horários já estão completos.', 'es' => 'Todas las fechas y horarios ya están completos.', 'en' => 'All dates and times are fully booked.', 'fr' => 'Toutes les dates et horaires sont complets.' ) ) ) ); ?></p>
                                <?php else : ?>
                                    <div class="esms-tabs">
                                        <?php $first = true; foreach ( $grouped as $day => $slots ) : ?>
                                            <button type="button" class="esms-tab <?php echo $first ? 'active' : ''; ?>" data-location="<?php echo esc_attr( $location_key ); ?>" data-day="<?php echo esc_attr( $day ); ?>"><?php echo esc_html( esms_format_day_label( $day ) ); ?></button>
                                        <?php $first = false; endforeach; ?>
                                    </div>
                                    <?php $first = true; foreach ( $grouped as $day => $slots ) : ?>
                                        <div class="esms-slot-day <?php echo $first ? 'active' : ''; ?>" data-location="<?php echo esc_attr( $location_key ); ?>" data-day-panel="<?php echo esc_attr( $day ); ?>">
                                            <div class="esms-slots">
                                                <?php foreach ( $slots as $slot ) : $id = 'esms_slot_' . md5( $location_key . '_' . $slot['value'] ); ?>
                                                    <input id="<?php echo esc_attr( $id ); ?>" type="radio" name="slot_datetime" value="<?php echo esc_attr( $slot['value'] ); ?>" required <?php checked( $sticky['slot_datetime'], $slot['value'] ); ?>>
                                                    <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $slot['label'] ); ?></label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php $first = false; endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <button class="esms-submit" type="submit" name="esms_submit" value="1"><?php echo esc_html( esms_text( 'label_book_button', esms_tr( array( 'pt' => 'Agendar Reunião', 'es' => 'Reservar Reunión', 'en' => 'Book Meeting', 'fr' => 'Réserver une Réunion' ) ) ) ); ?></button>
        </form>
    </div>
    <?php
    return (string) ob_get_clean();
}

function esms_get_sticky_form_values( bool $is_success ): array {
    if ( $is_success || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        return array(
            'full_name' => '',
            'email' => '',
            'phone' => '',
            'business_name' => '',
            'location_country' => '',
            'market_segments' => array(),
            'target_gender' => '',
            'slot_datetime' => '',
        );
    }

    return array(
        'full_name' => sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) ),
        'email' => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
        'phone' => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
        'business_name' => sanitize_text_field( wp_unslash( $_POST['business_name'] ?? '' ) ),
        'location_country' => sanitize_text_field( wp_unslash( $_POST['location_country'] ?? '' ) ),
        'market_segments' => array_map( 'sanitize_text_field', array_map( 'wp_unslash', (array) ( $_POST['market_segments'] ?? array() ) ) ),
        'target_gender' => sanitize_text_field( wp_unslash( $_POST['target_gender'] ?? '' ) ),
        'slot_datetime' => sanitize_text_field( wp_unslash( $_POST['slot_datetime'] ?? '' ) ),
    );
}

function esms_handle_submission(): array {
    if ( ! isset( $_POST['esms_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['esms_nonce'] ) ), 'esms_book_meeting' ) ) {
        return array( 'ok' => false, 'message' => esms_text( 'err_security', esms_tr( array( 'pt' => 'Falha de segurança.', 'es' => 'Error de seguridad.', 'en' => 'Security check failed.', 'fr' => 'Échec de sécurité.' ) ) ) );
    }
    if ( ! esms_is_human_submission() ) {
        return array( 'ok' => false, 'message' => esms_text( 'err_validation', esms_tr( array( 'pt' => 'Falha de validação.', 'es' => 'Error de validación.', 'en' => 'Validation failed.', 'fr' => 'Échec de validation.' ) ) ) );
    }
    if ( esms_is_rate_limited() ) {
        return array( 'ok' => false, 'message' => esms_text( 'err_rate_limited', esms_tr( array( 'pt' => 'Muitas tentativas. Tente novamente em alguns minutos.', 'es' => 'Demasiados intentos. Inténtalo en unos minutos.', 'en' => 'Too many attempts. Please try again in a few minutes.', 'fr' => 'Trop de tentatives. Réessayez dans quelques minutes.' ) ) ) );
    }

    $full_name = sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) );
    $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $business_name = sanitize_text_field( wp_unslash( $_POST['business_name'] ?? '' ) );
    $location_country = sanitize_text_field( wp_unslash( $_POST['location_country'] ?? '' ) );
    $meeting_location = sanitize_key( wp_unslash( $_POST['meeting_location'] ?? '' ) );
    $slot_datetime = sanitize_text_field( wp_unslash( $_POST['slot_datetime'] ?? '' ) );
    $market_segments = array_map( 'sanitize_text_field', array_map( 'wp_unslash', (array) ( $_POST['market_segments'] ?? array() ) ) );
    $target_gender = sanitize_text_field( wp_unslash( $_POST['target_gender'] ?? '' ) );
    $market_segments = array_values( array_intersect( $market_segments, array( 'Made to Measure', 'Ready to Wear' ) ) );
    if ( ! in_array( $target_gender, array( 'Men', 'Women' ), true ) ) {
        $target_gender = '';
    }

    if ( ! $full_name || ! $email || ! $phone || ! $business_name || ! $location_country || ! $meeting_location || ! $slot_datetime || ! $target_gender || empty( $market_segments ) ) {
        return array( 'ok' => false, 'message' => esms_text( 'err_required_fields', esms_tr( array( 'pt' => 'Preencha todos os campos obrigatórios.', 'es' => 'Completa todos los campos obligatorios.', 'en' => 'Please fill all required fields.', 'fr' => 'Veuillez remplir tous les champs obligatoires.' ) ) ) );
    }

    $locations = esms_meeting_locations();
    if ( ! isset( $locations[ $meeting_location ] ) ) {
        return array( 'ok' => false, 'message' => esms_text( 'err_invalid_location', esms_tr( array( 'pt' => 'Local inválido.', 'es' => 'Ubicación inválida.', 'en' => 'Invalid location.', 'fr' => 'Lieu invalide.' ) ) ) );
    }

    $allowed = esms_allowed_slots( $meeting_location );
    if ( ! isset( $allowed[ $slot_datetime ] ) ) {
        return array( 'ok' => false, 'message' => esms_text( 'err_invalid_slot', esms_tr( array( 'pt' => 'Horário inválido.', 'es' => 'Horario inválido.', 'en' => 'Invalid slot.', 'fr' => 'Créneau invalide.' ) ) ) );
    }

    global $wpdb;
    $ok = $wpdb->insert(
        $wpdb->prefix . ESMS_TABLE,
        array(
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'business_name' => $business_name,
            'location_country' => $location_country,
            'market_segments' => implode( ', ', $market_segments ),
            'target_genders' => $target_gender,
            'meeting_location' => $meeting_location,
            'slot_datetime' => $slot_datetime,
            'created_at' => current_time( 'mysql' ),
        ),
        array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
    );

    if ( false === $ok ) {
        return array( 'ok' => false, 'message' => esms_text( 'err_slot_taken', esms_tr( array( 'pt' => 'Este horário já foi reservado.', 'es' => 'Ese horario ya fue reservado.', 'en' => 'This slot was already booked.', 'fr' => 'Ce créneau est déjà réservé.' ) ) ) );
    }

    esms_send_emails(
        array(
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'business_name' => $business_name,
            'location_country' => $location_country,
            'market_segments' => implode( ', ', $market_segments ),
            'target_genders' => $target_gender,
            'meeting_location' => $meeting_location,
            'meeting_location_label' => $locations[ $meeting_location ]['label'],
            'slot_datetime' => $slot_datetime,
            'slot_label' => $allowed[ $slot_datetime ],
        )
    );

    return array( 'ok' => true, 'message' => esms_text( 'msg_booking_confirmed', esms_tr( array( 'pt' => 'Reserva confirmada.', 'es' => 'Reserva confirmada.', 'en' => 'Booking confirmed.', 'fr' => 'Réservation confirmée.' ) ) ) );
}

function esms_is_human_submission(): bool {
    $honeypot = sanitize_text_field( wp_unslash( $_POST['website'] ?? '' ) );
    if ( '' !== $honeypot ) {
        return false;
    }

    $posted_ts = absint( $_POST['esms_form_ts'] ?? 0 );
    if ( $posted_ts <= 0 ) {
        return false;
    }

    // Bots often submit instantly; require at least 3 seconds.
    if ( ( time() - $posted_ts ) < 3 ) {
        return false;
    }

    return true;
}

function esms_is_rate_limited(): bool {
    $ip = esms_get_request_ip();
    if ( '' === $ip ) {
        return false;
    }

    $key = 'esms_rate_' . md5( $ip );
    $attempts = (int) get_transient( $key );
    $attempts++;
    set_transient( $key, $attempts, 10 * MINUTE_IN_SECONDS );

    return $attempts > 12;
}

function esms_get_request_ip(): string {
    $remote = wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' );
    if ( is_string( $remote ) ) {
        $remote = trim( $remote );
    } else {
        $remote = '';
    }
    return filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '';
}

function esms_is_real_form_post(): bool {
    $required_scalar_keys = array( 'full_name', 'email', 'phone', 'business_name', 'location_country', 'meeting_location', 'slot_datetime' );
    foreach ( $required_scalar_keys as $key ) {
        if ( ! isset( $_POST[ $key ] ) || '' === trim( (string) wp_unslash( $_POST[ $key ] ) ) ) {
            return false;
        }
    }

    $segments = (array) ( $_POST['market_segments'] ?? array() );
    $gender   = sanitize_text_field( wp_unslash( $_POST['target_gender'] ?? '' ) );
    if ( empty( $segments ) || ! $gender ) {
        return false;
    }

    return true;
}
