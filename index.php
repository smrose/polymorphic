<?php
/*
 * NAME
 *
 *  index.php
 *
 * CONCEPT
 *
 *  Top-level index for polymorphic pattern manipulation project.
 *
 * FUNCTIONS
 *
 *  SelectTemplate      present a form for selecting a template
 *  SelectFeature       present a form for selecting a feature
 *  PatternForm         present a form for adding or editing a pattern
 *  FeatureForm         present a form for adding or editing a feature
 *  AbsorbFeatureEdit   accept pattern_feature edits
 *  AddPattern          add a pattern
 *  TemplateForm        present a form for adding or editing a template
 *  SelectPattern       select a pattern for editing
 *  ManageFeatures      manage features associated with a template
 *  AbsorbPatternUpdate absorb pattern update
 *  AbsorbNewPattern    absorb a new pattern
 *
 * NOTES
 *
 *  This toy application is for exploring the design of "polymorphic"
 *  patterns: patterns that lack a rigid definition.  It's based upon
 *  the concept of "pattern features," which have a name and data type
 *  and are associated with patterns via the pattern_template and
 *  pt_feature tables.
 *
 *  A pattern_feature:
 *
 *   CREATE TABLE `pattern_feature` (
 *    id int unsigned NOT NULL AUTO_INCREMENT,
 *    name varchar(255) NOT NULL,
 *    type enum('integer','tinytext','text','longtext') NOT NULL,
 *    notes varchar(255),
 *    PRIMARY KEY (id),
 *    UNIQUE KEY `name` (`name`,`type`)
 *   );
 *
 *  A named pattern_template record defines a set of features:
 *
 *   CREATE TABLE pattern_template (
 *    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 *    name VARCHAR(255) NOT NULL,
 *    notes VARCHAR(255),
 *   );
 *
 *  Each feature has an associated table that stores values. For example,
 *  a "title" feature, which has type "tinytext":
 *
 *   CREATE TABLE pf_title (
 *    id int unsigned NOT NULL AUTO_INCREMENT,
 *    pid int(10) unsigned NOT NULL,
 *    pfid int(10) unsigned NOT NULL,
 *    language char(2) NOT NULL DEFAULT 'en',
 *    value tinytext NOT NULL,
 *    PRIMARY KEY (id,language),
 *    KEY (pfid),
 *    CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern (id),
 *    CONSTRAINT FOREIGN KEY (pfid) REFERENCES pattern_feature (id)
 *   ) ;
 *
 *  Here is the definition of a pattern, each of which is associated
 *  with a pattern_template:
 *
 *   CREATE TABLE pattern (
 *    id int unsigned NOT NULL AUTO_INCREMENT,
 *    ptid int unsigned NOT NULL,
 *    PRIMARY KEY (id),
 *    KEY ptid (ptid),
 *    CONSTRAINT FOREIGN KEY (ptid) REFERENCES pattern_template (id)
 *   )
 *
 *  And here is the pt_feature table that associated features with
 *  pattern_templates:
 *
 *   CREATE TABLE `pt_feature` (
 *    ptid int unsigned NOT NULL,
 *    fid int(10) unsigned NOT NULL,
 *    KEY ptid (ptid),
 *    KEY fid (fid),
 *    CONSTRAINT FOREIGN KEY (ptid) REFERENCES pattern_template (id),
 *    CONSTRAINT FOREIGN KEY (fid) REFERENCES pattern_feature (id)
 *   );
 *
 *  For example, we have a pattern_template named "Liberating Voices"
 *  with id 1. To associate a pattern_feature with the name "title" -
 *  id 2 - with each pattern using that template we have a row in
 *  pt_feature with fid = 2 and ptid = 1. For the pattern with the
 *  title "The Good Life" - pattern.id = 3 - there is a row in the
 *  pf_title table with pfid = 2 and pid = 3.
 *  
 */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require 'pps.php';

DataStoreConnect();
$SuppressMain = false;

const ANOTHER = 'Accept and enter another';


/* SelectTemplate()
 *
 *  Present a form for selecting a template.
 *
 *  The $context argument, an array, tells us how to label the page
 *  (label), what hidden field to include (context = action), what
 *  submit buttons to include (submit[]), how to label the 0th menu
 *  item (zlabel, optional), and what leading instructions to include
 *  (notes, optional).
 */

