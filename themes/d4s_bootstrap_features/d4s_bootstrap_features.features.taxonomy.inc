<?php
/**
 * @file
 * d4s_bootstrap_features.features.taxonomy.inc
 */

/**
 * Implements hook_taxonomy_default_vocabularies().
 */
function d4s_bootstrap_features_taxonomy_default_vocabularies() {
  return array(
    'tags' => array(
      'name' => '標籤 (Tags)',
      'machine_name' => 'tags',
      'description' => '透過標籤 (tags) 來為相似的主題文章做分群、分類。',
      'hierarchy' => 0,
      'module' => 'taxonomy',
      'weight' => 0,
      'rdf_mapping' => array(
        'rdftype' => array(
          0 => 'skos:ConceptScheme',
        ),
        'name' => array(
          'predicates' => array(
            0 => 'dc:title',
          ),
        ),
        'description' => array(
          'predicates' => array(
            0 => 'rdfs:comment',
          ),
        ),
      ),
    ),
  );
}
