<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';
    require_once 'classes/CDH_awsVpc.php';    
    #require_once 'classes/CDH_awsInternetGateway.php';
    require_once 'defines/services_aws/CDH_awsDefineInternetGateway.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsInternetGateway extends CDH_abstractAwsService {
    
        
        public function __construct(){
            $this->setCredentials();
            $this->setClientEc2();
            $this->setClassName("CDH_awsInternetGateway");
        }
        

        public function create ( $data = [] ) {

            $client = $this->getClientEc2();
            #$id     = $data['DBInstanceIdentifier'];
            $name   = $data['TagSpecifications'][0]['Tags'][0]['Value'];
            
            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
                
                $result     = $client->createInternetGateway( $data );
                $identifier = $result;
                if( $this->waitToBeCreated(  $identifier  ) == false )
                    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );

                return $result['Instances'] ;
            
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
            #$client = CDH_abstractAwsService::getClientEc2();
            $name = $data['TagSpecifications'][0]['Tags'][0]['Value'];

            if ($this->existsByName( $name )) {        
                $msg = "OK;IG;002;058";
                die( "\nL042  VPC $name existe \n" );
            }

            $client = $this->getClientEc2();
            
            try{
                
                $result = $client->createInternetGateway( $data );
                    
                if( $this->waitToBeCreated(  $result['InternetGateway']['InternetGatewayId']  ) == false )
                    return false;

                return  $result['InternetGateway'] ;
                    
            }catch (AwsException $e) {
                echo $e->getMessage();
                return false;
            }catch(Exception $e){
                echo $e->getMessage();
                return false;
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
         * @param string $name - Nombre de InternetGateway
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
                    $resultEc2 = $clientEc2->describeInternetGateways([
                        'InternetGatewayIds' => [$id],
                    ]);
                }catch( AwsException $e){
                    return false;
                }

                if( !is_object($resultEc2) || !is_array( $resultEc2['InternetGateways'] ) || count( $resultEc2['InternetGateways'] ) != 1   )
                    return false;

                $services = $resultEc2['InternetGateways'];
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
                $resultEc2 = $clientEc2->describeInternetGateways([
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

            if( !is_object($resultEc2) || !is_array( $resultEc2['InternetGateways'] ) || count( $resultEc2['InternetGateways'] ) != 1   )
                return false;

            $services = $resultEc2['InternetGateways'];
            return $services[0];



        }


        public function delete( $id ){

            #$id = $this->getIdByName( $name );            
            
            try{
                $clientEc2 = $this->getClientEc2();
                $resultEc2 = $clientEc2->deleteInternetGateway([
                    #'DryRun' => true || false,
                    'InternetGatewayId' => $id, // REQUIRED
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
            if( $service == false)
                return false;

            return true;

        }



        public function existsByName( $name ){
            $client = $this->getClientEc2();
            #$name = "CD_sndnv_vpc";
            try{
                $result = $client->describeInternetGateways([
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


            if( isset ($result['InternetGateways']) && is_array($result['InternetGateways']) && count ( $result['InternetGateways']) != 1)
                return false;

            if( isset ($result['InternetGateways']) && is_array($result['InternetGateways']) && count ( $result['InternetGateways']) == 1)
                return true;

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

            for($i = 0; $i < $times;  $i++ ){
                $result = $client->describeInternetGateways([
                    'InternetGatewayIds' => [$id],
                ]); 
                $igs = $result['InternetGateways'];
                if( count( $igs ) == 1 )
                    return true;
                sleep($seconds );
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

            if( $service != false && is_array ( $service ) && isset( $service[ 'InternetGatewayId' ] ) )
                return $service[ 'InternetGatewayId' ];
            return false;
        }

        public function setId( $id ) {
            $this->id = $id;
        }

        public function getFields(){
            return $this->fields;
        }

        public function getStatus ($id ){

            $service = $this->getServiceById( $id );
            
            if ( $service != false && is_array( $service['Attachments'] ) && isset( $service['Attachments']['State'] ) )
                return $service['Attachments']['State'];
            return false;
        }


        public function getStatusById ($id ){
            return $this->getStatus( $id );
        }


        public function getStatusByName ($name ){

            $service = $this->getServiceByName( $name );
            
            if ( $service != false && is_array( $service['Attachments'] ) && isset( $service['Attachments']['State'] ) )
                return $service['Attachments']['State'];
            return false;


        }



        public function attachToVpc ( $igId, $vpcId){
            if( $igId != true || $vpcId != true )
                return false;

            if ( !$this->exists( $igId ))
                return false;

            $vpc = new CDH_awsVpc();

            if ( !$vpc->exists( $vpcId ))
                return false;

            $clientEc2 = $this->getClientEc2();

            try{
                
                $resultEc2 = $clientEc2->attachInternetGateway([
                    //'DryRun'                => IGW_DRYRUN,
                    'InternetGatewayId'     => $igId, // REQUIRED
                    'VpcId'                 => $vpcId // REQUIRED
                ]);
                
                return true;

            } catch( AwsException $e) {
                return false;

            }

        }


    }

?>
