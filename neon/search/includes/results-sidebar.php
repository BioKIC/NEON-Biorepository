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
            <?php foreach ($resultChips as $chip) { ?>
                <span class="chip"><span class="chip-label"><?= htmlspecialchars($chip, ENT_QUOTES | ENT_SUBSTITUTE) ?></span></span>
            <?php } ?>
        </div>
        <?php if (!$resultChips) { ?><p id="criteria-empty">Nothing selected</p><?php } ?>
    </div>
</div>
