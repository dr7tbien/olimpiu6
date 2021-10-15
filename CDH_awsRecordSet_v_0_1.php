<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';
    require_once 'classes/CDH_awsVpc.php';
    require_once 'classes/CDH_awsHostedZone.php';
    
    require_once 'defines/services_aws/CDH_awsDefineRecordSet.php';

    use Aws\Exception\AwsException;

    
    class CDH_awsRecordSet extends CDH_abstractAwsService {
    

        private $fields = [
            'AliasTarget',                  #array[DNSName:string, EvaluateTargetHealth:boolean, HostedZoneId:string]
            'Failover',                     #string
            'GeoLocation',                  #array[ContinentCode:string, CountryCode:string, SubdivisionCode:string]
            'HealthCheckId',                #string
            'MultiValueAnswer',             #boolean
            'Name',                         #string
            'Region',                       #string
            'ResourceRecords',              #array['Value' => #string]
            'SetIdentifier',                #string>',
            'TTL',                          #int
            'TrafficPolicyInstanceId',      #string>',
            'Type',                         #string
            'Weight'                        #int
        ];
        


        public function __construct(){
            $this->setCredentials();
            $this->setClientR53();
            $this->setClientEc2();

        }
        /**
         * 
         *
         * @param array $data = [
         *                  'ChangeBatch' => [ // REQUIRED
         *                      'Changes' => [ // REQUIRED
         *                          [
         *                              'Action' => 'CREATE|DELETE|UPSERT', // REQUIRED
         *                              'ResourceRecordSet' => [ // REQUIRED
         *                                  'AliasTarget' => [
         *                                      'DNSName' => '<string>', // REQUIRED
         *                                      'EvaluateTargetHealth' => true || false, // REQUIRED
         *                                      'HostedZoneId' => '<string>', // REQUIRED
         *                                  ],
         *                                  'Failover' => 'PRIMARY|SECONDARY',
         *                                  'GeoLocation' => [
         *                                      'ContinentCode' => '<string>',
         *                                      'CountryCode' => '<string>',
         *                                      'SubdivisionCode' => '<string>',
         *                                  ],
         *                                  'HealthCheckId' => '<string>',
         *                                  'MultiValueAnswer' => true || false,
         *                                  'Name' => '<string>', // REQUIRED
         *                                  'Region' => 'us-east-1|us-east-2|us-west-1|us-west-2|ca-central-1|eu-west-1|eu-west-2|eu-west-3|eu-central-1|ap-southeast-1|ap-southeast-2|ap-northeast-1|ap-northeast-2|ap-northeast-3|eu-north-1|sa-east-1|cn-north-1|cn-northwest-1|ap-east-1|me-south-1|ap-south-1|af-south-1|eu-south-1',
         *                                  'ResourceRecords' => [
         *                                      [
         *                                          'Value' => '<string>', // REQUIRED
         *                                      ],
         *                                  ],
         *                                  'SetIdentifier' => '<string>',
         *                                  'TTL' => <integer>,
         *                                  'TrafficPolicyInstanceId' => '<string>',
         *                                  'Type' => 'SOA|A|TXT|NS|CNAME|MX|NAPTR|PTR|SRV|SPF|AAAA|CAA|DS', // REQUIRED
         *                                  'Weight' => <integer>,
         *                              ], //ResourceRecordSet
         *                          ],//Changes[0]
         *                          
         *                      ], //Changes
         *                      'Comment' => '<string>',
         *                  ], //ChangeBatch
         *                  'HostedZoneId' => '<string>', // REQUIRED
         *            ]; //data 
         *                  
         * 
         * **/


        public function createForCloudator( $data ) {

            $res = $this->create( $data );

            if( $res == false )
                return false;

            return true;

        }


        /**
         * 
         *
         * @param array $data = [
         *                  'ChangeBatch' => [ // REQUIRED
         *                      'Changes' => [ // REQUIRED
         *                          [
         *                              'Action' => 'CREATE|DELETE|UPSERT', // REQUIRED
         *                              'ResourceRecordSet' => [ // REQUIRED
         *                                  'AliasTarget' => [
         *                                      'DNSName' => '<string>', // REQUIRED
         *                                      'EvaluateTargetHealth' => true || false, // REQUIRED
         *                                      'HostedZoneId' => '<string>', // REQUIRED
         *                                  ],
         *                                  'Failover' => 'PRIMARY|SECONDARY',
         *                                  'GeoLocation' => [
         *                                      'ContinentCode' => '<string>',
         *                                      'CountryCode' => '<string>',
         *                                      'SubdivisionCode' => '<string>',
         *                                  ],
         *                                  'HealthCheckId' => '<string>',
         *                                  'MultiValueAnswer' => true || false,
         *                                  'Name' => '<string>', // REQUIRED
         *                                  'Region' => 'us-east-1|us-east-2|us-west-1|us-west-2|ca-central-1|eu-west-1|eu-west-2|eu-west-3|eu-central-1|ap-southeast-1|ap-southeast-2|ap-northeast-1|ap-northeast-2|ap-northeast-3|eu-north-1|sa-east-1|cn-north-1|cn-northwest-1|ap-east-1|me-south-1|ap-south-1|af-south-1|eu-south-1',
         *                                  'ResourceRecords' => [
         *                                      [
         *                                          'Value' => '<string>', // REQUIRED
         *                                      ],
         *                                  ],
         *                                  'SetIdentifier' => '<string>',
         *                                  'TTL' => <integer>,
         *                                  'TrafficPolicyInstanceId' => '<string>',
         *                                  'Type' => 'SOA|A|TXT|NS|CNAME|MX|NAPTR|PTR|SRV|SPF|AAAA|CAA|DS', // REQUIRED
         *                                  'Weight' => <integer>,
         *                              ], //ResourceRecordSet
         *                          ],//Changes[0]
         *                          
         *                      ], //Changes
         *                      'Comment' => '<string>',
         *                  ], //ChangeBatch
         *                  'HostedZoneId' => '<string>', // REQUIRED
         *            ]; //data 
         *                  
         * 
         * **/

                 /**
         * @param array $recordSet[ 'value'=> array(), 
         *                          'name' => string, 
         *                          'type' => string, 
         *                          'ttl' => integer, 
         *                          'comment' => string, 
         *                          'hostedZoneId' => string, 
         *                          'hostedZoneName' => string
         * ]
         * 
         */


        public function create( $data = null ) {

            $clientR53 = $this->getClientR53();
            $clientEc2 = $this->getClientEc2();

            try{
                $resultR53 = $clientR53->changeResourceRecordSets($data);                
                return true;
            }catch (AwsException $e) {
                return false;
            }

        }

        /**
         * @param string $field el campo del recordset a buscar
         * @param array  $recordSet ['name' => string, 'type' => string, 'hostedZoneName' => string]
         * 
         */

        public function getFieldValue($field, $recordSet){
            if( $this->recordSetAnalize( $recordSet ) == false )
                return false;
            echo PHP_EOL . "L220" . PHP_EOL;

            $fields = $this->getFields();
            var_export( $fields );
            $field  = $this->in_array_ic($field, $fields);
            var_export( $field );
            if ( $field == false )
                return false;
    
            $rs = $this->getService( $recordSet );
            
            var_export( $rs );

            if ( $rs ==  false )
                return false;
            if(is_array( $rs[ $fields[$field] ]  ))
                return $rs[ $fields[$field] ];
            return trim($rs[ $fields[$field] ], ".");
        }

        /**
         * Esta función carece de sentido, pues los únicos valores interesantes son el Nombre y la Id y ambos los tenemos
         *  
         * */
        public function getFieldValueById ( $field, $id = null ) {
            return true;    
        }

        public function getFieldValueByName ( $field, $name ){
            
            return true;
        }

        public function getTagValueById( $tagKey,  $id ) {
            return true;
        }

        public function getTagValueByName( $tagKey,  $name ) {
            return true;
        }



        public function getServiceById( $id ){
                return $this->getService( $id );
        }

        /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag 
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */
        public function getServiceByName( $name  ){
            return true;
        }

        /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag.
         * Esta función es idéntica a getServiceByName y se ha implementado por tema de compatibilidad
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         * */

        public function getServiceByTagName( $name = null ){
            return true;
        }

        /**
         * @param array $recordSet[ 'value'=> string,   
         *                          'name' => string,   REQUIRED
         *                          'type' => string,   REQUIRED  
         *                          'hostedZoneName' => string REQUIIRED
         *                          ]
         * 
         */


        public function getService( $recordSet = array() ){
            if( $this->recordSetAnalize( $recordSet ) == false )
                return false;

            $clientR53 = $this->getClientR53();
            $clientEc2 = $this->getClientEc2();
    
            $hz = new CDH_awsHostedZone();

            $result = $clientR53->listResourceRecordSets([
                'HostedZoneId' => $recordSet['hostedZoneId'], // REQUIRED
            ]);

            $rss = $result['ResourceRecordSets'];
            for($i = 0; $i < count( $rss ); $i++ ){
                $rs = $rss[$i];
                $rs['Name'] = trim($rs['Name'], ".");
                #if( $rs['Name'] == $recordSet['name'] && $rs['Type'] == $recordSet['type']  ) {
                if( strcmp(trim($rs['Name'], "." ), $recordSet['name'] ) == 0  && strcmp($rs['Type'], $recordSet['type'] ) == 0 ) {
                    return $rs;
                    #break;
                }

            }

            return false;

        }

        /**
         * @param array $recordSet[ 'value'=> string,   
         *                          'name' => string,   REQUIRED
         *                          'type' => string,   REQUIRED  
         *                          'hostedZoneName' => string REQUIIRED
         *                          ]
         * Esta función no funciona porque requiere exactamante los mismos datoss de creaciópn del servicio
         */

        public function delete( $recordSet = null ){
            return true;

        }


        /**
         * @param array $recordSet[ 'name'              => string, 
         *                          'type'              => string, 
         *                          'hostedZoneName'    => string,
         *                          'hostedZoneId'      => string                 
         * ]
         * 
         */


        public function existsById( $id ){
         
            return true;

        }

        public function existsByName( $name ){
         
            return true;

        }


        public function exists( $recordSet = null ){
            if( $this->recordSetAnalize( $recordSet ) == false )
                return false;

            $service = $this->getService( $recordSet );

            if( $service == false )
                return false;

            return true;

        }

        
        /**
         * @param array $recordSet[ 'name'              => string, 
         *                          'type'              => string, 
         *                          'hostedZoneName'    => string]
         * 
         */


        public function isAvailable( $recordSet ){

            if( $this->recordSetAnalize( $recordSet ) == false )
                return false;

            $clientR53 = $this->getClientR53();
            #var_export($recordSet);
            try{
                $result = $clientR53->testDNSAnswer([
                    'HostedZoneId'  => $recordSet['hostedZoneId'], // REQUIRED
                    'RecordName'    => $recordSet['name'], // REQUIRED
                    'RecordType'    => $recordSet['type'], // REQUIRED                
                ]);
            }catch(AwsException $e ) {
                return false;

            }





            if( strcmp( $result['ResponseCode'], 'NOERROR' ) == 0 )
                return true;

            return false;
 

        }

        public function isAvailableByName( $name ){
            return true;

        } 

        public function isAvailableById( $id ){
            return true;

        } 



        /**
         * @param array $recordSet[ 'name'              => string, 
         *                          'type'              => string, 
         *                          'hostedZoneName'    => string]
         * 
         */

        public function waitToBeCreated( $recordSet, $seconds = null, $times = null ){

            echo PHP_EOL . "L432";
            if( $this->recordSetAnalize( $recordSet ) == false )
                return false;

            echo PHP_EOL . "L436";
            if ( !isset($seconds) || $seconds == null)
                $seconds = 10;

            if (!isset($times) || $times == null)
                $times = 12;

            for($i=0; $i < $times; sleep( $seconds ), $i++ ) 
                if( $this->isAvailable( $recordSet ) )
                    return true;

            return false;
                
        }

        public function waitToBeDeleted( $recordSet, $seconds = null, $times = null ){
            if (recordSetAnalize( $recordSet ) == false ) return false;

            if ( !isset($seconds) || $seconds == null)
                $seconds = 10;

            if (!isset($times) || $times == null)
                $times = 12;

            $clientR53 = $this->getClientR53();
            
            for($j = 0, $existeRecord = false; $j < $times; sleep($seconds), $j++){
                $resultR53 = $clientR53->listResourceRecordSets([
                    'HostedZoneId' => $recordSet['hostedZoneId'], // REQUIRED
                ]);
                $rss = $resultR53['ResourceRecordSets'];
                
                for($i = 0; $i < count( $rss   ); $i++){
                    $rs = $rss[$i];
                    if(  (strcmp( $rs['Name'], $recordSet['name']) == 0)  && (strcmp( $rs['Type'], $recordSet['type']) == 0) )
                        $existeRecord = true;
                }
                if( $existeRecord == false )
                    return true;
            }

            return false;
        }


        /**
         * @param array $recordSet[ 'name'              => string, 
         *                          'type'              => string, 
         *                          'hostedZoneName'    => string]
         * @param bool $value   Si true $recordSet['value']  ha de estar definido 
         */

        private function recordSetAnalize( &$recordSet ){
            
            $recordSet['date'] = date( 'Y-m-d-s'  );
            $hz = new CDH_awsHostedZone();

            #echo PHP_EOL . "Analize L493";
            if( !isset( $recordSet['name'] ) || !isset( $recordSet['type'] ) || !isset( $recordSet['hostedZoneName'] )  )
                return false;

            #echo PHP_EOL . "Analize L497";
            #if( (isset( $recordSet['value'] ) && is_array( $recordSet['value'] ) && count( $recordSet['value'] ) > 0) )
            #    return false;

            #echo PHP_EOL . "Analize L501";    
            if( @$recordSet['type'] != true ||  @$recordSet['name'] != true )     
                return false;

            #echo PHP_EOL . "Analize L505";    
            if( @$recordSet['hostedZoneId'] != true && @$recordSet['hostedZoneName'] != true )     
                return false;
            
            #echo PHP_EOL . "Analize L509";
            if( @$recordSet['hostedZoneId'] == true && @$recordSet['hostedZoneName'] == true ) {
                
                $hzId = $hz->getIdByName( $recordSet['hostedZoneName'] );
                if( strpos( $recordSet['hostedZoneId'], $hzId ) === false )
                    return false;
            }    

            #echo PHP_EOL . "Analize L517";
            if( @$recordSet['hostedZoneId'] != true && @$recordSet['hostedZoneName'] == true ) {
                $recordSet['hostedZoneId'] = $hz->getIdByName( $recordSet['hostedZoneName'] );
                if( !$recordSet['hostedZoneId'] ) 
                    return false;
            } 

            #echo PHP_EOL . "Analize L524";
            if( @$recordSet['hostedZoneId'] == true && @$recordSet['hostedZoneName'] != true ) {
                $res =  $hz->exists( false, $recordSet['hostedZoneId'] );
                if( $res == false )
                    return false;
            }

            #if ( !isset( $recordSet['ttl'] ) )
            #    $recordSet['ttl'] = 60;

            if ( !isset( $recordSet['comment'] ) )
                $recordSet['comment'] = "Another CDH RecordSet";

            return true;

        }


        /**
         * Consigue la id dando el nombre como parámetro.
         * 
         * La id de un hostedzone es un caso curioso pues tiene este formato:
         *  /hostedzone/Z[string]       
         * 
         * Pero en la mayoría de los lugares que se precisa la id de un hostedzone se requiere Z[string]
         * 
         * @param string $name N ombre del hostedzone a buscar
         * @return string Z[string] . Se eliminia la cadena '/hostedzone/'           
         * 
         * */


        public function getId() {
            return true;
        }

        public function getIdByName( $name ) {
            return true;
        }        

        public function getFields(){
            return $this->fields;
        }




        public function fileRecordSetWriteError( $recordSet ){
            if( !isset( $recordSet['date'] ) )
                $recordSet['date'] = null;

            if( !isset( $recordSet['name'] ) )
                $recordSet['name'] = null;

            if( !isset( $recordSet['type'] ) )
                $recordSet['type'] = null;

            if( !isset( $recordSet['hostedZoneId'] ) )
                $recordSet['hostedZoneId'] = null;

            if( !isset( $recordSet['status'] ) )
                $recordSet['status'] = null;

            if( !isset( $recordSet['responseCode'] ) )
                $recordSet['responseCode'] = null;

            if( !isset( $recordSet['error'] ) )
                $recordSet['error'] = null;


            $line = "RecordSet: "                                           . PHP_EOL;     
            $line .= "Date = "              . $recordSet['date']            . PHP_EOL;                 
            $line .= "Name = "              . $recordSet['name']            . PHP_EOL;
            $line .= "Type = "              . $recordSet['type']            . PHP_EOL; 
            $line .= "HostedZoneId = "      . $recordSet['hostedZoneId']    . PHP_EOL; 
            $line .= "Status = "            . $recordSet['status']          . PHP_EOL;
            $line .= "ResponseCode = "      . $recordSet['responseCode']    . PHP_EOL;
            $line .= "Error = "             . $recordSet['error']           . PHP_EOL . PHP_EOL;
            
            return file_put_contents( CDH_RECORDSET_ERRORS_FILE, $line, FILE_APPEND);


        }
        
        /*
        public function fileRecordSetWriteText( $path, $title ){
            if( !file_exists( $path ) )
                die( PHP_EOL . "El archivo $path no existe " . PHP_EOL); 
            return file_put_contents($path, $title);
        }
        */

        public function getStatus ($recordSet ){
            if ($this->recordSetAnalize( $recordSet ) == false ) return false;

            $clientR53 = $this->getClientR53();
            try{
                $result = $clientR53->testDNSAnswer([
                    'HostedZoneId'  => $recordSet['hostedZoneId'], // REQUIRED
                    'RecordName'    => $recordSet['name'], // REQUIRED
                    'RecordType'    => $recordSet['type'], // REQUIRED
                    
                ]);
                return $result['ResponseCode'];
            }catch( AwsException $e ){
                return false;
            }
        }

        public function getStatusById ($id ){
            return true;
        }

        public function getStatusByName ($name ){
            return true;
        }

        /**
         * @param object    $e0  
         * @param string    $e1 "0K;RECORDSET;004;64"
         * 
         * 
         * 
         */

        public function logError( $e_0, $recordSet ){
                /**
                 * $e->getAwsRequestId() 	= 426eca2f-49cd-4746-9613-9bfe35c85a1e
                 * $e->getAwsErrorType() 	= client
                 * $e->getAwsErrorCode() 	= NoSuchHostedZone
                 * $e->getAwsErrorMessage() = No hosted zone found with ID: ZZtop
                 * $e->getStatusCode() 	= 404
                 * $e->getAwsRequestId() 	= 426eca2f-49cd-4746-9613-9bfe35c85a1e
                 * 
                 */

                $type_0       = $e0->getAwsErrorType();    #client
                $statusCode_0 = $e0->getStatusCode();      #404


                $recordSet['date'] =  date( 'Y-m-d-s'  );
                $recordSet['error'] = $e0->getAwsErrorCode() . ": " . $e0->getAwsErrorMessage();
                

                $line = "RecordSet: "                                           . PHP_EOL;     
                $line .= "Date = "              . $recordSet['date']            . PHP_EOL;                 
                $line .= "Name = "              . $recordSet['name']            . PHP_EOL;
                $line .= "Type = "              . $recordSet['type']            . PHP_EOL; 
                $line .= "HostedZone id = "     . $recordSet['hostedZoneId']    . PHP_EOL; 
                $line .= "Status = "            . $recordSet['status']          . PHP_EOL;
                $line .= "ResponseCode = "      . $recordSet['responseCode']    . PHP_EOL;
                $line .= "Error = "             . $recordSet['error']           . PHP_EOL;
                
                return file_put_contents( CDH_RECORDSET_ERRORS_FILE, $line, FILE_APPEND);
    

                
                        #$recordSet['error'] = "Faltan datos: name | type | hostedZoneName ";
            

        }

    }

?>
