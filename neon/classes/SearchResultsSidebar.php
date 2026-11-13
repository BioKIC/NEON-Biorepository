<?php

class SearchResultsSidebar {
    private $names;
    private $criteria;

    public function __construct($names, $criteria) {
        $this->names = $names;
        foreach (array('page', 'imagepage', 'tabindex', 'cntperpage', 'sortfield1', 'sortfield2', 'sortorder', 'comingFrom', 'taxonfilter', 'mode', 'targettid') as $key) {
            unset($criteria[$key]);
        }
        $this->criteria = $names->canonical($criteria);
    }

    public function editUrl($clientRoot) {
        $criteria = $this->criteria;
        if ((string) ($criteria['taxontype'] ?? '') === '2') unset($criteria['taxontype']);
        // Preserve an empty collection selection instead of applying editor defaults.
        if (!array_key_exists('db', $criteria)) $criteria['db'] = '';
        return rtrim($clientRoot, '/') . '/neon/search/index.php?' . SearchParameterNames::queryString($criteria);
    }

    private function entry($entries, $token, $scope = null) {
        $key = strtolower($token);
        if (isset($entries[$key]) && ($scope === null || !array_diff($entries[$key]['ids'], $scope))) return $entries[$key];
        foreach ($entries as $entry) {
            if ($scope !== null && array_diff($entry['ids'], $scope)) continue;
            if (strtolower($entry['short'] ?? $entry['token']) === $key
                || ($scope !== null && substr(strtolower($entry['token']), -strlen('/'.$key)) === '/'.$key)
                || (ctype_digit($token) && $entry['ids'] === array((int) $token))) return $entry;
        }
        return null;
    }

    private function selectionLabel($key, $value) {
        $entries = $this->names->dictionary($key);
        $labels = array();
        foreach (explode(',', $value) as $token) {
            if ($token === '') continue;
            if ($key === 'db' && in_array($token, array('all', 'allspec', 'allobs'), true)) {
                $labels[] = $token === 'all' ? 'All Sample Types at the Biorepository' : ($token === 'allspec' ? 'All specimen collections' : 'All observation collections');
                continue;
            }
            $parts = explode('!', $token);
            $base = array_shift($parts);
            $entry = $this->entry($entries, $base);
            $label = $entry ? $this->entryLabel($entry) : ($base === '0' ? 'No matching selections' : $base);
            $excluded = array();
            foreach ($parts as $part) {
                $child = $this->entry($entries, $part, $entry['ids'] ?? array());
                $excluded[] = $child ? $this->entryLabel($child) : $part;
            }
            if ($excluded) $label .= ' (excluding '.implode(', ', $excluded).')';
            $labels[] = $label;
        }
        return implode(', ', array_unique($labels));
    }

    private function entryLabel($entry) {
        if (in_array($entry['view'], array('site', 'domain', 'state'), true)) return $entry['short'] ?? $entry['token'];
        return html_entity_decode(strip_tags($entry['label']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function humanize($value) {
        return ucfirst(str_replace('_', ' ', preg_replace('/([a-z])([A-Z])/', '$1 $2', $value)));
    }

    public function chips() {
        $criteria = $this->criteria;
        $chips = array();
        $add = function ($key, $label, $flag = false) use (&$criteria, &$chips) {
            if (!isset($criteria[$key]) || $criteria[$key] === '' || $criteria[$key] === array()) return;
            $value = $criteria[$key];
            unset($criteria[$key]);
            if ($flag && !$value) return;
            $chips[] = $flag ? $label : $label.': '.(is_array($value) ? implode(', ', $value) : $value);
        };
        if (!empty($criteria['db'])) {
            $label = $this->selectionLabel('db', $criteria['db']);
            $chips[] = $criteria['db'] === 'all' ? $label : 'Some Sample Types: '.$label;
        }
        unset($criteria['db']);
        $add('taxa', 'Taxa');
        if (isset($criteria['usethes']) && (string) $criteria['usethes'] === '0') $chips[] = 'Exact Taxon Only';
        unset($criteria['usethes']);
        if (!empty($criteria['datasetid'])) $chips[] = 'Sites / Datasets: '.$this->selectionLabel('datasetid', $criteria['datasetid']);
        unset($criteria['datasetid']);
        foreach (array('eventdate1' => 'Event Date Start', 'eventdate2' => 'Event Date End', 'catnum' => 'Identifier') as $key => $label) $add($key, $label);
        foreach (array('hasimages' => 'Only with images', 'hasgenetic' => 'Only with genetic', 'availableforloan' => 'Only available for loan') as $key => $label) $add($key, $label, true);
        $add('collector', 'Collector/ORCID');
        $add('materialsampletype', 'Material Sample');
        $advanced = array();
        for ($row = 1; $row <= 8; $row++) {
            $parts = array();
            foreach (array('andor', 'openparen', 'field', 'type', 'value', 'closeparen') as $part) {
                $key = 'q_custom'.$part.$row;
                if (isset($criteria[$key]) && $criteria[$key] !== '') {
                    $value = $criteria[$key];
                    if ($part === 'field') $value = $this->humanize($value);
                    elseif ($part === 'type') $value = strtolower(str_replace('_', ' ', $value));
                    elseif ($part === 'value') $value = "'".$value."'";
                    $parts[] = $value;
                }
                unset($criteria[$key]);
            }
            if ($parts) $advanced[] = implode(' ', $parts);
        }
        if ($advanced) $chips[] = 'Advanced Search: '.implode(' ', $advanced);
        foreach (array('taxontype', 'includeothercatnum', 'countryCode', 'countryRaw', 'countryCodeRaw', 'usethes-associations', 'associated-taxon-type') as $key) unset($criteria[$key]);
        foreach ($criteria as $key => $value) {
            if ($value !== '' && $value !== null && $value !== array()) $add($key, $this->humanize($key));
        }
        return $chips;
    }
}
