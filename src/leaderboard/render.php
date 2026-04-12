<?php 

global $wpdb;

$results = $wpdb->get_results(
    "SELECT user_id, COUNT(*) as total_games, SUM(points_earned) as total_points
    FROM {$wpdb->prefix}epl_predictions
    GROUP BY user_id
    ORDER BY total_points DESC" 
);

?>

<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>G</th>
            <th>P</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($results as $row) : ?>
            <tr>
                <td><?php echo get_userdata($row->user_id)->user_login; ?></td>
                <td><?php echo $row->total_games; ?></td>
                <td><?php echo $row->total_points; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>