function SelectTemplate($context) {
  $templates = GetTemplates();
  $zlabel = (isset($context['zlabel']))
    ? $context['zlabel'] : 'Select a template';
  $seltemplate = "<select name=\"template_id\">
 <option value=\"0\">$zlabel</option>
";
  
  print "<h2>{$context['label']}</h2>\n";
  
  foreach($templates as $template) {

    # If we are selecting a template for a pattern add, only offer templates
    # with associated features.

    if($context['context'] == 'pattern' && $context['action'] == 'add')
      $disabled = ($template['fcount']) ? '' : ' disabled="disabled"';

    # If we are selecting a template for a pattern edit, only offer templates
    # with associated patterns.
    
    elseif($context['context'] == 'pattern' && $context['action'] == 'edit')
      $disabled = ($template['pcount']) ? '' : ' disabled="disabled"';
    else
      $disabled = '';
    $seltemplate .= " <option value=\"{$template['id']}\"$disabled>
 {$template['name']}
</option>
";
  }
  $seltemplate .= "</select>\n";

  $submit = '';  
  foreach($context['submit'] as $label) {
    $submit .= "<input type=\"submit\" name=\"submit\" value=\"$label\">\n";
  }
  $submit .= "<input type=\"submit\" name=\"submit\" value=\"Cancel\">\n";

  if(isset($context['notes']))
    print $context['notes'];

  print "<form action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\">
<input type=\"hidden\" name=\"{$context['context']}\" value=\"{$context['action']}\">

<div class=\"fname\">Select a template:</div>
<div>$seltemplate</div>
<div class=\"fsub\">
$submit
</div>
</form>
";

} /* end SelectTemplate() */


/* SelectFeature()
 *
 *  Select a pattern_feature.
 */

function SelectFeature() {
  $features = GetFeatures();
  $menu = '<select name="feature_id">
 <option value="0">Select feature</option>
';
  foreach($features as $feature) {
    $menu .= " <option value=\"{$feature['id']}\" title=\"{$feature['notes']}\">{$feature['name']} - {$feature['type']}</option>\n";
  }
  $menu .= '</select>
';
  print "<h2>Edit a Feature</h2>
<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"featureform\">
<input type=\"hidden\" name=\"feature\" value=\"edit\">
<div>$menu<div>
<div>
 <input type=\"submit\" name=\"submit\" value=\"Select\">
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
<div>
</form>
";

} /* end SelectFeature() */


/* PatternForm()
 *
 *  Present a form for adding or editing a pattern. If editing, we have
 *  the id of the pattern; if adding, the id of the template.
 */

function PatternForm($action, $id) {
  if($action == 'edit') {
    $pattern = GetPattern($id);
    $features = $pattern['features'];
    $action = 'absorb_edit';
    $ptitle = isset($features['title']) ? $features['title']['value'] : '(no title)';
    $title = "Editing Pattern <i>$ptitle</i>";
    $context = "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
    $nvalue = $pattern['notes'];
    $note = "<p class=\"alert\">Editing a pattern with id <code>$id</code></p>";
  } else {
    $title = 'Adding Pattern';
    $template = GetTemplate($id);
    $features = $template['features'];
    $action = 'absorb_add';    
    $context = "<input type=\"hidden\" name=\"template_id\" value=\"$id\">\n";
    $nvalue = '';
    $note = "<p class=\"alert\">Adding a pattern with template
<code>{$template['name']}</code></p>";
  }
  print "<h2>$ptitle</h2>

$note

<form action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"featureform\" method=\"POST\">
<input type=\"hidden\" name=\"pattern\" value=\"$action\">
$context
";
  foreach($features as $feature) {
    $fname = "f-{$feature['name']}";
    if($feature['type'] == 'integer') {
      if(isset($feature['value']))
        $value = " value=\"{$feature['value']}\"";
      else
        $value = '';
      $input = "<input name=\"$fname\" type=\"text\" size=\"5\"$value>\n";
    } elseif($feature['type'] == 'tinytext') {
      if(isset($feature['value']))
        $value = " value=\"{$feature['value']}\"";
      else
        $value = '';
      $input = "<input name=\"$fname\" type=\"text\" size=\"50\"$value>\n";
    } else {
      if(isset($feature['value']))
        $value = $feature['value'];
      else
        $value = '';
      $input = "<textarea name=\"$fname\" rows=\"3\" cols=\"80\">$value</textarea>\n";
    }
    print "<div class=\"fname\">{$feature['name']} (${feature['type']}):</div>
 <div>$input</div>
";
  } // end loop on features
  
  print "<div class=\"fname\">
 Notes:</div>
<div>
 <input type=\"text\" name=\"notes\" size=\"60\" value=\"$nvalue\">
</div>
<div class=\"fsub\">
  <input type=\"submit\" name=\"submit\" value=\"Submit\">
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>
</form>
";
  
} /* end PatternForm() */


