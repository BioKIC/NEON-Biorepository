# Sample explorer scientific-name search

The explorer retains its local catalog text search and adds collection IDs returned by
`neon-react/biorepo_lib/sample-taxon-search.php`. It does not add taxa to the catalog JSON.
Deploy the PHP endpoint and `neon/classes/SampleCatalogTaxonSearch.php` together, then
rebuild/deploy the React explorer.

Scientific-name terms match the beginning of `omoccurrences.sciname`, using the database's
existing collation. Each term needs at least three characters. Quotes keep a full name
together, for example `"Peromyscus maniculatus"`; unquoted terms retain the explorer's
OR semantics. This is recorded scientific-name search, not a synonym or descendant-taxon
expansion service. Text fields still use their existing matching behavior.

Requests wait 350 ms after typing, cancel superseded requests, and time out in the browser
after ten seconds. Failures leave local text search available with a warning and retry.
The browser keeps at most 50 successful query results for five minutes. The endpoint
also caches successful results for five minutes with APCu when available; APCu is optional.
The endpoint accepts at most eight terms, each 3–200 characters, with bound SQL parameters
and escaped LIKE wildcards. It returns only IDs from the public catalog and excludes
occurrences with `recordSecurity = 5`. Virtual tissue collections use the same parent
collections and sample-type patterns as the catalog builder.

## Database verification

The checked-in schema declares `Index_sciname` on `omoccurrences(sciname)`. The queries
use `sciname LIKE 'prefix%'`, without a leading wildcard or a function on the column.
No schema changes are applied by this feature. Check the deployed database's index and
query plan before assuming production latency; local SQL fixtures are not a benchmark:

```sql
SHOW INDEX FROM omoccurrences WHERE Key_name = 'Index_sciname';
EXPLAIN SELECT DISTINCT o.collid
FROM omoccurrences o
WHERE o.sciname LIKE 'Peromyscus%' ESCAPE '!'
  AND o.collid IN (17,19,28)
  AND (o.recordSecurity IS NULL OR o.recordSecurity <> 5);
```

Use the actual catalog IDs and both common and uncommon names for production timing.
Broad prefixes may still match many records even with the existing index. The public
catalog snapshot must already exist; a missing/invalid snapshot produces a 503 response
and never causes the endpoint to rebuild the catalog during a search.
