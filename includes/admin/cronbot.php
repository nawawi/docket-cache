<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

namespace Nawawi\DocketCache;

\defined('ABSPATH') || exit;

$is_connected = $this->is_cronbot_connected();
$ping_last = esc_html__('Not Available', 'docket-cache');
$ping_next = $ping_last;
$ping_data = $this->ping_next();
if (!empty($ping_data)) {
    $ping_next = $ping_data['next'];
    $ping_last = $ping_data['last'];
}
?>
<div class="section cronbot">
    <?php $this->tab_title(esc_html__('Cronbot', 'docket-cache')); ?>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e('Service Status', 'docket-cache'); ?></th>
            <td><?php echo $is_connected ? esc_html__('Connected', 'docket-cache') : esc_html__('Not Connected', 'docket-cache'); ?></td>
        </tr>

        <tr>
            <th><?php esc_html_e('Last Received Ping', 'docket-cache'); ?></th>
            <td><?php echo $ping_last; ?></td>
        </tr>

        <tr>
            <th><?php esc_html_e('Next Expecting Ping', 'docket-cache'); ?></th>
            <td><?php echo $ping_next; ?></td>
        </tr>

    </table>
<?php

function get_schedules() {
    $schedules = wp_get_schedules();
    uasort( $schedules, function( array $a, array $b ) {
        return ( $a['interval'] - $b['interval'] );
    } );

    array_walk( $schedules, function( array &$schedule, $name ) {
        $schedule['name'] = $name;
    } );

    return $schedules;
}


function populate_callback( array $callback ) {
    // If Query Monitor is installed, use its rich callback analysis.
    if ( method_exists( '\QM_Util', 'populate_callback' ) ) {
        return \QM_Util::populate_callback( $callback );
    }

    if ( is_string( $callback['function'] ) && ( false !== strpos( $callback['function'], '::' ) ) ) {
        $callback['function'] = explode( '::', $callback['function'] );
    }

    if ( is_array( $callback['function'] ) ) {
        if ( is_object( $callback['function'][0] ) ) {
            $class  = get_class( $callback['function'][0] );
            $access = '->';
        } else {
            $class  = $callback['function'][0];
            $access = '::';
        }

	$callback['name'] = $class . $access . $callback['function'][1] . '()';
    } elseif ( is_object( $callback['function'] ) ) {
        if ( is_a( $callback['function'], 'Closure' ) ) {
            $callback['name'] = 'Closure';
        } else {
            $class = get_class( $callback['function'] );

            $callback['name'] = $class . '->__invoke()';
        }
    } else {
	$callback['name'] = $callback['function'] . '()';
    }

    return $callback;
}

function get_hook_callbacks( $name ) {
    global $wp_filter;

    $actions = array();

    if ( isset( $wp_filter[ $name ] ) ) {
        // See http://core.trac.wordpress.org/ticket/17817.
        $action = $wp_filter[ $name ];

        foreach ( $action as $priority => $callbacks ) {
            foreach ( $callbacks as $callback ) {
                $callback = populate_callback( $callback );

                $actions[] = array(
                    'priority' => $priority,
                    'callback' => $callback,
                );
            }
        }
    }

    return $actions;
}

function get() {
    $crons  = _get_cron_array();
    $events = array();

    if ( empty( $crons ) ) {
        return array();
    }

    foreach ( $crons as $time => $cron ) {
        foreach ( $cron as $hook => $dings ) {
            foreach ( $dings as $sig => $data ) {

                // This is a prime candidate for a Crontrol_Event class but I'm not bothering currently.
                $events[ $hook.'-'.$sig.'-'.$time ] = (object)[
                    'hook'     => $hook,
                    'time'     => $time, // UTC
                    'sig'      => $sig,
                    'args'     => $data['args'],
                    'schedule' => $data['schedule'],
                    'interval' => isset( $data['interval'] ) ? $data['interval'] : null
                ];

            }
        }
    }

    // Ensure events are always returned in date descending order.
    // External cron runners such as Cavalcade don't guarantee events are returned in order of time.
    uasort( $events, function( $a, $b ) {
        if ( $a->time === $b->time ) {
            return 0;
        } else {
            return ( $a->time > $b->time ) ? 1 : -1;
        }
    } );

    return $events;
}

?>
    <p class="submit">
        <?php if ($is_connected) : ?>
        <a href="<?php echo $this->plugin->action_query('disconnect-cronbot', ['idx' => 'cronbot']); ?>" class="button button-secondary button-large"><?php esc_html_e('Disconnect', 'docket-cache'); ?></a>
        <?php else : ?>
        <a href="<?php echo $this->plugin->action_query('connect-cronbot', ['idx' => 'cronbot']); ?>" class="button button-primary button-large"><?php esc_html_e('Connect', 'docket-cache'); ?></a>
        <?php endif; ?>
    </p>
</div>