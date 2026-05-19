<?php
// ============================================================
// PapeLESS - API: Notifications Handler
// ============================================================
require_once __DIR__.'/../includes/functions.php';

if(!isLoggedIn()) jsonResponse(['success'=>false,'error'=>'Unauthorized.'],401);

$action=$_POST['action']??'';

if($action==='mark_read'){
    markNotificationsRead($_SESSION['user_type'],$_SESSION['user_id']);
    jsonResponse(['success'=>true]);
}

jsonResponse(['success'=>false,'error'=>'Invalid action.'],400);
