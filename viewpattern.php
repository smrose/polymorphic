<?php
/* NAME
 *
 *  viewpattern.php
 *
 * CONCEPT
 *
 *  Display a pattern in a container.
 *
 * NOTES
 *
 *  Call this script with these query parameters:
 *
 *    action  if 'view', display the pattern; if empty, display a container
 *       pid  a value of pattern.id
 *      pvid  a value of pattern_view.id
 *
 *  We use an iframe to wrap the rendered pattern with a cluse button at the
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
    $token = $matches[1];
    $tmatch = $matches[0];
    $fv = $features[$token]['value'];
    $layout = str_replace($tmatch, $fv, $layout);
  }
  # Display it.
  
  print $layout;
  exit();
  
} /* end DisplayPattern() */


$pv = GetPV('id', $pvid = $_REQUEST['pvid']);
$pattern = GetPattern($pid = $_REQUEST['pid']);
$action = $_REQUEST['pattern'];

if(!$pv || !$pattern)
  print '<!doctype html>
<html>
<head>
 <title>System Error</title>
</head>
<body>
 <h1>System Error</h1>
</body>
</html>
';

if($_REQUEST['action'] == 'view') {

  # process the pattern in this view and print it
  
  DisplayPattern($pattern, $pv);
  exit();

} else {

  # emit a container with an iframe and a close button

  $url = "?action=view&pid=$pid&pvid=$pvid";
  $title = $pattern['features']['title']['value'];
?>
<!doctype html>
<html>
  <head>
    <title><?=$title?></title>
    <style>
      body {
	  font-family: sans-serif;
	  background-color: #eee;
      }
      #contain {
	  height: 90vh;
	  width: 96vw;
	  border: 1px solid black;
	  margin-left: 2vw;
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

  <body onload="init()">
    <iframe id="contain" title="iframe example" src="<?=$url?>"></iframe>
    <div id="closeme">Close</div>
  </body>

</html>
<?php
}
exit();

