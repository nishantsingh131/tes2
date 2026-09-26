-- Disable the old non-banking catalog before running the banking importer.
UPDATE series
SET active = 0
WHERE slug IN ('ssc-cgl', 'ssc-chsl', 'rrb-ntpc', 'rrb-group-d', 'bpsc-prelims', 'state-psc-mains', 'nda-cds', 'ctet');