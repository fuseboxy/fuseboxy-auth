<?php
// flash
ob_start();
include 'app/view/global/layout.flash.php';
$authLayout['flash'] = ob_get_clean();


// box
ob_start();
$layout['panelTitle'] = 'Fusebox Tiny Demo<br /><small>Admin Console</small>';
include 'app/view/auth/panel.php';
$layout['content'] = ob_get_clean();


// layout
include 'app/view/global/layout.basic.php';