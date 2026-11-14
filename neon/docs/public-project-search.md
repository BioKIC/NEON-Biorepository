# Public research project search

`neon/datasets/neonpubliclist.php` supplies filter metadata to the uncompiled
`NeonProjectTable.jsx` component. It no longer loads every project's specimen
counts and site lists during the HTML request.

The table requests `neon/datasets/rpc/publicprojects.php`, which uses
`PublicProjectSearch` to count matching projects and fetch one page. The default
page size is 25; supported sizes are 10, 25, 50, and 100. Ordering is newest start
date first, with dataset ID as a stable tie-breaker. Only public Request datasets
with a non-null request start date are returned, regardless of login status.

## URL filters

| Parameter | Meaning | Example |
| --- | --- | --- |
| `db` or `collID` | Sample-type collection IDs or existing readable sample-type aliases | `db=24,120` |
| `datasetid` / `datasetID` | Dataset IDs or existing readable dataset/site aliases | `datasetid=70` or `datasetid=ABBY` |
| `site` or `siteName` | NEON site codes or full site names | `site=ABBY` |
| `q` | Search words in project title, description, PI name, or institution | `q=soil+carbon` |
| `page` | One-based page number | `page=2` |
| `pageSize` | Projects per page | `pageSize=50` |

All parameter names are case-insensitive. Multiple selections within a filter
use commas and match any selected value. Different filters intersect: at least
one record in the project must match the selected sample type, dataset, and site
together. Virtual mammal tissue collection IDs follow the material-sample
mapping used by record searches.

A dataset filter finds projects sharing records with that public dataset. If it
is the only record filter, it also includes the project whose own dataset ID was
selected, even if that project has no linked records yet. Unknown names and
unknown/private dataset IDs do not remove a filter or broaden a search.

Search runs on the server before pagination. Each search word must occur in at
least one searchable field. The table updates the URL as criteria/page change,
supports browser Back, and cancels superseded requests.

## Overview and deployment

The four overview totals always cover all public projects, independently of the
table filters. They are fetched separately with `action=overview`; PHP releases
the session lock so this request does not block the table. Specimen totals count
distinct records across projects. Public totals are cached for 15 minutes in
`neon/cache/public-project-overview-v1.json` when that directory is writable.
Removing that file forces a refresh. No per-user data is cached.

Deploy the PHP changes together with a rebuilt React bundle from
`NEON-Biorepository-React-Components`. The source changes are in
`src/biorepo_components/NeonProjectTable.jsx` and `projectSearch.js`; generated
React files are not part of this change.
