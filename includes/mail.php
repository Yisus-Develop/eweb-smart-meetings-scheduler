<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function esms_send_emails( array $data ): void {
    $to_client = $data['email'];
    $settings = function_exists( 'esms_get_settings' ) ? esms_get_settings() : array();
    $to_admin = $settings['admin_recipients'] ?? get_option( 'admin_email' );
    if ( empty( $to_admin ) ) {
        $to_admin = get_option( 'admin_email' );
    }
    $from_name = $settings['from_name'] ?? get_bloginfo( 'name' );
    $from_email = $settings['from_email'] ?? get_option( 'admin_email' );

    $subject_client = $settings['subject_client'] ?? esms_tr( array( 'pt' => 'Confirmação de Reunião - EWEB', 'es' => 'Confirmación de Reunión - EWEB', 'en' => 'Meeting Confirmation - EWEB', 'fr' => 'Confirmation de Réunion - EWEB' ) );
    $subject_admin = $settings['subject_admin'] ?? esms_tr( array( 'pt' => 'Nova reunião agendada - EWEB', 'es' => 'Nueva reunión agendada - EWEB', 'en' => 'New meeting booked - EWEB', 'fr' => 'Nouveau rendez-vous planifié - EWEB' ) );

    $summary_text = "Name: {$data['full_name']}\nEmail: {$data['email']}\nPhone: {$data['phone']}\nBusiness: {$data['business_name']}\nCity/Country: {$data['location_country']}\nMeeting location: {$data['meeting_location_label']}\nSegment: {$data['market_segments']}\nGender: {$data['target_genders']}\nTime: {$data['slot_label']} (GMT -4)\n";
    $summary_html = esms_build_summary_html( $data );

    $ics_path = esms_generate_ics( $data );
    $attachments = $ics_path ? array( $ics_path ) : array();
    $headers_html = array( 'Content-Type: text/html; charset=UTF-8' );
    if ( is_email( $from_email ) ) {
        $headers_html[] = 'From: ' . wp_specialchars_decode( $from_name, ENT_QUOTES ) . ' <' . $from_email . '>';
        $headers_html[] = 'Reply-To: ' . $from_email;
    }

    $client_tpl = $settings['body_client'] ?? "Your meeting has been confirmed.\n\n{summary}";
    $admin_tpl  = $settings['body_admin'] ?? "New meeting booked:\n\n{summary}";
    $client_msg = esms_mail_replace_tokens( $client_tpl, $data, $summary_text );
    $admin_msg  = esms_mail_replace_tokens( $admin_tpl, $data, $summary_text );
    $client_msg_html = esms_build_client_email_html( $subject_client, $client_msg, $summary_html );
    $admin_msg_html = esms_build_admin_email_html( $subject_admin, $admin_msg, $summary_html );

    wp_mail( $to_client, $subject_client, $client_msg_html, $headers_html, $attachments );
    wp_mail( $to_admin, $subject_admin, $admin_msg_html, $headers_html, $attachments );

    if ( $ics_path && file_exists( $ics_path ) ) {
        wp_delete_file( $ics_path );
    }
}