/* FeatureForm()
 *
 *  Display a form for entering or editing a feature.
 */

function FeatureForm($id = null) {

  if(isset($id)) {

    # Editing an existing feature.
    
    $feature = GetFeature($id);
    if(!isset($feature))
      Error('System error: no feature with id <code>$id</code> exists.');
    $fname = " value=\"{$feature['name']}\"";
    $fnotes = $feature['notes'];
    $fid = "<input type=\"hidden\" name=\"feature_id\" value=\"{$feature['id']}\">\n";
    print "<h2>Edit Feature</h2>\n";
    $another = '<input type="submit" name="submit" value="Delete">
';
    if($stats = FeatureStats($id)) {
      $instr = "<p>This feature is used by the following templates:\n";
      foreach($stats as $stat) {
        $instr .= "<br><span style=\"margin-left: 2em\"><i>{$stat['name']}</i> - {$stat['count']} values</span>\n";
      }
    } else {
      $instr = "<p class=\"alert\">This feature isn't used in any templates yet.</p>\n";
    }
    
  } else {

    # Defining a new feature.

    $fname = $fnotes = $fid = '';
    print "<h2>Add a Feature</h2>\n";
    $another = '<input type="submit" name="submit" value="' . ANOTHER . "\">\n";
    $instr = "<p>Enter a name, data type, and optional notes for this new feature.</p>\n";
  }
  
  $typemenu = "<select name=\"type\">
 <option value=\"0\">Select a type</option>
";
  foreach(['integer', 'tinytext', 'text', 'longtext'] as $type) {
    $selected = (isset($feature) && $feature['type'] == $type)
      ? ' selected="selected"' : ''; 
    $typemenu .= " <option value=\"$type\"$selected>$type</option>\n";
  }
  $typemenu .= "</select>\n";
  
  print "$instr
<form action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"featureform\" method=\"POST\">
 <input type=\"hidden\" name=\"feature\" value=\"specify\">
$fid
 <div class=\"fname\">Feature name:</div>
 <div><input type=\"text\" name=\"name\"$fname></div>
 
 <div class=\"fname\">Type:</div>
 <div>$typemenu</div>

 <div class=\"fname\">Notes:</div>
 <div><textarea name=\"notes\" rows=\"3\" cols=\"80\">$fnotes</textarea></div>

 <div class=\"fsub\">
  <input type=\"submit\" name=\"submit\" value=\"Accept\">
  $another
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>
</form>
";

} /* end FeatureForm() */


/* AbsorbFeatureEdit()
 *
 *  Absorb the edit of a pattern_feature.
 */

function AbsorbFeatureEdit($id, $value) {
  $feature = GetFeature($id);
  if(!isset($feature))
    Error('No such feature');

  $update = [];
  foreach($value as $k => $v) {
    if($feature[$k] != $v)
      $update[$k] = $v;
  }
  if(count($update)) {
    $update['id'] = $id;
    UpdateFeature($update);
    print "<p class=\"alert\">Feature update accepted.</p>\n";
  } else
    print "<p class=\"alert\">No changes to feature.</p>\n";
  
} /* end AbsorbFeatureEdit(() */


/* AddPattern()
 *
 *  Add a pattern. To do it, the user first selects a template so we
 *  know what features to include.
 */

