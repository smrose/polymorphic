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
 *  Status              all the things on a single screen
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
 *  Each feature type has an associated table that stores values. For example,
 *  a "title" feature, which has type "varchar(255)", which the user sees
 *  as "string."
 *
 *   CREATE TABLE pf_string (
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
 *  pf_string table with pfid = 2 and pid = 3.
 *  
 */

if(false) {
  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
}

require 'pps.php';

DataStoreConnect();
$SuppressMain = false;

const ANOTHER = 'Accept and enter another';
const ADDPATTERNS = 'Accept and add patterns';
const ADDFEATURES = 'Accept and add features';


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
  $seltemplate = "<select name=\"template_id\" id=\"template_id\">
 <option value=\"0\"></option>
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

<script>

  /* stidf()
   *
   *  Handle 'input' events on the select-template popup. We don't want the
   *  submit buttons to be active unless a template has been selected.
   */

  function stidf() {
    submitState = (template_id.value != '0')
    submitState2 = (template_id.value > 1)
    if(metadata)
        metadata.disabled = ! submitState
    if(features)
        features.disabled = ! submitState2
    if(tsel)
        tsel.disabled = ! submitState

  } /* end stidf() */

  selecttemplate = document.querySelector('#selecttemplate')
  accept = document.querySelector('#accept')
  accepta = document.querySelector('#accepta')
  if(template_id = document.querySelector('#template_id'))
    template_id.addEventListener('input', stidf)
  metadata = document.querySelector('#metadata')
  features = document.querySelector('#features')
  tsel = document.querySelector('#tsel')
  if(metadata || features || tsel)
    stidf()
</script>
";

} /* end SelectTemplate() */


/* SelectFeature()
 *
 *  Select a pattern_feature.
 */

