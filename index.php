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
 *  ViewPattern         manage selecting pattern for display
 *  DisplayPattern      render a pattern
 *  ManageFeatures      manage features associated with a template
 *  ManagePatterns      manage pattern language members
 *  EatMe               members come and go - right here
 *  AddFeature          add the selected feature to the selected template
 *  RemoveFeatures      remove 1 or more features from a template
 *  AbsorbPatternUpdate absorb pattern update
 *  AbsorbNewPattern    absorb a new pattern
 *  TemplateMenu        popup menu of pattern templates
 *  PLForm              present a form for adding/editing a pattern language
 *  SelectPL            select a pattern_language for editing
 *  SelectPV            select a pattern_view for editing
 *  PVForm              present a form for adding/editing a pattern_view
 *  AbsorbPV            absorb new or edited pattern_view
 *  ValidateView        warn about issues in a a pattern_view
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
 *    type enum('string', 'text', 'image', 'integer'),
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
 *  a "title" feature, which has type "varchar(255)", which the user sees
 *  as "string."
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
 *    CONSTRAINNT UNIQUE(ptid, fid),
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
const ADDPATTERNS = 'Accept and add patterns';


/* SelectTemplate()
 *
 *  Present a form for selecting a template.
 *
 *  The $context argument, an array, tells us how to label the page
 *  (label), what hidden field to include (context = action), what
 *  submit buttons to include (submit[], with 'label' and, optionally,
 *  'id' fields), how to label the 0th menu item (zlabel, optional),
 *  and what leading instructions to include (notes, optional).
 */

function SelectTemplate($context) {
  $templates = GetTemplates();
  $zlabel = (isset($context['zlabel']))
    ? $context['zlabel'] : 'Select a template';
  $seltemplate = "<select name=\"template_id\" id=\"template_id\">
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
  foreach($context['submit'] as $sub) {
    if(array_key_exists('id', $sub))
      $id = " id=\"{$sub['id']}\"";
    else
      $id = '';
    $submit .= "<input type=\"submit\" name=\"submit\" value=\"{$sub['label']}\"$id>\n";
  }
  $submit .= "<input type=\"submit\" name=\"submit\" value=\"Cancel\">\n";

  if(isset($context['notes']))
    print $context['notes'];

  print "<form action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\" id=\"selecttemplate\">
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
  print '<h2>Edit a Feature</h2>
';
Alert("Select the feature you wish to edit.");
print "<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"featureform\">
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
    $note = "<p class=\"alert\">Editing a pattern with id <code>$id</code></p>
<h2>$ptitle</h2>
";
    $submit = ' <input type="submit" name="submit" value="Accept" id="accept">
';
  } else {
    $title = 'Adding Pattern';
    $template = GetTemplate($id);
    $features = $template['features'];
    $action = 'absorb_add';    
    $context = "<input type=\"hidden\" name=\"template_id\" value=\"$id\">\n";
    $nvalue = '';
    $note = '<h2>Add a Pattern</h2>
';
    Alert("Adding a pattern with template <code>{$template['name']}</code>");
    $submit = ' <input type="submit" name="submit" value="Accept" id="accept">
 <input type="submit" name="submit" value="' . ANOTHER . '">
';
  }
print "$note

<p>Required features are displayed <span class=\"required\">like this</span>.</p>

<form enctype=\"multipart/form-data\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"featureform\" method=\"POST\">
 <div class=\"fh\">Feature name</div>
 <div class=\"fh\">Value</div>
<input type=\"hidden\" name=\"pattern\" value=\"$action\">
$context
";

  /* Loop on features for this template, offering input elements for each.
   * We name the input fields with a "f-" prefix on the feature name to
   * minimize the chance of a clash with another parameter.
   *
   * Features of most types have but a name and a value, but image types
   * have both an input type=file as well as a value for the alternate text.
   */

  $imgs = ''; # image preview elements

  foreach($features as $feature) {

    $ilink = '';

    if($feature['type'] == 'integer') {
    
      # use 5-char <input type="text">

      if(isset($feature['value'])) {
        $value = " value=\"{$feature['value']}\"";
      } else {
        $value = '';
      }
      $input = "<input name=\"f-{$feature['name']}\" type=\"text\" size=\"5\"$value>\n";

    } elseif($feature['type'] == 'string') {

      # use <input type="text"> for string type

      $remove = '';
      $value = '';
      if(isset($feature['value'])) {
        $value = " value=\"{$feature['value']}\"";
        if(!$feature['required'])
          $remove = "<input type=\"checkbox\" name=\"d-{$feature['name']}\" tite=\"remove\">";
      }
      $input = "<input name=\"f-{$feature['name']}\" type=\"text\" size=\"80\"$value>$remove\n";

    } elseif($feature['type'] == 'image') {

      # use <input type="file"> for images, <input type="text"> for alttext

      if(isset($feature['alttext']))
        $value = " value=\"{$feature['alttext']}\"";
      else
        $value = '';
      $input = "<input name=\"{$feature['name']}\" type=\"file\">";
      $input2 = "<input type=\"text\" name=\"f-{$feature['name']}\" size=\"50\"$value>";
      
      // link to view existing image
      
      if(isset($feature['hash'])) {

        /* There is an image to preview:
         *  compute the path
         *  display a link to unhide/hide the preview element
         *  add a <div class="imagebox"> containing the image preview and
         *   filename, hidden
         *  add a checkbox to delete the feature
         */

        $ipath = IMAGEROOT . '/';
        for($i = 0; $i < IDEPTH; $i++)
          $ipath .= substr($feature['hash'], $i, 1) . '/';
        $ipath .= $feature['hash'];
        $ilink = "<span class=\"ilink\" id=\"l-{$feature['hash']}\" title=\"{$feature['filename']}\">&nbsp;preview&nbsp;</span><span class=\"rbox\"><input type=\"checkbox\" name=\"d-{$feature['name']}\">remove</span>";
        $imgs .= "<div class=\"imagebox\" id=\"i-{$feature['hash']}\"><img src=\"$ipath\">
 <div id=\"imagemeta\">
  <div class=\"h\">File name:</div>
  <div><code>{$feature['filename']}</code></div>
 </div>
</div>
";
      }
    } elseif($feature['type'] == 'text') {

      # use a <textarea> for text type.
      
      if(isset($feature['value'])) {
        $value = $feature['value'];
        $remove = "<input type=\"checkbox\" name=\"d-{$feature['name']}\" title=\"remove\">";
      } else {
        $value = '';
        $remove = '';
      }
      $input = "<textarea name=\"f-{$feature['name']}\" rows=\"3\" cols=\"80\">$value</textarea>$remove\n";
    } else {
      Error("Unrecognized feature type <code>{$feature['type']}</code>");
    }
    $class = ($feature['required']) ? 'fname required' : 'fname';

    print " <div class=\"$class\">{$feature['name']} ({$feature['type']}):</div>
 <div>{$input}{$ilink}</div>
";
    if($feature['type'] == 'image')
      print " <div class=\"fname required\" style=\"font-size: 80%\">Alternate text:</div>
 <div>$input2</div>
";

  } // end loop on features
  
  print "<div class=\"fname\">
 Notes:</div>