function AddPattern($template_id = null) {
  if(isset($template_id))
    PatternForm('add', $template_id);
  else
    SelectTemplate([
      'label' => 'Select Template',
      'context' => 'pattern',
      'action' => 'add',
      'submit' => ['Select'],
      'notes' => "<p class=\"alert\">Select the template that this pattern will use.
Templates with no associated features are disabled.</p>\n"
    ]);

} /* end AddPattern() */


/* TemplateForm()
 *
 *  Present a form for adding, editing, or deleting a template.
 */

function TemplateForm($id = null) {

  if(isset($id)) {

    # Editing a template.

    if(!$id)
      Error("You haven't selected a template.");
    $count = CountPatterns($id);
    print "<h2>Edit Template</h2>

<p class=\"alert\">This template is used by <code>$count</code> patterns.</p>
";
    $template = GetTemplate($id);
    if(! isset($template))
      Error('Template not found.');
    $idf = "<input type=\"hidden\" name=\"template_id\" value=\"$id\">\n";
    $name_value = " value=\"{$template['name']}\"";
    $notes_value = $template['notes'];
    $delete = '<input type="submit" name="submit" value="Delete">
';
  } else {

    # Adding a template.

    print "<h2>Add a Template</h2>\n";
    $idf = '';
    $name_value = $notes_value = '';
    $delete = '';
  }

  print "<form action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\">
 <input type=\"hidden\" name=\"template\" value=\"absorb_template\">
 $idf

 <div class=\"fname\">Template name:</div>
 <div><input type=\"text\" name=\"name\"$name_value\"></div>

 <div class=\"fname\">Notes:</div>
 <div>
   <textarea name=\"notes\" rows=\"3\" cols=\"80\">$notes_value</textarea>
 </div>

 <div class=\"fsub\">
  <input type=\"submit\" name=\"submit\" value=\"Accept\">
  $delete
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>
</form>
";
} /* end TemplateForm() */


/* SelectPattern()
 *
 *  Select a pattern for editing. First, specify a pattern_template.
 */

function SelectPattern($template_id = null) {

  if(isset($template_id)) {

    # template has been selected, offer a menu of patterns using it
    
    $patterns = GetPatterns(['ptid' => $template_id]);
    if(!count($patterns))
      Error('No patterns found for this tempate');
    $selpattern = '<select name="pattern_id">
 <option value="0">Select a pattern</option>
';
    foreach($patterns as $pattern) {
      $title = (isset($pattern['title']))
        ? $pattern['title'] : '(no title)';
      $selpattern .= " <option value=\"{$pattern['id']}\">$title - {$pattern['type']} (id {$pattern['id']})</option>\n";
    }
    $selpattern .= '</select>
';
    print "<h2>Select a Pattern</h2>

<form action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\">
<input type=\"hidden\" name=\"pattern\" value=\"edit\">

<div class=\"fname\">Select a pattern:</div>
<div>$selpattern</div>
<div class=\"fsub\">
 <input type=\"submit\" name=\"submit\" value=\"Edit\">
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";
  } else {

    /* Select a pattern_template. */

    SelectTemplate([
      'label' => 'Edit Pattern: Select a Pattern Template',
      'notes' => '<p class="alert">Every pattern is associated with a pattern
template. Filter the pattern selection menu to those of a particular template, or
 select from all patterns. Only templates with associated patterns can be
 selected.</p>
',
      'context' => 'pattern',
      'action' => 'edit',
      'submit' => ['Select']
    ]);
  }
  
} /* end SelectPattern() */


/* ManageFeatures()
 *
 *  Manage features associated with this template.
 */

