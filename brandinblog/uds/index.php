if (window['google'] != undefined && window['google']['loader'] != undefined) {
if (!window['google']['visualization']) {
window['google']['visualization'] = {};
google.visualization.Version = '1.0';
google.visualization.JSHash = '351cbc565e06280bb093b00ce39323d9';
<?php
if ($_REQUEST['file'] == 'visualization' && $_REQUEST['v'] == 1 && $_REQUEST['packages'] == 'geochart') {
?>
google.visualization.LoadArgs = 'file\75visualization\46v\0751\46packages\75geochart';
}
google.loader.writeLoadTag("script", google.loader.ServiceBase + "/api/visualization/1.0/351cbc565e06280bb093b00ce39323d9/format+en,default,geochart.I.js", false);
<?php
} else if ($_REQUEST['file'] == 'visualization' && $_REQUEST['v'] == 1 && $_REQUEST['packages'] == 'corechart') {
?>
google.visualization.LoadArgs = 'file\75visualization\46v\0751\46packages\75corechart';
}
google.loader.writeLoadTag("script", google.loader.ServiceBase + "/api/visualization/1.0/351cbc565e06280bb093b00ce39323d9/corechart.js", false);
<?php
} else if ($_REQUEST['file'] == 'visualization' && $_REQUEST['v'] == 1 && $_REQUEST['packages'] == 'controls') {
?>
google.visualization.LoadArgs = 'file\75visualization\46v\0751\46packages\75controls';
}
google.loader.writeLoadTag("css", google.loader.ServiceBase + "/api/visualization/1.0/351cbc565e06280bb093b00ce39323d9/controls.css", false);
google.loader.writeLoadTag("script", google.loader.ServiceBase + "/api/visualization/1.0/351cbc565e06280bb093b00ce39323d9/controls.js", false);
<?php
} else if ($_REQUEST['file'] == 'visualization' && $_REQUEST['v'] == 1 && $_REQUEST['packages'] == 'table') {
?>
google.visualization.LoadArgs = 'file\75visualization\46v\0751.0\46packages\75table\46async\0752\46sig\075351cbc565e06280bb093b00ce39323d9';
}
google.loader.writeLoadTag("script", google.loader.ServiceBase + "/api/visualization/1.0/351cbc565e06280bb093b00ce39323d9/table.js", false);
<?php
} else if ($_REQUEST['file'] == 'visualization' && $_REQUEST['v'] == 1 && $_REQUEST['packages'] == 'annotatedtimeline') {
?>
google.visualization.LoadArgs = 'file\75visualization\46v\0751\46packages\75annotatedtimeline';
}
google.loader.writeLoadTag("script", google.loader.ServiceBase + "/api/visualization/1.0/351cbc565e06280bb093b00ce39323d9/format+en,default,annotatedtimeline+en_US.I.js", false);
<?php
}
?>
}
