<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function esms_load_textdomain(): void {
    load_plugin_textdomain( 'eweb-eweb-smart-meetings-scheduler', false, dirname( plugin_basename( ESMS_FILE ) ) . '/languages' );
}

function esms_current_lang(): string {
    if ( function_exists( 'pll_current_language' ) ) {
        $lang = pll_current_language( 'slug' );
        if ( is_string( $lang ) && '' !== $lang ) {
            return $lang;
        }
    }
    return esms_lang_from_locale( get_locale() );
}

function esms_admin_lang(): string {
    $locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
    return esms_lang_from_locale( $locale );
}

function esms_lang_from_locale( string $locale ): string {
    $locale = strtolower( trim( $locale ) );
    if ( 0 === strpos( $locale, 'pt' ) ) {
        return 'pt';
    }
    if ( 0 === strpos( $locale, 'es' ) ) {
        return 'es';
    }
    if ( 0 === strpos( $locale, 'fr' ) ) {
        return 'fr';
    }
    return 'en';
}

function esms_tr( array $map ): string {
    $lang = esms_current_lang();
    if ( isset( $map[ $lang ] ) ) {
        return (string) $map[ $lang ];
    }
    return (string) ( $map['en'] ?? reset( $map ) );
}

function esms_admin_tr( array $map ): string {
    $lang = esms_admin_lang();
    if ( isset( $map[ $lang ] ) ) {
        return (string) $map[ $lang ];
    }
    return (string) ( $map['en'] ?? reset( $map ) );
}

function esms_default_ui_texts(): array {
    return esms_ui_texts_for_lang( esms_current_lang() );
}

function esms_ui_text_catalog(): array {
    return array(
        'label_full_name' => array( 'pt' => 'Nome e Apelido', 'es' => 'Nombre y Apellido', 'en' => 'First and Last Name', 'fr' => 'Nom et Prénom' ),
        'label_email' => array( 'pt' => 'Mail', 'es' => 'Correo', 'en' => 'Email', 'fr' => 'E-mail' ),
        'label_phone' => array( 'pt' => 'Contacto Telefónico', 'es' => 'Teléfono de Contacto', 'en' => 'Phone Number', 'fr' => 'Téléphone' ),
        'label_business' => array( 'pt' => 'Nome do Negócio', 'es' => 'Nombre del Negocio', 'en' => 'Business Name', 'fr' => 'Nom de l’Entreprise' ),
        'label_location' => array( 'pt' => 'Localidade e País', 'es' => 'Ciudad y País', 'en' => 'City and Country', 'fr' => 'Ville et Pays' ),
        'label_segment' => array( 'pt' => 'Em que segmento de mercado trabalha', 'es' => 'Segmento de mercado', 'en' => 'Market segment', 'fr' => 'Segment de marché' ),
        'label_gender' => array( 'pt' => 'Para que género produzem', 'es' => 'Género', 'en' => 'Gender', 'fr' => 'Genre' ),
        'label_slot' => array( 'pt' => 'Data e horário (30 min)', 'es' => 'Fecha y horario (30 min)', 'en' => 'Date and time (30 min)', 'fr' => 'Date et horaire (30 min)' ),
        'label_book_button' => array( 'pt' => 'Agendar Reunião', 'es' => 'Reservar Reunión', 'en' => 'Book Meeting', 'fr' => 'Réserver une Réunion' ),
        'label_mtm' => array( 'pt' => 'Made to Measure', 'es' => 'Made to Measure', 'en' => 'Made to Measure', 'fr' => 'Made to Measure' ),
        'label_rtw' => array( 'pt' => 'Ready to Wear', 'es' => 'Ready to Wear', 'en' => 'Ready to Wear', 'fr' => 'Ready to Wear' ),
        'label_men' => array( 'pt' => 'Homem', 'es' => 'Hombre', 'en' => 'Men', 'fr' => 'Homme' ),
        'label_women' => array( 'pt' => 'Mulher', 'es' => 'Mujer', 'en' => 'Women', 'fr' => 'Femme' ),
        'msg_all_booked' => array( 'pt' => 'Todas as datas e horários já estão completos.', 'es' => 'Todas las fechas y horarios ya están completos.', 'en' => 'All dates and times are fully booked.', 'fr' => 'Toutes les dates et horaires sont complets.' ),
        'err_security' => array( 'pt' => 'Falha de segurança.', 'es' => 'Error de seguridad.', 'en' => 'Security check failed.', 'fr' => 'Échec de sécurité.' ),
        'err_validation' => array( 'pt' => 'Falha de validação.', 'es' => 'Error de validación.', 'en' => 'Validation failed.', 'fr' => 'Échec de validation.' ),
        'err_rate_limited' => array( 'pt' => 'Muitas tentativas. Tente novamente em alguns minutos.', 'es' => 'Demasiados intentos. Inténtalo en unos minutos.', 'en' => 'Too many attempts. Please try again in a few minutes.', 'fr' => 'Trop de tentatives. Réessayez dans quelques minutes.' ),
        'err_required_fields' => array( 'pt' => 'Preencha todos os campos obrigatórios.', 'es' => 'Completa todos los campos obligatorios.', 'en' => 'Please fill all required fields.', 'fr' => 'Veuillez remplir tous les champs obligatoires.' ),
        'err_invalid_slot' => array( 'pt' => 'Horário inválido.', 'es' => 'Horario inválido.', 'en' => 'Invalid slot.', 'fr' => 'Créneau invalide.' ),
        'err_slot_taken' => array( 'pt' => 'Este horário já foi reservado.', 'es' => 'Ese horario ya fue reservado.', 'en' => 'This slot was already booked.', 'fr' => 'Ce créneau est déjà réservé.' ),
        'msg_booking_confirmed' => array( 'pt' => 'Reserva confirmada.', 'es' => 'Reserva confirmada.', 'en' => 'Booking confirmed.', 'fr' => 'Réservation confirmée.' ),
    );
}

function esms_ui_texts_for_lang( string $lang ): array {
    $catalog = esms_ui_text_catalog();
    $texts = array();

    foreach ( $catalog as $key => $map ) {
        $texts[ $key ] = (string) ( $map[ $lang ] ?? $map['en'] ?? reset( $map ) );
    }

    return $texts;
}

function esms_text( string $key, string $fallback ): string {
    $catalog = esms_ui_text_catalog();
    $current_lang = esms_current_lang();

    if ( function_exists( 'esms_get_settings' ) ) {
        $settings = esms_get_settings();
        if ( isset( $settings['ui_texts'][ $key ] ) ) {
            $value = trim( (string) $settings['ui_texts'][ $key ] );
            if ( '' !== $value ) {
                if ( 'pt' !== $current_lang && isset( $catalog[ $key ]['pt'] ) && $value === $catalog[ $key ]['pt'] ) {
                    return $fallback;
                }
                return $value;
            }
        }
    }
    return $fallback;
}
