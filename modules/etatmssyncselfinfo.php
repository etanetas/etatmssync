<?php
header('Content-Type: application/json');

$selfId = Auth::GetCurrentUser();

$self = $LMS->GetUserInfo($selfId);

if(!$self){
  http_response_code(404);
  $self = array();
}

$rights = $LMS->GetUserRights($selfId);

$resp = array(
  "id" => $self['id'],
  "name" => $self['name'],
  "lastname" => $self['lastname'],
  "login" => $self['login'],
  "rights" => $rights,
);

echo json_encode($resp);
?>