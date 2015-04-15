ALTER TABLE %TABLE_PREFIX%scopus_citations ADD COLUMN sc_diff_previous INT(10) NULL AFTER sc_eid; 

ALTER TABLE %TABLE_PREFIX%thomson_citations ADD COLUMN tc_diff_previous INT(10) NULL AFTER tc_isi_loc;

ALTER TABLE %TABLE_PREFIX%altmetric ADD COLUMN as_1d FLOAT DEFAULT 0 NULL AFTER as_last_checked, ADD COLUMN as_2d FLOAT DEFAULT 0 NULL AFTER as_1d, ADD COLUMN as_3d FLOAT DEFAULT 0 NULL AFTER as_2d, ADD COLUMN as_4d FLOAT DEFAULT 0 NULL AFTER as_3d, ADD COLUMN as_5d FLOAT DEFAULT 0 NULL AFTER as_4d, ADD COLUMN as_6d FLOAT DEFAULT 0 NULL AFTER as_5d, ADD COLUMN as_1w FLOAT DEFAULT 0 NULL AFTER as_6d, ADD COLUMN as_1m FLOAT DEFAULT 0 NULL AFTER as_1w, ADD COLUMN as_3m FLOAT DEFAULT 0 NULL AFTER as_1m, ADD COLUMN as_6m FLOAT DEFAULT 0 NULL AFTER as_3m, ADD COLUMN as_1y FLOAT DEFAULT 0 NULL AFTER as_6m;