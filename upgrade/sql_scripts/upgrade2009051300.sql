ALTER TABLE %TABLE_PREFIX%record_search_key ADD `rek_gs_citation_count` INT( 11 ) NULL AFTER `rek_thomson_citation_count` ;
ALTER TABLE %TABLE_PREFIX%record_search_key ADD `rek_gs_cited_by_link` TEXT NULL AFTER `rek_gs_citation_count` ;
