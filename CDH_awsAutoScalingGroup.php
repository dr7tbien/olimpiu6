<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';
    #require_once 'classes/CDH_awsVpc.php';    
    #require_once 'defines/services_aws/CDH_awsDefineRoute.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsAutoScalingGroup extends CDH_abstractAwsService {
        
        
        public function __construct(){
            $this->setCredentials();
            $this->setClientAsc();
            $this->setClientCwt();
            $this->setClassName("CDH_awsAutoScalingGroup");

        }
        









        public function create ( $data = [] ) {

            $client = $this->getClientAsc();
            #$id     = $data['DBInstanceIdentifier'];
            #$name   = $data['Bucket'];
            
            try{
                #if ( isset($id) && $this->existsById( $id ) )
                #    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                #if ( isset($name) && $this->existsByName( $name ) )
                #    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");

                $result = $client->createAutoScalingGroup( $data ); 
                #$service    = $result['HostedZone'];
                $identifier = $data;

                if( $this->waitToBeCreated(  $identifier  ) == false )
                    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );

                return true;
            
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


        /**
         * Esta función crea un objeto suibnet de AWS.
         * Si existe carga los datos del existente en el objeto
         * 
         * @param string $pos ['0', '1', '2']
         * 
         * @param string $type ['public', 'private', 'database']
         * 
         * @return array serviceAWS un array copn los contenidos de la subnet
         * 
         */

        #public function create($destinationCidrBlock = null, $gatewayId = null, $routeTableId = null) {
        public function createOld($data = []) {
    
            $client = $this->getClientAsc();
            
            try{
                $result = $client->createAutoScalingGroup( $data ); 
                if( $this->waitToBeCreated(  $data  ) == false ) 
                    throw new Exception("\nL045 Error createAutoScalingGroup -  waitToBeCreated\n");       
            }catch (AwsException $e) {
                echo $e->getMessage();
                die("\nL054 - Error en createAutoScalingGroup\n");
            }catch( Exception $e ){
                die("\nL056 - " . $e->getMessage() .  "\n");
            }

            return true;
            
        }


        public function putScalingPolicy( $data ){
            $client = $this->getClientAsc();

            try{
                $result = $client->putScalingPolicy( $data );        
            }catch (AwsException $e) {
                echo $e->getMessage();
                die( "\nL065 - Error putScalingPolicy \n" );
            }catch(Exception $e){
                echo $e->getMessage();
                die( "\nL068 - Error putScalingPolicy \n" );
            }
            return $result;

        }


        public function putMetricAlarm( $data ){
            $client = $this->getClientCwt();

            try{
                $result = $client->putMetricAlarm( $data );        
            }catch (AwsException $e) {
                echo $e->getMessage();
                die( "\nL082 - Error putMetricAlarm \n" );
            }catch(Exception $e){
                echo $e->getMessage();
                die( "\nL085 - Error putMetricAlarm \n" );
            }
            return true;

        }



        public function getArnByName( $data ){
            $client = $this->getClientAsc();

            try{
                $result = $client->describeAutoScalingGroups([
                    'AutoScalingGroupNames' => [$data]
                ]);
                $services = $result['AutoScalingGroups'];
                
                if( count( $services ) > 1 ){
                    $msg = "\nMás de un AutoScalingGroup con el nombre $data\n";
                    throw new Exception( $msg ); 
                }
                if( count( $services ) == 0 ){
                    $msg = "\nNo se ha encontrado AutoScalingGroup con el nombre $data\n";
                    throw new Exception( $msg ); 
                }
            }catch( Exception $e){
                echo $e->getMessage();
                return false;
            }

            return $services[0]['AutoScalingGroupARN'];

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


        public function delete( $id ){
            return true;
        }

        

        /**
         * @param array     $data       Una variable Objeto result
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $data, $seconds = null, $times = null ){
            if($seconds != true)
                $seconds = 20;
        
            if($times != true)
                $times = 12;

            $name = $data['AutoScalingGroupName'];

            $client = $this->getClientAsc();
            
            for($i = 0; $i < $times;  sleep($seconds), $i++ ){
                $result = $client->describeAutoScalingGroups([ 
                    'AutoScalingGroupNames' => [ $name ], 
                ]);
                $services = $result['AutoScalingGroups'];
                foreach( $services as $service ){

                    if( !isset( $service['AutoScalingGroupName'] ) ) 
                        continue;
                    
                    if( strcasecmp( $name, $service['AutoScalingGroupName'] ) == 0 )
                        return true;

                } 
                    
            }

            return false;

        }

        public function waitToBeDeleted( $id, $seconds = null, $times = null ){
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

        public function setId( $id ) {
            return true;
        }

        public function getFields(){
            return true;
        }

        public function getStatus ($id ){

            return true;

        }


        public function getStatusById ($id ){
            return true;
        }


        public function getStatusByName ($name ){
            return true;
        }




    }

?>