function ManageFeatures($template_id) {
  $template = GetTemplate($template_id);
  if(!isset($template))
    Error('No such template.');
    
  $template['features'] = GetPTFeatures($template_id);
  $features = GetFeatures(['required' => 0]);
  $ufeatures = [];
  foreach($features as $feature) {
    if(array_key_exists($feature['id'], $template['features']))
      continue;
    $ufeatures[] = $feature;
  }
  if(count($ufeatures)) {
  
    # there is at least one feature not used by this template; offer to add
    
    $fmenu = '<select name="feature_id">
 <option value="0">Select a feature to add</option>
';
    foreach($ufeatures as $ufeature) {
      $fmenu .= " <option value=\"{$ufeature['id']}\">${ufeature['name']} - {$ufeature['type']}</option>\n";
    }
    $fmenu .= '</select>
';
  }
    
  print "<h2>Managing Features for template <code>{$template['name']}</code></h2>

<p>The forms on this page are used to add and remove existing pattern
features from an existing pattern template.</p>

<p>(To add a feature that hasn't yet been defined, first use the
<code>Add Feature</code> facility linked to the main page to define
it. To add features to a pattern template that doesn't yet exist,
first use the <code>Add Template</code> facility linked to the main
page to add that.)</p>

<h3>Add Features</h3>

<p>To add a single feature, select it from the popup menu and press
<code>Submit</code>. To add a set of new features, press the <code>"
. ANOTHER . "</code> to return to this menu.</p>
";

  if(isset($fmenu)) {
    print "<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\" id=\"faform\">
<input type=\"hidden\" name=\"template\" value=\"edit\">
<input type=\"hidden\" name=\"template_id\" value=\"$template_id\">
<input type=\"hidden\" name=\"action\" value=\"addfeature\">
<div style=\"text-align: center\">$fmenu</div>
<div>
 <input type=\"submit\" name=\"submit\" value=\"Submit\">
 <input type=\"submit\" name=\"submit\" value=\"" . ANOTHER . "\">
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";
  } else {
    print "<p class=\"alert\">There are no features not already used by this template.</p>\n";
  }
  print "<h3>Remove Features</h3>

<p>To <i>remove</i> features assigned to this template, check the
boxes next to them and press <code>Submit</code>. <b>Any values
associated with this template and that feature will necessarily be
discarded</b>.</p>
";
  
  $optional = array_filter($template['features'],
                           function($e) { return !$e['required'];});

  if(count($optional)) {

    # this template has at least one optional feature; offer to remove each
    
    print "<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\" id=\"fform\">
<input type=\"hidden\" name=\"template_id\" value=\"$template_id\">
<input type=\"hidden\" name=\"template\" value=\"edit\">
<input type=\"hidden\" name=\"action\" value=\"rmfeatures\">
 <div class=\"fh\">Name</div>
 <div class=\"fh\">Data type</div>
 <div class=\"fh\">Existing values</div>
 <div class=\"fh\">Remove</div>
";
    foreach($template['features'] as $feature) {
      if($feature['required'])
        continue;
      $count = GetPTFcount($template_id, $feature);
      print " <div>
  {$feature['name']}</div>
  <div>{$feature['type']}</div>
  <div style=\"text-align: center\">{$count}</div>
  <div style=\"text-align: center\">
   <input type=\"checkbox\" name=\"{$feature['id']}\" value=\"1\">
  </div>
";
    }
    print " <div class=\"fsub4\" id=\"arow\">
  <input type=\"submit\" name=\"submit\" value=\"Submit\">
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>
</form>
";
  } else
    print "<p class=\"alert\">This template has no associated optional features.</p>\n";

} /* end ManageFeatures() */


/* AddFeature()
 *
 *  Add the selected feature to the selected template.
 */

function AddFeature($template_id, $feature_id) {
  InsertTemplateFeature($template_id, $feature_id);
  
} /* end AddFeature() */


/* RemoveFeatures()
 *
 *  Remove one or more associated features from a template.
 */

function RemoveFeatures($template_id, $features) {
  foreach($features as $feature)
    DeleteTemplateFeature($template_id, $feature);

} /* end RemoveFeatures() */


/* AbsorbPatternUpdate()
 *
 *  Implement pattern update.
 */

