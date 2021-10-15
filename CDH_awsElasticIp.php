<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';
    require_once 'classes/CDH_awsVpc.php';    
    #require_once 'classes/CDH_awsNatGateway.php';
    require_once 'defines/services_aws/CDH_awsDefineElasticIp.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsElasticIp extends CDH_abstractAwsService {

        public function __construct(){
            $this->setCredentials();
            $this->setClientEc2();
            $this->setClassName("CDH_awsElasticIp");
        }
      
        










        public function create ( $data = [] ) {

            $client = $this->getClientEc2();
            #$id     = $data['DBInstanceIdentifier'];
            $name = $data['TagSpecifications'][0]['Tags'][0]['Value'];
            
            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");

                #$result = $client->createFileSystem( $data ); 
                $result = $client->allocateAddress([
                    'Domain' => 'vpc',
                ]);
                #$service    = $result['HostedZone'];
                $identifier = $result['AllocationId'];

                if( $this->waitToBeCreated(  $identifier  ) == false )
                    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );
                
                $client->createTags([
                    'Resources' =>  [ $result[ 'AllocationId' ] ],
                    'Tags'      =>  [
                                        [
                                            'Key'   => 'Name',
                                            'Value' => $data['TagSpecifications'][0]['Tags'][0]['Value']
                                        ]
                                    ]
                ]);
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
            return true;
        }

        public function createOld( $data = [] ) {
            
            $name = $data['TagSpecifications'][0]['Tags'][0]['Value'];

            if ($this->existsByName( $name )) {        
                $msg = "OK;EIP;002;058";
                die( "\nL064  EIP $name existe \n" );
            }

            $client = $this->getClientEc2();
            
            try{
                $result = $client->allocateAddress([
                    'Domain' => 'vpc',
                ]);
                    
                if( $this->waitToBeCreated(  $result['AllocationId']  ) == false )
                    throw new Exception( "\nNo se ha podido crear adecuadamente la EllasticIp\n" );

                $client->createTags([
                    'Resources' =>  [ $result[ 'AllocationId' ] ],
                    'Tags'      =>  [
                                        [
                                            'Key'   => 'Name',
                                            'Value' => $data['TagSpecifications'][0]['Tags'][0]['Value']
                                        ]
                                    ]
                ]);                
                return true;
            }catch (AwsException $e) {
                echo $e->getMessage();
                return false;
            }catch(Exception $e){
                echo $e->getMessage();
                return false;
            }
            
            return true;
        }




        public function getTagValueByName( $tagKey, $name) {
            if( $tagKey != true || $name != true )
                return false;
            $service = $this->getServiceByName( $name );
            if(!is_array ($service) )
                return false;
            $tags = $service['Tags'];
            foreach( $tags as $tag ) {
                if( strcmp ($tag['Key'], $tagKey)  == 0)
                    return $tag['Value'];
            }
            return false;
        }


        public function getTagValueById( $tagKey, $id ) {
            if( $tagKey != true || $id != true )
                return false;
            $service = $this->getServiceById( $id );
            if(!is_array ($service) )
                return false;
            $tags = $service['Tags'];
            foreach( $tags as $tag ) {
                if( strcmp ($tag['Key'], $tagKey)  == 0)
                    return $tag['Value'];
            }
            return false;
        }


        /**
         * Esta función devuelve todos los elementos de un servicio cloud de una id determinada
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */


        public function getServiceById( $id ){
                if($id != true)
                    return false;
                $clientEc2 = $this->getClientEc2();
                try{
                    $resultEc2 = $clientEc2->describeAddresses([
                        'AllocationIds' => [ $id ],
                    ]);
                }catch( AwsException $e){
                    return false;
                }
                $services = $resultEc2['Addresses'];
                if( is_array($services) && count($services) > 0 )
                    return $services[0];

                return false;

        }

        
        /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag 
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */
        public function getServiceByName( $name  ){
            if($name != true)
                return false;
            $clientEc2 = $this->getClientEc2();
            
            try{
                $resultEc2 = $clientEc2->describeAddresses([
                    'Filters' => [
                        [
                            'Name' => 'tag:Name',
                            'Values' => [$name],
                        ]
                    ] 
                ]);
        
            }catch( AwsException $e){
                
                return false;
            }

            if( !is_object($resultEc2) || !is_array( $resultEc2['Addresses'] )  )
                return false;
        
            $services = $resultEc2['Addresses'];
            #var_export ($services);
            if( count($services) > 0 )
                return $services[0];

            return false;


        }


        public function getServiceByIp( $ip  ){
            if($ip != true)
                return false;
            $clientEc2 = $this->getClientEc2();
            
            try{
                $resultEc2 = $clientEc2->describeAddresses([
                    'PublicIps' => [ $ip ]
                ]);
        
            }catch( AwsException $e){
                
                return false;
            }

            if( !is_object($resultEc2) || !is_array( $resultEc2['Addresses'] )  )
                return false;
        
            $services = $resultEc2['Addresses'];

            if( count($services) > 0 )
                return $services[0];

            return false;


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
            return $this->getServiceByName( $name );
        }


        public function delete( $id ){
            if( $id != true )
                return false;

            $clientEc2 = $this->getClientEc2();
            
            try{
                /*
                $resultEc2 = $clientEc2->disassociateAddress([
                    'AssociationId' => $id,
                ]);
                */  
                $resultEc2 = $clientEc2->releaseAddress([
                    'AllocationId' => $id,
                ]);

                return $this->waitToBeDeleted( $id );
            }catch(AwsException $e){
                echo $e->getMessage();
                return false;
            }
        }

        public function deleteByIp( $ip ){
            if( $ip != true )
                return false;

            $clientEc2 = $this->getClientEc2();
            
            try{


                $clientEc2->disassociateAddress([
                    'PublicIp' => $ip,
                ]);
                    
                $clientEc2->releaseAddress([
                    'PublicIp' => $ip,
                ]);
                

                return $this->waitToBeDeleted( $this->getIdByIp( $ip ) );
            }catch(AwsException $e){
                echo $e->getMessage();
                return false;
            }
        }



        /**
         * @param string $name    Nombre de HostedZone
         * @param string $id      Una id de HostedZone. Usualmente comienza por "Z"
         * 
         *
         * @example 
         * <code>
         * <?php
         *  
         *  OK - In this sentence the function search matching param $id
         *  $object->exists( $name = null, $id  = "Zxxxxxxxx" ){ ... }
         *   
         *  OK - In this sentence the function search matching param $name
         *  $object->exists( $name = HZ_NAME, $id  = null){ ... }
         * 
         *  OK - In this sentence the function search matching both params
         *  $object->exists( $name = HZ_NAME, $id  = "Zxxxxxxxx" ){ ... }
         *
         * ?>
         * </code>
         * 
         */


        public function exists( $id ){
            return $this->existsById( $id );
        }

        public function existsById( $id ){
            if( $id != true)
                return false;

            $service = $this->getServiceById( $id );
            if( $service == false)
                return false;

            return true;

        }



        public function existsByName( $name ){
            $client = $this->getClientEc2();
            #$name = "CD_sndnv_vpc";
            try{
                $result = $client->describeAddresses([
                    'Filters' => [
                        [
                            'Name' => 'tag:Name',
                            'Values' => [$name],
                        ],
                            // ...
                    ],
                ]);

            } catch ( AwsException $e ) {
                return false;
            }

            if( isset ($result['Addresses']) && is_array($result['Addresses']) && count ( $result['Addresses']) == 1)
                return true;

            if( isset ($result['Addresses']) && is_array($result['Addresses']) && count ( $result['Addresses']) != 1)
                return false;

            return false;

        }


        public function existsByIp( $ip ){
            if( $ip != true)
                return false;

            $service = $this->getServiceByIp( $ip );
            if( $service == false)
                return false;

            return true;

        }
        
        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailableById( $id ){
            return $this->existsById( $id);
        }

        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailableByName( $name ){
            return $this->existsByName( $name);
        }

        public function isAvailableByIp( $ip ){
            return $this->existsByIp( $ip );
        }

        /**
         * @param string    $name    valoer del tag "Name"
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */


        public function waitToBeCreated( $id, $seconds = null, $times = null ){
            if($seconds != true)
                $seconds = 10;
        
            if($times != true)
                $times = 6;
            
            $client = $this->getClientEc2();


            for($i = 0; $i < $times;  $i++, sleep( $seconds ) ) {
                $result = $client->describeAddresses([
                    'AllocationIds' => [ $id ],
                ]);
                if( isset( $result['Addresses'] ) && is_array( $result['Addresses'] )   ){
                    $service = $result['Addresses'][0]; 
                    #if(  strcmp( $service['State'], 'available') == 0 )
                    #    return true;
                    if(  isset( $service['AllocationId']) ) 
                        return true;
                    
                }
            }

            return false;
        }



        public function waitToBeDeleted( $id, $seconds = null, $times = null ){
            if($seconds != true)
                $seconds = 10;
    
            if($times != true)
                $times = 6;
            
            $clientEc2 = $this->getClientEc2();

            for($i = 0; $i < $times;  $i++ ){
                $ig = $this->getServiceById( $id );
                if( $ig == false  )
                    return true;                
                sleep($seconds);
            }

            return false;
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
            return $this->id;
        }

        public function getIdByName( $name ) {

            $service = $this->getServiceByName( $name );
            if( is_array ( $service ) && isset( $service[ 'AllocationId' ] ) )
                return $service[ 'AllocationId' ];
            return false;
        }

        public function getIdByIp( $ip ) {

            $service = $this->getServiceByIp( $ip );
            if( is_array ( $service ) && isset( $service[ 'AllocationId' ] ) )
                return $service[ 'AllocationId' ];
            return false;
        }



        public function setId( $id ) {
            $this->id = $id;
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
