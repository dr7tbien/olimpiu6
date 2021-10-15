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


        public function __construct(){
            $this->setCredentials();
            $this->setClientR53();
            $this->setClientEc2();
            $this->setClassName("CDH_awsRecordSet");
        }
        

        public function create ( $data = null ) {

            $client = $this->getClientR53();
            #$id     = $data['DBInstanceIdentifier'];
            #$name   = $data['PolicyName'];
            $value          = $data['ChangeBatch']['Changes'][0]['ResourceRecordSet']['ResourceRecords'][0]['Value'];
            $hostedZoneId   = $data['HostedZoneId'];

            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
                if ($this->existsInHostedZoneByValue( $value, $hostedZoneId ) )
                    throw new Exception( "L043 El servicio " . $this->getClassName() . " con el nombre " . $value . " ya existe");
                $result     = $client->changeResourceRecordSets( $data );
                $identifier = $result ;
                if( $this->waitToBeCreated(  $identifier  ) == false )
                    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );
        
                return true ;
            
            }catch (AwsException $e) {
                $this->logAdmin(  "L059 Error  " . $this->getClassName() . "->create()" );  
                $this->logAdmin(  "\n Mensaje de error:" );
                $this->logAdmin(  $e->getMessage() );
                return false;
            }catch(Exception $e){
                $this->logAdmin(  "L064 Error  " . $this->getClassName() . "->create()" );  
                $this->logAdmin(  "\n Mensaje de error:" );
                $this->logAdmin(  $e->getMessage() );
                return false;
            }

        }


        public function createOld( $data = [] ) {
            $value          = $data['ChangeBatch']['Changes'][0]['ResourceRecordSet']['ResourceRecords'][0]['Value'];
            $hostedZoneId   = $data['HostedZoneId'];

            if ($this->existsInHostedZoneByValue( $value, $hostedZoneId ) ){
                echo "\n\n Existe \$value = $value | \$hostedZoneId = $hostedZoneId\n\n";
                return true;
            }
                
            $client = $this->getClientR53();
            
            try{

                $result = $client->changeResourceRecordSets( $data );    
                $created = $this->waitToBeCreated( $result );

                if(  !$created ) {
                    $msg = "OK;RECORDSET;003;112";
                    throw new Exception( $msg );
                }

            }catch (AwsException $e) {
                echo $e->getMessage();
                return false;
            }catch ( Exception $e) {
                echo $e->getMessage();
                return false;
            }
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


        public function existsInHostedZoneByValue( $value, $hostedZoneId ){
            $client             = $this->getClientR53();
            $result             = $client->listResourceRecordSets([ 'HostedZoneId' => $hostedZoneId ]);
            $resourceRecordSets = $result['ResourceRecordSets'];

            for($i = 0; $i < count ( $resourceRecordSets ); $i++ ) {
                $resourceRecords =  $resourceRecordSets[$i]['ResourceRecords'];
                foreach( $resourceRecords as $resourceRecord )
                    if( preg_match( "/$value/",  $resourceRecord['Value'] ) )
                        return true;
                
            }
            
            return false;
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

        public function waitToBeCreated( $data, $seconds = null, $times = null ){

            if ( !isset($seconds) || $seconds == null)
                $seconds = 20;

            if (!isset($times) || $times == null)
                $times = 12;

            $client     = $this->getClientR53();

            for($i=0; $i < $times; sleep( $seconds ), $i++ ) {
                $result = $client->getChange( [ 'Id' => $data['ChangeInfo']['Id'] ] );
                echo "\n\nL330099 \$result['ChangeInfo']['Status'] = " . $result['ChangeInfo']['Status'];
                echo "\n\nL280 \$i = " . $i;
                
                if( $result['ChangeInfo']['Status'] == "INSYNC")
                    return true;
            }
            echo "\nL283 ----------------\n";
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
