<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';
    require_once 'classes/CDH_awsVpc.php';
    require_once 'classes/CDH_awsHostedZone.php';
    require_once 'classes/CDH_awsRecordSet.php';
    
            
    #require_once 'classes/CDH_awsInternetGateway.php';
    require_once 'defines/services_aws/CDH_awsDefineRequestCertificate.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsRequestCertificate extends CDH_abstractAwsService {
        

        public function __construct(){
            $this->setCredentials();
            $this->setClientAcm();
            #$this->setClientAcm_us_east_1();
            $this->setClientR53();
            $this->setClassName("CDH_awsRequestCertificate");
        }
        
        public function create ( $data = null ) {

            $client = $this->getClientAcm();
            #$id     = $data['CacheClusterId'];
            $name   = $data['Tags'][0]['Value'];

            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
               
                $result     = $client->requestCertificate( $data );
                $identifier = $result['CertificateArn'] ;

                #if( $this->waitToBeCreated(  $identifier  ) == false )
                #    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );
       
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


        public function createOld( $data = []) {
            
            $name = $data['Tags'][0]['Value'];

            if ($this->existsByName( $name )) {        
                $msg = "OK;RT;002;058";
                echo "\n  RT $name existe \n" ;
                return false;
            }


            $client = $this->getClientAcm();

            try{
                 
                $result = $client->requestCertificate( $data );
                
            }catch (AwsException $e) {
                echo $e->getMessage();
                die("\nL52**");
                return false;
            }catch(Exception $e){
                echo $e->getMessage();
                 die("\nL56**");
                return false;
            }
            

            /*if( $this->waitToBeCreated(  $result['CertificateArn']  ) == false )
                return false;*/

            return $result['CertificateArn'];


        }


        public function getArnByDnsName( $dnsName ){
            $client = $this->getClientAcm();

            $certificates = $client->listCertificates([]);
            $certificates = $certificates['CertificateSummaryList'];
            foreach ( $certificates as $certificate ){
                if(  preg_match("/$dnsName/i", $certificate['DomainName']  ) )
                    return $certificate[ 'CertificateArn' ];
            }

            return false;
        }


        public function waitToBeValidated( $arnCertificate, $seconds = false, $times = false ){
            if( $seconds    == false ) $seconds = 20;
            if( $times      == false ) $times   = 20;

            $client     = $this->getClientAcm();

            for( $i=0; $i < $times; $i++, sleep( $seconds ) ){
                $result     = $client->describeCertificate([
                    'CertificateArn' => $arnCertificate,
                ]); 
                echo "\nL079 - Itinerando \$i = $i\n";
                if( isset(  $result['Certificate'][ 'DomainValidationOptions' ] [ 0 ] [ 'ResourceRecord' ] ['Name'] ) )
                    return true;

            }

            return false;


        }


        public function createRecordSetForRequestCertificate( $arnCertificate, $hostedZoneName ){
            if( $arnCertificate ==  false ){
                echo "\nL147 - Argumento \$result sin datos\n";
                return false;
            }

            echo "\nL152 \$hostedZoneName = $hostedZoneName \n";
            echo "\nL153 \$arnCertificate = $arnCertificate \n";

            if( !$this->waitToBeValidated( $arnCertificate ) ) {
                echo "\nL101 - Imposible validad certificado\n";
                die();
            }

            $hostedZone = new CDH_awsHostedZone();
            $recordSet  = new CDH_awsRecordSet();

            $client     = $this->getClientAcm();

            $result     = $client->describeCertificate([
                'CertificateArn' => $arnCertificate,
            ]); 

            print_r($result);
            
            $result_0_name  = $result['Certificate'][ 'DomainValidationOptions' ] [ 0 ] [ 'ResourceRecord' ] ['Name'];
            $result_0_type  = $result['Certificate'][ 'DomainValidationOptions' ] [ 0 ] [ 'ResourceRecord' ] ['Type'];
            $result_0_value = $result['Certificate'][ 'DomainValidationOptions' ] [ 0 ] [ 'ResourceRecord' ] ['Value'];

            $hostedZoneId   = $hostedZone->getServiceIdByName( $hostedZoneName );

            $dataRecordSet = [
                'ChangeBatch' => [
                    'Changes' => [
                        [
                            'Action' => 'CREATE',

                            'ResourceRecordSet' => [
                                'Name' => $result_0_name, // $result_0_name
                                'ResourceRecords' => [
                                    [
                                        'Value' => $result_0_value,  // $result_0_value
                                    ],
                                ],
                                'TTL' => 60,
                                'Type' => $result_0_type, // $result_0_type
                            ],
                        ],
                    ],
                    'Comment' => 'Web server for domain.com',
                ],
                'HostedZoneId' => $hostedZoneId, // $HostedZoneId

            ];

            echo "\nL146 - print_r (\$dataRecordSet)\n";

            print_r( $dataRecordSet );

            echo "\nL146 - /print_r (\$dataRecordSet)\n";


            if( $recordSet->create( $dataRecordSet ) == false ) {
                echo "\nL185 - No se ha podido crear el RecordSet para el certificado SSL\n";
                return false;
            }

            return true;

        }





        public function existsByName( $name ){
            if( $name != true)
                return false;

            $client = $this->getClientAcm();

            $result      = $client->listCertificates([]);

            $certs       = $result['CertificateSummaryList'];
            foreach ($certs as $cert ){
                if( strcmp( $cert['DomainName'], $name ) == 0 )
                    return true;    
            }
    
            
            return false;



        }

        
        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */


        /*public function waitToBeCreated( $arn, $seconds = null, $times = null ){
           if($seconds != true)
                $seconds = 40;
        
            if($times != true)
                $times = 15;
            
            $client = $this->getClientAcm();

            for($i = 0; $i < $times; sleep($seconds),  $i++ ){
                $result = $client->describeCertificate([
                    'CertificateArn' => $arn, // REQUIRED
                ]);

                if(  isset ( $result['Certificate'][ 'DomainValidationOptions' ] [ 0 ] [ 'ResourceRecord' ] ['Name'] ) ) 
                    return true;


            }


            return false;
        }*/
        public function waitToBeCreated( $arn, $seconds = null, $times = null ){
           if($seconds != true)
                $seconds = 40;
        
            if($times != true)
                $times = 15;
            
            $client = $this->getClientAcm();

            for($i = 0; $i < $times; sleep($seconds),  $i++ ){
                $result = $client->describeCertificate([
                    'CertificateArn' => $arn, // REQUIRED
                ]);

                $status = $result['Certificate'][ 'DomainValidationOptions' ] [ 0 ] [ 'ValidationStatus' ];


                if( $status != "SUCCESS" ){
                    echo "\nL226 \$status = $status";
                    continue;
                }

                #if(  isset ( $result['Certificate'][ 'DomainValidationOptions' ] [ 0 ] [ 'ResourceRecord' ] ['Name'] ) ) 
                return true;


            }


            return false;
        }

        /**
         * @param string    $name    valoer del tag "Name"
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeIssued( $arn, $seconds = null, $times = null ){
            if($seconds != true)
                $seconds = 40;
        
            if($times != true)
                $times = 15;
            
            $client = $this->getClientAcm();

            for($i = 0; $i < $times; sleep($seconds),  $i++ ){
                $result = $client->describeCertificate([
                    'CertificateArn' => $arn, // REQUIRED
                ]);
                var_export($result['Certificate']); echo "\nL287\n";
                if($result['Certificate']['Status'] == 'ISSUED' )
                    return true;

            }


            return false;
        }



    }

?>