function AbsorbPatternUpdate() {
  $pattern_id = $_REQUEST['id'];
  $pattern = GetPattern($pattern_id);
  $features = $pattern['features'];
  $update = [];
  $insert = [];
  $delete = [];
  $fnames = [];

  # Loop on form fields, looking for feature value changes and additions.
  
  foreach($_REQUEST as $k => $v) {
    if(preg_match('/^f-(.+)$/', $k, $matches)) {
      $name = $matches[1];
      $fnames[$name] = 1;
      if(isset($features[$name]) && isset($features[$name]['value'])) {

        # pattern has a value for this feature
      
        if($features[$name]['value'] == $v)
          continue; # no change
        else
          $update[] = ['name' => $name, 'value' => $v];
      } else {

        # pattern does not have this feature

        $insert[] = ['name' => $name, 'value' =>$v];
      }
    }
  } # end loop on features in form

  # Loop on existing pattern features, looking for deletions.

  foreach($features as $feature) {
    if(!array_key_exists($feature['name'], $fnames))
      $delete[] = ['name' => $feature['name']];
  }
  UpdatePatternFeatures($pattern_id, $update, $insert, $delete);
  $did = (count($update) > 0) || (count($insert) > 0) || (count($delete) > 0);
  if($_REQUEST['notes'] != $pattern['notes']) {
    UpdatePattern($pattern_id, $_REQUEST['notes']);
    $did = true;
  }
  return $did;
  
} /* end AbsorbPatternUpdate() */


/* AbsorbNewPattern()
 *
 *  Implement pattern insert.
 */

function AbsorbNewPattern() {

  // collect the features specified in the form
  
  $features = [];
  foreach($_REQUEST as $k => $v) {
    if(preg_match('/^f-(.+)$/', $k, $matches)) {

      # as a hack, feature names in the form start with "f-"

      $name = $matches[1];
      $features[$name] = $v;
    }
  }
  $notes = $_REQUEST['notes'];
  $template_id = $_REQUEST['template_id'];
  $pid = InsertPattern($notes, $template_id);
  foreach($features as $fname => $fvalue) {
    InsertFeatureValue($pid, $fname, $fvalue);
  }
  return $pid;
  
} /* end AbsorbNewPattern() */


?>
<!doctype html>
<html lang="en">

<head>
 <title>Polymorphic Pattern Sphere</title>
 <link rel="stylesheet" href="local.css">
 <link rel="stylesheet" href="pps.css">
 <script src="pps.js"></script>
</head>

<body onload="init()">

<header>
<h1>Polymorphic Pattern Sphere</h1>
</header>

<div id="reiner">

<?php

if(DEBUG && count($_POST)) {
  print "<div id=\"ass\">Show POST parameters.</div>
<div id=\"posterior\">\n";
  foreach($_POST as $k => $v) {
    print "<div>$k</div>\n<div>$v</div>\n";
  }
  print "</div>\n";
}

