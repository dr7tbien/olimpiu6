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
    

    class CDH_awsTargetGroup extends CDH_abstractAwsService {
        
        
        public function __construct(){
            $this->setCredentials();
            $this->setClientAlb();
            $this->setClassName("CDH_awsTargetGroup");
        }
        
        public function create ( $data = null ) {

            $client = $this->getClientAlb();
            #$id     = $data['CacheClusterId'];
            #$name   = $data['TagSpecifications'][0]['Tags'][0]['Value'];

            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
               
                $result     = $client->createTargetGroup( $data );
                $identifier = $result;
    
                $arnTargetGroup = $result['TargetGroups'][0]['TargetGroupArn'];
                return $arnTargetGroup;
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

        #public function create($destinationCidrBlock = null, $gatewayId = null, $routeTableId = null) {
        public function createOld($data = []) {
    
            $client = $this->getClientAlb();
            
            try{
                $result = $client->createTargetGroup( $data );        
            }catch (AwsException $e) {
                echo $e->getMessage();
                die("\nL045 No se ha podido crear TargetGroup \n");
            }catch(Exception $e){
                echo $e->getMessage();
                die("\nL048 No se ha podido crear TargetGroup \n");
            }

            sleep(5);
            $arnTargetGroup = $result['TargetGroups'][0]['TargetGroupArn'];

            return $arnTargetGroup;
        }


        public function createWithModifyTargetGroupAttributes( $data = []) {
    
            $client = $this->getClientAlb();
            
            try{
                $result_0       = $client->createTargetGroup( $data );        
                sleep(5);
                $arnTargetGroup = $result_0['TargetGroups'][0]['TargetGroupArn'];
                $result         = $client->modifyTargetGroupAttributes([
                    'Attributes' => [
                        [
                            'Key' => 'deregistration_delay.timeout_seconds',
                            'Value' => '30',
                        ],
                    ],
                    'TargetGroupArn' => $arnTargetGroup,
                ]);
    
            }catch (AwsException $e) {
                echo $e->getMessage();
                die("\nL075 No se ha podido crear createWithModifyTargetGroupAttributes \n");
            }catch(Exception $e){
                echo $e->getMessage();
                die("\nL078 No se ha podido crear createWithModifyTargetGroupAttributes \n");
            }

            return $arnTargetGroup;

        }


        public function createRule( $data =  [] ) {
            $clientAlb          = $this->getClientAlb();

            try{
                $result = $clientAlb->createRule( $data );
    
            }catch (AwsException $e) {
                echo $e->getMessage();
                die("\nL097 No se ha podido crear Rule \n");
            }catch(Exception $e){
                echo $e->getMessage();
                die("\nL100 No se ha podido crear Rule \n");
            }

            return $result['Rules'][0];

        }



        public function getArnByName( $data ){
            $client = $this->getClientAlb();

            try{
                $result = $client->describeTargetGroups([
                    'Names' => [$data]
                ]);
                $services = $result['TargetGroups'];
                
                if( count( $services ) > 1 ){
                    $msg = "\nMás de un TargetGroup con el nombre $data\n";
                    throw new Exception( $msg ); 
                }
                if( count( $services ) == 0 ){
                    $msg = "\nNo se ha encontrado TargetGroup con el nombre $data\n";
                    throw new Exception( $msg ); 
                }
            }catch( Exception $e){
                echo $e->getMessage();
                return false;
            }

            return $services[0]['TargetGroupArn'];

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

            if( !isset( $data['TargetGroups'][0]['TargetGroupName'] ) )
                return false;
            
            $name = $data['TargetGroups'][0]['TargetGroupName'];

            $client = $this->getClientAlb();
            
            for($i = 0; $i < $times;  sleep($seconds), $i++ ){
                $result = $client->describeTargetGroups([ 
                    'Names' => [ $name ], 
                ]);
                $services = $result['TargetGroups'];
                foreach( $services as $service ){

                    if( !isset( $service['TargetGroupName'] ) ) 
                        continue;
                    
                    if( strcasecmp( $name, $service['TargetGroupName'] ) == 0 )
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
