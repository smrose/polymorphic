<?php
/* NAME
 *
 *  pps.php
 *
 * CONCEPT
 *
 *  Libary functions and constants for the Pattern Sphere project.
 *
 * FUNCTIONS
 *
 *  DataStoreConnect  open a database connection
 *  CountPatterns     count of pattern using a template
 *  GetPatterns       fetch pattern records
 *  GetPattern        fetch a pattern with its features
 *  InsertPattern     insert a pattern record
 *  GetTemplates      fetch pattern_template records
 *  GetTemplate       fetch pattern_template record with its features
 *  UpdateTemplate    update pattern_template record
 *  DeleteTemplate    delete a template and associated data structures
 *  GetPTFeatures     fetch pattern_feature records for this template
 *  GetPTFcount       count feature values for this feature and template
 *  GetFeatures       fetch pattern_features
 *  GetFeature        fetch pattern_feature by id
 *  InsertFeature     insert a pattern_feature and create a table for it
 *  InsertTemplateFeature associate this feature with this template
 *  UpdateFeature     apply pattern_feature update
 *  DeleteFeature     delete this feature, its table, and references to it
 *  InsertTemplate    insert a pattern_template
 *  UpdatePattern     update a pattern
 *  UpdatePatternFeatures update features for a pattern
 *  DeleteFeatureValue delete this feature value
 *  UpdateFeatureValue update one feature value
 *  InsertFeatureValue insert a feature value
 *  FeatureStats      return stats of use of a pattern_feature
 *  Error             die with message
 *  Alert             bold message
 *  CheckIdentifier   true if an identifier can be used in a MySQL table name
 *  CheckFile         validate file upload
 *  IsImageUsed       true if an image is referenced in a feature value
 *  DeleteImage       delete the file specified by its hash
 *  ImagePath         return the file system path to an uploaded file
 */
 
require 'db.php';

define('FOOT', '<div id="foot"><a href="https://www.publicsphereproject.org/">Public Sphere Project</a></div>');
define('DEBUG', true);

# TYPE is a catalog of human-readable feature types => database types

define('TYPE', array(
  'image' => 'mediumblob',
  'integer' => 'integer',
  'string' => 'varchar(255)',
  'text' => 'mediumtext'
  ));
define('IMAGEROOT', 'images');
define('MAXIMAGE', 8000000);
define('IDEPTH', 1);
define('GIWHITE', array('image/gif', 'image/jpeg', 'iage/png'));
define('NOFILE', 4);
define('UPERROR', array(
  1 => 'too large per system limit',
  2 => 'too large per application limit',
  3 => 'partial upload',
  NOFILE => 'no file selected'
));


/* DataStoreConnect()
 *
 *  Connect to the database (unless it's already been done).
 */
 
