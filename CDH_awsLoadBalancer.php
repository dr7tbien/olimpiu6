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
    

    class CDH_awsLoadBalancer extends CDH_abstractAwsService {
        
        
        public function __construct(){
            $this->setCredentials();
            $this->setClientAlb();
            $this->setClassName("CDH_awsLoadBalancer");
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
                
                $result     = $client->createLoadBalancer( $data );
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


        public function createOld($data = []) {
    
            $client = $this->getClientAlb();
            
            try{
                $result = $client->createLoadBalancer( $data );        
            }catch (AwsException $e) {
                echo $e->getMessage();
                return false;
            }catch(Exception $e){
                echo $e->getMessage();
                return false;
            }
            return $this->waitToBeCreated( $result );
            
        }

        public function getArnByName( $data ){
            $client = $this->getClientAlb();

            try{
                $result = $client->describeLoadBalancers([
                    'Names' => [$data]
                ]);
                $services = $result['LoadBalancers'];
                
                if( count( $services ) > 1 ){
                    $msg = "\nMás de un LoadBalancer con el nombre $data\n";
                    throw new Exception( $msg ); 
                }
                if( count( $services ) == 0 ){
                    $msg = "\nNo se ha encontrado LoadBalancer con el nombre $data\n";
                    throw new Exception( $msg ); 
                }
            }catch( Exception $e){
                echo $e->getMessage();
                return false;
            }

            return $services[0]['LoadBalancerArn'];

        }


        /**
         * Esta función devuelve todos los elementos de un servicio cloud de una id determinada
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */


        public function getServiceById( $id ){
            return true;
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

            if( !isset( $data['LoadBalancers'][0]['LoadBalancerName'] ) ){
                return false;
            }

            $name = $data['LoadBalancers'][0]['LoadBalancerName'];

            $client = $this->getClientAlb();
            
            for($i = 0; $i < $times;  sleep($seconds), $i++ ){
                $result = $client->describeLoadBalancers([ 
                    'Names' => [ $name ], 
                ]);
                $services = $result['LoadBalancers'];
                foreach( $services as $service ){

                    if( !isset( $service['LoadBalancerName'] ) ) 
                        continue;
                    
                    if( strcasecmp( $name, $service['LoadBalancerName'] ) == 0 )
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
