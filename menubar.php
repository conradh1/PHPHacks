<?php
/**
* menubar.php
* Implement a dynamic menu bar.
*/
?>

<?php
  // Used to find out what page we are on so that it matches
  $current_page = $_SERVER["SCRIPT_NAME"];
  $current_page = preg_replace ('/\/.*\//', '', $current_page);  # Get rid of any subdirectories

function getBgColor($curPage, $pages) {
  $len = sizeof($pages);
  for ($i = 0; $i < $len; $i ++) {
    if ($pages[$i] == $curPage) return " style='background-color: #546EA8;' ";
  }
  return "";
}

?>

<div style="width: 100%; height: 32px; background: #dde;" class="menubar">
  <ul class="dropdown dropdown-horizontal">
    <li class="dir" onclick="javascript:onIDevice('psps');" <?=getBgColor($current_page, array("index.php", "profile.php","home.php"))?>>Main
        <ul>
          <li <?=getBgColor($current_page, array("home.php"))?>><a href="home.php">Index Map</a></li>
          <li><a href="index.php">News</a></li>
          <li><a href="<?=$PSISession->getHelpWikiURL()?>" target="_blank">Help</a></li>
          <li><a href="profile.php">Edit Profile</a></li>
          <li><a href="logout.php">Logout</a></li>
        </ul>
    </li>

    <li <?=getBgColor($current_page, array("query_page.php"))?>><a href="query_page.php">Query Page</a></li>
    <li <?=getBgColor($current_page, array("queued.php"))?>><a href="queued.php">Queued Jobs</a></li>
    <li <?=getBgColor($current_page, array("mydb.php"))?>><a href="mydb.php">MyDB</a></li>
    <li <?=getBgColor($current_page, array("graph.php"))?>><a href="graph.php">Graphing</a></li>
    <li class="dir" onclick="javascript:onIDevice('psps');" <?=getBgColor($current_page, array("postage_stamp.php", "postage_stamp_reqlist.php", "postage_status_status.php", "postage_stamp_releases.php"))?>>Postage Stamp
        <ul>
            <li><a href="postage_stamp.php">Request Form</a></li>
            <li><a href="postage_stamp_results.php">Results Page</a></li>
            <li><a href="postage_stamp_status.php">Server Status</a></li>
            <li><a href="postage_stamp_releases.php">Summary of Releases</a></li>
        </ul>
    </li>
    <li class="dir" onclick="javascript:onIDevice('psps');" <?=getBgColor($current_page, array("query_builder_step1.php", "query_builder_step2.php","query_builder_step3.php", "query_builder_step4.php" ))?>> &nbsp; PSPS &nbsp;
      <ul id='ul_psps'>
        <li><a href="query_builder_step1.php">PSPS Query Builder</a></li>
        <li><a href="javascript:openNewPage('schema_browser.php' );">PSPS Schema Browser</a></li>
      </ul>
    </li>
    <li class="dir" onclick="javascript:onIDevice('mops');" <?=getBgColor($current_page, array("mops_query_builder_step1.php", "mops_query_builder_step2.php","mops_query_builder_step3.php", "mops_query_builder_step4.php", "mops_view_neo_report.php" ))?>> &nbsp; MOPS &nbsp;
      <ul id='ul_mops'>
        <li><a href="mops_query_builder_step1.php">MOPS Query Builder</a></li>
        <li><a href="<?=$PSISession->getMopsSchemaURL()?>" target="_blank">MOPS Schema Browser</a></li>
        <li><a href="mops_view_neo_report.php?type=SubmittedNeos">250 most recent PS1 NEO submissions</a></li>
        <li><a href="mops_view_neo_report.php?type=DiscoveredNeos">PS1 NEO Discoveries</a></li>
      </ul>
    </li>
  </ul>
</div>

<script type="text/javascript" language="javascript">
// For compatibility with mobile devices.
function onIDevice(o) {
  if (! ( navigator.userAgent.match(/iPhone/i) ||
          navigator.userAgent.match(/iPod/i) ||
          navigator.userAgent.match(/iPad/i) ||
          navigator.userAgent.match(/android/i) ) ) return true;

  var u = document.getElementById('ul_psps');
  var v = document.getElementById('ul_mops');
  if (o == 'psps') {
    toggleDropdownMenu(u, v);
  } else if (o == 'mops') {
    toggleDropdownMenu(v, u);
  }

  return true;
}

function toggleDropdownMenu(u, v) {
  v.style.visibility = 'hidden';

  u.style.left = u.parentNode.offsetLeft + 'px';
  if (u.style.visibility == 'visible') {
    u.style.visibility = 'hidden';
  } else {
    u.style.visibility = 'visible';
  }
}
</script>
