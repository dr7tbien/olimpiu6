<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';
    require_once 'classes/CDH_awsVpc.php';
    require_once 'classes/CDH_awsSubnet.php';
    require_once 'classes/CDH_awsInternetGateway.php';
    require_once 'classes/CDH_awsNatGateway.php';
        
    require_once 'classes/CDH_awsRoute.php';
        
    require_once 'defines/services_aws/CDH_awsDefineRouteTable.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsRouteTable extends CDH_abstractAwsService {




        public function __construct(){
            $this->setCredentials();
            $this->setClientEc2();
            $this->setClassName("CDH_awsRouteTable");
        }
        

        public function create ( $data = null ) {

            $client = $this->getClientEc2();
            #$id     = $data['CacheClusterId'];
            $name   = $data['TagSpecifications'][0]['Tags'][0]['Value'];

            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
               
                $result     = $client->createRouteTable( $data );
                $identifier = $result['RouteTable']['RouteTableId'];

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

        public function createOld( $data = []) {
            $name = $data['TagSpecifications'][0]['Tags'][0]['Value'];
            
            if ($this->existsByName( $name )) {        
                $msg = "OK;RT;002;058";
                die( "\n  RT $name existe \n" );
            }


            $client = $this->getClientEc2();
            
            try{

                $result = $client->createRouteTable( $data );
                    
                $service = $result['RouteTable'];
                
                if( $this->waitToBeCreated(  $service['RouteTableId']  ) == false )
                    return false;

                return $service;
                    
            }catch (AwsException $e) {
                echo $e->getMessage();
                return false;
            }catch(Exception $e){
                echo $e->getMessage();
                return false;
            }
            
            return false;

        }


        public function associate(  $data ){

            $client = $this->getClientEc2();

            try{
                $result = $client->associateRouteTable($data);
                if( $this->waitToBeAssociated( $data['RouteTableId'], $result['AssociationId'] ) == false ){
                    return false;
                }
                return true;
            }catch(AwsException $e ){
                echo $e->getMessage();
                return false;
            }

            return false;

        }

        public function waitToBeAssociated( $routeTableId, $associationId ){
            $time = 10;
            $times = 30;

            $client = $this->getClientEc2();
            $result = $client->describeRouteTables([
                'RouteTableIds' => [$routeTableId]
            ]);
            if( !isset( $result['RouteTables'][0]['Associations'] ) )
                return false;


            $associations = $result['RouteTables'][0]['Associations'];


            for( $i = 0; $i < $times ; $i++, sleep($time) ){
                $association = $associations[$i];
                if( strcmp( $associationId, $association['RouteTableAssociationId'] ) == 0 ){
                    if( strcmp( $association['AssociationState']['State'], 'associated' ) == 0 )
                        return true;
                }
            }

            return false;
        }



        

        /**
         * Esta función carece de sentido, pues los únicos valores interesantes son el Nombre y la Id y ambos los tenemos
         *  
         * */
        public function getFieldValueById ( $field, $id ) {
            if( $field != true || $id != true )
                return false;
            $fields = $this->getFields();
            $service = $this->getServiceById( $id );
            if ( in_array( $field, $fields ) && $service != false ) 
                return $service[$field];
            return false;               

        }

        /**
         * Esta función busca el valor de un campo del array. Existen campos con igual nombre
         * pero en distinto nivel del array. si el campo buscado está en el primer nivel o es único
         * la función trabaja bien.
         * 
         * En los casos en que el campo esté repetido, pero en distinto nivel, es necesario insertar 
         * el argumento $cloudObject como un array interno con campos únicos   
         * 
         * @param string $field cualquier valor contenido en el HostedZone - Realmente sólo puede captar la Id
         *                  
         * 'Id'                          (cadena)
         * 
         * @param string $name - Nombre de la Subnet
         * 
         * @return string|array|boolean
         * 
         * */

        public function getFieldValueByName ( $field, $name ){
            if( $field != true || $name != true )
                return false;

            $fields = $this->getFields();
            $i = $this->in_array_ic ($field, $fields);
            if( $i == false ) 
                return false;
            
            $field = $fields[$i];
            $service = $this->getServiceByName($name);
            if ($service == false)
                return false;
            return $service[$field];


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
                    $resultEc2 = $clientEc2->describeRouteTables([
                        'RouteTableIds' => [$id],
                    ]);
                }catch( AwsException $e){
                    return false;
                }
                if( !is_object($resultEc2) || !is_array( $resultEc2['RouteTables'] ) || count( $resultEc2['RouteTables'] ) != 1   )
                    return false;

                $services = $resultEc2['RouteTables'];
                return $services[0];
        }

        
        /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag 
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */

        public function getServiceByName( $name  ){
            return $this->getServiceByTagName( $name );
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

            if($name != true)
                return false;

            $clientEc2 = $this->getClientEc2();
            
            try{
                $resultEc2 = $clientEc2->describeRouteTables([
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

            if( !is_object($resultEc2) || !is_array( $resultEc2['RouteTables'] ) || count( $resultEc2['RouteTables'] ) != 1   )
                return false;

            $services = $resultEc2['RouteTables'];
            return $services[0];

        }


        public function delete( $id ){
            if( !$this->exists($id) )
                return false;
            
            try{
                $clientEc2 = $this->getClientEc2();
                $resultEc2 = $clientEc2->deleteRouteTable([
                    'RouteTableId' => $id, // REQUIRED
                ]);

                return $this->waitToBeDeleted( $id );
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
            if( $service != false && is_array ( $service ) )
                return true;

            return false;

        }


        public function existsByName( $name ){
            $client = $this->getClientEc2();
            
            try{
                $result = $client->describeRouteTables([
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

            $services = $result['RouteTables'];

            foreach ($services as $service) {
                
                if( strcmp( $service['Routes'][0]['State'], 'active' ) == 0)
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

        public function isAvailableById( $id ){
            return $this->existsById( $id );
        }

        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailableByName( $name ){
            return $this->existsByName( $name );
        }


        /**
         * @param string    $name    valoer del tag "Name"
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $id, $seconds = null, $times = null ){
            if($seconds != true)
                $seconds = 50;
        
            if($times != true)
                $times = 6;
            
            $client = $this->getClientEc2();


            for($i = 0; $i < $times;  $i++, sleep( $seconds ) ) {
                $result = $client->describeRouteTables([
                    'RouteTableIds' => [ $id ],
                ]);
                if( isset( $result['RouteTables'] ) && is_array( $result['RouteTables'] )   ){
                    $service = $result['RouteTables'][0]; 
                    if(  strcmp( $service['Routes'][0]['State'], 'active') == 0 )
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
                $service = $this->getServiceById( $id );
                if( $service == false  )
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

            #var_export( $service );

            if( $service != false && is_array ( $service ) && isset( $service[ 'RouteTableId' ] ) )
                return $service[ 'RouteTableId' ];
            return false;
        }

        public function setId( $id ) {
            $this->id = $id;
        }

        public function getFields(){
            return $this->fields;
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
