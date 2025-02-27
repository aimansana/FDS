<h3>Land Details</h3>

<?php if (!empty($land_details)): ?>
    <table border="1">
        <thead>
            <tr>
                <th>Land ID</th>
                <th>Location</th>
                <th>Soil Type</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($land_details as $land): ?>
                <tr>
                    <td><?php echo htmlspecialchars($land['landID']); ?></td>
                    <td><?php echo htmlspecialchars($land['landlocation']); ?></td>
                    <td><?php echo htmlspecialchars($land['soiltype']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No land records found.</p>
<?php endif; ?>
