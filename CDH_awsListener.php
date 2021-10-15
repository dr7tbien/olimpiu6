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
    

    class CDH_awsListener extends CDH_abstractAwsService {
        
        
        public function __construct(){
            $this->setCredentials();
            $this->setClientAlb();
            $this->setClientEc2();    
            $this->setClassName("CDH_awsListener");        
        }
        
        public function create ( $data = null ) {

            $client = $this->getClientAlb();
            #$id     = $data['DBInstanceIdentifier'];
            #$name   = $data['KeyName'];
            
            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
                
                $result     = $client->createListener( $data );
                $identifier = $result;
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

        #public function create($destinationCidrBlock = null, $gatewayId = null, $routeTableId = null) {
        public function createOld($data = []) {
            #print_r ($data); die();
            $client = $this->getClientAlb();
            
            try{
                $result = $client->createListener( $data );        
            }catch (AwsException $e) {
                echo $e->getMessage();
                return false;
            }catch(Exception $e){
                echo $e->getMessage();
                return false;
            }
            return $this->waitToBeCreated( $result );
            
        }

        public function getArnByLoadBalancerName( $loadBalancerArn, $port ){
            $client = $this->getClientAlb();
            
            $result = $client->describeListeners([
                'LoadBalancerArn' => $loadBalancerArn,
            ]);
            #print_r( $result );
            #die( "\nL061\n" );
            
            $services = $result['Listeners'];
            foreach( $services as $service ) 
                #if( $service['Tags'][0]['Value'] == $data )
                if( $service['Port'] == $port ){
                    #echo "\nL068 - \$ListenerArn = " . $service['ListenerArn'] . "\n"; die();
                    return $service['ListenerArn'];
                }
                    

            return false;

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

            print_r( $data );

            $listener   = $data['Listeners'][0];
            $arn        = $listener['ListenerArn'];   

            #if ( isset ( $data['Listeners'][0]['ListenerArn'] ) )
            #    return true;



            $client = $this->getClientAlb();
            
            for($i = 0; $i < $times;  sleep($seconds), $i++ ){
                $result = $client->describeListeners([ 
                    'ListenerArns' => [ $arn ], 
                ]);
                $services = $result['Listeners'];
                foreach( $services as $service ){
                    echo "\n\n L109 \n\n";
                    print_r( $service );
                    echo "L110\n\$arn = $arn";
                    echo "\n\n L111 \n\n";

                    if( !isset( $service['ListenerArn'] ) ) 
                        continue;
                    
                    echo "\n\$listener['ListenerArn'] = " . $listener['ListenerArn'];
                    echo "\n \$service['ListenerArn'] = " . $service['ListenerArn'] . "\n";
                    
                    if( strcasecmp( $listener['ListenerArn'], $service['ListenerArn'] ) == 0 )
                        return true;

                } 
                    
            }

            return false;

        }






    }

?>
