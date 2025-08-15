<?php 
// v1.0
include __DIR__ . '/../layout.php'; ?>

<h1>Seznam období</h1>
<table>
    <tr><th>Název</th><th>Popis</th></tr>
    <?php foreach($periods as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['display']) ?></td>
            <td><?= htmlspecialchars($p['description']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
