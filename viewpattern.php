<?php
/* NAME
 *
 *  viewpattern.php
 *
 * CONCEPT
 *
 *  Display patterns in a container.
 *
 * NOTES
 *
 *  Call this script with these query parameters:
 *
 *    action  if 'view', display the single pattern using the specified view; if
 *            empty emit a container with with an iframe for each pattern
 *
 *        id  values of <pattern.id> . '.' . <pattern_view.id>, comma-separated
 *
 *  We use an iframe to wrap the rendered patterns with a close button at the
 *  bottom of the viewport.
 */
 
require 'pps.php';
DataStoreConnect();


/* DisplayPattern()
 *
 *  Render this pattern with this pattern view.
 */

function DisplayPattern($pattern, $pv) {
  $layout = $pv['layout'];
  $features = $pattern['features'];

  # Loop on the layout string, replacing tokens with values, until the
  # last token is replaced.

  while(preg_match(TOKENMATCH, $layout, $matches, 0)) {

    $fname = $matches[1]; // the token that was found, e.g. 'title'
    $tmatch = $matches[0]; // the substring that was matched, e.g. '%%title%%'

    if(preg_match('/^(.+)-alttext$/', $fname, $matches)) {

      /* this token is for alternative text */
      
      $fname = $matches[1]; // actual feature name
      $alttext = true;
    } else
      $alttext = false;
      
    $feature = $features[$fname];

    if($feature['type'] == 'image') {
      if($alttext)
        $fvalue = $feature['alttext'];
      else
    
        // what we have is a hash, which needs to be converted to a URL

        $fvalue = ImagePath($feature['hash']);
    } else
        $fvalue = $feature['value'];

    // replace the token with the value

    $layout = str_replace($tmatch, $fvalue, $layout);
    
  } // end loop on token search
  
  # Display it.
  
  print $layout;
  exit();
  
} /* end DisplayPattern() */


/* serror()
 *
 *  It went sideways.
 */

function serror($msg) {
  print "<!DOCTYPE html>
<html lang=\"en\">
<head>
 <meta charset=\"utf-8\">
 <title>System Error</title>
 <style>
  .error {
    font-weight: bold;
    font-size: 16pt;
    color: #600;
  }
 </style>
</head>
<body>
 <h1>System Error</h1>

 <p class=\"error\">$msg</p>
</body>
</html>
";
  exit();
  
} /* end serror() */


# Main program.

if(!isset($_REQUEST['id']))
  serror('No patterns specified');

if(!(count($ids = explode(',', $_REQUEST['id']))))
  serror('Pattern/view specification is malformed');

# Build $d[], an array of arrays each with 'pid' and 'pvid' fields.

$d = [];
foreach($ids as $id) {
  if(!preg_match('/^(\d+)\.(\d+)$/', $id, $matches))
    serror('Faulty query parameters');
  $d[] = [
    'pid' => $matches[1],
    'pvid' => $matches[2]
  ];
}
  
if($_REQUEST['action'] == 'view') {

  # display this pattern
  
  if(count($d) != 1)
    serror('Specify a single pattern and view for display');
    
  if(!($pattern = GetPattern($pid = $d[0]['pid'])))
    serror("No pattern with id $pid was found");
  if(!($pview = GetPV($pvid = $d[0]['pvid'])))
    serror("No pattern view with id $pvid was found");

  DisplayPattern($pattern, $pview);
  exit();

} else {

  # Emit a container with an iframe for each pattern and a close button
  # at the bottom of the page.

print "<!DOCTYPE html>
<html lang=\"en\">
<head>
  <meta charset=\"utf-8\">
  <title>Patterns</title>
  <style>
      body {
          font-family: sans-serif;
          background-color: #eee;
      }
      .contain {
          height: 90vh;
          width: 98vw;
          border: 1px solid black;
          margin-left: 1vw;
          background-color: white;
      }
      #closeme {
          margin-top: 2vh;
          padding: .5em;
          width: max-content;
          border: 1px solid black;
          background-color: #ddd;
          left: 50%;
          position: fixed;
          font-weight: bold;
      }
      #closeme:hover {
          cursor: pointer;
          background-color: #eee;
      }
  </style>

  <script>
      function init() {
          document.querySelector('#closeme').addEventListener('click', () => {
              window.close()
          })
      }
  </script>
    
</head>

<body onload=\"init()\">
";
  foreach($d as $p) {
    $url = "{$_SERVER['SCRIPT_NAME']}?action=view&id={$p['pid']}.{$p['pvid']}";
    $title = $pattern['features']['title']['value'];
    print "      <iframe class=\"contain\" src=\"$url\"></iframe>
";
  }
  print "      <div id=\"closeme\">Close</div>
</body>
</html>
";
}
exit();