function DataStoreConnect() {
  global $pdo;

  if(isset($pdo))
    return;

  try {
    $pdo = new PDO(DSN, USER, PW);
    //$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
} /* end DataStoreConnect() */


/* CountPatterns()
 *
 *  Return a count of patterns using this template.
 */

function CountPatterns($ptid) {
  global $pdo;

  $query = 'SELECT count(*) FROM pattern p WHERE ptid = ?';
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute([$ptid]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  $pcount = $sth->fetchColumn();
  return $pcount;
  
} /* end CountPatterns() */


/* GetPatterns()
 *
 *  Fetch patterns - all or selected.
 */

function GetPatterns($which = null) {
  global $pdo;

  if(isset($which)) {
    if(is_array($which)) {
      $q = '';
      $u = [];
      foreach($which as $column => $value) {
        if(strlen($q))
	  $q .= ' AND ';
        $q .= " $column = ?";
	$u[] = $value;
      }
      $q = "WHERE $q";
    }
  } else {
    $q = '';
    $u = [];
  }

  $query = "SELECT p.*, pt.name AS ptname, pft.value AS title FROM pattern p
 JOIN pattern_template pt ON p.ptid = pt.id
 LEFT JOIN pf_title pft ON p.id = pft.pid $q";

  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
        exit();
  }
  try {
    $rv = $sth->execute($u);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
  }
  $patterns = [];
  while($pattern = $sth->fetch()) {
    $patterns[$pattern['id']] = $pattern;
  }
  return $patterns ? $patterns : null;
  
} /* end GetPatterns() */


/* GetPattern()
 *
 *  Get a pattern - specified by pattern.id - with its features and feature
 *  values.
 *
 *  The 'features' field will include an array for every feature supported
 *  by the template. If that array has a 'value' field, there is a record
 *  in the feature value table for this pattern and feature.
 */

function GetPattern($id) {
  global $pdo;

  # Fetch the pattern itself.

  try {
    $sth = $pdo->prepare('SELECT * FROM pattern WHERE id = ?');
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, $e->getMessage(), ' ', $e->getCode();
    exit();
  }
  try {
    $sth->execute([$id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, $e->getMessage(), ' ', $e->getCode();
    exit();
  }
  $pattern = $sth->fetch();

  if(!$pattern)
    return false;
    
  # Fetch all the features associated with the template associated with
  # this pattern.
  
  $query = "SELECT pf.id, pf.name, pf.type, pf.required
 FROM pt_feature ptf JOIN pattern_feature pf ON ptf.fid = pf.id
 WHERE ptf.ptid = (SELECT ptid FROM pattern WHERE id = ?)";
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, $e->getMessage(), ' ', $e->getCode();
    exit();
  }
  try {
    $sth->execute([$id]);
  } catch(PDOException $e) {
    echo __FILE__, ' ', __LINE__, ' ', $e->getMessage(), ' ', $e->getCode();
    exit();
  }
  $features = [];
  while($feature = $sth->fetch())
    $features[$feature['name']] = $feature;
    
  # Fetch all the feature values from their per-feature tables.

  foreach($features as $feature) {
    $query = "SELECT * FROM pf_{$feature['name']} WHERE pid = ?";
    try {
      $sth = $pdo->prepare($query);
    } catch(PDOException $e) {
      echo __FILE__, ':', __LINE__, $e->getMessage(), ' ', $e->getCode();
      exit();
    }
    try {
      $sth->execute([$id]);
    } catch(PDOException $e) {
      echo __FILE__, ' ', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
      exit();
    }
    if($v = $sth->fetch()) {

      // found a feature value
      
      if(array_key_exists('value', $v))
        $features[$feature['name']]['value'] = $v['value'];
      $features[$feature['name']]['language'] = $v['language'];
      $features[$feature['name']]['required'] = $feature['required'];
      $features[$feature['name']]['type'] = $feature['type'];
      if($feature['type'] == 'image') {

        // image features have alttext, filename, and hash attributes
	
        $features[$feature['name']]['alttext'] = $v['alttext'];
        $features[$feature['name']]['filename'] = $v['filename'];
        $features[$feature['name']]['hash'] = $v['hash'];
      }
    }
  } // end loop on features
  
  $pattern['features'] = $features;
  return $pattern;
  
} /* end GetPattern() */


/* InsertPattern()
 *
 *  Insert a pattern record and return it as an associative array.
 */

function InsertPattern($notes, $template_id) {
  global $pdo;

  $query = 'INSERT INTO pattern (ptid, notes) VALUES(?, ?)';
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute([$template_id, $notes]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  return GetPattern($pdo->lastInsertId());

} /* end InsertPattern() */


/* GetTemplates()
 *
 *  Return the selected pattern_template records with an added 'pcount'
 *  field - count of associated patterns - and 'fcount' - count of
 *  associated features.
 *
 *  I don't know how to do this in a single query - do you?
 */

function GetTemplates($which = null) {
  global $pdo;

  if(isset($which)) {
    $q = '';
    $u = [];
    foreach($which as $column => $value) {
      if(strlen($q))
        $q .= ' AND ';
      $q .= " $column = ?";
      $u[] = $value;
    }
    $q = "WHERE $q";
  } else {
    $q = '';
    $u = [];
  }
  $query = "SELECT *, 0 AS pcount, 0 AS fcount FROM pattern_template $q";
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute($u);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', $e->getCode();
    exit();
  }
  $templates = [];
  while($template = $sth->fetch())
    $templates[$template['id']] = $template;

  # Update the 'pcount' fields.
  
  $query = 'SELECT pt.id AS tid, count(*) AS pcount
 FROM pattern_template pt
  JOIN pattern p ON pt.id = p.ptid
 GROUP BY pt.id';

  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, $e->getMessage(), ' ', $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute($u);
  } catch(PDOException $e) {
    echo __FILE__, ':',  __LINE__, ' ', $e->getMessage(), ' ', $e->getCode();
    exit();
  }
  while($count = $sth->fetch())
    $templates[$count['tid']]['pcount'] = $count['pcount'];

  # Update the 'fcount' fields.
  
  $query = 'SELECT pt.id AS tid, count(*) AS fcount
 FROM pattern_template pt JOIN pt_feature ptf ON pt.id = ptf.ptid
 GROUP BY pt.id';

  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, $e->getMessage(), ' ', $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute($u);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', $e->getCode();
    exit();
  }
  while($count = $sth->fetch())
    $templates[$count['tid']]['fcount'] = $count['fcount'];
    
  return $templates;

} /* end GetTemplates() */