<div>
 <input type=\"text\" name=\"notes\" size=\"60\" value=\"$nvalue\">
</div>
<div class=\"fsub\">
$submit
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
$imgs
";
  
} /* end PatternForm() */


/* FeatureForm()
 *
 *  Display a form for entering or editing a feature.
 */

function FeatureForm($id = null) {
  $disabled = ''; // used to disable the type menu
  
  if(isset($id)) {

    # Editing an existing feature.
    
    $feature = GetFeature($id);
    $checked = $feature['required'] ? ' checked="checked"' : '';
    if(!isset($feature))
      Error('System error: no feature with id <code>$id</code> exists.');
    $fname = " value=\"{$feature['name']}\"";
    $fnotes = $feature['notes'];
    $fid = "<input type=\"hidden\" name=\"feature_id\" value=\"{$feature['id']}\">\n";
    print "<h2>Edit Feature</h2>\n";
    $another = '<input id=\"faformsubmit3\" type="submit" name="submit" value="Delete">
';
    if($stats = FeatureStats($id)) {
      $valueCount = 0;
      $instr = "<p class=\"alert\">This feature is used by the following templates (number of patterns with feature values):\n";
      foreach($stats as $stat) {
        $instr .= "<br><span style=\"margin-left: 2em\"><i>{$stat['name']}</i> ({$stat['count']} patterns</span>)\n";
        $valueCount += $stat['count'];
      }
      if($valueCount)
        $disabled = ' disabled="disabled"';
    } else {
      $instr = "<p class=\"alert\">This feature isn't used in any templates yet.</p>\n";
    }
    
  } else {

    # Defining a new feature.

    $fname = $fnotes = $fid = '';
    print "<h2>Add a Feature</h2>

<p class=\"instr\">Select from these types:
 <ul>
  <li><code>string</code>: a character string of 255 characters or fewer</li>
  <li><code>text</code>: up to sixteen million characters</li>
  <li><code>integer</code>: a integer value</li>
  <li><code>image</code>: a graphic image, up to sixteen million bytes</li>
 </ul>
</p>
";
    $another = '<input type="submit" id="accepta" name="submit" value="' . ANOTHER . "\">\n";
    $instr = "<p class=\"alert\">Enter a name, data type, and optional notes
for this new feature. Check the <code>Required?</code> box if every pattern
using a template with this feature is required to have a value for it.</p>
";
  }
  
  $typemenu = "<select name=\"type\" id=\"faformtype\"$disabled>
 <option value=\"0\">Select a type</option>
";
  foreach(TYPE as $alias => $type) {
    $selected = (isset($feature) && $feature['type'] == $alias)
      ? ' selected="selected"' : ''; 
    $typemenu .= " <option value=\"$alias\"$selected>$alias</option>\n";
    $checked = '';
  }
  $typemenu .= "</select>\n";

  print "$instr
<form action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"featureform\" method=\"POST\" id=\"featureform\">
 <input type=\"hidden\" name=\"feature\" value=\"specify\">
$fid
 <div class=\"fname\">Feature name:</div>
 <div><input type=\"text\" name=\"name\" id=\"faformname\"$fname></div>
 
 <div class=\"fname\">Type:</div>
 <div>$typemenu</div>

 <div class=\"fname\">Required?</div>
 <div><input type=\"checkbox\" name=\"required\"$checked></div>

 <div class=\"fname\">Notes:</div>
 <div><textarea name=\"notes\" rows=\"3\" cols=\"80\">$fnotes</textarea></div>

 <div class=\"fsub\">
  <input type=\"submit\" name=\"submit\" id=\"accept\" value=\"Accept\" id=\"accept\">
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
    Alert('Feature update accepted.');
  } else
    Alert('No changes to feature.');
  
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
      'submit' => [
        [
          'id' => 'tsel',
          'label' => 'Select'
        ]
      ],
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
  <input type=\"submit\" name=\"submit\" value=\"Accept and add features\">
  <input type=\"submit\" name=\"submit\" value=\"Accept\" id=\"accept\">
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
    $selpattern = '<select name="pattern_id" id="pattern_id">
 <option value="0">Select a pattern</option>
