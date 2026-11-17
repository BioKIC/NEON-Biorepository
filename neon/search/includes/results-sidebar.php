<?php
include_once($SERVER_ROOT . '/neon/classes/SearchResultsSidebar.php');
$resultsSidebar = new SearchResultsSidebar($collManager->getSearchParameterNames(), $collManager->getQueryTermArr());
$resultChips = $resultsSidebar->chips();
?>
<div id="criteria-panel" class="neon-search-controls search-results-controls">
    <a id="edit-search-btn" href="<?= htmlspecialchars($resultsSidebar->editUrl($CLIENT_ROOT), ENT_QUOTES | ENT_SUBSTITUTE) ?>">
        <svg class="search-button-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"></path></svg>
        <span>Edit Search</span>
    </a>
    <div class="selected-criteria">
        <h3>Selected Criteria</h3>
        <div id="chips">
            <?php foreach ($resultChips as $criterion => $chip) { ?>
                <span class="chip">
                    <span class="chip-label"><?= htmlspecialchars($chip, ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
                    <a class="chip-remove-btn" href="<?= htmlspecialchars($resultsSidebar->removeUrl($CLIENT_ROOT, $criterion), ENT_QUOTES | ENT_SUBSTITUTE) ?>" aria-label="<?= htmlspecialchars('Remove '.$chip, ENT_QUOTES | ENT_SUBSTITUTE) ?>" title="<?= htmlspecialchars('Remove '.$chip, ENT_QUOTES | ENT_SUBSTITUTE) ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm5 13.6L15.6 17 12 13.4 8.4 17 7 15.6l3.6-3.6L7 8.4 8.4 7l3.6 3.6L15.6 7 17 8.4 13.4 12Z"></path></svg>
                    </a>
                </span>
            <?php } ?>
        </div>
        <?php if (!$resultChips) { ?><p id="criteria-empty">Nothing selected</p><?php } ?>
    </div>
</div>