/* GetTemplate()
 *
 *  Return the pattern_template record, with features, for the
 *  argument 'id'.
 */

function GetTemplate($id) {
  global $pdo;

  $query = 'SELECT * FROM pattern_template WHERE id = ?';
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute([$id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  $template['features'] = [];
  if($template = $sth->fetch())
    $template['features'] = GetPTFeatures($id);

  return $template ? $template : null;

} /* end GetTemplate() */


/* UpdateTemplate()
 *
 *  Apply template update.
 */

function UpdateTemplate($update) {
  global $pdo;

  $q = '';
  foreach($update as $k => $v) {
    if($k == 'id')
      continue;
    if(strlen($q))
      $q .= ', ';
    $q .= "$k = :$k";
  }
  $sql = "UPDATE pattern_template SET $q WHERE id = :id";
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute($update);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
} /* end UpdateTemplate() */


/* DeleteTemplate()
 *
 *  Delete a pattern_template along with associated data: pattern and
 *  pt_feature records.
 */

function DeleteTemplate($template_id) {
  global $pdo;

  # delete pt_feature records ("on delete cascade" should have been used...)
  try {
    $sth = $pdo->prepare('DELETE FROM pt_feature WHERE ptid = ?');
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute([$template_id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  # delete pattern records ("on delete cascade" should have been used...)
  try {
    $sth = $pdo->prepare('DELETE FROM pattern WHERE ptid = ?');
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute([$template_id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  # finally, delete the pattern_template
  try {
    $sth = $pdo->prepare('DELETE FROM pattern_template WHERE id = ?');
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute([$template_id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  
} /* end DeleteTemplate() */


/* GetPTFeatures()
 *
 *  Get pattern_feature records for a pattern_template. Those are returned
 *  in an associative array keyed on pattern_feature.name with all the fields
 *  from pattern_feature.
 *
 */

function GetPTFeatures($template_id) {
  global $pdo;

  $sql = 'SELECT pf.* FROM pt_feature ptf
  JOIN pattern_feature pf ON ptf.fid = pf.id
  JOIN pattern_template pt ON pt.id = ptf.ptid
 WHERE pt.id = ?';

 try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute([$template_id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  $features = [];
  while($feature = $sth->fetch())
    $features[$feature['name']] = $feature;
  return $features;

} /* end GetPTFeatures() */


/* GetPTFcount()
 *
 *  Report on the number of feature values that exist for this feature
 *  in the context of this template.
 */

function GetPTFcount($template_id, $feature) {
  global $pdo;

  $tblname = "pf_{$feature['name']}";
  $sth = $pdo->prepare("SELECT count(*) AS count
 FROM $tblname pf
  JOIN pattern p ON pf.pid = p.id
 WHERE ptid = ?");
  $sth->execute([$template_id]);
  $count = $sth->fetchColumn();
  return $count;

} /* end GetPTFcount() */


/* GetFeatures()
 *
 *  Fetch pattern_feature records.
 */

function GetFeatures($which = null) {
  global $pdo;

  $sql = 'SELECT * FROM pattern_feature';

  if(isset($which)) {
    if(is_array($which)) {
      $q = '';
      $u = [];
      foreach($which as $column => $value) {
        if(strlen($q))
	  $q .= ' AND ';
        $q .= " $column = ?";
	$u[] = $value;
      }
      $q = "WHERE $q";
    }
  } else {
    $q = '';
    $u = [];
  }
  $query = "SELECT * FROM pattern_feature pf $q";
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute($u);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  $features = $sth->fetchall();
  return count($features) ? $features : null;

} /* end GetFeatures() */


/* GetFeature()
 *
 *  Get a single pattern_feature specified by pattern_feature.id.
 */

function GetFeature($id) {
  $features = GetFeatures(['id' => $id]);
  return isset($features) ? $features[0] : null;
  
} /* end GetFeature() */


/* InsertFeature()
 *
 *  Insert a pattern_feature value and create a table for it.
 */

function InsertFeature($params) {
  global $pdo;

  # First, create the pattern_feature record.
  
  $params['name'] = trim($params['name']);
  if(!strlen($params['name']))
    Error('Name of feature may not be empty.');

  /* $params['name'] must be a valid MySQL identifier */

  CheckIdentifier($params['name']);

  if(GetFeatures(['name' => $params['name']]))
    Error("There is already a feature with name \"{$params['name']}\" and there cannot be two.");

  $sql = 'INSERT INTO pattern_feature(name, type, notes) VALUES(?,?,?)';
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute([$params['name'], $params['alias'], $params['notes']]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  $feature = GetFeature($pdo->lastInsertId());

  # Create a table to hold the values.

  if($params['alias'] == 'image') {
    $sql = "CREATE TABLE pf_{$params['name']} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pid INT UNSIGNED NOT NULL,
  pfid INT UNSIGNED NOT NULL,
  filename VARCHAR(255),
  alttext VARCHAR(1023) NOT NULL,
  hash CHAR(40) NOT NULL,
  language CHAR(2) NOT NULL DEFAULT 'en',
  created TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  modified TIMESTAMP NOT NULL DEFAULT current_timestamp()
   ON UPDATE current_timestamp(),
  CONSTRAINT FOREIGN KEY(language) REFERENCES language(code),
  CONSTRAINT FOREIGN KEY(pid) REFERENCES pattern(id),
  CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature(id)
)";
  } else {
    $sql = "CREATE TABLE pf_{$params['name']} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  pfid INT UNSIGNED NOT NULL,
  language CHAR(2) DEFAULT 'en',
  value {$params['type']} NOT NULL,
  PRIMARY KEY (id, language),
  CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern(id),
  CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature(id)
)";
  }
  try {
    $pdo->exec($sql);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  return $feature;
 
} /* end InsertFeature() */


/* InsertTemplateFeature()
 *
 *  Insert a pt_feature record and return it as an associative array.
 */

function InsertTemplateFeature($template_id, $feature_id) {
  global $pdo;

  if($feature_id < 1)
    Error('No feature was selected');
  if($template_id < 1)
    Error('No template was selected');

  try {
    $sth = $pdo->prepare('INSERT INTO pt_feature (ptid, fid) VALUES(?, ?)');
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute([$template_id, $feature_id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  return GetTemplateFeature($pdo->lastInsertId());
  
} /* end InsertTemplateFeature() */


/* GetTemplateFeature()
 *
 *  Return a pt_feature record, augmented with feature and template names,
 *  selected by id.
 */

function GetTemplateFeature($id) {
  global $pdo;

  try {
    $sth = $pdo->prepare('SELECT ptf.*, pt.name AS tname, pf.name AS fname
 FROM pt_feature ptf
  JOIN pattern_template pt ON ptf.ptid = pt.id
  JOIN pattern_feature pf ON ptf.fid = pf.id
 WHERE ptf.id = ?');
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, $e->getMessage(), ' ', $e->getCode();
    exit();
  }
  try {
    $sth->execute([$id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, $e->getMessage(), ' ', $e->getCode();
    exit();
  }
  $pt = $sth->fetch();

  if(!$pt)
    return false;
  return $pt;
  
} /* end GetTemplateFeature() */


/* DeleteTemplateFeature()
 *
 *  Remove a pt_feature record as well as any associated values.
 */

function DeleteTemplateFeature($template_id, $feature_id) {
  global $pdo;
  
  $feature = GetFeature($feature_id);
  if(! isset($feature))
    Error("Feature $feature_id not found.");
  $tblname = "pf_{$feature['name']}";
  
  $pdo->beginTransaction();

  # Remove any values from the associated feature values table that
  # are associated with this template.

  try {
    $sth = $pdo->prepare("DELETE FROM $tblname WHERE id IN
 (SELECT v.id FROM $tblname v JOIN pattern p ON v.pid = p.id WHERE ptid = ?)");
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute([$template_id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }

  # Remove the pt_feature record for this template and feature.
  
  try {
    $sth = $pdo->prepare('DELETE FROM pt_feature WHERE fid = ? AND ptid = ?');
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute([$feature_id, $template_id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  $pdo->commit();

} /* end DeleteTemplateFeature() */


/* UpdateFeature()
 *
 *  Apply feature update.
 */

function UpdateFeature($update) {
  global $pdo;

  /* $update['name'] must be a valid MySQL identifier */

  if(isset($update['name']))
    CheckIdentifier($update['name']);

  iF(array_key_exists('type', $update))
    unset($update['type']);
  iF(array_key_exists('alias', $update)) {
    $update['type'] = $update['alias'];
    unset($update['alias']);
  }
  $q = '';
  foreach($update as $k => $v) {
    if($k == 'id')
      continue;
    if(strlen($q))
      $q .= ', ';
    $q .= "$k = :$k";
  }
  $sql = "UPDATE pattern_feature SET $q WHERE id = :id";
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute($update);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }

} /* end UpdateFeature() */


/* DeleteFeature()
 *
 *  Delete this pattern_feature record and pt_feature records that refer to
 *  it and drop the table that contains values for it.
 */

function DeleteFeature($feature_id) {
  global $pdo;
  
  $feature = GetFeature($feature_id);
  if(! isset($feature))
    Error("Feature $feature_id not found.");
  $tblname = "pf_{$feature['name']}";
  
  $pdo->beginTransaction();
  try {
    $sth = $pdo->prepare('DELETE FROM pt_feature WHERE fid = ?');
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute([$feature_id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth = $pdo->prepare('DELETE FROM pattern_feature WHERE id = ?');
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute([$feature_id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  $pdo->exec("DROP TABLE $tblname");
  $pdo->commit();

} /* end DeleteFeature() */


/* InsertTemplate()
 *
 *  Insert a pattern_template.
 *
 *  Besides creating the template, we add pt_feature records for all
 *  pattern_features that are marked as required.
 */

function InsertTemplate($params) {
  global $pdo;

  $params['name'] = trim($params['name']);
  if(!strlen($params['name']))
    Error('Name of template may not be empty.');
  if(GetTemplate(['name' => $params['name']]))
    Error("There is already a template with name \"{$params['name']}\" and there cannot be two.");

  $sql = 'INSERT INTO pattern_template(name, notes) VALUES(:name, :notes)';
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $rv = $sth->execute($params);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  $template = GetTemplate($template_id = $pdo->lastInsertId());
  $rfeatures = GetFeatures(['required' => 1]);
  if(count($rfeatures))
    foreach($rfeatures as $rfeature)
      $template['features'][$rfeature['name']] =
        InsertTemplateFeature($template_id, $rfeature['id']);
  return $template;
  
} /* end InsertTemplate() */


/* UpdatePattern()
 *
 *  Update a pattern. All we have is a 'notes' field. (Feature value updates
 *  are handled elsewhere.)
 */

function UpdatePattern($pid, $notes) {
  global $pdo;

  try {
    $sth = $pdo->prepare('UPDATE pattern SET notes = ? WHERE id = ?');
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute([$notes, $pid]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  return true;
  
} /* end UpdatePattern() */


/* UpdatePatternFeatures()
 *
 *  Update features for this pattern specified by pattern.id.
 */

function UpdatePatternFeatures($pid, $updates, $inserts, $deletes) {

  if(count($updates))
    foreach($updates as $update) {
      $update['pid'] = $pid;
      UpdateFeatureValue($update);
    }
  if(count($inserts))
    foreach($inserts as $insert) {
      $insert['pid'] = $pid;
      InsertFeatureValue($insert);
    }
  if(count($deletes))
    foreach($deletes as $delete) {
      $delete['pid'] = $pid;
      DeleteFeatureValue($delete);
    }
  
} /* end UpdatePatternFeatures() */


/* DeleteFeatureValue()
 *
 *  Delete a feature value.
 */

function DeleteFeatureValue($fvalue) {
  global $pdo;
  
  $name = $fvalue['name'];
  $tablename = "pf_$name";

  try {
    $sth = $pdo->prepare("DELETE FROM $tablename WHERE pid = ? AND pfid = ?");
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute([$fvalue['pid'], $fvalue['id']]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  if(! $sth->rowCount())
    Alert('No row deleted!');

  if($fvalue['type'] == 'image') {

    # Images are a special case, as there is an associated file in the
    # file system. Furthermore, that file may be referenced by other feature
    # values. Delete iff this is the sole reference.

    if(!IsImageUsed($fvalue['hash']))
      DeleteImage($fvalue['hash']);
   }

} /* end DeleteFeatureValue() */


/* UpdateFeatureValue()
 *
 *  Update the value of a pattern feature for this pattern.
 *
 *  The argument is an associative array with pattern.id as the value of
 *  'pid', the feature name as the value of 'featurename', and the other
 *  fields values for the named feature attributes.
 */
 
function UpdateFeatureValue($update) {
  global $pdo;

  $assignments = '';
  $vals = [];
  foreach($update as $k => $v) {
    if($k == 'pid')
      $pid = $v;
    else {
      $assignments .= strlen($assignments) ? ',' : '';
      if($k == 'featurename')
        continue;
      elseif($k == 'name')
        $field = 'filename';
      else
        $field = $k;
      $assignments .= "$field = ?";
      $vals[] = $v;
    }
  }
  $vals[] = $pid;
  $query = "UPDATE pf_{$update['featurename']} SET $assignments WHERE pid = ?";

  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode(), "<br>$query";
    exit();
  }
  try {
    $sth->execute($vals);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode(), "<br>$query";
    exit();
  }
    
} /* end UpdateFeatureValue() */


/* InsertFeatureValue()
 *
 *  Insert a feature value. The argument is an associative array:
 *
 *       pid  pattern.id
 *     fname  name of feature
 *     value  value of feature (if not image)
 *      hash  hash of upload (if image)
 *   alttext  alternative text (if image)
 *      name  source filename (if image)
 *
 */

function InsertFeatureValue($fv) {
  global $pdo;

  # we need the id of the pattern_feature row corresponding to this feature.

  $pfs = GetFeatures(['name' => $fv['fname']]);
  if(!isset($pfs) || !count($pfs))
    Error("No feature named <code>{$fv['fname']}</code> was found");
  $pf = $pfs[0];
  $pfid = $pf['id'];

  $tblname = "pf_{$fv['fname']}";

  if($pf['type'] == 'image') {

    /* create a feature value record with the alttext, mime type, filename,
     * and hash */

    $query = "INSERT INTO $tblname (pid, pfid, filename, alttext, hash)
VALUES(?,?,?,?,?)";
    try {
      $sth = $pdo->prepare($query);
    } catch(PDOException $e) {
      echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
      exit();
    }
    try {
      $sth->execute([
	$fv['pid'],
	$pfid,
	$fv['name'],
	$fv['alttext'],
	$fv['hash']
      ]);
    } catch(PDOException $e) {
      echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode(), '<br>', $query;
      exit();
    }

  } else {

    // not an 'image' feature type
    
    $value = trim($fv['value']);
    if($pf['required'] && !strlen($value))
      Error("{$fv['fname']} is a required field");

    $query = "INSERT INTO $tblname (pid, pfid, value) VALUES(?, ?, ?)";
    try {
      $sth = $pdo->prepare($query);
    } catch(PDOException $e) {
      echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
      exit();
    }
    try {
      $sth->execute([$fv['pid'], $pfid, $fv['value']]);
    } catch(PDOException $e) {
      echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
      exit();
    }
  }
  return $pdo->lastInsertId();
  
} /* end InsertFeatureValue() */


/* FeatureStats()
 *
 *  Given a pattern_feature.id, return the pattern_templates that refer
 *  to it and the number of values per-template. We return an array
 *  of associative arrays, keyed on pattern_template.id, with 'name',
 *  'id', and 'count' fields.
 */

function FeatureStats($feature_id) {
  global $pdo;

  # Get the pattern_feature record; we need the name.

  $feature = GetFeature($feature_id);
  if(!isset($feature))
    Error("No such feature with id = $feature_id");
  $tblname = 'pf_' . $feature['name'];

  # Get the names and ids of all the templates that use this feature.

  try {
    $sth = $pdo->prepare('SELECT pt.name, pt.id FROM pt_feature ptf
   JOIN pattern_template pt ON pt.id = ptid
  WHERE fid = ?');
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute([$feature_id]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  $templates = [];
  while($template = $sth->fetch()) {
    $templates[$template['id']] = $template;
    $templates[$template['id']]['count'] = 0;
  }

  # If this feature isn't used in any templates there is no point
  # computing the per-template counts.

  if(!count($templates))
    return null;

  # Count the per-template number of values.

  try {
    $sth = $pdo->prepare("SELECT count(*) AS count, pt.id
 FROM {$tblname} pft
  JOIN pattern p ON pft.pid = p.id
  JOIN pattern_template pt ON p.ptid = pt.id
 GROUP BY pt.id");
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  try {
    $sth->execute([]);
  } catch(PDOException $e) {
    echo __FILE__, ':', __LINE__, ' ', $e->getMessage(), ' ', (int) $e->getCode();
    exit();
  }
  while($template = $sth->fetch())
    $templates[$template['id']]['count'] = $template['count'];

  return $templates;
  
} /* end FeatureStats() */


/* Error
 *
 *  Display an error message with a "continue" link.
 */
 
function Error($msg) {
  print "<p class=\"error\">$msg</p>

<p><a href=\"{$_SERVER['SCRIPT_NAME']}\">Continue</a>.</p>
";
  exit();
  
} /* end Error() */


/* Alert()
 *
 *  Bold informative message.
 */

function Alert($alert) {
  print "<p class=\"alert\">$alert</p>\n";

} /* end Alert() */


/* CheckIdentifier()
 *
 *  Check that this identifier can be used in a MySQL table name.
 */

function CheckIdentifier($identifier) {
  if(strlen($identifier) > 32) {
    Error("We accept names up to 32 characters long; <code>$identifier</code> is " .
     strlen($identifier) . '.');
  }
  if(!preg_match('/^[a-zA-Z0-9_-]+$/', $identifier))
    Error("We can't use the name <code>$identifier</code>. Use alphanumeric characters, <code>_</code>, or <code>-</code> only.");
  if(substr($identifier, 0, 2) == 'f-')
    Error('Leading <code>f-</code> on names is reserved.');
  return true;
  
} /* end CheckIdentifier() */


/* CheckFile()
 *
 *  Validate a file upload. The argument is from $_FILES[] with an added
 *  'alttext' field. If all is well, we add a 'hash' field and return it,
 *  else false.
 */

function CheckFile($file) {
  if($file['error']) {

    // something went wrong with the upload
      
    if(false)
      Alert("Upload failed for <code>{$file['name']}</code>: " .
        UPERROR[$file['error']] . "\n");
    return false;
  }
  if(!strlen($file['alttext'])) {
    Alert(" {$file['name']}: alternative text is required for images");
    return false;
  }

  if($file['size'] > MAXIMAGE) {

    // file size is too large, which implies malevolent behavior
      
    Alert("File size of <code>{$file['size']}</code> exceeeds maximum supported size of " . MAXIMAGE . " for <code>{$file['fname']}</code>\n");
    return false;
  }

  # use finfo() to get the actual (rather than asserted) MIME type
    
  $finfo = finfo_open(FILEINFO_MIME_TYPE); // fails mysteriously
  $mime = finfo_file($finfo, $file['tmp_name']);
  if(!in_array($mime, GIWHITE)) {

    // unsupported file type

    Alert("MIME type <code>$mime</code> of uploaded file <code>{$file['fname']}</code> isn't supported as an image");
    return false;
  }
    
  // build a filename based on content (and build the dirtree as necessary)
    
  $file['hash'] = hash_file('sha1', $file['tmp_name']);
  $spath = ImagePath($file['hash']);

  // if this file has been uploaded before, silently move on
    
  if(!file_exists($spath)) {
    if(!move_uploaded_file($file['tmp_name'], $spath)) {
      Alert("Could not move uploaded file for {$file['fname']} to <code>$spath</code>\n");
      return false;
    }
  }
  return $file;
    
} /* end CheckFile() */


/* IsImageUsed()
 *
 *  True if an image is referenced by any feature value.
 */

function IsImageUsed($hash) {
  global $pdo;

  # Get the feature names that are of type "image."

  $sth = $pdo->prepare('SELECT name FROM pattern_feature WHERE type = "image"');
  $sth->execute();
  $names = $sth->fetchall();

  # Search those tables for any reference to this image.

  foreach ($names as $name) {
    $tblname = "pf_{$name['name']}";
    $query = "SELECT count(*) AS count FROM $tblname WHERE hash = ?";
    $sth = $pdo->prepare($query);
    $sth->execute([$hash]);
    $count = $sth->fetchcolumn();
    if($count)
      return true;
  }
  return false;
  
} /* end IsImageUsed() */


/* DeleteImage()
 *
 *  Delete this image, specified by hash. Return false if we didn't find it
 *  or failed to unlink it.
 */
 
function DeleteImage($hash) {
  if(! file_exists($imagePath = ImagePath($hash)))
    return false;
  else
    return unlink($imagePath);

} /* end DeleteImage() */


/* ImagePath()
 *
 *  Given a file hash, return the file system path, creating subdirs as needed.
 */

function ImagePath($hash) {
  $spath = IMAGEROOT . '/';
  for($i = 0; $i < IDEPTH; $i++) {
    $spath .= substr($hash, $i, 1) . '/';
    if(!is_dir($spath))
      if(!mkdir($spath, 0775))
        Error('Failed to create directory tree for file upload');
  }
  $spath .= $hash;
  return $spath;
  
} /* end ImagePath() */
