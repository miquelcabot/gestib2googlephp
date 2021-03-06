<?php
require_once 'client.php';
require_once 'domainuser.php';

function deleteDomainUsers($xmlusers, $domainusers, $apply, $service, $selectedgroup, $onlyteachers) {
    $cont = 0;
    echo("===============================<br>\r\n");
    echo("ESBORRANT USUARIS DEL DOMINI...<br>\r\n");
    echo("===============================<br>\r\n");
    foreach ($domainusers as $domainuser) {     // Per cada usuari del domini
        if (!$domainuser->suspended && !$domainuser->withoutcode) {
            if (!array_key_exists($domainuser->id, $xmlusers)) {
                if (empty($selectedgroup)) {
                    $group_ok = TRUE;
                } else {
                    $group_ok = FALSE;
                    foreach ($domainuser->groups as $group) {
                        if ((strpos($group, $selectedgroup) !== FALSE && strpos($group, $selectedgroup) == 0)) {
                            $group_ok = TRUE;
                        }
                    }
                }
                if ($group_ok) { // Aplicar només al grup seleccionat
                    if (!$onlyteachers || $domainuser->teacher) {
                        if (in_array($domainuser->organizationalUnit, ['/', TEACHERS_ORGANIZATIONAL_UNIT, STUDENTS_ORGANIZATIONAL_UNIT])) {

                            // No eliminam professors del @iesfbmoll.org
                            if (in_array($domainuser->organizationalUnit, ['/', TEACHERS_ORGANIZATIONAL_UNIT]) && strpos($domainuser->email(),"@iesfbmoll.org")) {
                                // No fer res.
                                // No eliminam professors del @iesfbmoll.org
                            } else {

                                // Aplicar només les unitats organitzatives 'Professorat', 'Alumnat' i '/'
                                echo("SUSPENDRE --> ".$domainuser."<br>\r\n");
                                $cont++;
                                if ($apply) {
                                    // Suspendre l'usuari del domini
                                    $userObj = new Google_Service_Directory_User(
                                        array(
                                            'suspended' => TRUE
                                        )
                                    );
                                    $service->users->update($domainuser->email(), $userObj);
                                    // Eliminar tots els grups
                                    foreach ($domainuser->groupswithdomain() as $groupwithdomain) {
                                        // https://developers.google.com/admin-sdk/directory/v1/reference/members/delete
                                        $service->members->delete($groupwithdomain, $domainuser->email());
                                    }
                                }
                                
                            }
                        }
                    }
                }
            }
        }
    }
    return $cont;
}

