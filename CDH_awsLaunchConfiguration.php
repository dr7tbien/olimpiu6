<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';
    #require_once 'classes/CDH_awsAutoScalingGroup.php';
    
    #require_once 'classes/CDH_awsVpc.php';    
    #require_once 'defines/services_aws/CDH_awsDefineRoute.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsLaunchConfiguration extends CDH_abstractAwsService {
        
        
        public function __construct(){
            $this->setCredentials();
            #$this->setClientAlb();
            $this->setClientAsc();
            $this->setClassName("CDH_awsLaunchConfiguration");
        }
        
        public function create ( $data = null ) {

            $client = $this->getClientAsc();
            #$id     = $data['DBInstanceIdentifier'];
            #$name   = $data['KeyName'];
            
            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
                
                $result     = $client->createLaunchConfiguration( $data );
                $identifier = $data['LaunchConfigurationName'];
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


        public function createOld($data = []) {
            #print_r( $data ); #die();

            $client = $this->getClientAsc();
            
            try{
                $result = $client->createLaunchConfiguration( $data );  
                if( $this->waitToBeCreated(  $data['LaunchConfigurationName']  ) == false ) 
                    throw new Exception("\nL048 Error createLaunchConfiguration -  waitToBeCreated\n");      
            }catch (AwsException $e) {
                echo $e->getMessage();
                die("\nL049 - Error en createLaunchConfiguration\n");
            }catch(Exception $e){
                die("\nL053 - " . $e->getMessage() .  "\n");
            }

            return true;
            
        }

        public function getArnByName( $data ){
            $client = $this->getClientAsc();

            $result = $client->describeLaunchConfiguration([
                'LaunchConfigurationNames' => [ $data ],
            ]);
            $services = $result['LaunchConfigurations'];
            
            if( count( $services ) > 1 ){
                $msg = "\nL063 - Más de un LoadBalancer con el nombre $data\n";
                die(); 
            }
            if( count( $services ) == 0 ){
                $msg = "\nL067 - No se ha encontrado LoadBalancer con el nombre $data\n";
                return false;  
            }

            return $services[0]['LaunchConfigurationARN'];

        }        

        /**
         * @param string    $data       Una string nombre del LaunchConfiguration
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $data, $seconds = null, $times = null ){
            if($seconds != true)
                $seconds = 20;
        
            if($times != true)
                $times = 12;

            $client = $this->getClientAsc();
            
            for($i = 0; $i < $times;  sleep($seconds), $i++ ){
                $result = $client->describeLaunchConfigurations([ 
                    'LaunchConfigurationNames' => [ $data ], 
                ]);
                $services = $result['LaunchConfigurations'];
                foreach( $services as $service ){

                    if( !isset( $service['LaunchConfigurationName'] ) ) 
                        continue;
                    
                    if( strcasecmp( $data, $service['LaunchConfigurationName'] ) == 0 )
                        return true;

                } 
                    
            }

            return false;

        }






    }

?>
