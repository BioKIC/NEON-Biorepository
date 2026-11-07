<?php
// Keep the displayed citation and downloadable metadata together.
$neonCitation = array(
    'author' => 'NEON (National Ecological Observatory Network) Biorepository',
    'title' => $collData['collectionname'],
    'collectionCode' => $collData['collectioncode'] ?? '',
    'collectionId' => (int) $collData['collid'],
    'url' => 'https://biorepo.neonscience.org/portal/collections/misc/neoncollprofiles.php?collid=' . (int) $collData['collid'],
    'accessDate' => date('Y-m-d'),
    'license' => 'CC BY 4.0',
    'licenseUrl' => 'https://creativecommons.org/licenses/by/4.0/'
);
$neonCitation['note'] = 'Data accessed from ' . $neonCitation['url'] . ' on ' . $neonCitation['accessDate']
    . '. Licensed under ' . $neonCitation['license'] . ' (' . $neonCitation['licenseUrl']
    . '). Data were filtered and reformatted for analysis. Data archived at [your DOI].';
$neonCitationText = $neonCitation['author'] . '. ' . $neonCitation['title'] . '. ' . $neonCitation['note'];
?>
<span data-neon-citation="<?php echo htmlspecialchars(json_encode($neonCitation), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($neonCitationText, ENT_QUOTES, 'UTF-8'); ?></span>
