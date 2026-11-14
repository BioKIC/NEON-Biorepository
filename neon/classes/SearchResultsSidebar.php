<?php

class SearchResultsSidebar {
    private $names;
    private $criteria;
    private $sampleTypes;

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

    public function removeUrl($clientRoot, $criterion) {
        $criteria = $this->criteria;
        if (strpos($criterion, 'db:') === 0) {
            $remaining = array();
            $wasAll = in_array('all', explode(',', $this->names->resolve('db', $criteria['db'] ?? '')), true);
            foreach ($this->sampleTypes() as $key => $selection) {
                if ((string) $key === substr($criterion, 3)) continue;
                $remaining = array_merge($remaining, $key === 'all' && $wasAll ? array('all') : $selection['ids']);
            }
            if ($remaining) $criteria['db'] = $this->names->encode('db', implode(',', $remaining), $this->names->collectionView($criteria['db']));
            else unset($criteria['db']);
        } elseif ($criterion === 'advanced') {
            foreach (array_keys($criteria) as $key) {
                if (preg_match('/^q_custom(?:andor|openparen|field|type|value|closeparen)\d+$/', $key)) unset($criteria[$key]);
            }
        } elseif ($criterion === 'dates') {
            unset($criteria['eventdate1'], $criteria['eventdate2']);
        } else {
            unset($criteria[$criterion]);
            if ($criterion === 'taxa') unset($criteria['taxontype'], $criteria['usethes']);
            if ($criterion === 'country') unset($criteria['countryCode'], $criteria['countryRaw'], $criteria['countryCodeRaw']);
        }
        if ((string) ($criteria['taxontype'] ?? '') === '2') unset($criteria['taxontype']);
        $query = SearchParameterNames::queryString($criteria);
        return rtrim($clientRoot, '/') . '/collections/list.php' . ($query !== '' ? '?'.$query : '');
    }

    private function sampleTypes() {
        if ($this->sampleTypes !== null) return $this->sampleTypes;
        $this->sampleTypes = array();
        if (!isset($this->criteria['db']) || $this->criteria['db'] === '') return $this->sampleTypes;
        $entries = $this->names->dictionary('db');
        $leaves = array();
        $fallback = array();
        $allIds = array();
        foreach ($entries as $entry) {
            if ($entry['view'] === 'taxonomic') {
                foreach ($entry['ids'] as $id) $allIds[$id] = $id;
                if (count($entry['ids']) === 1 && !isset($leaves[$entry['ids'][0]])) $leaves[$entry['ids'][0]] = $entry;
            } elseif (count($entry['ids']) === 1 && $entry['view'] === '') {
                $fallback[$entry['ids'][0]] = $this->entryLabel($entry);
            }
        }
        $labelCounts = array_count_values(array_column($leaves, 'label'));
        $selected = explode(',', $this->names->resolve('db', $this->criteria['db']));
        if (in_array('all', $selected, true)) $selected = array_merge(array_values($allIds), array_diff($selected, array('all')));
        if ($allIds && !array_diff($allIds, $selected)) {
            $this->sampleTypes['all'] = array('ids' => array_values($allIds), 'label' => 'All Sample Types at the Biorepository');
            $selected = array_values(array_diff($selected, $allIds));
        }
        $view = $this->names->collectionView($this->criteria['db']);
        $groups = array_filter($entries, function ($entry) use ($view) {
            return $entry['view'] === 'other' || (count($entry['ids']) > 1 && $entry['view'] === $view);
        });
        uasort($groups, function ($a, $b) { return count($b['ids']) <=> count($a['ids']); });
        foreach ($groups as $entry) {
            if (array_diff($entry['ids'], $selected)) continue;
            $this->sampleTypes[$entry['token']] = array('ids' => $entry['ids'], 'label' => $this->entryLabel($entry));
            $selected = array_values(array_diff($selected, $entry['ids']));
        }
        foreach (array_unique($selected) as $id) {
            if (isset($leaves[$id])) {
                $entry = $leaves[$id];
                $label = $this->entryLabel($entry);
                if ($labelCounts[$entry['label']] > 1) {
                    $path = array();
                    $parts = explode('/', $entry['token']);
                    for ($length = 1; $length <= count($parts); $length++) {
                        $parent = $entries[strtolower(implode('/', array_slice($parts, 0, $length)))] ?? null;
                        if ($parent) $path[] = $this->entryLabel($parent);
                    }
                    $label = implode(' / ', $path);
                }
            } else {
                $label = $fallback[$id] ?? ($id === '0' ? 'No matching selections' : $this->selectionLabel('db', $id));
            }
            $this->sampleTypes[$id] = array('ids' => array($id), 'label' => $label);
        }
        return $this->sampleTypes;
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
            $chips[$key] = $flag ? $label : $label.': '.(is_array($value) ? implode(', ', $value) : $value);
        };
        foreach ($this->sampleTypes() as $key => $selection) {
            $chips['db:'.$key] = (count($selection['ids']) > 1 ? 'Sample Types: ' : 'Sample Type: ') . $selection['label'];
        }
        unset($criteria['db']);
        $add('taxa', 'Taxa');
        if (isset($criteria['usethes']) && (string) $criteria['usethes'] === '0') $chips['usethes'] = 'Exact Taxon Only';
        unset($criteria['usethes']);
        if (!empty($criteria['datasetid'])) $chips['datasetid'] = 'Sites / Datasets: '.$this->selectionLabel('datasetid', $criteria['datasetid']);
        unset($criteria['datasetid']);
        $dates = array_filter(array($criteria['eventdate1'] ?? '', $criteria['eventdate2'] ?? ''), 'strlen');
        if ($dates) $chips['dates'] = 'Event Date: '.implode(' – ', $dates);
        unset($criteria['eventdate1'], $criteria['eventdate2']);
        $add('catnum', 'Identifier');
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
        if ($advanced) $chips['advanced'] = 'Advanced Search: '.implode(' ', $advanced);
        foreach (array('taxontype', 'includeothercatnum', 'countryCode', 'countryRaw', 'countryCodeRaw', 'usethes-associations', 'associated-taxon-type') as $key) unset($criteria[$key]);
        foreach ($criteria as $key => $value) {
            if ($value !== '' && $value !== null && $value !== array()) $add($key, $this->humanize($key));
        }
        return $chips;
    }
}
