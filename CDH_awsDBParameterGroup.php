<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsDBParameterGroup extends CDH_abstractAwsService {
        
        public function __construct(){
            $this->setCredentials();
            $this->setClientRds();
            $this->setClassName("CDH_awsDBParameterGroup");
        }
        
        
        













        public function create( $data = null ) {

            $client = $this->getClientRds();
            $name = $data['DBParameterGroupName'];

            try{
                if ( $this->existsByName( $name ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
                
                $result     = $client->createDBParameterGroup( $data );
                $service    = $result['DBParameterGroup'];

                if( $this->waitToBeCreated(  $service['DBParameterGroupName']  ) == false )
                    throw new Exception( "L050 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );
                
                return true;
            
            }catch (AwsException $e) {
                $this->logAdmin(  "L055 Error  " . $this->getClassName() . "->create()" );  
                $this->logAdmin(  "\n Mensaje de error:" );
                $this->logAdmin(  $e->getMessage() );
                return false;
            }catch(Exception $e){
                $this->logAdmin(  "L060 Error  " . $this->getClassName() . "->create()" );  
                $this->logAdmin(  "\n Mensaje de error:" );
                $this->logAdmin(  $e->getMessage() );
                return false;
            }

            return $service;

        }



        public function getTagValueByName( $tagKey, $name) {
            #return false;
            
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
            return false;
            if( $tagKey != true || $id != true )
                return false;

            $service = $this->getServiceById( $id );

            if(!is_array ($service) )
                return false;

            $tags = $service['Tags'];

            foreach( $tags as $tag ) {
                if(  isset( $tag['Key'] ) && strcmp ($tag['Key'], $tagKey)  == 0 )
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
            return false;
            /*
            if($id != true)
                return false;
            $client = $this->getClientRds();
            try{
                $result = $client->describeDBParameterGroups([
                    'FileSystemId' => $id,
                ]);
            }catch( AwsException $e){
                return false;
            }
            if( !is_object($result ) || !is_array( $result ['FileSystems'] ) || count( $result['FileSystems'] ) != 1   )
                return false;

            $services = $result['FileSystems'];

            return $services[0];
            */
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

            $client = $this->getClientRds();

            try{
                $result = $client->describeDBParameterGroups([
                    'DBParameterGroupName' => $name
                ]);
            }catch( AwsException $e){
                return false;
            }

            if( !is_object($result) || !is_array( $result['DBParameterGroups'] )  )
                return false;

            $services = $result['DBParameterGroups'];

            foreach ( $services as $service ){
                #echo PHP_EOL . "\$service = " . $service['Name'] . " | \$name = $name";    
                if( strcmp ($service['DBParameterGroupName'], $name ) == 0 ) 
                    return $service;
                
            }    

            #echo PHP_EOL;
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

            if($name != true)
                return false;

            $client = $this->getClientRds();
            
            try{
                $result = $client->describeDBParameterGroups([
                    #'Filters' => [
                    #    [
                    #        'Name' => 'tag:Name',
                    #        'Values' => [$name],
                    #    ]
                    #] 
                ]);
        
            }catch( AwsException $e){
                
                return false;
            }

            if( !is_object($result ) || !is_array( $result ['DBParameterGroups'] )    )
                return false;

            $services = $result['DBParameterGroups'];
            
            foreach( $services as $service ){
                $tags = $service['Tags'];
                foreach( $tags as $tag ){
                    if( $tag['Key'] == 'Name' && $tag['Value'] == $name)
                        return $service;
                }


            }
            
            
            return false;

        }

        public function delete( $name ){
            return false;


            if( !$this->exists($name) )
                #echo PHP_EOL . "bucket no existe " . PHP_EOL;
                return true;
                
            try{
                $client = $this->getClientRds();
                $result = $client->deleteDBParameterGroup([
                    'DBParameterGroupName' => $name, // REQUIRED
                ]);
                return true;
            }catch(AwsException $e){
                $e->getMessage();
                return false;

            }

        }

        public function exists( $name ){
            return $this->getServiceByName(  $name );
        }

        public function existsById( $id ){
            return false;

            if( $id != true)
                return false;

            $service = $this->getServiceById( $id );
            if( $service == false)
                return false;

            return true;

        }


        public function existsByName( $name ){
            
            return $this->getServiceByName(  $name );

            if( $name != true)
                #echo "No es true";    
                return false;
            #echo "L464\n";
            $service = $this->getServiceByName( $name );

            #var_export( $service ); 
            #die( "L468" );

            if( $service != false && is_array ( $service ) )
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
            return false;

            $service = $this->getServiceById( $id );
            if( isset( $service['LifeCycleState'] ) && strcmp( $service['LifeCycleState'], 'available' ) == 0 )
                return true;
            
            return false;
            
            /*    
            if( strcmp( $service['State'], 'pending' ) == 0 )
                return false;   

            if( strcmp( $service['State'], 'available' ) == 0 )
                return true;
                
            return false;
            */
        }

        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailableByName( $name ){
            #return false;

            return $this->getServiceByName(  $name );
    
            $service = $this->getServiceByName( $name );
            return $this->isAvailableById( $service['FileSystemId'] );



            #if( $service == false )
            #    return false;
            
            #return true;


            /*
            if( $service == false )
                return false;
            if( strcmp( $service['State'], 'pending' ) == 0 )
                return false;   

            if( strcmp( $service['State'], 'available' ) == 0 )
                return true;
                
            return false;
            */
        }


        /**
         * @param string    $name    valoer del tag "Name"
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $name, $seconds = null, $times = null ){
            
            if($seconds != true)
                $seconds = 10;
        
            if($times != true)
                $times = 6;
            
            #$clientEc2 = $this->getClientEc2();

            for($i = 0; $i < $times;  $i++ ){
                $service = $this->getServiceByName( $name );
                #$service = $this->getServiceById( $id );
                #var_export( $ig );
                if( is_array( $service )  )
                    return true;                
                sleep($seconds);
            }

            return false;
        }

        public function waitToBeDeleted( $name, $seconds = null, $times = null ){
            return false;

            if($seconds != true)
                $seconds = 10;
    
            if($times != true)
                $times = 6;
            
            for($i = 0; $i < $times;  $i++ ){
                $service = $this->getServiceByName( $name );
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
            return false;
            return $this->id;
        }

        public function getIdByName( $name ) {
            return false;

            $service = $this->getServiceByName( $name );
            if( isset( $service[ 'FileSystemId' ] ) )
                return $service[ 'FileSystemId' ];
            
            return false;
        }

        public function setId( $id ) {
            $this->id = $id;
        }

        public function getFields(){
            #return false;

            return $this->fields;
        }

        public function getStatus ($id ){
            return false;

            $service = $this->getServiceById( $id );
            
            if ( isset( $service['LifeCycleState'] )  )
                return $service['LifeCycleState'];
            return false;
        
        }


        public function getStatusById ($id ){
            return false;

            return $this->getStatus( $id );
        }


        public function getStatusByName ($name ){
            return false;
            
            $service = $this->getServiceByName( $name );
            
            return $this->getStatus(  $service[ 'FileSystemId' ]  );



            #if ( $service == false)
            #    return false;

            #return $service['State'];

        }




    }

?>
