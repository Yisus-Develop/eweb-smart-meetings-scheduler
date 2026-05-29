<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function esms_activate(): void {
    esms_maybe_upgrade_schema();
}

function esms_maybe_upgrade_schema(): void {
    global $wpdb;
    $table_name = $wpdb->prefix . ESMS_TABLE;

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        full_name VARCHAR(190) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(100) NOT NULL,
        business_name VARCHAR(190) NOT NULL,
        location_country VARCHAR(190) NOT NULL,
        market_segments VARCHAR(80) NOT NULL,
        target_genders VARCHAR(80) NOT NULL,
        meeting_location VARCHAR(80) NOT NULL DEFAULT '',
        slot_datetime DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY unique_slot_location (meeting_location, slot_datetime)
    ) {$wpdb->get_charset_collate()};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

function esms_booked_slots( string $meeting_location = '' ): array {
    global $wpdb;

    if ( '' !== $meeting_location ) {
        $sql = $wpdb->prepare(
            'SELECT slot_datetime FROM ' . $wpdb->prefix . ESMS_TABLE . ' WHERE meeting_location = %s',
            $meeting_location
        );
        $rows = $sql ? $wpdb->get_col( $sql ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    } else {
        $rows = $wpdb->get_col( 'SELECT slot_datetime FROM ' . $wpdb->prefix . ESMS_TABLE ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    return array_map( 'strval', (array) $rows );
}
