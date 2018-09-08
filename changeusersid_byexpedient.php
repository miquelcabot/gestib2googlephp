<?php
    include 'login.php';
    require_once 'api/client.php';
    require_once 'api/domainuser.php';
    require_once 'api/xmlfile.php';
    require_once 'api/domainread.php';
    require_once 'api/domainoperations.php';
  
    // Echo while every long loop iteration
    while (@ob_end_flush());      
    ob_implicit_flush(true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="Miquel A. Cabot">
  <title><?php echo APPLICATION_NAME;?></title>
</head>

<body>
<?php
    $apply = FALSE;

    $client = getClient();
    $service = new Google_Service_Directory($client);

    $xml = simplexml_load_file("exportacioDadesCentre_borja.xml");
    $xmlusers = readXmlFile($xml);

    $domainusers = readDomainUsers();
    
    $contador = 0;
    $total = 0;
    foreach($domainusers as $key=>$domainuser) {
        $domainid = $domainuser->id;
        $ambid = FALSE;
        if (substr( strtolower($domainid), 0, 2 ) === "id") {
            $domainid = substr( strtolower($domainid), 2, 5 );
            $ambid = TRUE;
        }
        $total++;
        foreach($xmlusers as $xmlkey=>$xmluser) {
            if ($xmluser->expedient==$domainid) { // tenen el mateix numero expedient
                if ($xmluser->id!=$domainuser->id) {
                    if ($ambid) {
                        echo "---------------------------<br>";
                    }
                    echo "DOMAIN: ";
                    echo $domainuser->id." - ";
                    echo $domainuser->surname.", ".$domainuser->name." - ";
                    echo $domainuser->domainemail."<br>";
                    echo "XML: ";
                    echo $xmluser->expedient." - ";
                    echo $xmluser->surname.", ".$xmluser->name." - ";
                    echo $xmluser->domainemail."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                    echo "NEW ID: ".$xmluser->id."<br>";
                    $contador++;
                    if ($apply) {
                        $userObj = new Google_Service_Directory_User(
                            array(
                                'orgUnitPath' => '/Alumnat',
                                'externalIds' => array(array("type" => 'organization', "value" => $xmluser->id)),
                            )
                        );
                        $service->users->update($domainuser->email(), $userObj);
                    }
                }
            }
        }
    }
    echo "TROBATS: ".$contador."<br>";
    echo "TOTAL: ".$total;
    
    //$cont = applyDomainChanges($xmlusers, $domainusers, $apply);

    /*if ($apply) {
        echo($cont['deleted']." users have been suspended<br>\r\n");
        echo($cont['created']." users have been created<br>\r\n");
        echo($cont['activated']." users have been activated<br>\r\n");
        echo($cont['groupsmodified']." users have been changed their group membership<br>\r\n");
    } else {
        echo($cont['deleted']." users will be suspended<br>\r\n");
        echo($cont['created']." users will be created<br>\r\n");
        echo($cont['activated']." users will be activated<br>\r\n");
        echo($cont['groupsmodified']." users will change their group membership<br>\r\n"); 
    }*/
      
?>
</body>

</html>