if(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Cancel') {
  true;
} elseif(isset($_REQUEST['pattern'])) {
  $action = $_REQUEST['pattern'];

  # pattern actions
  
  if($action == 'edit') {

    # editing an existing pattern

    if(isset($_REQUEST['pattern_id'])) {

      # Editing a pattern.
      
      $pattern_id = $_REQUEST['pattern_id'];
      if(!$pattern_id)
        Error('You failed to select a pattern.');
      PatternForm('edit', $pattern_id);
      $SuppressMain = true;
    } elseif(isset($_REQUEST['template_id'])) {

      # Selecting a pattern to edit.
      
      $template_id = $_REQUEST['template_id'];
      if(!$template_id)
        Error('You failed to select a template.');
      SelectPattern($template_id);
      $SuppressMain = true;
    } else {
      SelectPattern();
      $SuppressMain = true;
    }
  } elseif($action == 'add') {
    $template_id = (isset($_REQUEST['template_id']))
      ? $_REQUEST['template_id'] : null;
    AddPattern($template_id);
    $SuppressMain = true;
  } elseif($action == 'absorb_edit') {
    if(AbsorbPatternUpdate())
      Alert('Updated pattern.');
    else
      Alert('No update.');
  } elseif($action == 'absorb_add') {
    $id = AbsorbNewPattern();
    Alert("Created new pattern with id $id");
  }

} elseif(isset($_REQUEST['feature'])) {

  # Deal with pattern_feature actions.

  if($_REQUEST['feature'] == 'edit') {

    if(isset($_REQUEST['feature_id'])) {
      $feature_id = $_REQUEST['feature_id'];
      if($feature_id) {
      
        # Present the form for editing this feature.
      
        FeatureForm($feature_id);
      } else
        Error('You failed to select a feature.');

    } else {

      # Present the form for selecting a feature.
      
      SelectFeature();
    }
    $SuppressMain = true;

  } elseif($_REQUEST['feature'] == 'add') {

    # Present the form used for specifying a new feature.

    FeatureForm();
    $SuppressMain = true;
    
  } elseif($_REQUEST['feature'] == 'specify') {

    $value = [
      'name' => $_REQUEST['name'],
      'type' => $_REQUEST['type'],
      'notes' => $_REQUEST['notes']
    ];

    if(isset($_REQUEST['feature_id'])) {
      $feature_id = $_REQUEST['feature_id'];
      
      if($_REQUEST['submit'] == 'Delete') {

        # Delete this feature.

        DeleteFeature($feature_id);
      } else {

        # Absorb feature edits.

        AbsorbFeatureEdit($_REQUEST['feature_id'], $value);
      }
    } else {
    
      # Insert a pattern_feature and create a table for values.

      $fid = InsertFeature($value);
      Alert("Feature inserted, id $fid.");
      if($_REQUEST['submit'] == ANOTHER) {
        FeatureForm();
        $SuppressMain = true;
      }
    }
  }
} elseif(isset($_REQUEST['template'])) {

  # template actions

  if($_REQUEST['template'] == 'edit') {

    if(isset($_REQUEST['template_id'])) {
      if($template_id = $_REQUEST['template_id']) {

        # we are acting on a specific template
        
        if($_REQUEST['submit'] == 'Edit metadata') {
          TemplateForm($template_id);
	  $SuppressMain = 1;
        } elseif(isset($_REQUEST['action']) &&
                 $_REQUEST['action'] == 'addfeature') {

          # we are adding a feature to this template

          AddFeature($template_id, $_REQUEST['feature_id']);
          if($_REQUEST['submit'] == ANOTHER) {
            ManageFeatures($template_id);
            $SuppressMain = true;
          }
        } elseif(isset($_REQUEST['action']) &&
                 $_REQUEST['action'] == 'rmfeatures') {

          # we are removing features from this template

          $fs = [];
          foreach($_REQUEST as $k => $v)
            if(preg_match('/^\d+$/', $k))
              $fs[] = $k;
          RemoveFeatures($template_id, $fs);
        } else {

          # we are managing (adding or removing) features of this template
          
          ManageFeatures($template_id);
          $SuppressMain = true;
	  }
       } else {
         Error('You failed to select a template.');
       }
    } else {

      # no template yet selected

      SelectTemplate([
        'label' => 'Edit Template',
        'context' => 'template',
        'action' => 'edit',
        'submit' => [
          'Edit metadata',
          'Manage features'
        ]
      ]);
      $SuppressMain = true;
    }
  } elseif($_REQUEST['template'] == 'add') {

    # Specify metadata for a new pattern_template.
    
    TemplateForm();
    $SuppressMain = true;

  } elseif($_REQUEST['template'] == 'absorb_template') {

    if(isset($_REQUEST['template_id'])) {
      $template_id = $_REQUEST['template_id'];

      if($_REQUEST['submit'] == 'Delete') {

          # We are deleting this template.
          
          DeleteTemplate($template_id);
          alert('Deleted template.');

      } else {
      
        # Absorbing a pattern_template edit.

        UpdateTemplate([
          'id' => $template_id,
          'name' => $_REQUEST['name'],
          'notes' => $_REQUEST['notes']
        ]);
        Alert("Template <code>{$_REQUEST['name']}</code> updated.");
      }
    } else {
    
      # Absorb a new pattern_template.
    
      $tid = InsertTemplate([
        'name' => $_REQUEST['name'],
        'notes' => $_REQUEST['notes']
      ]);
      Alert("Inserted template, id $tid.");
    }
  }
}
if(!$SuppressMain) {
?>

<h2>Pattern Features</h2>

<ul>
 <li><a href="?feature=add">Create a feature</a></li>
 <li><a href="?feature=edit">Edit a feature</a></li>
</ul>

<h2>Pattern Templates</h2>

<ul>
 <li><a href="?template=add">Create a template</a></li>
 <li><a href="?template=edit">Edit a template</a></li>
</ul>

<h2>Patterns</h2>

<ul>
 <li><a href="?pattern=add">Create a pattern</a></li>
 <li><a href="?pattern=edit">Edit a pattern</a></li>
</ul>

</div>
<?php
}
?>
</div>
<?=FOOT?>
</body>
</html>