';
    foreach($patterns as $pattern) {
      $title = (isset($pattern['title']))
        ? $pattern['title'] : '(no title)';
      $selpattern .= " <option value=\"{$pattern['id']}\">$title - (id {$pattern['id']})</option>\n";
    }
    $selpattern .= '</select>
';
    print "<h2>Select a Pattern</h2>

<form action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\" id=\"selpat\">
<input type=\"hidden\" name=\"pattern\" value=\"edit\">

<div class=\"fname\">Select a pattern:</div>
<div>$selpattern</div>
<div class=\"fsub\">
 <input type=\"submit\" id=\"accept\" name=\"submit\" value=\"Edit\">
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
      'submit' => [
        [
          'label' => 'Select',
          'id' => 'tsel'
        ]
      ]
    ]);
  }
  
} /* end SelectPattern() */


/* ViewPattern()
 *
 *  View a pattern. There are these prerequite steps:
 *
 *   1. Select a pattern language.
 *   2. Select a pattern view that shares the template with the language.
 *   3. Click on a per-pattern link to generate the page.
 */

function ViewPattern($context) {

  if(array_key_exists('plid', $context)) {

    # Language has been selected, offer a set of links and a popup menu to
    # choose a view. A 'change' event handler on the popup menu controls
    # whether the links are active, which requires a view be selected.

    $pl = GetPL($plid = $context['plid']);
    $ptid = $pl['ptid'];
    $pvs = GetPVs(['ptid' => $ptid]);
    $pvsel = '<select name="pvid" id="pvid">
 <option value="0">Select a view</option>
';
    foreach($pvs as $pv)
      $pvsel .= "<option value=\"{$pv['id']}\">{$pv['name']}</option>\n";
    $pvsel .= '</select>
';
    $plmembers = GetPLMembers(['plid' => $plid]);
    $titles = [];
    foreach($plmembers as $plmember) {
      $pattern = GetPattern($plmember['pid']);
      $title = $pattern['features']['title']['value'];
      $titles[] = ['id' => $pattern['id'], 'title' => $title];
    }
    usort($titles, 'bytitle');
    print "<h2>View <em>{$pl['name']}</em> Patterns Using the Selected View</h2>

<p>Select a pattern view, then click on a linked pattern title to view that
pattern with that view.</p>

<form class=\"featureform\" id=\"pview\">
 <div class=\"fname\">Select a view:</div>
 <div>$pvsel</div>
</form>
<ul id=\"ice\">\n";
    foreach($titles as $title)
      print " <li><a data-id=\"{$title['id']}\">{$title['title']}</a></li>\n";
    print "</ul>
 <a href=\"./\">Continue</a>.
";
    
  } else {

    # select a pattern language
    
    $pls = GetPLs();
    $wpls = GetPLs(null, true);
    $selpl = '<select name="plid">
   <option value="0">Select pattern language</option>
  ';
    foreach($pls as $pl) {
      $disabled = array_key_exists($pl['id'], $wpls)
	? ''
	: ' disabled="disabled"';
      $selpl .= " <option value=\"{$pl['id']}\">{$pl['name']}</option>\n";
    }
    $selpl .= '</select>
  ';
    print "<h2>Select a pattern language</h2>

<p>We will offer a set of links to patterns that are members of the pattern
language you select below. Only pattern languages that have member patterns
can be selected.</p>

<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"featureform\">
 <input type=\"hidden\" name=\"pattern\" value=\"view\">

 <div class=\"fname\">Select a pattern language:</div>
 <div>$selpl</div>

 <div class=\"fsub\">
  <input type=\"submit\" name=\"submit\" value=\"Accept\">
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>

</form>
";
  }

} /* end ViewPattern() */


/* ManageFeatures()
 *
 *  Manage features associated with this template.
 */

