    </main>

    <?php
    // Dokumente anzeigen (oberhalb Footer)
    $dokumente = [];
    $dokumenteJson = getSetting('DOKUMENTE', '');
    if (!empty($dokumenteJson)) {
        $dokumente = json_decode($dokumenteJson, true) ?: [];
    }
    if (!empty($dokumente)):
    ?>
    <div class="dokumente-container">
        <div class="dokumente-header">
            <strong>📄 Nützliche Dokumente</strong>
        </div>
        <div class="dokumente-content">
            <!-- Desktop: Kompakte Links mit Tooltip -->
            <div class="dokumente-desktop">
                <?php
                $links = [];
                foreach ($dokumente as $dok) {
                    $title = escape($dok['titel']);
                    $link = escape($dok['link']);
                    $beschreibung = !empty($dok['beschreibung']) ? escape($dok['beschreibung']) : '';
                    $links[] = '<a href="' . $link . '" target="_blank" title="' . $beschreibung . '" class="dokument-link">' . $title . '</a>';
                }
                echo implode('<span class="dokument-separator">•</span>', $links);
                ?>
            </div>

            <!-- Mobile: Cards mit Beschreibung -->
            <div class="dokumente-mobile">
                <?php foreach ($dokumente as $dok): ?>
                <div class="dokument-card">
                    <div class="dokument-card-title">
                        <a href="<?php echo escape($dok['link']); ?>" target="_blank">
                            <?php echo escape($dok['titel']); ?>
                        </a>
                    </div>
                    <?php if (!empty($dok['beschreibung'])): ?>
                    <div class="dokument-card-desc">
                        <?php echo escape($dok['beschreibung']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <footer class="footer">
        <a href="../impressum.php">Impressum</a>
        <a href="../disclaimer.php">Disclaimer</a>
    </footer>
</body>
</html>