function SelectFeature() {
  $features = GetFeatures();
  $menu = '<select name="feature_id" id="feature_id">
 <option value="0"></option>
';
  foreach($features as $feature) {
    $menu .= " <option value=\"{$feature['id']}\" title=\"{$feature['notes']}\">{$feature['name']} - {$feature['type']}</option>\n";
  }
  $menu .= '</select>
';
  print '<h2>Edit a Feature</h2>
';
Alert("Select the feature you wish to edit.");
print "<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"featureform\" id=\"selectfeature\">
<input type=\"hidden\" name=\"feature\" value=\"edit\">
<div>$menu<div>
<div>
 <input type=\"submit\" name=\"submit\" value=\"Select\" id=\"accept\">
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
<div>
</form>
";
  print "<script>

  function f(e) {
    enable = feature_id.value != '0'
    accept.disabled = !enable
    
  } /* end feature_id() */
  
  var feature_id = document.querySelector('#feature_id')
  var accept = document.querySelector('#accept')
  feature_id.addEventListener('change', f)
  f()
</script>
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
    $context = "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
    $nvalue = $pattern['notes'];
    $note = "<h2>Editing <em>$ptitle</em></h2>
";
    $submit = ' <input type="submit" name="submit" value="Accept" id="accept">
 <input type="submit" name="submit" value="Delete">
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
 <input type="submit" id="accepta" name="submit" value="' . ANOTHER . '">
';
  }
  print "$note\n";
  Alert('Required features are displayed <span class="required">like this</span>. Check the checkbox at the right to remove a value.');
  print "<form enctype=\"multipart/form-data\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"featureform\" method=\"POST\" id=\"patternform\">
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
      $input = "<input name=\"f-{$feature['id']}\" type=\"text\" size=\"5\"$value>\n";

    } elseif($feature['type'] == 'string') {

      # use <input type="text"> for string type

      $remove = '';
      $value = '';
      $iclass = $feature['required'] ? ' class="rinput"' : '';
      if(isset($feature['value'])) {
        $value = " value=\"{$feature['value']}\"";
        if(!$feature['required'])
          $remove = "<input type=\"checkbox\" name=\"d-{$feature['id']}\" tite=\"remove\">";
      }
      $input = "<input name=\"f-{$feature['id']}\" type=\"text\" size=\"80\"$value$iclass>$remove\n";

    } elseif($feature['type'] == 'image') {

      # use <input type="file"> for images, <input type="text"> for alttext

      if(isset($feature['alttext']))
        $value = " value=\"{$feature['alttext']}\"";
      else
        $value = '';
      $input = "<input name=\"{$feature['id']}\" type=\"file\">";
      $input2 = "<input type=\"text\" name=\"f-{$feature['id']}\" size=\"50\"$value>";
      
      // link to view existing image
      
      if(isset($feature['hash'])) {

        /* There is an image to preview:
         *  compute the path
         *  display a link to unhide/hide the preview element
         *  add a <div class="imagebox"> containing the image preview and
         *   filename, hidden
         *  add a checkbox to delete the feature
         */

        $ipath = ImagePath($feature['hash']);
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
        $remove = "<input type=\"checkbox\" name=\"d-{$feature['id']}\" title=\"remove\">";
      } else {
        $value = '';
        $remove = '';
      }
      $input = "<textarea name=\"f-{$feature['id']}\" rows=\"3\" cols=\"80\">$value</textarea>$remove\n";
    } else {
      Error("Unrecognized feature type <code>{$feature['type']}</code>");
    }
    $lclass = ($feature['required']) ? 'fname required' : 'fname';

    print " <div class=\"$lclass\">{$feature['name']}:</div>
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
<script>

  /* pf()
   *
   *  Input fields that are required have the 'rinput' class. We disable
   *  the submit buttons unless there is a value provided for all of those.
   *
   *  Alternative text is required for image file uploads. We disable the
   *  submit buttons unless alternative text is provided for each file upload
   *  field for which a file is provided.
   */
   
  function pf(e) {
    disable = false

    // look at required fields
    
    rinputs = document.querySelectorAll('.rinput')
    for(rinput of rinputs)
        if(rinput.value.length == 0)
            disable = true

    if(!disable) {

      // look at file uploads
      
      ffs = document.querySelectorAll(\"input[type='file']\")
      for(ff of ffs) {
        if(ff.value.length) {
	  if(at = document.querySelector('input[name=\"f-' + ff.name + '\"'))
	    if(! at.value.length)
	      disable = true
	}
      }
    }
    if(accept = document.querySelector('#accept'))
      accept.disabled = disable 

  } // end pf()
  
  finputs = document.querySelectorAll(\"input[type='file']\") // file fields
  rinputs = document.querySelectorAll('.rinput') // required fields
  if(rinputs.length) {
      for(rinput of rinputs)
        rinput.addEventListener('input', pf)
      pf()
  }
  if(finputs.length) {
  
    // put a click event listener on each file input
    
    for(finput of finputs) {
      finput.addEventListener('input', pf)
      
      // the input field for alt text for this field is named f-<feature-id>
      
      if(atinput = document.querySelector('input[name=\"f-' + finput.name + '\"]'))
        atinput.addEventListener('input', pf)
    }
  }
</script>
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

<p class=\"instr\">Select from these types:</p>
 <ul>
  <li><code>string</code>: a character string of 255 characters or fewer</li>
  <li><code>text</code>: up to sixteen million characters</li>
  <li><code>integer</code>: a integer value</li>
  <li><code>image</code>: a graphic image, up to sixteen million bytes</li>
 </ul>
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

<script>
  /* faform()
   *
   *  Control enablement of submit buttons on feature add/edit form.
   */
   
  function faform(event) {
    enable = (faformtype.value != '0') && (faformname.value.length > 0)
    accept.disabled = ! enable
    if(accepta)
      accepta.disabled = ! enable
  } /* end faform() */
  
  var faformtype = document.querySelector('#faformtype')
  var faformname = document.querySelector('#faformname')
  var accept = document.querySelector('#accept')
  var accepta = document.querySelector('#accepta')
  faformname.addEventListener('input', faform)
  faformtype.addEventListener('change', faform)
  faform()
</script>
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

  $addfeatures = '<input type="submit" name="submit" value="' . ADDFEATURES .
                     '" id="accepta">';

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
    if($id == 1)
      $addfeatures = $delete = '';

  } else {

    # Adding a template.

    print "<h2>Add a Template</h2>\n";
    $idf = '';
    $name_value = $notes_value = '';
    $delete = '';
  }

  print "<form action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\" id=\"tmeta\">
 <input type=\"hidden\" name=\"template\" value=\"absorb_template\">
 $idf

 <div class=\"fname\">Template name:</div>
 <div><input type=\"text\" id=\"name\" name=\"name\"$name_value\"></div>

 <div class=\"fname\">Notes:</div>
 <div>
   <textarea name=\"notes\" rows=\"3\" cols=\"80\">$notes_value</textarea>
 </div>

 <div class=\"fsub\">
  $addfeatures
  <input type=\"submit\" name=\"submit\" value=\"Accept\" id=\"accept\">
  $delete
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>
</form>

<script>
  function et(e) {
    tname = document.querySelector('#name')
    submitstate = (tname.value.length > 0) ? false : true
    accept.disabled = submitstate
    if(accepta)
      accepta.disabled = submitstate
  } // end et()

  accept = document.querySelector('#accept')   // submit button 1
  accepta = document.querySelector('#accepta') // submit button 2
  tname = document.querySelector('#name')
  tname.addEventListener('input', et)
  et()
</script>
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
 <option value="0"></option>
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

<script>
  function spidf() {
    submitState = (pattern_id.value != '0')
    if(accept)
        accept.disabled = ! submitState

  } /* end spidf()
  accept = document.querySelector('#accept')   // submit button 1
  pattern_id = document.querySelector('#pattern_id'))
  pattern_id.addEventListener('input', spidf)
  spidf()
</script>
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
 *   2. Select pattern views that share templates with patterns in the language.
 *   3. Click on a per-pattern link to generate the page.
 */

function ViewPattern($context) {

  if(array_key_exists('plid', $context)) {

    # Language has been selected, offer a set of links and
    # per-template popup menus to choose a view. 'change' event
    # handlers on the popup menus controls whether the links are
    # active, which requires a view be selected. In the case of a
    # template with no views, the anchors are never activated. In
    # the case of a template with a single view, the view id is in
    # a hidden field.

    $alerts = '';
    $pl = GetPL($plid = $context['plid']);
    $plmembers = GetPLMembers($plid);
    $templates = GetTemplates();

    # Which templates participate?
    
    $ptids = [];
    foreach($plmembers as $plmember)
      $ptids[$plmember['ptid']] = 0;

    # Which views are available for each template? Build popup menus
    # in $pvsels for those with multiple, a hidden field for those with
    # just one.
    
    $pvsels = [];
    $multi = 0;
    
    foreach($ptids as $ptid => $vcount) {
      $pvs[$ptid] = GetPVs(['ptid' => $ptid]);
      if(isset($pvs[$ptid])) {

        if(($ptids[$ptid] = count($pvs[$ptid])) > 1) {

          # there are multiple views for this template; offer a popup
          
          $multi++;
          $template = $templates[$ptid];
          $pvsels[$ptid] = "<div class=\"fname\">
  Select a view for template <code>{$template['name']}</code>:
 </div>
 <div>
  <select name=\"pvid-$ptid\" id=\"pvid-$ptid\" class=\"selv\">
   <option value=\"0\"></option>
";
          foreach($pvs[$ptid] as $pv)
            $pvsels[$ptid] .= "   <option value=\"{$pv['id']}\">{$pv['name']}</option>\n";
          $pvsels[$ptid] .= '  </select>
 </div>
';
        } elseif(count($pvs[$ptid])) {

          # there is one view for this template; use hidden element

	  $pv = array_shift($pvs[$ptid]);
          $pvid = $pv['id'];
          $pvsels[$ptid] = "<input type=\"hidden\" name=\"pvid-$ptid\" id=\"pvid-$ptid\" value=\"$pvid\" class=\"selv\" data-text=\"{$pv['name']}\">\n";
        } else {
	
	  /* there are no pattern views for this template, complain */

	  $template = $templates[$ptid];
	  if(strlen($alerts))
	    $alerts .= "<br>\n";
	  $alerts .= "There are no pattern views defined for template <em>{$template['name']} </em>";
	}
      }

    } /* end loop on participating templates */
    
    # Build $titles as a sorted-by-title array of arrays with 'title',
    # 'ptid', and 'id' fields, one per pattern

    $titles = [];
    foreach($plmembers as $plmember) {
      $pattern = GetPattern($plmember['pid']);
      $title = $pattern['features']['title']['value'];
      $titles[] = [
        'id' => $pattern['id'],
        'ptid' => $pattern['ptid'],
        'title' => $title
      ];
    }
    usort($titles, 'bytitle');

    print "<h2>View <em>{$pl['name']}</em> Patterns</h2>\n";

    if($multi > 1) {
      Alert('Select a pattern view for each template. Click on a linked pattern title to view that pattern with that view.');
      $display = '';
    } elseif($multi) {
      Alert('Select a pattern view for this template. Click on a linked pattern title to view that pattern with that view.');
      $display = '';
    } else {
      Alert('Click on a linked pattern title to view that pattern.');
      $display = ' style="display: none"';
    }
      
    print "<div class=\"featureform\" id=\"pview\"$display>
";

    foreach($pvsels as $pvsel)
      print $pvsel;

    print "</div>\n";
    
    if(strlen($alerts))
      Alert($alerts);

    print "<ul id=\"ice\">\n";
    foreach($titles as $title)
      print " <li><a data-pid=\"{$title['id']}\" data-ptid=\"{$title['ptid']}\" data-tname=\"{$templates[$title['ptid']]['name']}\">{$title['title']}</a></li>\n";
    print "</ul>
<p><a href=\"./\">Continue</a>.</p>

<style>
  .linklike {
    color: #339;
    text-decoration: underline;
  }
  .linklike:hover {
    cursor: pointer;
  }
</style>

<script>

    /* contain()
     *
     *  Handles 'click' events on anchors: if it has the 'linklike' class, act.
     */

    function contain(event) {
        el = event.target
        if(!el.classList.contains('linklike'))
            return false

        // what pattern is this?

        pid = el.dataset.pid

        // what template does the pattern use?
        
        ptid = el.dataset.ptid

        // what is the selected view for this template?
        
        sel = document.querySelector('#pvid-' + ptid)
        pvid = sel.value

        // call this script with query parameters

        window.open('viewpattern.php?id=' + pid + '.' + pvid)
        
    } // end contain()

    
    /* setpv()
     *
     *  Handles 'change' events on popup menus.
     */

    function setpv(event) {
        setpv2(event.target)
        
    } // end setpv()


    /* setpv2()
     *
     *  Given pattern_template.id and pattern_view.id values, set the class of
     *  anchors on implicated patterns duly.
     */

    function setpv2(el) {
        if(! (found = el.id.match(/^\D+(\d+)$/)))
            return false
        ptid = found[1]
        viewid = el.value
        if(state = (viewid != 0)) {
	    if(el.options)
	        vname = el.options[el.selectedIndex].text
	    else
	        vname = el.dataset.text
	} else
	    vname = ''
        for(anchor of anchors)
            if(anchor.dataset.ptid == ptid)
                ablef(anchor, state, vname)
    } /* end setpv2() */


    /* ablef()
     *
     *  Add or remove 'linklike' class from argument element and set title
     *  attribute.
     */

    function ablef(element, state, vname) {
        title = 'Template: ' + element.dataset.tname
	if(vname.length > 0)
	  title += '; View: ' + vname
        element.title = title
        if(state) // enable
            element.classList.add('linklike')
        else      // disable
            element.classList.remove('linklike')

    } // end ablef()
    
    /* anchors on pattern titles */

    const anchors = document.querySelectorAll('#ice li a')

    /* popup menus and hidden fields for setting views */
    
    const selvs = document.querySelectorAll('.selv')

    // set 'click' handlers on all the anchors

    if(anchors.length)
        for(anchor of anchors)
            anchor.addEventListener('click', contain)

    // set 'change' handlers on all the popup menus

    if(selvs.length)
        for(selv of selvs) {
            selv.addEventListener('change', setpv)
            setpv2(selv)
        }
  </script>
";

  } else {

    # select a pattern language
    
    $pls = GetPLs();
    $wpls = GetPLs(null, true);
    $selpl = '<select name="plid">
   <option value="0"></option>
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
  <input type=\"submit\" name=\"submit\" value=\"Accept\" id=\"accept\">
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>

</form>
<script>
</script>
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
 <option value="0"></option>
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

<div class=\"fname\">Select a feature to add:</div>
<div style=\"text-align: center\">$fmenu</div>

<div style=\"grid-column: span 2\">
 <input type=\"submit\" id=\"accept\" name=\"submit\" value=\"Accept\">
 <input type=\"submit\" id=\"accepta\" name=\"submit\" value=\"" . ANOTHER . "\">
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>

<script>
  /* mfaformf()
   *
   *  We don't want to accept template feature additions if a feature hasn't
   *  been selected.
   */

  function mfaformf() {
      submitState = featuresel.value != '0'
      accept.disabled = !submitState
      if(accepta)
          accepta.disabled = !submitState

  } /* end mfaformf() */

  featuresel = document.querySelector('#featuresel')
  featuresel.addEventListener('input', mfaformf)
  accept = document.querySelector('#accept')
  accepta = document.querySelector('#accepta')
  mfaformf()
</script>
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

function byname($a, $b) {
  return $a['name'] <=> $b['name'];
} /* end byname() */

function byfname($a, $b) {
  return $a['fname'] <=> $b['fname'];
} /* end byfname() */


/* ManagePatterns()
 *
 *  Manage patterns in this language.
 *
 *  We group patterns by template.
 */

function ManagePatterns($plid) {
  $pl = GetPL($plid);
  $templates = GetTemplates();
  usort($templates, 'bytitle');
  if(!isset($pl))
    Error("No pattern language with id $plid.");

  # Get the existing members.
  
  $plmembers = GetPLMembers($plid);

  print "<style>
  .linklike {
    color: #339;
    text-decoration: underline;
  }
  .linklike:hover {
    cursor: pointer;
  }
</style>

<h2>Managing Patterns for Pattern Language <code>{$pl['name']}</code></h2>

<p>A <em>pattern language</em> is a named set of patterns. Use this
form to add and remove patterns from this pattern language. Checkboxes
are already checked for those patterns that are already members of
this language. Patterns are grouped by pattern template.</p>

<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\" id=\"faform\">
 <input type=\"hidden\" name=\"pl\" value=\"eatme\">
 <input type=\"hidden\" name=\"plid\" value=\"$plid\">
 <div class=\"fh\">Pattern title</div>
 <div class=\"fh\">Membership</div>
";

  # Loop on templates.

  foreach($templates as $template) {
  
    # Get the patterns using this template. If none, skip, else sort by title.

    $patterns = GetPatterns(['ptid' => $template['id']]);
    if(!isset($patterns) || !count($patterns))
      continue;
    print " <div title=\"{$template['id']}\" class=\"fsub\" style=\"background-color: #eec\">{$template['name']}</div>\n";
    usort($patterns, 'bytitle');

    # Add an 'ismember' value to each pattern record.
  
    foreach($patterns as $k => $pattern)
      $patterns[$k]['ismember'] = array_key_exists($pattern['id'], $plmembers);

    # Loop on patterns in this template.
    
    foreach($patterns as $pattern) {
      $checked = $pattern['ismember'] ? ' checked="checked"' : '';
      print " <div class=\"antifa\">{$pattern['title']}</div>
  <div class=\"centrist\">
   <input type=\"checkbox\" name=\"{$pattern['id']}\"$checked>
  </div>
";
    }
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
  $omembers = GetPLMembers($plid);

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

  BeginTransaction();

  $pattern_id = $_REQUEST['id'];
  $pattern = GetPattern($pattern_id);
  $features = $pattern['features'];
  $fbyi = [];
  foreach($features as $feature)
    $fbyi[$feature['id']] = $feature;

  $update = [];
  $insert = [];
  $delete = [];

  # Loop on form fields, looking for feature value changes and additions.
  
  foreach($_REQUEST as $k => $v) {
  
    if(!preg_match('/^f-(.+)$/', $k, $matches))
      continue;
      
    $id = $matches[1];
    $feature = $fbyi[$id];

    if(isset($feature) && isset($feature['value'])) {

      # pattern has a value for this feature; changed?
      
      if($feature['value'] == $v)
        continue; # no change
      else
        $update[$id] = [
	  'pid' => $pattern_id,
	  'pfid' => $feature['id'],
	  'type' => $feature['type'],
          'value' => $v
        ];

    } elseif(isset($feature) && $feature['type'] == 'image' &&
             isset($feature['hash'])) {

      # feature is an existing image; alttext and/or hash and filename
      # may have changed

      $file = $_FILES[$id];
      $file['alttext'] = trim($v);
      if(!strlen($file['alttext']))
        Error('Provide a non-empty value for alternative text');

      if($haveUpload = ($file['error'] != NOFILE))
        $file = CheckFile($file);

      if(($haveUpload && $feature['hash'] != $file['hash']) ||
         ($feature['alttext'] != $v)) {
        $update[$name] = [
          'pid' => $pattern_id,
	  'pfid' => $feature['id'],
	  'type' => 'image',
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

        // 'image' feature values require upload and alternative text

        $file = $_FILES[$id];
        if($file['error'] == NOFILE)
          continue;
        $file['alttext'] = trim($_REQUEST[$k]);
	
        if($file = CheckFile($file))
          $insert[$name] = [
            'name' => $file['name'],
            'alttext' => $file['alttext'],
	    'fid' => $id,
            'hash' => $file['hash']
          ];
        else
          Alert("Failed to set a value for $name.");
      } else {
        if($feature['type'] == 'string' || $feature['type'] == 'text') {
          if(strlen($v))
            $insert[] = ['fname' => $name, 'value' => $v];
          } else  
            $insert[] = ['fname' => $name, 'value' => $v];
      }
    }
  } # end loop on features in form

  # Loop on existing pattern features, looking for deletions.

  foreach($features as $feature)
    if(array_key_exists("d-{$feature['id']}", $_REQUEST))
      $delete[] = $feature;

  # perform the pattern feature changes.
  
  UpdatePatternFeatures($pattern_id, $update, $insert, $delete);

  $did = (count($update) > 0) || (count($insert) > 0) || (count($delete) > 0);

  # update the pattern.note if needed

  if($_REQUEST['notes'] != $pattern['notes']) {
    UpdatePattern($pattern_id, $_REQUEST['notes']);
    $did = true;
  }
  CommitTransaction();
  return $did;
  
} /* end AbsorbPatternUpdate() */


/* AbsorbNewPattern()
 *
 *  Implement pattern insert, returning a pattern array with 'features'.
 */

function AbsorbNewPattern() {
  BeginTransaction();

  // collect the features specified in the form
  
  $template = GetTemplate($template_id = $_REQUEST['template_id']);

  $template_features = [];
  foreach($template['features'] as $tf)
    $template_features[$tf['id']] = $tf;
  
  $features = [];

  foreach($_REQUEST as $k => $v) {
    if(preg_match('/^f-(.+)$/', $k, $matches)) {

      # as a hack, feature names in the form start with "f-"

      $id = $matches[1];
      $features[$id] = $v;
    }
  }
  $notes = $_REQUEST['notes'];
  $template_id = $_REQUEST['template_id'];
  $pattern = InsertPattern($notes, $template_id);
  
  foreach($features as $fid => $fvalue) {
    $feature = $template_features[$fid];

    if($feature['type'] == 'image') {

      # handle a feature of type 'image'
      
      $file = $_FILES[$fid];
      if($haveUpload = ($file['error'] != NOFILE)) {
        $file['alttext'] = $fvalue;
        if($file = CheckFile($file))
          InsertFeatureValue([
            'pid' => $pattern['id'],
            'fid' => $fid,
            'alttext' => $fvalue,
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
        'fid' => $fid,
        'value' => $fvalue
      ]);
    }
  }
  CommitTransaction();
  return GetPattern($pattern['id']);
  
} /* end AbsorbNewPattern() */


/* TemplateMenu()
 *
 *  Return a popup menu of pattern_templates.
 */

function TemplateMenu($id = null, $multi = false) {
  $pts = GetTemplates();
  $tm = '<select id="template_id" name="template_id"' . ($multi ? ' multiple' : '') . '>
';
  if(!$multi)
    $tm .= " <option value=\"0\"></option>\n";

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
    $context = "<input type=\"hidden\" name=\"plid\" value=\"$id\">\n";

  } else {

    # Adding.

    $title = 'Add a Pattern Langage';
    $name_value = $notes_value = $delete = '';
    $tmenu = TemplateMenu(null, true);
    $notes = '<p>Enter a unique name and, optionally, notes for a new pattern language. If you select
one or more templates, you can add patterns that use those templates to be members of the language
in the next screen.</p>
';
  }
  print "<h2>$title</h2>

$notes

<form action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\">
 <input type=\"hidden\" name=\"pl\" value=\"absorb_pl\">
$context

 <div class=\"fname\">Pattern language name:</div>
 <div><input type=\"text\" name=\"name\"$name_value id=\"name\"></div>

 <div class=\"fname\">Notes:</div>
 <div>
  <textarea name=\"notes\" rows=\"3\" cols=\"80\">$notes_value</textarea>
 </div>

 <div class=\"fsub\">
  <input type=\"submit\" name=\"submit\" value=\"" . ADDPATTERNS . "\" id=\"accepta\">
  <input type=\"submit\" name=\"submit\" value=\"Accept\" id=\"accept\">
  $delete
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>

</form>

<script>
  function namef(e) {
    acceptel.disabled = (nameel.value.length > 0) ? false : true
    acceptael.disabled = (nameel.value.length > 0) ? false : true
  }
  nameel = document.querySelector('#name')
  acceptel = document.querySelector('#accept')
  acceptael = document.querySelector('#accepta')
  nameel.addEventListener('input', namef)
  namef()
</script>
";
  
} /* end PLForm() */


/* SelectPL()
 *
 *  Select a pattern language.
 */

function SelectPL($context) {
  $pls = GetPLs();
  $ppls = GetPLs(null, true); # pls with pattern counts
  
  $selpl = "<select name=\"plid\" id=\"plid\">
 <option value=\"0\"></option>
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

<script>
  function plidf(e) {
    submitstate = (plidel.value > 0) ? false : true  
    metadatael.disabled = submitstate
    managepel.disabled = submitstate
    delplel.disabled = submitstate
  }

  metadatael = document.querySelector('#metadata')
  managepel = document.querySelector('#managep')
  delplel = document.querySelector('#delpl')

  plidel = document.querySelector('#plid')

  plidel.addEventListener('change', plidf)
  plidf()
</script>
";

} /* end SelectPL() */


/* SelectPV()
 *
 *  Select a pattern view.
 */

function SelectPV($context) {
  $pvs = GetPVs();
  $pvsbyt = [];
  foreach($pvs as $pv)
    $pvsbyt[$pv['ptid']][] = $pv;
  
  $selpv = "<select name=\"pvid\" id=\"pvid\">
 <option value=\"0\"></option>
";
  foreach($pvsbyt as $ptid => $pvbyt) {
    $selpv .= " <optgroup label=\"{$pvbyt[0]['ptname']}\">\n";
    foreach($pvbyt as $pv)
      $selpv .= " <option value=\"{$pv['id']}\">{$pv['name']}</option>\n";
    $selpv .= " </optgroup>\n";
  }

  $submit = '';
  foreach($context['submit'] as $sub) {
    if(array_key_exists('id', $sub))
      $id = " id=\"{$sub['id']}\"";
    else
      $id = '';
    $submit .= "<input type=\"submit\" name=\"submit\" id=\"accept\" value=\"{$sub['label']}\"$id>\n";
  }
  $submit .= "<input type=\"submit\" name=\"submit\" value=\"Cancel\">\n";

  print "<h2>{$context['label']}</h2>\n";
  
  print "<form action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\" id=\"selectpv\">
 <input type=\"hidden\" name=\"{$context['context']}\" value=\"{$context['action']}\">

 <div class=\"fname\">Select a pattern view:</div>
 <div>$selpv</div>
 
 <div class=\"fsub\">
  $submit
 </div>
</form>

<script>
  function pvidf(e) {
    accept.disabled = pvid.value == '0'
  }
  pvid = document.querySelector('#pvid')
  pvid.addEventListener('change', pvidf)
  accept = document.querySelector('#accept')
  pvidf()
</script>
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
    $delete = '<input type="submit" name="submit" value="Delete">';
    $title = 'Edit Pattern View';
    $context = "<input type=\"hidden\" name=\"pvid\" value=\"$id\">\n";
    $tmenu = TemplateMenu($pv['ptid']);
    if($l = strlen($pv['layout']))
      $existing = "(<a href=\"?dlpvl={$pv['id']}\" title=\"download this layout\">$l characters</a>)";
  } else {

    // adding a pattern view
    
    $title = 'Add Pattern View';
    $name_value = $notes_value = $delete = '';
    $tmenu = TemplateMenu();
    $existing = '(undefined)';
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

<form enctype=\"multipart/form-data\" action=\"{$_SERVER['SCRIPT_NAME']}\" method=\"POST\" class=\"featureform\">
 <input type=\"hidden\" name=\"pv\" value=\"absorb_pv\">
 $context

 <div class=\"fname\">Pattern view name:</div>
 <div><input type=\"text\" name=\"name\"$name_value\" id=\"pvname\"></div>

 <div class=\"fname\">Notes:</div>
 <div>
  <textarea name=\"notes\" rows=\"3\" cols=\"80\">$notes_value</textarea>
 </div>

 <div class=\"fname\">Pattern template:</div>
 <div>$tmenu</div>
 
 <div class=\"fname\">Layout file:</div>
 <div><input name=\"layout\" type=\"file\">$existing</div>

 <div class=\"fsub\">
  <input type=\"submit\" name=\"submit\" value=\"Accept\" id=\"accept\">
  $delete
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
 </div>

</form>

<script>

  /* sub()
   *
   *  Enable the submit button if there is a name value and a selected
   *  template.
   */
   
  function sub(event) {
    enable = (pvname.value.length > 0) && (template_id.value > 0)
    accept.disabled = ! enable
  } 
  
  var pvname = document.querySelector('#pvname')
  pvname.addEventListener('input', sub)
  var template_id = document.querySelector('#template_id')
  template_id.addEventListener('change', sub)
  var accept = document.querySelector('#accept')
  sub()

</script>
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

  if($layout && strlen($layout))
    ValidateView($layout, $pt);

  if(isset($_REQUEST['pvid'])) {

    # working on an update
    
    $pvid = $_REQUEST['pvid'];
    $pv = GetPV($pvid);
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


/* Tokens($layout, $template)
 *
 *  Loop on token matches in the layout, returning the found token names.
 */

function Tokens($layout) {
  $tokens = [];
  $offset = 0;
  while(preg_match(TOKENMATCH, $layout, $matches, PREG_OFFSET_CAPTURE,
        $offset)) {
    $token = $matches[1][0];
    $tokens[$token] = $token;
    $offset = $matches[1][1] + strlen($token) + 2;
  }
  return($tokens);

} /* end Tokens() */


/* ValidateView()
 *
 *  Warn about detected problems in the pattern view.
 */

function ValidateView($layout, $template) {

  # Get the tokens found in the layout.
  
  $tokens = Tokens($layout);
  
  # Find feature names found/not found in the template and features found in
  # the template not used in the layout.

  $features = $template['features'];
  $found = [];   # found in both
  $orphans = []; # found only in layout
  $unused = [];  # found only in template

  foreach($tokens as $token)
    if(preg_match('/^(.+)-alttext$/', $token, $matches))
      $token = $matches[1]; // treat FEATURENAME-alttext as FEATURENAME
    if(array_key_exists($token, $features))
      $found[$token] = true;
    else
      $orphans[$token] = true;

  foreach($features as $feature) {
    if(!array_key_exists($feature['name'], $tokens))
      $unused[$feature['name']] = $feature['name'];
  }

  # Report on token usage.

  $status = true;
  if(count($found)) {
    Alert("These feature tags found in the layout are in the <code>{$template['name']}</code> template: <code>" .
      implode('</code> , <code>', $tokens) . '</code>');
  }
  if(count($orphans)) {
    Alert("These feature tags found in the layout are not in the <code>{$template['name']}</code> template: <code>" .
      implode('</code> , <code>', array_keys($orphans)) . '</code>');
    $status = false;
  }
  if(count($unused)) {
    Alert("These feature tags found in the <code>{$template['name']}</code> template are not used in the layout: <code>" .
      implode('</code> , <code>', $unused) . '</code>');
  }
  return $status;
  
} /* end ValidateView() */


/* Status()
 *
 *  Display all the data on a single screen.
 */

function Status() {

  # pattern_templates: name, pattern count, feature count, view names.
  
  $templates = GetTemplates();

  print '<style type="text/css">
 .some {
   display: grid;
   width: max-content;
   max-width: 95vw;
   margin-left: 1em;
   border: 1px solid #300;
   background-color: white;
 }
 .foursome {
   grid-template-columns: repeat(4, auto);
 }
 .threesome {
   grid-template-columns: repeat(3, auto);
 }
 .somehead {
  background-color: #ffd;
  font-weight: bold;
  text-align: center;
 }
 .some div {
  padding: .1em;
  border: 1px solid #ccc;
  text-align: center;
 }
</style>

<h1>System Summary</h1>

<ul>
 <li><a href="#templates">Pattern Templates</a></li>
 <li><a href="#views">Pattern Views</a></li>
 <li><a href="#languages">Pattern Languages</a></li>
 <li><a href="#features">Pattern Features</a></li>
</ul>

<h2 id="templates">Pattern Templates</h2>

<p>A pattern template is a named collection of pattern features that is used
to specify which features a pattern must or may have and which features
a pattern view may support.</p>

<div class="some foursome">
  
 <div class="somehead">Name</div>
 <div class="somehead">Features</div>
 <div class="somehead">Patterns</div>
 <div class="somehead">Views</div>
';

  # Loop on templates, displaying a table. $pvt is an array, keyed on
  # template name, with values that are arrays of pattern views using
  # that template.

  $pvt = [];
  foreach($templates as $id => $template) {
    $pvs = GetPVs(['ptid' => $template['id']]);
    $pvt[$template['name']] = $pvs;
    $pvnames = [];
    foreach($pvs as $pv)
      $pvnames[] = $pv['name'];
    if(count($pvs))
      $pvstring = implode(', ', $pvnames);
    else
      $pvstring = '(none)';
    print " <div title=\"id {$template['id']}\">{$template['name']}</div>
 <div style=\"text-align: center\">{$template['fcount']}</div>
 <div style=\"text-align: center\">{$template['pcount']}</div>
 <div>$pvstring</div>

";
  }
  print '</div>

<h2 id="views">Pattern Views</h2>

<p>Pattern views are named and are associated with a template and a layout
file that specifies how to display feature values.</p>

<div class="some threesome">

 <div class="somehead">View Name</div>
 <div class="somehead">Template Name</div>
 <div class="somehead">Feature Names</div>

';

  foreach($pvt as $tname => $pvs) {
    foreach($pvs as $pv) {
      if(strlen($pv['layout'])) {
        $tokens = Tokens($pv['layout']);
        $fstring = implode(', ', $tokens);
      } else
        $fstring = '(none)';
      print " <div>{$pv['name']}</div>
 <div>$tname</div>
 <div>$fstring</div>

";
    }
  }
  
  print '</div>

<h2 id="languages">Pattern Languages</h2>

<p>Pattern languages are named collections of patterns.</p>

<div class="some threesome">

 <div class="somehead">Language name</div>
 <div class="somehead">Template name</div>
 <div class="somehead">Pattern Titles</div>

';
  # Loop on languages.

  $pls = GetPLs();
  usort($pls, 'byname');
  foreach($pls as $pl) {
    $plmembers = GetPLMembers($pl['id']);
    $pbyt = [];
    if(count($plmembers))
      foreach($plmembers as $plmember)
        $pbyt[$plmember['ptid']][] = $plmember;

    # Loop on templates.
    
    $plp = [];
    
    foreach($templates as $template) {
      $patterns = isset($pbyt[$template['id']]) && count($pbyt[$template['id']])
        ? $pbyt[$template['id']] : [];
      if(count($patterns)) {
        usort($patterns, 'bytitle');
        $pstring = '';
        foreach($patterns as $pattern)
          $pstring .= strlen($pstring) ? ", {$pattern['title']}" : $pattern['title'];
        $plp[$template['name']] = $pstring;
      }
    }
    if(count($plp))
      if(count($plp) > 1)
        $grs = ' style="grid-row: span ' . count($plp) . '"';
      else
        $grs = '';
    print " <div$grs>{$pl['name']}</div>\n";
    if(count($plp)) {
      foreach($plp as $tname => $pstring)
        print " <div>$tname</div> \n <div style=\"text-align: left\">$pstring</div>\n";
    } else
      print " <div style=\"grid-column: span 2\">(none)</div>\n";
  }
  print '</div>

<h2 id="features">Pattern Features</h2>

<p>Patterns are each a collection of feature values. Each pattern uses
a template to define what features for which it might (and those for
which it must) have values. The table below lists all the features, their
types, the templates that include them, and the number of values that the
exist, per template.</p>

<div class="some foursome">

 <div class="somehead">Name</div>
 <div class="somehead">Type</div>
 <div class="somehead">Template</div>
 <div class="somehead">Value Count</div>
';

  # For each feature, display the name, data type, the templates that refer to it,
  # and the per-template value count, 
  
  $features = GetFeatures();
  usort($features, 'byname');
  foreach($features as $feature) {
    $stats = FeatureStats($feature['id']);
    if(count($stats) > 1)
      $grs = ' style="grid-row: span ' . count($stats) . '"';
    else
      $grs = '';
    print " <div$grs>{$feature['name']}</div>
 <div$grs>{$feature['type']}</div>
";
    foreach($stats as $stat)
      print " <div>{$stat['name']}</div>
 <div>{$stat['count']}</div>\n\n";
  }
  print "</div>

<p><a href=\"{$_SERVER['SCRIPT_NAME']}\">Continue.</a></p>
";
  
} /* end Status() */


if(isset($_REQUEST['dlpvl'])) {
  $pvid = $_REQUEST['dlpvl'];

  // user clicked on link to download a pattern_view.layout

  if(($pv = GetPV($pvid)) && strlen($pv['layout'])) {
    $filename = "{$pv['name']}.layout";
    $length = strlen($pv['layout']);
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=$filename;");
    header('Content-Transfer-Encoding: binary');
    header("Content-Length: $length");
    print $pv['layout'];
  }
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
 <meta charset="utf-8">
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
} elseif(isset($_REQUEST['summary'])) {

  # Display a summmry of data.

  Status();
  $SuppressMain = true;
  
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
    $SuppressMain = true;

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
    if($_REQUEST['submit'] == 'Delete') {
      if(DeletePattern($_REQUEST['id']))
        Alert('Deleted pattern.');
      else
        Alert('Pattern deletion failed.');
    } elseif(AbsorbPatternUpdate())
      Alert('Updated pattern.');
    else
      Alert('No update.');
  } elseif($action == 'absorb_add') {

    # absorbing pattern addition
    
    $pattern = AbsorbNewPattern();
    Alert("Created new pattern with title <em>{$pattern['features']['title']['value']}</em>, id {$pattern['id']}");
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
    if($_REQUEST['submit'] == 'Delete')
      DeletePV($_REQUEST['pvid']);
    else
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
        } elseif($_REQUEST['submit'] == 'Delete') {
          if(DeletePL($plid))
            Alert('Deleted pattern language');
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
            'id' => 'metadata'
          ],
          [
            'label' => 'Delete',
            'id' => 'delpl'
          ],
          [
            'label' => 'Manage patterns',
            'id' => 'managep'
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
      if($_REQUEST['submit'] == ADDFEATURES) {
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

<h2>Documentation</h2>

<ul>
 <li><a href="?summary=1">System summary</a></li>
 <li><a href="README.html">README</a></li>
</ul>

</div>
<?php
}
?>
<?=FOOT?>
</body>
</html>
