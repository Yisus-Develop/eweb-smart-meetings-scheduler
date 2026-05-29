<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'esms_admin_menu' );
add_action( 'admin_init', 'esms_register_settings' );

function esms_admin_menu(): void {
    add_menu_page(
        'EWEB Smart Meetings',
        'EWEB Meetings',
        'manage_options',
        'esms-settings',
        'esms_render_settings_page',
        'dashicons-calendar-alt',
        25
    );
}

function esms_register_settings(): void {
    register_setting(
        'esms_settings_group',
        'esms_settings',
        array(
            'type' => 'array',
            'sanitize_callback' => 'esms_sanitize_settings',
            'default' => array(),
        )
    );
}

function esms_sanitize_settings( $input ): array {
    $input = is_array( $input ) ? $input : array();

    $admin_recipients = sanitize_text_field( $input['admin_recipients'] ?? '' );
    $emails = array_filter( array_map( 'trim', explode( ',', $admin_recipients ) ) );
    $emails = array_values( array_filter( $emails, 'is_email' ) );

    $ui_texts = array();
    if ( isset( $input['ui_texts'] ) && is_array( $input['ui_texts'] ) ) {
        foreach ( $input['ui_texts'] as $key => $value ) {
            $k = sanitize_key( (string) $key );
            $ui_texts[ $k ] = sanitize_text_field( (string) $value );
        }
    }

    return array(
        'admin_recipients' => implode( ', ', $emails ),
        'from_name' => sanitize_text_field( $input['from_name'] ?? '' ),
        'from_email' => sanitize_email( $input['from_email'] ?? '' ),
        'calendar_event_title' => sanitize_text_field( $input['calendar_event_title'] ?? '' ),
        'calendar_organizer_name' => sanitize_text_field( $input['calendar_organizer_name'] ?? '' ),
        'calendar_organizer_email' => sanitize_email( $input['calendar_organizer_email'] ?? '' ),
        'subject_client' => sanitize_text_field( $input['subject_client'] ?? '' ),
        'subject_admin' => sanitize_text_field( $input['subject_admin'] ?? '' ),
        'body_client' => wp_kses_post( $input['body_client'] ?? '' ),
        'body_admin' => wp_kses_post( $input['body_admin'] ?? '' ),
        'success_message' => sanitize_text_field( $input['success_message'] ?? '' ),
        'ui_texts' => $ui_texts,
    );
}

function esms_get_settings(): array {
    $site_name = get_bloginfo( 'name' );
    $admin_email = get_option( 'admin_email' );

    $defaults = array(
        'admin_recipients' => $admin_email,
        'from_name' => $site_name,
        'from_email' => $admin_email,
        'calendar_event_title' => trim( $site_name . ' Meeting' ),
        'calendar_organizer_name' => $site_name,
        'calendar_organizer_email' => $admin_email,
        'subject_client' => esms_admin_tr( array( 'pt' => 'Confirmação de Reunião - EWEB', 'es' => 'Confirmación de Reunión - EWEB', 'en' => 'Meeting Confirmation - EWEB', 'fr' => 'Confirmation de Réunion - EWEB' ) ),
        'subject_admin' => esms_admin_tr( array( 'pt' => 'Nova reunião agendada - EWEB', 'es' => 'Nueva reunión agendada - EWEB', 'en' => 'New meeting booked - EWEB', 'fr' => 'Nouveau rendez-vous planifié - EWEB' ) ),
        'body_client' => esms_admin_tr( array( 'pt' => "A sua reunião foi confirmada.\n\n{summary}", 'es' => "Su reunión ha sido confirmada.\n\n{summary}", 'en' => "Your meeting has been confirmed.\n\n{summary}", 'fr' => "Votre rendez-vous a été confirmé.\n\n{summary}" ) ),
        'body_admin' => esms_admin_tr( array( 'pt' => "Nova reunião agendada:\n\n{summary}", 'es' => "Nueva reunión agendada:\n\n{summary}", 'en' => "New meeting booked:\n\n{summary}", 'fr' => "Nouveau rendez-vous planifié :\n\n{summary}" ) ),
        'success_message' => esms_admin_tr( array( 'pt' => 'Reserva confirmada. Enviámos a confirmação por email.', 'es' => 'Reserva confirmada. Hemos enviado la confirmation por correo.', 'en' => 'Booking confirmed. We have sent confirmation by email.', 'fr' => 'Réservation confirmée. Nous avons envoyé la confirmation par e-mail.' ) ),
        'ui_texts' => esms_default_ui_texts(),
    );

    $saved = get_option( 'esms_settings', array() );
    if ( ! is_array( $saved ) ) {
        $saved = array();
    }

    return wp_parse_args( $saved, $defaults );
}