function esms_build_summary_html( array $data ): string {
    $rows = array(
        'Name' => $data['full_name'] ?? '',
        'Email' => $data['email'] ?? '',
        'Phone' => $data['phone'] ?? '',
        'Business' => $data['business_name'] ?? '',
        'City/Country' => $data['location_country'] ?? '',
        'Meeting location' => $data['meeting_location_label'] ?? '',
        'Segment' => $data['market_segments'] ?? '',
        'Gender' => $data['target_genders'] ?? '',
        'Time' => ( $data['slot_label'] ?? '' ) . ' (GMT -4)',
    );

    $html = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-collapse:collapse;">';
    foreach ( $rows as $label => $value ) {
        $html .= '<tr>';
        $html .= '<td style="padding:8px 10px;border-bottom:1px solid #edf1f7;font-weight:600;color:#24324a;width:34%;">' . esc_html( $label ) . '</td>';
        $html .= '<td style="padding:8px 10px;border-bottom:1px solid #edf1f7;color:#1f2a44;">' . esc_html( $value ) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';

    return $html;
}

function esms_build_client_email_html( string $subject, string $message, string $summary_html ): string {
    $message = str_replace( '{summary_table}', $summary_html, $message );
    $message = wpautop( wp_kses_post( $message ) );
    return '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="margin:0;padding:24px;background:#f5f7fb;font-family:Arial,sans-serif;color:#1f2a44;">'
        . '<div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e6ebf4;border-radius:14px;overflow:hidden;">'
        . '<div style="background:#1f376f;color:#fff;padding:16px 20px;font-size:18px;font-weight:700;">' . esc_html( $subject ) . '</div>'
        . '<div style="padding:20px;">'
        . $message
        . '<p style="margin:14px 0 0;font-size:12px;color:#5b6b87;">Calendar file (.ics) attached for Gmail, Apple Calendar and Outlook.</p>'
        . '</div></div></body></html>';
}

function esms_build_admin_email_html( string $subject, string $message, string $summary_html ): string {
    $message = str_replace( '{summary_table}', $summary_html, $message );
    $message = wpautop( wp_kses_post( $message ) );
    return '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="margin:0;padding:24px;background:#f5f7fb;font-family:Arial,sans-serif;color:#1f2a44;">'
        . '<div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e6ebf4;border-radius:14px;overflow:hidden;">'
        . '<div style="background:#0f2247;color:#fff;padding:16px 20px;font-size:18px;font-weight:700;">' . esc_html( $subject ) . '</div>'
        . '<div style="padding:20px;">'
        . $message
        . '</div></div></body></html>';
}

function esms_mail_replace_tokens( string $template, array $data, string $summary ): string {
    $summary_table_text = $summary;
    $replace = array(
        '{full_name}' => $data['full_name'] ?? '',
        '{email}' => $data['email'] ?? '',
        '{phone}' => $data['phone'] ?? '',
        '{business_name}' => $data['business_name'] ?? '',
        '{location_country}' => $data['location_country'] ?? '',
        '{market_segments}' => $data['market_segments'] ?? '',
        '{target_genders}' => $data['target_genders'] ?? '',
        '{meeting_location}' => $data['meeting_location_label'] ?? '',
        '{slot_label}' => $data['slot_label'] ?? '',
        '{summary}' => $summary,
        '{summary_table}' => $summary_table_text,
    );
    return strtr( $template, $replace );
}

function esms_generate_ics( array $data ): string {
    $settings = function_exists( 'esms_get_settings' ) ? esms_get_settings() : array();
    $tz = new DateTimeZone( ESMS_TZ );
    $start = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $data['slot_datetime'], $tz );
    if ( ! $start ) {
        return '';
    }
    $end = $start->modify( '+30 minutes' );
    $start_utc = $start->setTimezone( new DateTimeZone( 'UTC' ) );
    $end_utc = $end->setTimezone( new DateTimeZone( 'UTC' ) );
    $calendar_title = trim( (string) ( $settings['calendar_event_title'] ?? '' ) );
    if ( '' === $calendar_title ) {
        $calendar_title = trim( get_bloginfo( 'name' ) . ' Meeting' );
    }
    $organizer_name = trim( (string) ( $settings['calendar_organizer_name'] ?? '' ) );
    if ( '' === $organizer_name ) {
        $organizer_name = $settings['from_name'] ?? get_bloginfo( 'name' );
    }
    $organizer_email = is_email( $settings['calendar_organizer_email'] ?? '' ) ? $settings['calendar_organizer_email'] : '';
    if ( '' === $organizer_email ) {
        $organizer_email = is_email( $settings['from_email'] ?? '' ) ? $settings['from_email'] : '';
    }
    if ( '' === $organizer_email ) {
        $organizer_email = is_email( get_option( 'admin_email' ) ) ? get_option( 'admin_email' ) : 'no-reply@example.com';
    }
    $attendee_email = is_email( $data['email'] ?? '' ) ? $data['email'] : '';
    $uid = uniqid( 'esms_', true ) . '@example.com';
    $description = esms_ics_escape(
        'Business: ' . ( $data['business_name'] ?? '' ) .
        ' | Contact: ' . ( $data['full_name'] ?? '' ) .
        ' | Email: ' . ( $data['email'] ?? '' ) .
        ' | Phone: ' . ( $data['phone'] ?? '' )
    );

    $ics = "BEGIN:VCALENDAR\r\n" .
        "PRODID:-//EWEB//Smart Meetings Scheduler//EN\r\n" .
        "VERSION:2.0\r\n" .
        "CALSCALE:GREGORIAN\r\n" .
        "METHOD:REQUEST\r\n" .
        "BEGIN:VEVENT\r\n" .
        "UID:{$uid}\r\n" .
        'DTSTAMP:' . gmdate( 'Ymd\\THis\\Z' ) . "\r\n" .
        'DTSTART:' . $start_utc->format( 'Ymd\\THis\\Z' ) . "\r\n" .
        'DTEND:' . $end_utc->format( 'Ymd\\THis\\Z' ) . "\r\n" .
        'SUMMARY:' . esms_ics_escape( $calendar_title ) . "\r\n" .
        'LOCATION:' . esms_ics_escape( $data['meeting_location_label'] ?? '' ) . "\r\n" .
        "DESCRIPTION:{$description}\r\n" .
        "STATUS:CONFIRMED\r\n" .
        "SEQUENCE:0\r\n" .
        "TRANSP:OPAQUE\r\n" .
        'ORGANIZER;CN=' . esms_ics_escape( $organizer_name ) . ':mailto:' . esms_ics_escape( $organizer_email ) . "\r\n" .
        ( $attendee_email ? 'ATTENDEE;CN=' . esms_ics_escape( $data['full_name'] ?? 'Guest' ) . ';RSVP=TRUE:mailto:' . esms_ics_escape( $attendee_email ) . "\r\n" : '' ) .
        "BEGIN:VALARM\r\n" .
        "TRIGGER:-PT30M\r\n" .
        "ACTION:DISPLAY\r\n" .
        "DESCRIPTION:Meeting reminder\r\n" .
        "END:VALARM\r\n" .
        "END:VEVENT\r\n" .
        "END:VCALENDAR\r\n";

    $tmp_dir = get_temp_dir();
    if ( ! is_dir( $tmp_dir ) || ! wp_is_writable( $tmp_dir ) ) {
        return '';
    }

    $tmp = trailingslashit( $tmp_dir ) . 'eweb-meeting-' . wp_generate_password( 10, false, false ) . '.ics';
    $written = file_put_contents( $tmp, $ics );
    if ( false === $written ) {
        return '';
    }

    return $tmp;
}

function esms_ics_escape( string $value ): string {
    $value = str_replace( array( '\\', ';', ',' ), array( '\\\\', '\\;', '\\,' ), $value );
    return str_replace( array( "\r\n", "\r", "\n" ), '\\n', $value );
}
