<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function esms_meeting_locations(): array {
    return array(
        'new-york' => array(
            'label' => 'New York',
            'days' => array(
                '2026-07-21' => array( '09:00', '17:00' ),
                '2026-07-22' => array( '09:00', '17:00' ),
                '2026-07-23' => array( '09:00', '15:00' ),
            ),
        ),
        'chicago' => array(
            'label' => 'Chicago',
            'days' => array(
                '2026-08-01' => array( '09:00', '17:00' ),
                '2026-08-02' => array( '09:00', '17:00' ),
                '2026-08-03' => array( '09:00', '15:00' ),
            ),
        ),
    );
}

function esms_default_meeting_location(): string {
    $locations = esms_meeting_locations();
    $keys = array_keys( $locations );
    return (string) ( $keys[0] ?? '' );
}

function esms_allowed_slots( string $meeting_location = '' ): array {
    $locations = esms_meeting_locations();
    if ( '' === $meeting_location ) {
        $meeting_location = esms_default_meeting_location();
    }

    if ( ! isset( $locations[ $meeting_location ]['days'] ) ) {
        return array();
    }

    $slots = array();
    $tz = new DateTimeZone( ESMS_TZ );

    foreach ( $locations[ $meeting_location ]['days'] as $day => $range ) {
        $start = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $day . ' ' . $range[0], $tz );
        $end = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $day . ' ' . $range[1], $tz );
        if ( ! $start || ! $end ) {
            continue;
        }

        for ( $time = $start; $time < $end; $time = $time->modify( '+30 minutes' ) ) {
            $key = $time->format( 'Y-m-d H:i:s' );
            $slots[ $key ] = $time->format( 'H:i' );
        }
    }

    return $slots;
}

function esms_available_slots_by_location(): array {
    $locations = esms_meeting_locations();
    $available = array();

    foreach ( $locations as $location_key => $location ) {
        $all = esms_allowed_slots( $location_key );
        $booked = array_flip( esms_booked_slots( $location_key ) );
        $location_slots = array();

        foreach ( $all as $slot_value => $slot_label ) {
            if ( ! isset( $booked[ $slot_value ] ) ) {
                $location_slots[ $slot_value ] = $slot_label;
            }
        }

        $available[ $location_key ] = array(
            'label' => $location['label'],
            'grouped_days' => esms_group_slots_by_day( $location_slots ),
        );
    }

    return $available;
}

function esms_group_slots_by_day( array $available ): array {
    $grouped = array();
    foreach ( $available as $value => $label ) {
        $day = substr( $value, 0, 10 );
        if ( ! isset( $grouped[ $day ] ) ) {
            $grouped[ $day ] = array();
        }
        $grouped[ $day ][] = array(
            'value' => $value,
            'label' => $label,
        );
    }
    return $grouped;
}

function esms_format_day_label( string $day ): string {
    $tz = new DateTimeZone( ESMS_TZ );
    $date = DateTimeImmutable::createFromFormat( 'Y-m-d', $day, $tz );

    if ( ! $date ) {
        return $day;
    }

    return $date->format( 'j M' );
}