function esms_render_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $s = esms_get_settings();
    $ui_texts = esms_default_ui_texts();
    $locations = esms_meeting_locations();
    if ( isset( $s['ui_texts'] ) && is_array( $s['ui_texts'] ) ) {
        $ui_texts = wp_parse_args( $s['ui_texts'], $ui_texts );
    }
    $table = $wpdb->prefix . ESMS_TABLE;

    if ( isset( $_POST['esms_delete_leads'] ) && isset( $_POST['esms_delete_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['esms_delete_nonce'] ) ), 'esms_delete_leads' ) ) {
        $ids = array_map( 'absint', (array) ( $_POST['lead_ids'] ?? array() ) );
        $ids = array_values( array_filter( $ids ) );
        if ( ! empty( $ids ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
            $sql = $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ($placeholders)", $ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            if ( $sql ) {
                $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                echo '<div class="notice notice-success"><p>' . esc_html( esms_admin_tr( array( 'pt' => 'Registos selecionados eliminados.', 'es' => 'Leads seleccionados eliminados.', 'en' => 'Selected leads deleted.', 'fr' => 'Prospects sélectionnés supprimés.' ) ) ) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-warning"><p>' . esc_html( esms_admin_tr( array( 'pt' => 'Não selecionou registos para eliminar.', 'es' => 'No seleccionaste leads para eliminar.', 'en' => 'No leads were selected for deletion.', 'fr' => 'Aucun prospect sélectionné à supprimer.' ) ) ) . '</p></div>';
        }
    }

    $leads = $wpdb->get_results( "SELECT id, full_name, email, phone, business_name, location_country, market_segments, target_genders, meeting_location, slot_datetime, created_at FROM {$table} ORDER BY created_at DESC LIMIT 200", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    ?>
    <div class="wrap">
        <h1>EWEB Smart Meetings Scheduler</h1>
        <p><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Tokens disponíveis para os templates:', 'es' => 'Tokens disponibles para plantillas:', 'en' => 'Available template tokens:', 'fr' => 'Jetons disponibles pour les modèles :' ) ) ); ?> <code>{full_name}</code>, <code>{email}</code>, <code>{phone}</code>, <code>{business_name}</code>, <code>{location_country}</code>, <code>{meeting_location}</code>, <code>{market_segments}</code>, <code>{target_genders}</code>, <code>{slot_label}</code>, <code>{summary}</code>, <code>{summary_table}</code>.</p>

        <form method="post" action="options.php">
            <?php settings_fields( 'esms_settings_group' ); ?>
            <table class="form-table" role="presentation">
                <tr><th scope="row"><label for="esms_admin_recipients"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Emails de destino (admin)', 'es' => 'Correos destino (admin)', 'en' => 'Admin recipient emails', 'fr' => 'E-mails destinataires (admin)' ) ) ); ?></label></th><td><input name="esms_settings[admin_recipients]" id="esms_admin_recipients" type="text" class="regular-text" value="<?php echo esc_attr( $s['admin_recipients'] ); ?>" /><p class="description"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Separados por vírgula.', 'es' => 'Separados por coma.', 'en' => 'Separate multiple emails with commas.', 'fr' => 'Séparez les e-mails par des virgules.' ) ) ); ?></p></td></tr>
                <tr><th scope="row"><label for="esms_from_name"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Nome do remetente', 'es' => 'Nombre remitente', 'en' => 'Sender name', 'fr' => 'Nom de l’expéditeur' ) ) ); ?></label></th><td><input name="esms_settings[from_name]" id="esms_from_name" type="text" class="regular-text" value="<?php echo esc_attr( $s['from_name'] ); ?>" /></td></tr>
                <tr><th scope="row"><label for="esms_from_email"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Email do remetente', 'es' => 'Email remitente', 'en' => 'Sender email', 'fr' => 'E-mail de l’expéditeur' ) ) ); ?></label></th><td><input name="esms_settings[from_email]" id="esms_from_email" type="email" class="regular-text" value="<?php echo esc_attr( $s['from_email'] ); ?>" /></td></tr>
                <tr><th scope="row"><label for="esms_calendar_event_title"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Título do evento no calendário', 'es' => 'Título del evento en calendario', 'en' => 'Calendar event title', 'fr' => 'Titre de l’événement du calendrier' ) ) ); ?></label></th><td><input name="esms_settings[calendar_event_title]" id="esms_calendar_event_title" type="text" class="regular-text" value="<?php echo esc_attr( $s['calendar_event_title'] ); ?>" /></td></tr>
                <tr><th scope="row"><label for="esms_calendar_organizer_name"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Nome do organizador no calendário', 'es' => 'Nombre del organizador en calendario', 'en' => 'Calendar organizer name', 'fr' => 'Nom de l’organisateur du calendrier' ) ) ); ?></label></th><td><input name="esms_settings[calendar_organizer_name]" id="esms_calendar_organizer_name" type="text" class="regular-text" value="<?php echo esc_attr( $s['calendar_organizer_name'] ); ?>" /></td></tr>
                <tr><th scope="row"><label for="esms_calendar_organizer_email"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Email do organizador no calendário', 'es' => 'Email del organizador en calendario', 'en' => 'Calendar organizer email', 'fr' => 'E-mail de l’organisateur du calendrier' ) ) ); ?></label></th><td><input name="esms_settings[calendar_organizer_email]" id="esms_calendar_organizer_email" type="email" class="regular-text" value="<?php echo esc_attr( $s['calendar_organizer_email'] ); ?>" /><p class="description"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Use um email válido da marca para o campo "Quem" do convite.', 'es' => 'Usa un email válido de la marca para el campo "Quién" de la invitación.', 'en' => 'Use a valid brand email for the invitation organizer field.', 'fr' => 'Utilisez un e-mail valide de la marque pour le champ organisateur de l’invitation.' ) ) ); ?></p></td></tr>
                <tr><th scope="row"><label for="esms_subject_client"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Assunto do cliente', 'es' => 'Asunto cliente', 'en' => 'Client subject', 'fr' => 'Objet client' ) ) ); ?></label></th><td><input name="esms_settings[subject_client]" id="esms_subject_client" type="text" class="regular-text" value="<?php echo esc_attr( $s['subject_client'] ); ?>" /></td></tr>
                <tr><th scope="row"><label for="esms_subject_admin"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Assunto do admin', 'es' => 'Asunto admin', 'en' => 'Admin subject', 'fr' => 'Objet admin' ) ) ); ?></label></th><td><input name="esms_settings[subject_admin]" id="esms_subject_admin" type="text" class="regular-text" value="<?php echo esc_attr( $s['subject_admin'] ); ?>" /></td></tr>
                <tr><th scope="row"><label for="esms_body_client"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Mensagem para o cliente', 'es' => 'Mensaje cliente', 'en' => 'Client message', 'fr' => 'Message client' ) ) ); ?></label></th><td><textarea name="esms_settings[body_client]" id="esms_body_client" rows="6" class="large-text"><?php echo esc_textarea( $s['body_client'] ); ?></textarea></td></tr>
                <tr><th scope="row"><label for="esms_body_admin"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Mensagem para o admin', 'es' => 'Mensaje admin', 'en' => 'Admin message', 'fr' => 'Message admin' ) ) ); ?></label></th><td><textarea name="esms_settings[body_admin]" id="esms_body_admin" rows="6" class="large-text"><?php echo esc_textarea( $s['body_admin'] ); ?></textarea></td></tr>
                <tr><th scope="row"><label for="esms_success_message"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Mensagem de sucesso do formulário', 'es' => 'Mensaje éxito formulario', 'en' => 'Form success message', 'fr' => 'Message de succès du formulaire' ) ) ); ?></label></th><td><input name="esms_settings[success_message]" id="esms_success_message" type="text" class="regular-text" value="<?php echo esc_attr( $s['success_message'] ); ?>" /></td></tr>
            </table>
            <h2><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Textos do frontend (editáveis)', 'es' => 'Frontend Texts (Editable)', 'en' => 'Frontend Texts (Editable)', 'fr' => 'Textes du frontend (modifiables)' ) ) ); ?></h2>
            <table class="form-table" role="presentation">
                <?php foreach ( $ui_texts as $key => $value ) : ?>
                    <tr>
                        <th scope="row"><label for="esms_ui_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $key ); ?></label></th>
                        <td><input name="esms_settings[ui_texts][<?php echo esc_attr( $key ); ?>]" id="esms_ui_<?php echo esc_attr( $key ); ?>" type="text" class="regular-text" value="<?php echo esc_attr( $value ); ?>" /></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php submit_button(); ?>
        </form>

        <hr>
        <h2><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Leads guardados (últimos 200)', 'es' => 'Leads guardados (últimos 200)', 'en' => 'Saved leads (latest 200)', 'fr' => 'Prospects enregistrés (200 derniers)' ) ) ); ?></h2>
        <form method="post">
            <?php wp_nonce_field( 'esms_delete_leads', 'esms_delete_nonce' ); ?>
            <p>
                <button type="submit" name="esms_delete_leads" value="1" class="button button-secondary" onclick="return confirm('<?php echo esc_js( esms_admin_tr( array( 'pt' => 'Eliminar os registos selecionados? Esta ação não pode ser desfeita.', 'es' => '¿Eliminar leads seleccionados? Esta acción no se puede deshacer.', 'en' => 'Delete selected leads? This action cannot be undone.', 'fr' => 'Supprimer les prospects sélectionnés ? Cette action est irréversible.' ) ) ); ?>');">
                    <?php echo esc_html( esms_admin_tr( array( 'pt' => 'Eliminar selecionados', 'es' => 'Eliminar seleccionados', 'en' => 'Delete selected', 'fr' => 'Supprimer la sélection' ) ) ); ?>
                </button>
            </p>
        <table class="widefat striped">
            <thead><tr><th>ID</th><th><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Data', 'es' => 'Fecha', 'en' => 'Date', 'fr' => 'Date' ) ) ); ?></th><th><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Nome', 'es' => 'Nombre', 'en' => 'Name', 'fr' => 'Nom' ) ) ); ?></th><th>Email</th><th><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Telefone', 'es' => 'Teléfono', 'en' => 'Phone', 'fr' => 'Téléphone' ) ) ); ?></th><th><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Negócio', 'es' => 'Negocio', 'en' => 'Business', 'fr' => 'Entreprise' ) ) ); ?></th><th><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Localização', 'es' => 'Ubicación', 'en' => 'Location', 'fr' => 'Lieu' ) ) ); ?></th><th><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Sede', 'es' => 'Sede', 'en' => 'Venue', 'fr' => 'Site' ) ) ); ?></th><th><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Segmento', 'es' => 'Segmento', 'en' => 'Segment', 'fr' => 'Segment' ) ) ); ?></th><th><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Género', 'es' => 'Género', 'en' => 'Gender', 'fr' => 'Genre' ) ) ); ?></th><th>Slot</th></tr></thead>
            <tbody>
            <?php if ( empty( $leads ) ) : ?>
                <tr><td colspan="11"><?php echo esc_html( esms_admin_tr( array( 'pt' => 'Ainda não existem registos.', 'es' => 'Sin registros todavía.', 'en' => 'No records yet.', 'fr' => 'Aucun enregistrement pour le moment.' ) ) ); ?></td></tr>
            <?php else : foreach ( $leads as $lead ) : ?>
                <tr>
                    <td><label><input type="checkbox" name="lead_ids[]" value="<?php echo esc_attr( (string) $lead['id'] ); ?>"> <?php echo esc_html( $lead['id'] ); ?></label></td>
                    <td><?php echo esc_html( $lead['created_at'] ); ?></td>
                    <td><?php echo esc_html( $lead['full_name'] ); ?></td>
                    <td><?php echo esc_html( $lead['email'] ); ?></td>
                    <td><?php echo esc_html( $lead['phone'] ); ?></td>
                    <td><?php echo esc_html( $lead['business_name'] ); ?></td>
                    <td><?php echo esc_html( $lead['location_country'] ); ?></td>
                    <td><?php echo esc_html( $locations[ $lead['meeting_location'] ]['label'] ?? $lead['meeting_location'] ); ?></td>
                    <td><?php echo esc_html( $lead['market_segments'] ); ?></td>
                    <td><?php echo esc_html( $lead['target_genders'] ); ?></td>
                    <td><?php echo esc_html( $lead['slot_datetime'] ); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </form>
    </div>
    <?php
}