function ManageFeatures($template_id) {
  $template = GetTemplate($template_id);
  if(!isset($template))
    Error('No such template.');
  $features = GetFeatures(['required' => 0]);
  $ufeatures = [];
  foreach($features as $feature) {
    if(array_key_exists($feature['name'], $template['features']))
      continue;
    $ufeatures[] = $feature;
  }
  if(count($ufeatures)) {
  
    # there is at least one feature not used by this template; offer to add
    
    $fmenu = '<select name="feature_id" id="featuresel">
 <option value="0">Select a feature to add</option>
';
    foreach($ufeatures as $ufeature) {
      $fmenu .= " <option value=\"{$ufeature['id']}\">{$ufeature['name']} - {$ufeature['type']}</option>\n";
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
 <input type=\"submit\" id=\"accept\" name=\"submit\" value=\"Accept\">
 <input type=\"submit\" id=\"aaccept\" name=\"submit\" value=\"" . ANOTHER . "\">
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


function bytitle($a, $b) {
  return $a['title'] <=> $b['title'];
} /* end bytitle() */


/* ManagePatterns()
 *
 *  Manage patterns in this language.
 */

function ManagePatterns($plid) {
  $pl = GetPL($plid);
  if(!isset($pl))
    Error("No pattern language with id $plid.");
  $ptid = $pl['ptid'];
  
  # Any pattern with the same pattern_template as this language can be a member.
  
  $patterns = GetPatterns(['ptid' => $ptid]);

  # Sort by title.

  if(isset($patterns) && count($patterns))
    usort($patterns, 'bytitle');
  else
    $patterns = [];

  # Get the existing members.
  
  $plmembers = GetPLMembers(['plid' => $plid]);

  # Add an 'ismember' value to each pattern record.
  
  foreach($patterns as $k => $pattern)
    $patterns[$k]['ismember'] = array_key_exists($pattern['id'], $plmembers);

  print "<h2>Managing Patterns for Pattern Language <code>{$pl['name']}</code></h2>

<p>A <em>pattern language</em> consists of a set of patterns that share a
<em>pattern template</em> - a common set of features. Use this form to add
and remove patterns from this pattern language. Checkboxes are already
checked for those patterns that are already members of this language.</p>

<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\" id=\"faform\">
 <input type=\"hidden\" name=\"pl\" value=\"eatme\">
 <input type=\"hidden\" name=\"plid\" value=\"$plid\">
 <div class=\"fh\">Pattern title</div>
 <div class=\"fh\">Membership</div>
";
  foreach($patterns as $pattern) {
    $checked = $pattern['ismember'] ? ' checked="checked"' : '';
    print " <div class=\"antifa\">{$pattern['title']}</div>
  <div class=\"centrist\">
   <input type=\"checkbox\" name=\"{$pattern['id']}\"$checked>
  </div>
";
  }
  print " <div class=\"fsub\">
 <input type=\"submit\" name=\"submit\" value=\"Accept\">
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>
</form>
";

} /* end ManagePatterns() */


/* EatMe()
 *
 *  Make a hearty meal of assigments - and unassignments - of patterns to ths
 *  pattern language. Schema afficianados know: that's a matter of inserting
 *  and deleting rows in the 'plmember' table.
 */

function EatMe() {
  $pl = GetPL($plid = $_REQUEST['plid']);
  if(!isset($pl))
    Error("No pattern language with id <code>$plid</code>.");
  $inserts = [];
  $deletes = [];
  $nmembers = [];
  $omembers = GetPLMembers(['plid' => $plid]);

  // If a POST parameter is numeric, that's a checked box.

  foreach($_REQUEST as $k => $v)
    if(preg_match('/^\d+$/', $k))
      $nmembers[$k] = true;

  // Look for INSERTs - new members.
  
  foreach($nmembers as $k => $v)
    if(!array_key_exists($k, $omembers))
      $inserts[] = $k;

  // Look for DELETEs - departing members.
  
  foreach($omembers as $k => $v)
    if(!array_key_exists($k, $nmembers))
      $deletes[] = $k;

  $stats = UpdatePLMembers($plid, $inserts, $deletes);
  Alert("Added {$stats['inserts']} and deleted {$stats['deletes']} patterns to the <em>{$pl['name']}</em> pattern language.");

} /* end EatMe() */


/* AddFeature()
 *
 *  Add the selected feature to the selected template.
 */

function AddFeature($template_id, $feature_id) {
  return InsertTemplateFeature($template_id, $feature_id);
  
} /* end AddFeature() */


/* RemoveFeatures()
 *
 *  Remove one or more associated features from a template.
 */

function RemoveFeatures($template_id, $features) {
  $count = 0;
  foreach($features as $feature) {
    DeleteTemplateFeature($template_id, $feature);
    $count++;
  }
  Alert("Removed $count features from template");

} /* end RemoveFeatures() */


/* AbsorbPatternUpdate()
 *
 *  Implement pattern update, including associated feature values. Return
 *  true if we took any actions.
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
  
    if(!preg_match('/^f-(.+)$/', $k, $matches))
      continue;
      
    $name = $matches[1];
    $fnames[$name] = 1;
    $feature = $features[$name];

    if(isset($feature) && isset($feature['value'])) {

      # pattern has a value for this feature; changed?
      
      if($feature['value'] == $v)
        continue; # no change
      else
        $update[$name] = [
          'featurename' => $name,
          'value' => $v
        ];

    } elseif(isset($feature) && $feature['type'] == 'image' &&
             isset($feature['hash'])) {

      # feature is an existing image; alttext and/or hash and filename
      # may have changed

      $file = $_FILES[$name];
      $file['alttext'] = trim($v);

      if($haveUpload = ($file['error'] != NOFILE))
        $file = CheckFile($file);

      if(($haveUpload && $feature['hash'] != $file['hash']) ||
         ($feature['alttext'] != $v)) {
        $update[$name] = [
          'featurename' => $name,
          'pid' => $pattern_id
        ];
        if($haveUpload && $feature['hash'] != $file['hash']) {
          $update[$name]['hash'] = $file['hash'];
          $update[$name]['name'] = $file['name'];
        }
        if($feature['alttext'] != $v)
          $update[$name]['alttext'] = $v;
      }          
    } else {

      # pattern does not have a value for this feature; possible insert

      if($feature['type'] == 'image') {

        // feature values of type 'image' require upload and alternative text

        $file = $_FILES[$name];
        if($file['error'] == NOFILE)
          continue;
        $file['alttext'] = trim($_REQUEST[$k]);
        if($file = CheckFile($file))
          $insert[$name] = [
            'name' => $file['name'],
            'fname' => $name,
            'alttext' => $file['alttext'],
            'hash' => $file['hash']
          ];
        else
          Alert("Failed to set a value for $name.");
       } else {
        if($feature['type'] == 'string' || $feature['type'] == 'text') {
          if(strlen($v))
            $insert[] = ['name' => $name, 'value' => $v];
             } else  
          $insert[] = ['name' => $name, 'value' => $v];
      }
    }
  } # end loop on features in form

  # Loop on existing pattern features, looking for deletions.

  foreach($features as $feature)
    if(array_key_exists("d-{$feature['name']}", $_REQUEST))
      $delete[] = $feature;

  # perform the pattern feature changes.
  
  UpdatePatternFeatures($pattern_id, $update, $insert, $delete);

  $did = (count($update) > 0) || (count($insert) > 0) || (count($delete) > 0);

  # update the pattern.note if needed

  if($_REQUEST['notes'] != $pattern['notes']) {
    UpdatePattern($pattern_id, $_REQUEST['notes']);
    $did = true;
  }
  return $did;
  
} /* end AbsorbPatternUpdate() */


/* AbsorbNewPattern()
 *
 *  Implement pattern insert, returning a pattern array with 'features'.
 */

function AbsorbNewPattern() {

  // collect the features specified in the form
  
  $template = GetTemplate($template_id = $_REQUEST['template_id']);
  $template_features = $template['features'];
  
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
  $pattern = InsertPattern($notes, $template_id);
  
  foreach($features as $fname => $fvalue) {
    $feature = $template_features[$fname];

    if($feature['type'] == 'image') {

      # handle a feature of type 'image'
      
      $file = $_FILES[$fname];
      if($haveUpload = ($file['error'] != NOFILE)) {
        $file['alttext'] = $fvalue;
        if($file = CheckFile($file))
          InsertFeatureValue([
            'pid' => $pattern['id'],
            'fname' => $fname,
            'alttext' => $file['alttext'],
            'hash' => $file['hash'],
            'name' => $file['name']
          ]);
      }
    } else {

      # handle other feature types

      if(($feature['type'] == 'string' || $feature['type'] == 'text')
          && !strlen($fvalue))
        continue;
        
      InsertFeatureValue([
        'pid' => $pattern['id'],
        'fname' => $fname,
        'value' => $fvalue
      ]);
    }
  }
  $pattern['features'] = $features;
  return $pattern;
  
} /* end AbsorbNewPattern() */


/* TemplateMenu()
 *
 *  Return a popup menu of pattern_templates.
 */

function TemplateMenu($id = null) {
  $pts = GetTemplates();
  $tm = '<select name="template_id">
 <option value="0">Select a pattern template</option>
';
  foreach($pts as $pt) {
    $selected = (isset($id) && $id == $pt['id']) ? ' selected="selected"' : '';
    $tm .= " <option value=\"{$pt['id']}\"$selected>{$pt['name']}</option>\n";
  }
  $tm .= "</select>\n";
  return $tm;
  
} /* end TemplateMenu() */


/* PLForm()
 *
 * Add, edit, delete pattern_languages.
 */

function PLForm($id = null) {

  $pls = GetPLs();

  if(isset($id)) {

    # Working with existing.

    if(!$id)
      Error("You haven't selected a pattern language.");
    $pl = $pls[$id];
    if(! isset($pl) )
      Error('Pattern language not found');

    $name_value = " value=\"{$pl['name']}\"";
    $notes_value = $pl['notes'];
    $delete = '';
    $title = 'Edit Pattern Language';
    $tmenu = TemplateMenu($pl['ptid']);
    $context = "<input type=\"hidden\" name=\"plid\" value=\"$id\">\n";

  } else {

    # Adding.

    $title = 'Add a Pattern Langage';
    $name_value = $notes_value = $delete = '';
    $tmenu = TemplateMenu();
  }
  print "<h2>$title</h2>

<form action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\">
 <input type=\"hidden\" name=\"pl\" value=\"absorb_pl\">
$context

 <div class=\"fname\">Pattern language name:</div>
 <div><input type=\"text\" name=\"name\"$name_value\"></div>

 <div class=\"fname\">Notes:</div>
 <div>
  <textarea name=\"notes\" rows=\"3\" cols=\"80\">$notes_value</textarea>
 </div>

 <div class=\"fname\">Pattern template:</div>
 <div>$tmenu</div>

 <div class=\"fsub\">
  <input type=\"submit\" name=\"submit\" value=\"" . ADDPATTERNS . "\">
  <input type=\"submit\" name=\"submit\" value=\"Accept\" id=\"accept\">
  $delete
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>

</form>
";
  
} /* end PLForm() */


/* SelectPL()
 *
 *  Select a pattern language.
 *
 *  All the patterns in a pattern language share a template. If there are
 *  no patterns in the template a pattern language uses, there is no ability
 *  to add patterns to it. Therefore, while we support creation and editing
 *  of pattern languages, we do the user no favor by offering them to add
 *  patterns to pattern languages based on patternless templates. In the
 *  event that we find such pattern languages, we offer a separate menu
 *  for selecting for metadata edits and for selecting for adding patterns.
 */

function SelectPL($context) {
  $pls = GetPLs();
  $ppls = GetPLs(null, true); # pls with pattern counts
  
  $selpl = "<select name=\"plid\">
 <option value=\"0\">Select a pattern language</option>
";
  $submit = '';
  foreach($context['submit'] as $sub) {
    if(array_key_exists('id', $sub))
      $id = " id=\"{$sub['id']}\"";
    else
      $id = '';
    $submit .= "<input type=\"submit\" name=\"submit\" value=\"{$sub['label']}\"$id>\n";
  }
  $submit .= "<input type=\"submit\" name=\"submit\" value=\"Cancel\">\n";

  print "<h2>{$context['label']}</h2>\n";
  
  foreach($pls as $pl)
    $selpl .= " <option value=\"{$pl['id']}\">{$pl['name']}</option>\n";
  $selpl .= "</select>\n";

  print "<form action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\" id=\"selectpl\">
 <input type=\"hidden\" name=\"{$context['context']}\" value=\"{$context['action']}\">

 <div class=\"fname\">Select a pattern language:</div>
 <div>$selpl</div>
 
 <div class=\"fsub\">
  $submit
 </div>
</form>
";

} /* end SelectPL() */


/* SelectPV()
 *
 *  Select a pattern view.
 */

function SelectPV($context) {
  $pvs = GetPVs();
  
  $selpv = "<select name=\"pvid\">
 <option value=\"0\">Select a pattern view</option>
";
  $submit = '';
  foreach($context['submit'] as $sub) {
    if(array_key_exists('id', $sub))
      $id = " id=\"{$sub['id']}\"";
    else
      $id = '';
    $submit .= "<input type=\"submit\" name=\"submit\" value=\"{$sub['label']}\"$id>\n";
  }
  $submit .= "<input type=\"submit\" name=\"submit\" value=\"Cancel\">\n";

  print "<h2>{$context['label']}</h2>\n";
  
  foreach($pvs as $pv)
    $selpv .= " <option value=\"{$pv['id']}\">{$pv['name']}</option>\n";
  $selpv .= "</select>\n";

  print "<form action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\" id=\"selectpv\">
 <input type=\"hidden\" name=\"{$context['context']}\" value=\"{$context['action']}\">

 <div class=\"fname\">Select a pattern view:</div>
 <div>$selpv</div>
 
 <div class=\"fsub\">
  $submit
 </div>
</form>
";

} /* end SelectPV() */


/* PVForm()
 *
 *  Form to adding/editing pattern views.
 */

function PVForm($id = null) {
  $pvs = GetPVs();

  if(isset($id)) {

    // editing a pattern view
    
    if(!$id)
      Error("You haven't selected a pattern view.");
    $pv = $pvs[$id];
    if(!isset($pv))
      Error("Pattern view not found.");
    $name_value = " value=\"{$pv['name']}\"";
    $notes_value = $pv['notes'];
    $layout_value = $pv['layout'];
    $delete = '';
    $title = 'Edit Pattern View';
    $context = "<input type=\"hidden\" name=\"pvid\" value=\"$id\">\n";
    $tmenu = TemplateMenu($pv['ptid']);
  } else {

    // adding a pattern view
    
    $title = 'Add Pattern View';
    $name_value = $notes_value = $delete = '';
    $tmenu = TemplateMenu();
  }
  print "<h2>$title</h2>

<p>A pattern view is code for an HTML page in which strings of the form
<code>%%<feature-name>%%</code> are replaced with the value of that
feature for a pattern when it is displayed. We will be expanding this
primitive layout language soon to support other features, such as
hiding feature lables for patterns that lack an associated value.</p>

<p>Each pattern view has an associated pattern template which
determines which features might be defined for a pattern used with the
view.<p>

<p>You can use the text area to enter a pattern view, or you can upload
a file. If you upload a file we will ignore any input in the <code>Layout</code>
textarea.</p>

<form enctype=\"multipart/form-data\" action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\">
 <input type=\"hidden\" name=\"pv\" value=\"absorb_pv\">
 $context

 <div class=\"fname\">Pattern view name:</div>
 <div><input type=\"text\" name=\"name\"$name_value\"></div>

 <div class=\"fname\">Notes:</div>
 <div>
  <textarea name=\"notes\" rows=\"3\" cols=\"80\">$notes_value</textarea>
 </div>

 <div class=\"fname\">Pattern template:</div>
 <div>$tmenu</div>
 
 <div class=\"fname\">Layout file:</div>
 <div><input name=\"layout\" type=\"file\"></div>

 <div class=\"fname\">Layout:</div>
 <div>
  <textarea name=\"layout\" rows=\"3\" cols=\"80\">$layout_value</textarea>
 </div>

 <div class=\"fsub\">
  <input type=\"submit\" name=\"submit\" value=\"Accept\" id=\"accept\">
  $delete
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>
";

} /* end PVForm() */


/* AbsorbPV()
 *
 *  Absorb a new pattern view or edits of an existing one.
 *
 *  We don't require that a layout be provided, though a pattern view
 *  without one is useless. If one is provided, we perform a simple
 *  validation check: are there feature tokens present in the layout
 *  that aren't in the template. If so, we warn but accept.
 */

function AbsorbPV() {

  # check the name
  
  CheckIdentifier($name = $_REQUEST['name'], true);
  $notes = $_REQUEST['notes'];

  # get the template
  
  if(!isset($_REQUEST['template_id']) || !($ptid = $_REQUEST['template_id']))
    Error('Select a pattern template');
  $pt = GetTemplate($ptid = $_REQUEST['template_id']);
  if(!$pt)
    Error('No such pattern template');

  # get the layout

  if(($file = $_FILES['layout']) && !$file['error']) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    if($mime != 'text/html')
      Error("<code>{$file['name']}</code> doesn't seem to be an HTML file");
    if($file['size'] > MAXIMAGE)
      Error('Layout file size exceeds maximum accepted of ' . MAXIMAGE . ' bytes');
    $layout = file_get_contents($file['tmp_name']);
  }
  elseif(isset($_REQUEST['layout']))
    $layout = $_REQUEST['layout'];

  if($layout && strlen($layout))
    ValidateView($layout, $pt);

  if(isset($_REQUEST['pvid'])) {

    # working on an update
    
    $pvid = $_REQUEST['pvid'];
    $pv = GetPV('id', $pvid);
    if(!$pv)
      Error('No such pattern view found');
    $update = ['id' => $pvid];
    if($pv['name'] != $name)
      $update['name'] = $name;
    if($pv['ptid'] != $ptid)
      $update['ptid'] = $ptid;
    if($pv['layout'] != $layout)
      $update['layout'] = $layout;
    UpdatePV($update);
  } else {
    $insert = [
      'ptid' => $ptid,
      'name' => $name,
      'notes' => $notes
    ];
    if(strlen($layout))
      $insert['layout'] = $layout;
    InsertPV($insert);
  }

} /* end AbsorbPV() */


/* ValidateView()
 *
 *  Warn about detected problems in the pattern view.
 */

function ValidateView($layout, $template) {
  $tokens = [];

  $offset = 0;
  while(preg_match(TOKENMATCH, $layout, $matches, PREG_OFFSET_CAPTURE,
        $offset)) {
    $token = $matches[1][0];
    $tokens[$token] = $token;
    $offset = $matches[1][1] + strlen($token) + 2;
  }

  # Find feature tokens found/not found in the template.

  $features = $template['features'];
  $found = [];
  $orphans = [];
  foreach($tokens as $token)
    if(array_key_exists($token, $features))
      $found[$token] = true;
    else
      $orphans[$token] = true;

  $status = true;
  if(count($found)) {
    Alert("These feature tags found in the layout are in the template: <code>" .
      implode('</code> , <code>', $tokens) . '</code>');
  }
  if(count($orphans)) {
    Alert("These feature tags found in the layout are not in the template: <code>" .
      implode('</code> , <code>', $orphans) . '</code>');
    $status = false;
  }
  return $status;
  
} /* end ValidateView() */

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
  print "<div class=\"ass\" id=\"ass\">Show POST parameters</div>
<div id=\"posterior\">\n";
  foreach($_POST as $k => $v) {
    print " <div>$k</div>\n<div>$v</div>\n";
  }
  print "</div>\n";
}
if(DEBUG && count($_FILES)) {
  print '<div class="ass" id="bum">Show FILES array</div>
<div id="booty">
';
  foreach($_FILES as $fn => $file) {
    print "<div class=\"bun\">$fn</div>\n";
    foreach($file as $k => $v) {
      print " <div class=\"booty\">$k</div>
 <div class=\"booty\">$v</div>\n";
    }
  }
  print "</div>\n";
}  

if(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Cancel') {
  true;
} elseif(isset($_REQUEST['pattern'])) {

  # pattern actions
  
  $action = $_REQUEST['pattern'];

  if($action == 'view') {
  
    if(isset($_REQUEST['pid'])) {

      # display this pattern using this view
      
      ViewPattern(['pid' => $_REQUEST['pid'], 'pvid' => $_REQUEST['pvid']]);
    } else {

      # if plid is set, show linked pattern titles and a view selector, else
      #  a pattern language selector
      
      $plid = isset($_REQUEST['plid']) ? $_REQUEST['plid'] : null;
      if($plid)
        ViewPattern(['plid' => $plid]);
      else
        ViewPattern([]);
    }
    $SuppressMain();

  } elseif($action == 'edit') {

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

    # adding a feature

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

    # absorbing pattern addition
    
    $pattern = AbsorbNewPattern();
    Alert("Created new pattern with title <em>{$pattern['features']['title']}</em>, id {$pattern['id']}");
    if($_REQUEST['submit'] == ANOTHER) {
      PatternForm('add', $_REQUEST['template_id']);
      $SuppressMain = true;
    }
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

    if(array_key_exists($_REQUEST['type'], TYPE))
      $type = TYPE[$_REQUEST['type']];
    else
      Error("Type <code>{$_REQUEST['type']}</code> is unknown");

    $value = [
      'name' => $_REQUEST['name'],
      'type' => $type,
      'alias' => $_REQUEST['type'],
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

      $feature = InsertFeature($value);
      Alert("Feature <i>{$feature['name']}</i> inserted, type <code>{$feature['type']}</code>, id <code>{$feature['id']}</code>.");
      if($_REQUEST['submit'] == ANOTHER) {
        FeatureForm();
        $SuppressMain = true;
      }
    }
  }
} elseif(isset($_REQUEST['pv'])) {

  // All actions Pattern View.
  
  if($_REQUEST['pv'] == 'edit') {
    if(isset($_REQUEST['pvid'])) {
      $pvid = $_REQUEST['pvid'];
      if(!$pvid)
        Error('You failed to select a pattern view');
      PVForm($pvid);
      $SuppressMain = true;
    } else {
      SelectPV([
        'label' => 'Edit Pattern View',
	'context' => 'pv',
	'action' => 'edit',
	'submit' => [
	  [
	    'label' => 'Select',
	    'id' => 'metadata'
	  ]
        ]
      ]);
      $SuppressMain = true;
    }
  }
  elseif($_REQUEST['pv'] == 'add') {
    PVForm();
    $SuppressMain = true;
  } elseif($_REQUEST['pv'] == 'absorb_pv') {
    AbsorbPV();
  }
  
} elseif(isset($_REQUEST['pl'])) {

  # pattern language actions

  if($_REQUEST['pl'] == 'edit') {
    if(isset($_REQUEST['plid'])) {

      // working with the selected language

      if($plid = $_REQUEST['plid']) {
        if($_REQUEST['submit'] == 'Edit metadata') {
          PLForm($plid);
          $SuppressMain = 1;
        } elseif($_REQUEST['submit'] == 'Manage patterns') {
          ManagePatterns($plid);
	  $SuppressMain = true;
        } elseif(isset($_REQUEST['action']) &&
                 $_REQUEST['action'] == 'rmpatterns') {
          true;
        } else {
          ManagePatterns($plid);
          $SuppressMain = true;
        }
      } else {
        Error('You failed to select a pattern language');
      }
    } else {
      SelectPL([
        'label' => 'Edit Pattern Language',
        'context' => 'pl',
	'action' => 'edit',
	'submit' => [
	  [
	    'label' => 'Edit metadata',
	    'id' => 'metadata',
	  ],
	  [
	    'id' => 'plsel',
	    'label' => 'Manage patterns'
	  ]
	],
      ]);
      $SuppressMain = true;
    }
  } elseif($_REQUEST['pl'] == 'add') {
    PLForm();
    $SuppressMain = 1;
  } elseif($_REQUEST['pl'] == 'eatme') {
    EatMe();
  } elseif($_REQUEST['pl'] == 'absorb_pl') {
    if(isset($_REQUEST['plid'])) {
      $plid = $_REQUEST['plid'];
      if($_REQUEST['submit'] == 'Delete') {
        DeletePL($plid);
        Alert('Deleted pattern language');
      } else {
        CheckIdentifier($_REQUEST['name'], true);
	if(UpdatePL([
	    'id' => $plid,
	    'ptid' => $_REQUEST['template_id'],
	    'name' => $_REQUEST['name'],
	    'notes' => $_REQUEST['notes']
	  ]))
	  Alert("Pattern language <code>{$_REQUEST['name']}</code> updated.");
        else
	  Alert("No update to pattern language <code>{$_REQUEST['name']}</code>.");
      }
    } else {
        CheckIdentifier($_REQUEST['name'], true);
	$pl = InsertPL([
	  'name' => $_REQUEST['name'],
	  'notes' => $_REQUEST['notes'],
	  'ptid' => $_REQUEST['template_id']
	]);
	Alert("Inserted pattern language <code>{$_REQUEST['name']}</code> (id <code>{$pl['id']}</code>).");
	if($_REQUEST['submit'] == ADDPATTERNS) {
	  ManagePatterns($pl['id']);
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

          $f = AddFeature($template_id, $_REQUEST['feature_id']);
          Alert("Added feature <i>{$f['fname']}</i> to template <i>{$f['tname']}</i>");
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
          [
            'label' => 'Edit metadata',
            'id' => 'metadata'
          ],
          [
            'label' => 'Manage features',
            'id' => 'features'
          ]
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
        Alert('Deleted template.');

      } else {
      
        # Absorbing a pattern_template edit.

        CheckIdentifier($_REQUEST['name'], true);
        UpdateTemplate([
          'id' => $template_id,
          'name' => $_REQUEST['name'],
          'notes' => $_REQUEST['notes']
        ]);
        Alert("Template <code>{$_REQUEST['name']}</code> updated.");
      }
    } else {
    
      # Absorb a new pattern_template.
    
      CheckIdentifier($_REQUEST['name'], true);
      $template = InsertTemplate([
        'name' => $_REQUEST['name'],
        'notes' => $_REQUEST['notes']
      ]);
      Alert("Inserted template <i>{$template['name']}</i>, id <code>{$template['id']}</code>.");
      if($_REQUEST['submit'] == 'Accept and add features') {
        ManageFeatures($template['id']);
        $SuppressMain = true;
      }
    }
  }
} // end actions

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
 <li><a href="?pattern=view">View a pattern</a></li>
</ul>

<h2>Pattern Languages</h2>

<ul>
 <li><a href="?pl=add">Create a pattern language</a></li>
 <li><a href="?pl=edit">Edit a pattern language</a></li>
</ul>

<h2>Pattern Views</h2>

<ul>
 <li><a href="?pv=add">Create a pattern view</a></li>
 <li><a href="?pv=edit">Edit a pattern view</a></li>
</ul>

<p><a href="README.html">README</a></p>

</div>
<?php
}
?>
</div>
<?=FOOT?>
</body>
</html>
