<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsCloudator.php';
    require_once 'defines/services_aws/CDH_awsDefineStep0NetSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep00RecordSetSystem.php';
    
    require_once 'classes/CDH_awsVpc.php';            
    require_once 'classes/CDH_awsRequestCertificate.php';   
    require_once 'classes/CDH_awsHostedZone.php';
    #require_once 'classes/CDH_awsSecurityGroup.php';
    
    #require_once 'classes/CDH_awsElasticIp.php';
    
    #require_once 'classes/CDH_awsInternetGateway.php';      
    #require_once 'classes/CDH_awsSubnet.php';      
    #require_once 'classes/CDH_awsNatGateway.php';      
    #require_once 'classes/CDH_awsRouteTable.php';   

    


    use Aws\Exception\AwsException;
    #use Aws\Credentials\Credentials;

    class CDH_awsStep00RecordSetSystem extends CDH_abstractAwsCloudator {

        public $recordSets = [];

        


        public function __construct(){
            $this->createNames();
        }
        
        
        public function createForCloudator() {

            #region hostedZone
            $this->logUser('{"two":"Creando Hosted Zone"},');

            #mensaje para el admin
            $this->logUser('{"two":"\nL045 - Creando Hosted Zone\n"},');
            sleep(5);
            file_put_contents(CDH_NS_FILE_PATH, "registros dns \nregistro dns2");
            return true;

            if( $this->createHostedZone() == false ){
                $this->logUser('{"two":"\nL045 - Error Creando Hosted Zone\n"},');
                return false;
            }
            $this->logUser('{"two":"\nL049 - HostedZone creado\n"},');
            #endregion hostedZone
            

            #region createRecordSets
            if( $this->createRecordSets() == false ){
                $this->logAdmin( "L046 - Error creando Record Sets");
                return false;
            }
            
            $this->logUser('{"two":"\n\nL060 - RecordSets creados\n"},');
            #endregion createRecordSets
            
            #region checkRecordSets
            if( $this->checkRecordSets() == false ){
                $this->logAdmin( "L062 - RecordSets no creados.");
                return false;
            }
            $this->logUser('{"two":"\nL069 - RecordSets chequeados y OK\n"},');
            #endregion checkRecordSets
            
            #region dnsAws
            if( $this->checkDnsAws() == false ){
                $this->logUser('{"two":"\nL046 - DNSs NO insertados\n"},');
                return false;
            }
            $this->logUser('{"two":"\nL079 - DNSs insertados\n"},');
            #endregion dnsAws
            
            #region checkCmsServices
            if(  $this->checkCmsServices() == false ){
                $this->logUser('{"two":"\nL070 - CMS Services no funcionan. Consultar servicio técnico"},');
                return false;
            }
            $this->logUser('{"two":"\nL065 - CMS Services funcionan\n"},');
            #endregion checkCmsServices
            
            $this->logUser('{"two":"\nL092 - TODO TODITO creado.\n"},');
                        
            return true;


        }

        public function createHostedZone(){
            $service            = new CDH_awsHostedZone(); 
            #$vpc                = new CDH_awsVpc();
            #$vpcId              = $vpc->getIdByName( $this->getVpcName() ); 
        
            $CDH_RECORDSET_SYSTEM_HOSTED_ZONE           = CDH_RECORDSET_SYSTEM_HOSTED_ZONE;
            $CDH_RECORDSET_SYSTEM_HOSTED_ZONE['CallerReference']                    = CDH_DOMINIO_CON_PUNTO . "_" . time();      

            if( $service->create( $CDH_RECORDSET_SYSTEM_HOSTED_ZONE )  )
                return true;
            else
                return false;

        }

        public function checkDnsAws(){
            $comando = "nslookup -type=NS " . CDH_DOMINIO_CON_PUNTO;
            
            $result = shell_exec( $comando );
            $result = explode( PHP_EOL, $result  );
            
            for( $i = 0, $aws = false, $dnsUrls = []; $i < count( $result); $i++ ) {
                if( preg_match("/awsdns/i", $result[$i]) ){
                    $aws = true;
                    $campos = explode(" ", $result[$i]);
                    $dnsUrls[] = end($campos);
                }
            }
            
            if( $aws == false ){
                $hz = new CDH_awsHostedZone(); 
                $rs = $hz->getRecordSetsNs( CDH_RECORDSET_SYSTEM_HOSTED_ZONE['Name'] );
                $this->logUser('{"two":"\nMensaje para el usuario sobre tareas antes de seguir: "},');
                $this->logUser('{"two":"\n"},');
                #$this->logUser('{"two":"1. Detenga la actividad de su CMS( prestashop, wordpress, etc )\n";
                $this->logUser('{"two":"1. Vaya a a su area de DNS y haga una captura de pantalla de las entradas DNS(RecordSets)\n"},');
                
                $this->logUser('{"two":"2. Sustituya las entradas DNS del punto anterior por las siguientes:\n"},');
                //limpiar el fichero con los dns anteriores
                file_put_contents(CDH_NS_FILE_PATH, "");
                foreach ( $rs as $r){
                    $this->logUser('{"two":"'.$r['Value'].'"},');
                    file_put_contents(CDH_NS_FILE_PATH, $r['Value'] . "\n", FILE_APPEND);
                    #echo "\t" . $r['Value'] . "\n";
                }

                $this->logUser('{"two":"3. Compruebe que su sitio funciona\n"},');
                $this->logUser('{"two":"4. Si su sitio no funciona: vuelva a colocar los DNSs originales y contacte con nuestro SAT\n"},');
                $this->logUser('{"two":"\n"},');

                #return false;

            } 

            if( $aws == true ){
                $this->logUser('{"two":"\n\$aws = true"},');
                return true;

            } 


        }

        public function validateRecords( $records ){
            $recordsInexistentes = [];

            for( $i = 1; $i < count( $records ); $i++ ){
                $rss = explode(',', $records[$i]);
                if( in_array( strtoupper( $rss[0] ) , CDH_RECORDSETS_ALLOWED )  )
                    continue;

                $recordsInexistentes[] = strtoupper( $records[$i] );
                
            }
            if( count( $recordsInexistentes ) > 0 ){
                $this->logUser('{"two":"\nEl archivo " . CDH_RECORDSET_SYSTEM_FILE_RECORD_SETS . " contiene RecordSets que necesitan programación:\n"},');
                #var_export( $recordsInexistentes );
                return false;
            }
            return true;

        }

        public function createRecordSets(){
            $hostedZone                     = new CDH_awsHostedZone();
            $recordSets                     = [];
            $CHANGE_RESOURCE_RECORD_SETS    = CDH_RECORDSET_SYSTEM_CHANGE_RESOURCE_RECORD_SETS;
            $recordSetsLines                = file( CDH_RECORDSET_SYSTEM_FILE_RECORD_SETS );
            $service                        = new CDH_awsRecordSet();



            if( !$this->validateRecords( $recordSetsLines ) ){ 
                $this->logAdmin("L192 - Archivo " .  CDH_RECORDSET_SYSTEM_FILE_RECORD_SETS . " con records que necestan reprogramación" );
                return false;
            }
            $i = 0;
            foreach( $recordSetsLines as $recordSetsLine){
                if( $i == 0) { $i++; continue; }

                $recordSetsLine = explode(",", $recordSetsLine);

                $recordSet['Type']  = trim($recordSetsLine[0]);

                $recordSet['Name']  = trim($recordSetsLine[1]);
                $recordSet['Value'] = trim($recordSetsLine[2]); 
                
                $recordSets[] = $recordSet;
            }

            $CHANGE_RESOURCE_RECORD_SETS['HostedZoneId'] = $hostedZone->getIdByName( CDH_RECORDSET_SYSTEM_HOSTED_ZONE['Name'] );
            #var_export($recordSets);

            if( !$this->insertRecordSetIntoArrayMain( $CHANGE_RESOURCE_RECORD_SETS, $recordSets ) ){
                $this->logAdmin( "No existe record set principal\n" );
                return false;
            }
            
            sleep(2);

            if( !$this->insertRecordSetIntoArrayA( $CHANGE_RESOURCE_RECORD_SETS, $recordSets ) ){
                $this->logAdmin( "\nProblemas insertando Recorsetds de Tipo A\n" );
                return false;
            }

            sleep(2);

            if( !$this->insertRecordSetIntoArrayMx( $CHANGE_RESOURCE_RECORD_SETS, $recordSets ) ){
                $this->logAdmin( "\nProblemas insertando Recorsetds de Tipo MX\n" );
                return false;
            }

            sleep(2);

            if( !$this->insertRecordSetIntoArraySpf( $CHANGE_RESOURCE_RECORD_SETS, $recordSets ) ){
                $this->logAdmin( "\nNo se han encontrado Recorsetds de Tipo Spf\n" );
                return false;
            }
            
            sleep(2);
            
            if( !$this->insertRecordSetIntoArrayTxt( $CHANGE_RESOURCE_RECORD_SETS, $recordSets ) ){
                $this->logAdmin( "\nNo se han encontrado Recorsetds de Tipo Txt\n" );
                return false;
            }
            
            sleep(2);

            if( !$this->insertRecordSetIntoArrayCname( $CHANGE_RESOURCE_RECORD_SETS, $recordSets ) ){
                $this->logAdmin( "\nNo se han encontrado Recorsetds de Tipo CNAME\n" );
                return false;
            }
            
            $this->logUser('{"two":"\n\n Retodo hecho. Copiados los recorsets al Cloud\n\n"},');
            
            return true;

        }

        public function checkRecordSets(){
            
            $recordSets         = [];
            $recordSetsLines    = file( CDH_RECORDSET_SYSTEM_FILE_RECORD_SETS );
            $hostedZone         = new CDH_awsHostedZone();
            $rs                 = new CDH_awsRecordSet();
            $hostedZoneId       = $hostedZone->getIdByName( CDH_RECORDSET_SYSTEM_HOSTED_ZONE['Name'] );
            $i                  = 0;

            foreach( $recordSetsLines as $recordSetsLine){
                if( $i == 0) { $i++; continue; }
                
                $recordSetsLine = explode(",", $recordSetsLine);

                $recordSet['Type']  = trim($recordSetsLine[0]);
                $recordSet['Name']  = trim($recordSetsLine[1]);
                $recordSet['Value'] = trim($recordSetsLine[2]); 
                
                $recordSets[] = $recordSet;
            }

            $existenTodosLosRecordSets = true;
            foreach( $recordSets as $recordSet ){
                if( !$rs->existsInHostedZoneByValue( $recordSet['Value'], $hostedZoneId ) ) {
                    /*$this->logUser('{"two":"\nL254 - RecordSet No Existe: \n Type = " . $recordSet['Type'] . "\nName = " . $recordSet['Name'] . "\nValue = " . $recordSet['Value'] . "\n"},');
                    $this->logUser('{"two":"\n"},');*/
                    $existenTodosLosRecordSets = false;
                }

            }

            return $existenTodosLosRecordSets;

        }

        public function checkCmsServices(){
            try{
                $ch = @get_headers( "http://" . CDH_DOMINIO_CON_PUNTO  );
                
                if( $ch == false  ){
                        $msg = "URL no existe";
                        throw new Exception ( $msg );
                }    
            }catch( Exception $e ){
                $this->logUser('{"two":"Url no existe"},');;
                return false;
            }

            $this->logUser('{"two":"\n\n CMS funciona OK\n\n"},');;

            return true;
                
        }

        private function insertRecordSetIntoArrayCname( &$CHANGE_RESOURCE_RECORD_SETS, &$recordSets ) {
            $contador       = 0;
            $service        = new CDH_awsRecordSet();
            $hostedZone     = new CDH_awsHostedZone();

            unset($CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes']);
            $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'] = array();

            foreach( $recordSets as $recordSet ){
                if( $recordSet['Type'] == "CNAME" )  {
                    $contador++;
                    
                    $rs = [
                        'Action' => 'CREATE',
                        'ResourceRecordSet' => [
                            'Name'              => CDH_DOMINIO_CON_PUNTO,
                            'Type'              => 'CNAME',
                            'TTL'               => 60,
                            'SetIdentifier'     => "CDH_" . CDH_DOMINIO_SIN_PUNTO . "_" . date("YmdHis"), 
                            'Region'            => CDH_AWS_REGION,
                            'ResourceRecords'   => [
                                [
                                'Value' => $recordSet['Value'],  
                                ]
                            ],
                        ]
                    ];
                    
                    $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'][] = $rs;
                }
            }

            if( $contador < 1 ){
                $this->logUser('{"two":"\nL377 No se ha encontrado record sets de tipo CNAME\n"},');
                return true; 
            }

            $CHANGE_RESOURCE_RECORD_SETS['HostedZoneId'] = $hostedZone->getIdByName( CDH_RECORDSET_SYSTEM_HOSTED_ZONE['Name'] );

            if( $service->create( $CHANGE_RESOURCE_RECORD_SETS ) ){
                $this->logUser('{"two":"\nL485 Creados $contador registros de tipo CNAME\n"},');
                return true;
            }
             $this->logAdmin("\nL372 No se ha podido crear los RecordSets de tipo CNAME\n");
            return false;



        }

        private function insertRecordSetIntoArrayTxt( &$CHANGE_RESOURCE_RECORD_SETS, &$recordSets ) {
            $contador       = 0;
            $service        = new CDH_awsRecordSet();
            $hostedZone     = new CDH_awsHostedZone();

            unset($CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes']);
            $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'] = array();

            for($i = 0; $i < count( $recordSets ); $i++  ){
                if( $recordSets[$i]['Type'] != "TXT" )
                    continue;


                if( !preg_match( "/" . CDH_DOMINIO_SIN_PUNTO . "/", $recordSets[$i]['Name'] ) ) {
                    $contador++;
                    $rs = [
                        'Action' => 'CREATE',
                        'ResourceRecordSet' => [
                            'Name'              => $recordSets[$i]['Name'] . "." . CDH_DOMINIO_CON_PUNTO,
                            'Type'              => 'TXT',
                            'TTL'               => 60,
                            'SetIdentifier'     => "CDH_" . CDH_DOMINIO_SIN_PUNTO . "_" . date("YmdHis"), 
                            'Region'            => CDH_AWS_REGION,
                            'ResourceRecords'   => [
                                [
                                'Value' => "\"" . $recordSets[$i]['Value'] . "\"",  
                                ]
                            ],
                        ]
                    ];

                    $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'][] = $rs;

                    continue;

                }

                $contador++;
                $rs = [
                    'Action' => 'CREATE',
                    'ResourceRecordSet' => [
                        'Name'              => CDH_DOMINIO_CON_PUNTO,
                        'Type'              => 'TXT',
                        'TTL'               => 60,
                        'SetIdentifier'     => "CDH_" . CDH_DOMINIO_SIN_PUNTO . "_" . date("YmdHis"), 
                        'Region'            => CDH_AWS_REGION,
                        'ResourceRecords'   => [
                                [
                                    'Value' => "\"" . $recordSets[$i]['Value'] . "\"",  

                                ]
                        ],
                    ]
                ];

                $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'][] = $rs;
                #print_r($CHANGE_RESOURCE_RECORD_SETS); die();
            }

            if( $contador < 1 ){
                $this->logUser('{"two":"\nL438 No se ha encontrado un record sets de tipo TXT\n"},');
                return true; 
            }

            $CHANGE_RESOURCE_RECORD_SETS['HostedZoneId'] = $hostedZone->getIdByName( CDH_RECORDSET_SYSTEM_HOSTED_ZONE['Name'] );

            if( $service->create( $CHANGE_RESOURCE_RECORD_SETS ) ){
                $this->logUser('{"two":"\nL485 Creados $contador registros de tipo TXT\n"},');
                return true;
            }
             $this->logAdmin("\nL444 No se ha podido crear los RecordSets de tipo TXT\n");
            return false;

        }

        private function insertRecordSetIntoArraySpf( &$CHANGE_RESOURCE_RECORD_SETS, &$recordSets ) {
            $contador       = 0;
            $service        = new CDH_awsRecordSet();
            $hostedZone     = new CDH_awsHostedZone();

            unset($CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes']);
            $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'] = array();

            $contador = 0; $i=1;
            foreach( $recordSets as $recordSet ){
                if( $recordSet['Type'] != "SPF" )
                    continue;

                $contador++;

                $rs = [
                    'Action' => 'CREATE',
                    'ResourceRecordSet' => [
                        'Name'              => CDH_DOMINIO_CON_PUNTO,
                        'Type'              => 'SPF',
                        'TTL'               => 60,
                        'SetIdentifier'     => "CDH_" . CDH_DOMINIO_SIN_PUNTO . "_" . date("YmdHis"), 
                        'Region'            => CDH_AWS_REGION,
                        'ResourceRecords'   => [
                            'Value' => $recordSet['Value'],  
                        ],
                    ]
                ];
    
                $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'][] = $rs;
            }
            if( $contador < 1 ){
                $this->logUser('{"two":"\nL614 No se ha encontrado un record sets de tipo SPF\n"},');
                return true;
            }

            $CHANGE_RESOURCE_RECORD_SETS['HostedZoneId'] = $hostedZone->getIdByName( CDH_RECORDSET_SYSTEM_HOSTED_ZONE['Name'] );

            if( $service->create( $CHANGE_RESOURCE_RECORD_SETS ) ){
                $this->logUser('{"two":"\nL492 Creados $contador registros de tipo SPF\n"},');
                return true;
            }
             $this->logAdmin("\nL491 No se ha podido crear los RecordSets de tipo SPF\n");
            return false;


        }

        private function insertRecordSetIntoArrayMx( &$CHANGE_RESOURCE_RECORD_SETS, &$recordSets ) {
            $contador       = 0;
            $service        = new CDH_awsRecordSet();
            $hostedZone     = new CDH_awsHostedZone();

            unset($CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes']);
            $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'] = array();
            
            $rs = [
                'Action' => 'CREATE',
                'ResourceRecordSet' => [
                    'Name'  => CDH_DOMINIO_CON_PUNTO,
                    'Type'  => 'MX',
                    'TTL'   => 60,
                    'SetIdentifier' => "CDH_" . CDH_DOMINIO_SIN_PUNTO . "_" . date("YmdHis"), 
                    'Region' => CDH_AWS_REGION,
                    'ResourceRecords' => [
                    ],
                ]
            ];

            $mxs = [];
            foreach( $recordSets as $recordSet ) {
                if( $recordSet['Type'] == "MX" ){    
                    $mxs[]  = $recordSet['Value'];
                    $contador++;
                }
            }   

            if( $contador < 1 ){
                $this->logUser('{"two":"\n\nL484 No se han encontrado RecordSets MX\n\n"},');
                return true;
            }

            if( !sort( $mxs, SORT_REGULAR) )
                return false;
            
            for($i = 0, $j = 0; $i < count($mxs); $i++ ){
                if( $i%2 == 0 )
                    $j += 10;
                $mxs[$i] = strval( $j ) . " " . $mxs[$i];
                $rs['ResourceRecordSet']['ResourceRecords'][] = ["Value" => $mxs[$i]]; 
            }
            $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'][] = $rs;

            $CHANGE_RESOURCE_RECORD_SETS['HostedZoneId'] = $hostedZone->getIdByName( CDH_RECORDSET_SYSTEM_HOSTED_ZONE['Name'] );

            if( $service->create( $CHANGE_RESOURCE_RECORD_SETS ) ){
                $this->logUser('{"two":"\nL534 Creados $contador registros de tipo MX\n"},');
                return true;
            }
             $this->logAdmin( "\nL537 No se ha podido crear los RecordSets de tipo MX\n" );

            return false;

        }

        private function insertRecordSetIntoArrayA( &$CHANGE_RESOURCE_RECORD_SETS, &$recordSets ) {
            $contador       = 0;
            $service        = new CDH_awsRecordSet();
            $hostedZone     = new CDH_awsHostedZone();
            
            unset($CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes']);
            $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'] = array();

            foreach( $recordSets as $recordSet ){
                #$this->logUser('{"two":"\n\$recordSet['Name'] = " . $recordSet['Name'] . "  |  \$recordSet['Type'] = " . $recordSet['Type'] . "\n";
                if( $recordSet['Name'] != "@" && $recordSet['Type'] == "A" ){
                    $contador++;
                    $rs = [
                        'Action' => 'CREATE',
                        'ResourceRecordSet' => [

                            'Name'              => CDH_DOMINIO_CON_PUNTO,
                            'Type'              => 'A',
                            'TTL'               => 60,
                            'SetIdentifier'     => "CDH_" . CDH_DOMINIO_SIN_PUNTO . "_" . date("YmdHis"), 
                            'Region'            => CDH_AWS_REGION,

                            'ResourceRecords'   => [
                                [    
                                   'Value' => $recordSet['Value'],  
                                ],

                            ],

                            #'Type' => $recordSet['type'], // $result_0_type
                            #'Type' => $recordSet['Type'],
                        ]

                    ];
                    $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'][] = $rs;
                }
                    
            }
            if( $contador < 1 ){
                $this->logUser('{"two":"\nL614 No se han encontrado record sets de tipo A\n"},');
                return true;
            }

            $CHANGE_RESOURCE_RECORD_SETS['HostedZoneId'] = $hostedZone->getIdByName( CDH_RECORDSET_SYSTEM_HOSTED_ZONE['Name'] );

            if( $service->create( $CHANGE_RESOURCE_RECORD_SETS ) ){
                $this->logUser('{"two":"\nL580 Creados $contador registros de tipo A\n"},');
                return true;
            }
            $this->logAdmin( "\nL610 No se ha podido crear los RecordSets de tipo A\n" );
            return false;
        }

        private function insertRecordSetIntoArrayMain( &$CHANGE_RESOURCE_RECORD_SETS, &$recordSets ){
            $contador       = 0;
            $service        = new CDH_awsRecordSet();
            $hostedZone     = new CDH_awsHostedZone();
            
            foreach( $recordSets as $recordSet ){
                if( $recordSet['Name'] == "@" ){
                    $contador++;
                }
                    
            }
            print_r( $contador );
            if( $contador != 1 ){
                $this->logUser('{"two":"\nNo se ha encontrado record set principal\n"},');
                //die("\nL628 No se ha podido crear RecordSet Principal\n\n");
                return false;
            }
            $contador = 0;
            foreach( $recordSets as $recordSet ){
                if( $recordSet['Name'] == "@" ){
                    $contador++;

                    $rs = [
                        'Action' => 'CREATE',
                        'ResourceRecordSet' => [
                            'Name' => CDH_DOMINIO_CON_PUNTO,
                            'Type'          => 'A',
                            'TTL'           => 60,
                            'SetIdentifier' => "CDH_" . CDH_DOMINIO_SIN_PUNTO . "_" . date("YmdHis"), 
                            'Region' => CDH_AWS_REGION,

                            'ResourceRecords' => [
                                [    
                                   'Value' => $recordSet['Value'],  
                                ],

                            ],
                        ]

                    ];
                    $CHANGE_RESOURCE_RECORD_SETS['ChangeBatch']['Changes'][] = $rs;
                    
                    break;
                }

            }
            if( $contador != 1 ){
                $this->logUser('{"two":"\nL614 No se ha encontrado un record set principal tipo @\n"},');
                $this->logAdmin("\nL615 - Comprobar archivo " . CDH_RECORDSET_SYSTEM_FILE_RECORD_SETS .  " \n");
            }

            $CHANGE_RESOURCE_RECORD_SETS['HostedZoneId'] = $hostedZone->getIdByName( CDH_RECORDSET_SYSTEM_HOSTED_ZONE['Name'] );

            if( $service->create( $CHANGE_RESOURCE_RECORD_SETS ) ){
                $this->logUser('{"two":"\nL614 Creado RecordSet principal\n"},');
                return true;
            }

            $this->logUser('{"two":\nL671 No se ha podido crear RecordSet Principal\n\n"},');
            

        }


    }

?>