function addDomainUsers($xmlusers, $domainusers, $domaingroupsmembers, $apply, $service, $selectedgroup, $onlyteachers) {
    $contc = 0;
    $conta = 0;
    $contm = 0;
    $conto = 0;
    $contg = 0;
  
    echo("=============================<br>\r\n");
    echo("AFEGINT USUARIS DEL DOMINI...<br>\r\n");
    echo("=============================<br>\r\n");
    foreach ($xmlusers as $xmluser) {     // Per cada usuari de l'XML
        if (!array_key_exists($xmluser->id, $domainusers)) {  // Si no existeix al domini...
            if (empty($selectedgroup)) {
                $group_ok = TRUE;
            } else {
                $group_ok = FALSE;
                foreach ($xmluser->groups as $group) {
                    if ((strpos($group, $selectedgroup) !== FALSE && strpos($group, $selectedgroup) == 0)) {
                        $group_ok = TRUE;
                    }
                }
            }
            if ($group_ok) { // Aplicar només al grup seleccionat
                if (!$onlyteachers || $xmluser->teacher) {
                    // Email pot ser repetit, comprovar-ho!!
                    if (!$xmluser->teacher && LONG_STUDENTS_EMAIL===FALSE) {  // Short email
                        foreach ($domainusers as $d_user) {
                            // Si hi ha un usuari del domini amb les 3 primeres lletres iguals
                            if (mb_substr($d_user->email(),0,3)===mb_substr($xmluser->email(),0,3)) {
                                $n_email_dom = intval(mb_substr($d_user->email(),3,5));
                                $n_email_xml = intval(mb_substr($xmluser->email(),3,5));
                                if ($n_email_dom>=$n_email_xml) {
                                    $n_email = $n_email_dom+1;
                                    $xmluser->domainemail = mb_substr($xmluser->email(),0,3).str_pad($n_email, 2, '0', STR_PAD_LEFT)."@".DOMAIN;
                                }
                            }
                        }
                    } elseif (!$xmluser->teacher && LONG_STUDENTS_EMAIL==='2surnames') {        // Email amb dos llinatges
                        // Primer, provam m.cabotnadal
                        $emailok = TRUE;
                        $newemail = normalizedname(mb_substr($xmluser->name,0,1)) .
                          "." . 
                          normalizedname($xmluser->surname1) . 
                          normalizedname($xmluser->surname2) . 
                          "@".DOMAIN;
                        foreach ($domainusers as $d_user) {
                          // Si hi ha un usuari del domini amb el mateix email
                          if ($d_user->email()===$newemail) {
                            $emailok = FALSE;
                          }
                        }
                        // Finalment, m.cabotnadal2, m.cabotnadal3...
                        if (!$emailok) {
                            $emailnumber = 1;
                            while (!$emailok) {
                                $emailok = TRUE;
                                $emailnumber++;
                                $newemail = normalizedname(mb_substr($xmluser->name,0,1)) .
                                  "." . 
                                  normalizedname($xmluser->surname1) . 
                                  normalizedname($xmluser->surname2) . 
                                  $emailnumber .
                                  "@".DOMAIN;
                                foreach ($domainusers as $d_user) {
                                  // Si hi ha un usuari del domini amb el mateix email
                                  if ($d_user->email()===$newemail) {
                                    $emailok = FALSE;
                                  }
                                }
                            }
                        }
                        $xmluser->domainemail = $newemail;
                    } else {        // Email llarg
                        // Primer, provam mcabot
                        $emailok = TRUE;
                        $newemail = normalizedname(mb_substr($xmluser->name,0,1)) .
                          normalizedname($xmluser->surname1) . 
                          "@".DOMAIN;
                        foreach ($domainusers as $d_user) {
                          // Si hi ha un usuari del domini amb el mateix email
                          if ($d_user->email()===$newemail) {
                            $emailok = FALSE;
                          }
                        }
                        // Segon, provam mcabotn
                        if (!$emailok && isset($xmluser->surname2) && !empty($xmluser->surname2)) {
                            $emailok = TRUE;
                            $newemail = normalizedname(mb_substr($xmluser->name,0,1)) .
                              normalizedname($xmluser->surname1) . 
                              normalizedname(mb_substr($xmluser->surname2,0,1)) .
                              "@".DOMAIN;
                            foreach ($domainusers as $d_user) {
                              // Si hi ha un usuari del domini amb el mateix email
                              if ($d_user->email()===$newemail) {
                                $emailok = FALSE;
                              }
                            }
                        }
                        // Tercer, provam mcabotnad
                        if (!$emailok && isset($xmluser->surname2) && !empty($xmluser->surname2)) {
                            $emailok = TRUE;
                            $newemail = normalizedname(mb_substr($xmluser->name,0,1)) .
                              normalizedname($xmluser->surname1) . 
                              normalizedname(mb_substr($xmluser->surname2,0,3)) .
                              "@".DOMAIN;
                            foreach ($domainusers as $d_user) {
                              // Si hi ha un usuari del domini amb el mateix email
                              if ($d_user->email()===$newemail) {
                                $emailok = FALSE;
                              }
                            }
                        }
                        // Finalment, mcabot2, mcabot3...
                        if (!$emailok) {
                            $emailnumber = 1;
                            while (!$emailok) {
                                $emailok = TRUE;
                                $emailnumber++;
                                $newemail = normalizedname(mb_substr($xmluser->name,0,1)) .
                                  normalizedname($xmluser->surname1) . 
                                  $emailnumber .
                                  "@".DOMAIN;
                                foreach ($domainusers as $d_user) {
                                  // Si hi ha un usuari del domini amb el mateix email
                                  if ($d_user->email()===$newemail) {
                                    $emailok = FALSE;
                                  }
                                }
                            }
                        }
                        $xmluser->domainemail = $newemail;
                    }
                    // Afegim l'usuari que cream al diccionari de usuaris del domini
                    $domainusers[$xmluser->id] = new DomainUser(
                        $xmluser->id,
                        $xmluser->name, 
                        $xmluser->surname1, 
                        $xmluser->surname2,
                        $xmluser->surname,
                        $xmluser->email(),     // domainemail
                        $xmluser->suspended,   // suspended
                        $xmluser->teacher,     // teacher
                        $xmluser->withoutcode, // withoutcode
                        $xmluser->groups,      // groups
                        NULL,                  // expedient
                        NULL,                  // organizationalUnit
                        NULL                   // lastLoginTime
                        );
                    foreach ($xmluser->groupswithprefixadded() as $gr) {
                        // Si el grup no existeix, el cream
                        if (!array_key_exists($gr, $domaingroupsmembers)) {
                            echo("CREAR --> GRUP ".$gr."@".DOMAIN."<br>\r\n");
                            $contg++;
                            if (!$apply) {
                                $domaingroupsmembers[$gr] = [];
                            }
                        }      
                    }
                    echo("CREAR --> ".$xmluser."<br>\r\n");
    
                    $contc++;
                    if ($apply) {
                        try {
                            // Crear l'usuari del domini
                            // https://developers.google.com/admin-sdk/reseller/v1/codelab/end-to-end
                            $userObj = new Google_Service_Directory_User(array(
                                    'primaryEmail' => $xmluser->email(), 
                                    'name' => array("givenName" => $xmluser->name, "familyName" => $xmluser->surname), 
                                    'orgUnitPath' => ($xmluser->teacher?TEACHERS_ORGANIZATIONAL_UNIT:STUDENTS_ORGANIZATIONAL_UNIT),
                                    'externalIds' => array(array("type" => 'organization', "value" => $xmluser->id)),
                                    'suspended' => FALSE,
                                    'changePasswordAtNextLogin' => TRUE,
                                    'password' => DEFAULT_PASSWORD //Password per defecte
                                ));
                            $service->users->insert($userObj);
                            // Insertar tots els grups TEACHERS_GROUP_PREFIX,  STUDENTS_GROUP_PREFIX and TUTORS_GROUP_PREFIX
                            foreach ($xmluser->groupswithprefixadded() as $gr) {
                                // https://developers.google.com/admin-sdk/directory/v1/reference/members/insert
                                // Si el grup no existeix, el cream
                                if (!array_key_exists($gr, $domaingroupsmembers)) {
                                    $groupObj = new Google_Service_Directory_Group(
                                        array(
                                            'email' => $gr."@".DOMAIN
                                        )
                                    );
                                    $service->groups->insert($groupObj);
                                    $domaingroupsmembers[$gr] = [];
                                    sleep(1);
                                }
                                // Insertar el membre al grup
                                $memberObj = new Google_Service_Directory_Member(array(
                                    'email' => $xmluser->email()));
                                $service->members->insert($gr."@".DOMAIN, $memberObj);
                            }
                        } catch(Exception $e) {
                            $error = json_decode($e->getMessage());
                            echo('ERROR: ' .$error->error->message."<br>\r\n");
                        }
                    }
                }
            }
        } else {
            $domainuser = $domainusers[$xmluser->id];
            if (empty($selectedgroup)) {
                $group_ok = TRUE;
            } else {
                $group_ok = FALSE;
                foreach ($domainuser->groups as $group) {
                    if ((strpos($group, $selectedgroup) !== FALSE && strpos($group, $selectedgroup) == 0)) {
                        $group_ok = TRUE;
                    }
                }
                foreach ($xmluser->groups as $group) {
                    if ((strpos($group, $selectedgroup) !== FALSE && strpos($group, $selectedgroup) == 0)) {
                        $group_ok = TRUE;
                    }
                }
            }
            if ($group_ok) { // Aplicar només al grup seleccionat
                if (!$onlyteachers || $xmluser->teacher || $domainuser->teacher) {
                    if (in_array($domainuser->organizationalUnit, ['/', TEACHERS_ORGANIZATIONAL_UNIT, STUDENTS_ORGANIZATIONAL_UNIT])) {
                        // Aplicar només les unitats organitzatives 'Professorat', 'Alumnat' i '/'
                        if ($domainuser->suspended) {
                            echo("ACTIVAR --> ".$xmluser."<br>\r\n");
                            $conta++;
                            if ($apply) {
                                // Activar l'usuari del domini
                                $userObj = new Google_Service_Directory_User(array(
                                        'suspended' => FALSE
                                    ));
                                $service->users->update($domainuser->email(), $userObj);
                            }
                        }
                        // Tant si estava actiu com no, existeix, i per tant, actualitzar 
                        // els grups TEACHERS_GROUP_PREFIX, STUDENTS_GROUP_PREFIX i  TUTORS_GROUP_PREFIX
                        $creategroups = array_diff($xmluser->groupswithprefixadded(), $domainuser->groupswithprefix());
                        $deletegroups = array_diff($domainuser->groupswithprefix(), $xmluser->groupswithprefixadded());
                        if (!$domainuser->suspended && (count($creategroups)>0 || count($deletegroups)>0)) {
                            foreach ($creategroups as $gr) {
                                // Si el grup no existeix, el cream
                                if (!array_key_exists($gr, $domaingroupsmembers)) {
                                    echo("CREAR --> GRUP ".$gr."@".DOMAIN."<br>\r\n");
                                    $contg++;
                                    if (!$apply) {
                                        $domaingroupsmembers[$gr] = [];
                                    }
                                }
                            }
                            if (count($deletegroups)) {
                                echo("ESBORRAR MEMBRE --> ".$domainuser->surname.", ".$domainuser->name.
                                    " (".$domainuser->email().") [".implode(", ",$deletegroups)."]<br>\r\n");
                            }
                            if (count($creategroups)>0) {
                                echo("AFEGIR MEMBRE --> ".$domainuser->surname.", ".$domainuser->name.
                                    " (".$domainuser->email().") [".implode(", ",$creategroups)."]<br>\r\n");
                            }
                            $contm++;
                            if ($apply) {
                                // Actualitzam els grups de l'usuari
                                foreach ($deletegroups as $gr) {
                                    // https://developers.google.com/admin-sdk/directory/v1/reference/members/delete
                                    $service->members->delete($gr."@".DOMAIN, $domainuser->email());
                                }
                                foreach ($creategroups as $gr) {
                                    // Si el grup no existeix, el cream
                                    if (!array_key_exists($gr, $domaingroupsmembers)) {
                                        $groupObj = new Google_Service_Directory_Group(
                                            array(
                                                'email' => $gr."@".DOMAIN
                                            )
                                        );
                                        $service->groups->insert($groupObj);
                                        $domaingroupsmembers[$gr] = [];
                                        sleep(1);
                                    }
                                    // https://developers.google.com/admin-sdk/directory/v1/reference/members/insert
                                    $memberObj = new Google_Service_Directory_Member(array(
                                        'email' => $domainuser->email()));
                                    $service->members->insert($gr."@".DOMAIN, $memberObj);
                                }
                            }
                        }
                        // Actualitzar unitat organtizativa
                        if ($domainuser->organizationalUnit != ($xmluser->teacher?TEACHERS_ORGANIZATIONAL_UNIT:STUDENTS_ORGANIZATIONAL_UNIT)) {
                            echo("CANVIAR UNITAT ORGANITZATIVA --> ".$domainuser->surname.", ".$domainuser->name.
                                " (".$domainuser->email().") [".($xmluser->teacher?TEACHERS_ORGANIZATIONAL_UNIT:STUDENTS_ORGANIZATIONAL_UNIT)."]<br>\r\n");
                            $conto++;
                            if ($apply) {
                                $userObj = new Google_Service_Directory_User(
                                    array(
                                        'orgUnitPath' => ($xmluser->teacher?TEACHERS_ORGANIZATIONAL_UNIT:STUDENTS_ORGANIZATIONAL_UNIT)
                                    )
                                );
                                $service->users->update($domainuser->email(), $userObj);
                            }
                        }
                    }
                }
            }
        }
    }

    return array("created" => $contc,
                 "activated" => $conta,
                 "membersmodified" => $contm,
                 "orgunitmodified" => $conto,
                 "groupsmodified" => $contg);
}

function applyDomainChanges($xmlusers, $domainusers, $domaingroupsmembers, $apply, $selectedgroup, $onlyteachers) {
    $client = getClient();
    $service = new Google_Service_Directory($client);
  
    $contd = deleteDomainUsers($xmlusers, $domainusers, $apply, $service, $selectedgroup, $onlyteachers);
    $cont = addDomainUsers($xmlusers, $domainusers, $domaingroupsmembers, $apply, $service, $selectedgroup, $onlyteachers);
    return array("deleted" => $contd,
                 "created" => $cont['created'],
                 "activated" => $cont['activated'],
                 "membersmodified" => $cont['membersmodified'],
                 "orgunitmodified" => $cont['orgunitmodified'],
                 "groupsmodified" => $cont['groupsmodified']);
}
?>